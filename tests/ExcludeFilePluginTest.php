<?php declare(strict_types=1);

/*
 * This file is part of the "composer-exclude-files" plugin.
 *
 * © Chauncey McAskill <chauncey@mcaskill.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\McAskill\Composer;

use Composer\Autoload\AutoloadGenerator;
use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Installer\InstallationManager;
use Composer\Package\BasePackage;
use Composer\Package\Link;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Repository\InstalledArrayRepository;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableRepositoryInterface;
use Composer\Semver\Constraint\MatchAllConstraint;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;
use Composer\Util\Silencer;
use McAskill\Composer\ExcludeFilePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

class ExcludeFilePluginTest extends TestCase
{
    /**
     * @var Composer
     */
    private $composer = null;

    /**
     * @var Config
     */
    private $config = null;

    /**
     * @var AutoloadGenerator
     */
    private $generator = null;

    /**
     * @var InstallationManager
     */
    private $im = null;

    /**
     * @var IOInterface
     */
    private $io = null;

    /**
     * @var string
     */
    private $origDir = null;

    /**
     * @var InstalledRepositoryInterface
     */
    private $repository = null;

    /**
     * @var string
     */
    private $vendorDir = null;

    /**
     * @see \Composer\Test\Autoload\AutoloadGeneratorTest::assertAutoloadFiles()
     */
    public static function assertAutoloadFiles(
        string $fixture,
        string $dir,
        string $type = 'namespaces'
    ): void {
        $a = __DIR__ . '/Fixtures/autoload_' . $fixture . '.php';
        $b = $dir . '/autoload_' . $type . '.php';
        static::assertFileContentEquals($a, $b);
    }

    /**
     * @see \Composer\Test\Autoload\AutoloadGeneratorTest::assertEqualsNormalized()
     */
    public static function assertEqualsNormalized(
        string $expected,
        string $actual,
        string $message = ''
    ): void {
        static::assertEquals(
            \str_replace("\r", '', $expected),
            \str_replace("\r", '', $actual),
            $message
        );
    }

    /**
     * @see \Composer\Test\Autoload\AutoloadGeneratorTest::assertFileContentEquals()
     */
    public static function assertFileContentEquals(
        string $expected,
        string $actual,
        string $message = ''
    ): void {
        static::assertEqualsNormalized(
            (string) \file_get_contents($expected),
            (string) \file_get_contents($actual),
            ($message ?: $expected . ' equals ' . $actual)
        );
    }

    /**
     *
     */
    public function testAutoloadDump(): void
    {
        \assert($this->composer instanceof Composer);
        \assert($this->generator instanceof AutoloadGenerator);
        \assert($this->io instanceof IOInterface);
        \assert($this->repository instanceof WritableRepositoryInterface);
        \assert(\is_string($this->vendorDir));

        $plugin = new ExcludeFilePlugin();
        $plugin->activate($this->composer, $this->io);

        // 1. Subscribed to "pre-autoload-dump" event.
        $subscriptions = ExcludeFilePlugin::getSubscribedEvents();
        $this->assertArrayHasKey(ScriptEvents::PRE_AUTOLOAD_DUMP, $subscriptions);

        $rootPackage = $this->createRootPackage();
        $this->composer->setPackage($rootPackage);

        $packages = $this->createPackages();
        foreach ($packages as $package) {
            $this->repository->addPackage($package);
        }

        $fs = new Filesystem;

        $fs->ensureDirectoryExists($this->vendorDir . '/a/a');
        $fs->ensureDirectoryExists($this->vendorDir . '/b/b');
        $fs->ensureDirectoryExists($this->vendorDir . '/c/c/foo/bar');
        \file_put_contents($this->vendorDir . '/a/a/test.php', '<?php function test1() {}');
        \file_put_contents($this->vendorDir . '/b/b/test2.php', '<?php function test2() {}');
        \file_put_contents($this->vendorDir . '/c/c/foo/bar/test3.php', '<?php function test3() {}');
        \file_put_contents($this->vendorDir . '/c/c/foo/bar/test4.php', '<?php function test4() {}');

        // 2. Check plugin is ignored if the root package does not exclude files.
        $plugin->parseAutoloads();

        $this->generator->dump(
            $this->config,
            $this->repository,
            $rootPackage,
            $this->im,
            'composer',
            true,
            '_1'
        );

        // Check standard autoload
        $this->assertAutoloadFiles('files1', $this->vendorDir . '/composer', 'files');

        // 4. Check plugin filters autoloads if the root package
        // excludes files from "extra" section.
        $rootPackage->setExtra([
            'exclude-from-files' => [
                'b/*/test2.php',
                'c/c/foo/bar/test3.php',
            ],
        ]);

        $plugin->parseAutoloads();

        $this->generator->dump(
            $this->config,
            $this->repository,
            $rootPackage,
            $this->im,
            'composer',
            true,
            '_1'
        );

        // Ensure autoload has excluded specified files.
        $this->assertAutoloadFiles('files2', $this->vendorDir . '/composer', 'files');

        // 5. Check plugin filters autoloads if the root package
        // excludes all files from "extra" section.
        $rootPackage->setExtra([
            'exclude-from-files' => [
                '*',
            ],
        ]);

        $plugin->parseAutoloads();

        $this->generator->dump(
            $this->config,
            $this->repository,
            $rootPackage,
            $this->im,
            'composer',
            true,
            '_1'
        );

        // Ensure autoload has NOT generated the "include files" file.
        $this->assertFileDoesNotExist($this->vendorDir . '/composer/autoload_files.php');
    }

    /**
     * @return EventDispatcher&MockObject
     */
    protected function createMockEventDispatcher(): MockObject
    {
        $ed = $this->getMockBuilder(EventDispatcher::class)
                   ->disableOriginalConstructor()
                   ->getMock();

        return $ed;
    }

    /**
     * @return InstallationManager&MockObject
     */
    protected function createMockInstallationManager(): MockObject
    {
        $im = $this->getMockBuilder(InstallationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $im->expects($this->any())
            ->method('getInstallPath')
            ->will($this->returnCallback($this->getMockInstallationManagerInstallPathCallback()));

        return $im;
    }

    /**
     * @return IOInterface&MockObject
     */
    protected function createMockIOInterface(): MockObject
    {
        $io = $this->getMockBuilder(IOInterface::class)->getMock();

        return $io;
    }

    /**
     * @return RepositoryManager&MockObject
     */
    protected function createMockRepositoryManager(InstalledRepositoryInterface $localRepo): MockObject
    {
        $rm = $this->getMockBuilder(RepositoryManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $rm->expects($this->any())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepo));

        return $rm;
    }

    /**
     * @return \Composer\Package\PackageInterface[]
     */
    protected function createPackages(): array
    {
        $packages = [];

        $packages[] = $a = new Package('a/a', '1.0', '1.0');
        $packages[] = $b = new Package('b/b', '1.0', '1.0');
        $packages[] = $c = new Package('c/c', '1.0', '1.0');
        $packages[] = $d = new Package('d/d', '1.0', '1.0');
        $packages[] = $e = new Package('e/e', '1.0', '1.0');
        $packages[] = $this->getMockForAbstractClass(BasePackage::class, [ 'f/f', '1.0', '1.0' ]);

        $a->setAutoload([ 'files' => [ 'test.php' ] ]);
        $b->setAutoload([ 'files' => [ 'test2.php' ] ]);
        $c->setAutoload([ 'files' => [ 'test3.php', 'foo/bar/test4.php' ] ]);
        $c->setTargetDir('foo/bar');
        $c->setRequires([
            'd/d' => new Link('c', 'd/d', new MatchAllConstraint())
        ]);
        $d->setRequires([
            'e/e' => new Link('d', 'e/e', new MatchAllConstraint())
        ]);
        $e->setType('metapackage');

        return $packages;
    }

    /**
     * @return RootPackage
     */
    protected function createRootPackage(): RootPackage
    {
        $rootPackage = new RootPackage('a', '1.0', '1.0');
        $rootPackage->setRequires([
            'a/a' => new Link('a', 'a/a', new MatchAllConstraint()),
            'b/b' => new Link('a', 'b/b', new MatchAllConstraint()),
            'c/c' => new Link('a', 'c/c', new MatchAllConstraint()),
        ]);

        return $rootPackage;
    }

    /**
     * @see \Composer\TestCase::ensureDirectoryExistsAndClear()
     */
    protected function ensureDirectoryExistsAndClear(string $directory): void
    {
        if (\is_dir($directory)) {
            (new Filesystem)->removeDirectory($directory);
        }

        \mkdir($directory, 0777, true);
    }

    /**
     * @see \Composer\TestCase::getUniqueTmpDirectory()
     *
     * @throws RuntimeException If the unique temporary directory can not be created.
     */
    protected function getUniqueTmpDirectory(): string
    {
        $attempts = 5;
        $root = \sys_get_temp_dir();

        do {
            $unique = $root . DIRECTORY_SEPARATOR . \uniqid('composer-test-' . \rand(1000, 9000));

            if (!\file_exists($unique) && Silencer::call('mkdir', $unique, 0777)) {
                if ($unique = \realpath($unique)) {
                    return $unique;
                }
            }
        } while (--$attempts);

        throw new RuntimeException(
            'Failed to create a unique temporary directory'
        );
    }

    /**
     * Returns a callback for the return value of the mock {@see InstallationManager::getInstallPath()}.
     *
     * Before Composer 2.5.6, the method returns a string.
     * After Composer 2.5.6, the method returns a string or null.
     *
     * @return (callable(\Composer\Package\PackageInterface):?string)
     */
    protected function getMockInstallationManagerInstallPathCallback(): callable
    {
        $test = $this;
        $ref  = (new ReflectionMethod(InstallationManager::class, 'getInstallPath'))->getReturnType();

        return function ($package) use ($test, $ref) {
            if ($package->getType() === 'metapackage') {
                return ($ref && $ref->allowsNull() ? null : '');
            }

            $targetDir = $package->getTargetDir();

            return $test->vendorDir . '/' . $package->getName() . ($targetDir ? '/' . $targetDir : '');
        };
    }

    protected function setUp(): void
    {
        $this->setUpVendorDir();
        $this->setUpConfig();
        $this->setUpComposer();
        $this->setUpWorkingDir();
    }

    protected function setUpComposer(): void
    {
        $this->io = $this->createMockIOInterface();

        $this->repository = new InstalledArrayRepository();
        $rm = $this->createMockRepositoryManager($this->repository);

        $this->im = $this->createMockInstallationManager();

        $ed = $this->createMockEventDispatcher();
        $this->generator = new AutoloadGenerator($ed);

        $composer = new Composer();
        if ($this->config) {
            $composer->setConfig($this->config);
        }
        $composer->setRepositoryManager($rm);
        $composer->setInstallationManager($this->im);
        $composer->setAutoloadGenerator($this->generator);

        $this->composer = $composer;
    }

    protected function setUpConfig(): void
    {
        $this->config = new Config(false);
        $this->config->merge([
            'config' => [
                'vendor-dir' => $this->vendorDir,
            ],
        ]);
    }

    protected function setUpVendorDir(): void
    {
        $this->vendorDir = $this->getUniqueTmpDirectory();
        $this->ensureDirectoryExistsAndClear($this->vendorDir);
    }

    protected function setUpWorkingDir(): void
    {
        if (!($cwd = \getcwd())) {
            throw new RuntimeException(
                'Failed to retrieve the current working directory'
            );
        }

        $this->origDir = $cwd;

        if (\is_string($this->vendorDir) && \is_dir($this->vendorDir)) {
            \chdir($this->vendorDir);
        }
    }

    protected function tearDown(): void
    {
        $this->tearDownWorkingDir();
        $this->tearDownVendorDir();
    }

    protected function tearDownVendorDir(): void
    {
        if (\is_string($this->vendorDir) && \is_dir($this->vendorDir)) {
            (new Filesystem)->removeDirectory($this->vendorDir);
        }
    }

    protected function tearDownWorkingDir(): void
    {
        if (\is_string($this->origDir) && \is_dir($this->origDir)) {
            \chdir($this->origDir);
        }
    }
}
