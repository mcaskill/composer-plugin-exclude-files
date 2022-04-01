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

use Composer\Composer;
use Composer\Config;
use Composer\Autoload\AutoloadGenerator;
use Composer\Package\Link;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Plugin\PluginInterface;
use Composer\Repository\InstalledArrayRepository;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Semver\Constraint\MatchAllConstraint;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;
use Composer\Util\Silencer;
use McAskill\Composer\ExcludeFilePlugin;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject;
use UnexpectedValueException;

class ExcludeFilePluginTest extends TestCase
{
    /**
     * @var string
     */
    public $vendorDir;

    /**
     * @var string
     */
    private $origDir;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var InstalledRepositoryInterface
     */
    private $repository;

    /**
     * @var AutoloadGenerator
     */
    private $generator;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var MockObject<\Composer\Installer\InstallationManager>
     */
    private $im;

    /**
     * @var MockObject<\Composer\IO\IOInterface>
     */
    private $io;

    protected function setUp()
    {
        $that = $this;

        $this->fs = new Filesystem;

        $this->vendorDir = $this->getUniqueTmpDirectory();
        $this->ensureDirectoryExistsAndClear($this->vendorDir);

        $this->origDir = getcwd();
        chdir($this->vendorDir);

        $this->io = $this->createMockIOInterface();

        $this->repository = new InstalledArrayRepository();

        $rm = $this->createMockRepositoryManager($this->repository);

        $this->im = $this->createMockInstallationManager();

        $ed = $this->createMockEventDispatcher();

        $this->generator = new AutoloadGenerator($ed);

        $this->config = new Config(false);
        $this->config->merge([
            'config' => [
                'vendor-dir' => $this->vendorDir,
            ],
        ]);

        $composer = new Composer();
        $composer->setConfig($this->config);
        $composer->setRepositoryManager($rm);
        $composer->setInstallationManager($this->im);
        $composer->setAutoloadGenerator($this->generator);

        $this->composer = $composer;
    }

    protected function tearDown()
    {
        chdir($this->origDir);

        if (is_dir($this->vendorDir)) {
            $this->fs->removeDirectory($this->vendorDir);
        }
    }

    public function testAutoloadDump()
    {
        $plugin = new ExcludeFilePlugin();
        $plugin->activate($this->composer, $this->io);

        // 1. Subscribed to "pre-autoload-dump" event
        $subscriptions = ExcludeFilePlugin::getSubscribedEvents();
        $this->assertArrayHasKey(ScriptEvents::PRE_AUTOLOAD_DUMP, $subscriptions);

        $rootPackage = $this->createRootPackage();
        $this->composer->setPackage($rootPackage);

        $packages = $this->createPackages();
        foreach ($packages as $package) {
            $this->repository->addPackage($package);
        }

        $this->fs->ensureDirectoryExists($this->vendorDir . '/a/a');
        $this->fs->ensureDirectoryExists($this->vendorDir . '/b/b');
        $this->fs->ensureDirectoryExists($this->vendorDir . '/c/c/foo/bar');
        file_put_contents($this->vendorDir . '/a/a/test.php', '<?php function test1() {}');
        file_put_contents($this->vendorDir . '/b/b/test2.php', '<?php function test2() {}');
        file_put_contents($this->vendorDir . '/c/c/foo/bar/test3.php', '<?php function test3() {}');
        file_put_contents($this->vendorDir . '/c/c/foo/bar/test4.php', '<?php function test4() {}');

        // 2. Check plugin is ignored if the root package does not exclude files
        $plugin->parseAutoloads();

        $this->generator->dump($this->config, $this->repository, $rootPackage, $this->im, 'composer', true, '_1');

        // Check standard autoload
        $this->assertAutoloadFiles('files1', $this->vendorDir . '/composer', 'files');

        $rootPackage->setExtra([
            'exclude-from-files' => [
                'b/b/test2.php',
                'c/c/foo/bar/test3.php',
            ],
        ]);

        // 4. Check plugin filters autoloads if the root package
        // excludes files from "extra" section
        $plugin->parseAutoloads();

        $this->generator->dump($this->config, $this->repository, $rootPackage, $this->im, 'composer', true, '_1');

        // Make autoload has excluded specified files
        $this->assertAutoloadFiles('files2', $this->vendorDir . '/composer', 'files');
    }

    /**
     * @return RootPackage
     */
    protected function createRootPackage()
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
     * @return \Composer\Package\PackageInterface[]
     */
    protected function createPackages()
    {
        $packages = [];

        $packages[] = $a = new Package('a/a', '1.0', '1.0');
        $packages[] = $b = new Package('b/b', '1.0', '1.0');
        $packages[] = $c = new Package('c/c', '1.0', '1.0');
        $packages[] = $d = new Package('d/d', '1.0', '1.0');
        $packages[] = $e = new Package('e/e', '1.0', '1.0');

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

        return $packages;
    }

    /**
     * @return MockObject<\Composer\EventDispatcher\EventDispatcher>
     */
    protected function createMockEventDispatcher()
    {
        $ed = $this->getMockBuilder('Composer\EventDispatcher\EventDispatcher')
                   ->disableOriginalConstructor()
                   ->getMock();

        return $ed;
    }

    /**
     * @return MockObject<\Composer\IO\IOInterface>
     */
    protected function createMockIOInterface()
    {
        $io = $this->getMockBuilder('Composer\IO\IOInterface')->getMock();

        return $io;
    }

    /**
     * @return MockObject<\Composer\Installer\InstallationManager>
     */
    protected function createMockInstallationManager()
    {
        $test = $this;

        $im = $this->getMockBuilder('Composer\Installer\InstallationManager')
            ->disableOriginalConstructor()
            ->getMock();

        $im->expects($this->any())
            ->method('getInstallPath')
            ->will($this->returnCallback(function ($package) use ($test) {
                $targetDir = $package->getTargetDir();

                return $test->vendorDir . '/' . $package->getName() . ($targetDir ? '/' . $targetDir : '');
            }));

        return $im;
    }

    /**
     * @return MockObject<\Composer\Repository\RepositoryManager>
     */
    protected function createMockRepositoryManager(InstalledRepositoryInterface $localRepo)
    {
        $rm = $this->getMockBuilder('Composer\Repository\RepositoryManager')
            ->disableOriginalConstructor()
            ->getMock();

        $rm->expects($this->any())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepo));

        return $rm;
    }

    /**
     * @see \Composer\TestCase::getUniqueTmpDirectory()
     */
    protected function getUniqueTmpDirectory()
    {
        $attempts = 5;
        $root = sys_get_temp_dir();

        do {
            $unique = $root . DIRECTORY_SEPARATOR . uniqid('composer-test-' . rand(1000, 9000));

            if (!file_exists($unique) && Silencer::call('mkdir', $unique, 0777)) {
                return realpath($unique);
            }
        } while (--$attempts);

        throw new \RuntimeException('Failed to create a unique temporary directory.');
    }

    /**
     * @see \Composer\TestCase::ensureDirectoryExistsAndClear()
     */
    protected function ensureDirectoryExistsAndClear($directory)
    {
        $fs = new Filesystem();

        if (is_dir($directory)) {
            $fs->removeDirectory($directory);
        }

        mkdir($directory, 0777, true);
    }

    /**
     * @see \Composer\Test\Autoload\AutoloadGeneratorTest::assertAutoloadFiles()
     */
    public function assertAutoloadFiles($fixture, $dir, $type = 'namespaces')
    {
        $a = __DIR__ . '/Fixtures/autoload_' . $fixture . '.php';
        $b = $dir . '/autoload_' . $type . '.php';
        $this->assertFileContentEquals($a, $b);
    }

    /**
     * @see \Composer\Test\Autoload\AutoloadGeneratorTest::assertFileContentEquals()
     */
    public static function assertFileContentEquals(
        $expected,
        $actual,
        $message = '',
        $canonicalize = false,
        $ignoreCase = false
    ) {
        return self::assertEqualsNormalized(
            file_get_contents($expected),
            file_get_contents($actual),
            ($message ?: $expected . ' equals ' . $actual),
            0,
            10,
            $canonicalize,
            $ignoreCase
        );
    }

    /**
     * @see \Composer\Test\Autoload\AutoloadGeneratorTest::assertEqualsNormalized()
     */
    public static function assertEqualsNormalized(
        $expected,
        $actual,
        $message = '',
        $delta = 0,
        $maxDepth = 10,
        $canonicalize = false,
        $ignoreCase = false
    ) {
        return parent::assertEquals(
            str_replace("\r", '', $expected),
            str_replace("\r", '', $actual),
            $message,
            $delta,
            $maxDepth,
            $canonicalize,
            $ignoreCase
        );
    }
}
