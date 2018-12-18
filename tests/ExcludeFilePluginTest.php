<?php

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
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;
use Composer\Util\Silencer;
use McAskill\Composer\ExcludeFilePlugin;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject;

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
     * @var MockObject|\Composer\Repository\InstalledRepositoryInterface
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
     * @var MockObject|\Composer\Installer\InstallationManager
     */
    private $im;

    /**
     * @var MockObject|\Composer\IO\IOInterface
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

        $this->io = $this->createMock('Composer\IO\IOInterface');

        $this->repository = $this->createMock('Composer\Repository\InstalledRepositoryInterface');

        $rm = $this->getMockBuilder('Composer\Repository\RepositoryManager')
            ->disableOriginalConstructor()
            ->getMock();
        $rm->expects($this->any())
            ->method('getLocalRepository')
            ->will($this->returnValue($this->repository));

        $im = $this->getMockBuilder('Composer\Installer\InstallationManager')
            ->disableOriginalConstructor()
            ->getMock();
        $im->expects($this->any())
            ->method('getInstallPath')
            ->will($this->returnCallback(function ($package) use ($that) {
                $targetDir = $package->getTargetDir();

                return $that->vendorDir . '/' . $package->getName() . ($targetDir ? '/' . $targetDir : '');
            }));

        $this->im = $im;

        $ed = $this->getMockBuilder('Composer\EventDispatcher\EventDispatcher')
                   ->disableOriginalConstructor()
                   ->getMock();

        $this->generator = new AutoloadGenerator($ed);

        $this->config = new Config();
        $this->config->merge([
            'config' => [
                'vendor-dir' => $this->vendorDir
            ]
        ]);

        $composer = new Composer();
        $composer->setConfig($this->config);
        $composer->setRepositoryManager($rm);
        $composer->setInstallationManager($im);
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

        // 1. Check plugin is ignored if the root package is missing
        $plugin->parseAutoloads();

        $package = new RootPackage('a', '1.0', '1.0');
        $package->setRequires([
            new Link('a', 'a/a'),
            new Link('a', 'b/b'),
            new Link('a', 'c/c'),
        ]);
        $this->composer->setPackage($package);

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
        $c->setRequires([ new Link('c', 'd/d') ]);
        $d->setRequires([ new Link('d', 'e/e') ]);

        $this->repository->expects($this->any())
            ->method('getCanonicalPackages')
            ->will($this->returnValue($packages));

        $this->fs->ensureDirectoryExists($this->vendorDir.'/a/a');
        $this->fs->ensureDirectoryExists($this->vendorDir.'/b/b');
        $this->fs->ensureDirectoryExists($this->vendorDir.'/c/c/foo/bar');
        file_put_contents($this->vendorDir.'/a/a/test.php', '<?php function test1() {}');
        file_put_contents($this->vendorDir.'/b/b/test2.php', '<?php function test2() {}');
        file_put_contents($this->vendorDir.'/c/c/foo/bar/test3.php', '<?php function test3() {}');
        file_put_contents($this->vendorDir.'/c/c/foo/bar/test4.php', '<?php function test4() {}');

        // 2. Check plugin is ignored if the root package does not exclude files
        $plugin->parseAutoloads();

        $this->generator->dump($this->config, $this->repository, $package, $this->im, 'composer', true, '_1');

        // Check standard autoload
        $this->assertAutoloadFiles('files1', $this->vendorDir.'/composer', 'files');

        $package->setAutoload([
            'exclude-from-files' => [
                'b/b/test2.php',
                'c/c/foo/bar/test3.php'
            ]
        ]);

        // 3. Check plugin filters autoloads if the root package
        // excludes files from "autoload" section
        $plugin->parseAutoloads();

        $this->generator->dump($this->config, $this->repository, $package, $this->im, 'composer', true, '_1');

        // Make autoload has excluded specified files
        $this->assertAutoloadFiles('files2', $this->vendorDir.'/composer', 'files');

        $package->setAutoload([]);
        $package->setExtra([
            'exclude-from-files' => [
                'b/b/test2.php',
                'c/c/foo/bar/test3.php'
            ]
        ]);

        // 4. Check plugin filters autoloads if the root package
        // excludes files from "extra" section
        $plugin->parseAutoloads();

        $this->generator->dump($this->config, $this->repository, $package, $this->im, 'composer', true, '_1');

        // Make autoload has excluded specified files
        $this->assertAutoloadFiles('files2', $this->vendorDir.'/composer', 'files');
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
    public function assertAutoloadFiles($name, $dir, $type = 'namespaces')
    {
        $a = __DIR__ . '/Fixtures/autoload_' . $name . '.php';
        $b = $dir . '/autoload_' . $type . '.php';
        $this->assertFileEquals($a, $b);
    }

    /**
     * @see \Composer\Test\Autoload\AutoloadGeneratorTest::assertFileEquals()
     */
    public static function assertFileEquals(
        $expected,
        $actual,
        $message = '',
        $canonicalize = false,
        $ignoreCase = false
    ) {
        return self::assertEquals(
            file_get_contents($expected),
            file_get_contents($actual),
            $message ?: $expected.' equals '.$actual,
            0,
            10,
            $canonicalize,
            $ignoreCase
        );
    }

    /**
     * @see \Composer\Test\Autoload\AutoloadGeneratorTest::assertEquals()
     */
    public static function assertEquals(
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
