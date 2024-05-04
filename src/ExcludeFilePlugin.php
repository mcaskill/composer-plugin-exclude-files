<?php declare(strict_types=1);

/*
 * This file is part of the "composer-exclude-files" plugin.
 *
 * © Chauncey McAskill <chauncey@mcaskill.ca>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace McAskill\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;

/**
 * @phpstan-import-type AutoloadRules from PackageInterface
 */
class ExcludeFilePlugin implements
    PluginInterface,
    EventSubscriberInterface
{
    public const INCLUDE_FILES_PROPERTY = 'files';
    public const EXCLUDE_FILES_PROPERTY = 'exclude-from-files';

    /**
     * @var Composer
     */
    private $composer;

    /**
     * Apply plugin modifications to Composer.
     *
     * @param  Composer    $composer The Composer instance.
     * @param  IOInterface $io       The Input/Output instance.
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
    }

    /**
     * Remove any hooks from Composer.
     *
     * @codeCoverageIgnore
     *
     * @param  Composer    $composer The Composer instance.
     * @param  IOInterface $io       The Input/Output instance.
     * @return void
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // no need to deactivate anything
    }

    /**
     * Prepare the plugin to be uninstalled.
     *
     * @codeCoverageIgnore
     *
     * @param  Composer    $composer The Composer instance.
     * @param  IOInterface $io       The Input/Output instance.
     * @return void
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // no need to uninstall anything
    }

    /**
     * Gets a list of event names this subscriber wants to listen to.
     *
     * @return array<string, string|array{0: string, 1?: int}|array<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::PRE_AUTOLOAD_DUMP => 'parseAutoloads',
        ];
    }

    /**
     * Parse the vendor 'files' to be included before the autoloader is dumped.
     *
     * @return void
     */
    public function parseAutoloads(): void
    {
        $rootPackage = $this->composer->getPackage();

        $excludedFiles = $this->getExcludedFiles($rootPackage);
        if ($excludedFiles->isEmpty()) {
            return;
        }

        $generator  = $this->composer->getAutoloadGenerator();
        $packageMap = $generator->buildPackageMap(
            $this->composer->getInstallationManager(),
            $rootPackage,
            $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages()
        );

        $this->filterPackageMapAutoloads($packageMap, $rootPackage, $excludedFiles);
    }

    /**
     * Alters packages to exclude files required in "autoload.files"
     * by "extra.exclude-from-files".
     *
     * @param  array{PackageInterface, ?string}[] $packageMap    List of packages
     *     and their installation paths.
     * @param  RootPackageInterface               $rootPackage   Root package instance.
     * @param  Paths                              $excludedFiles Collection of Path instances
     *     to exclude from the "files" autoload mechanism.
     * @return void
     */
    private function filterPackageMapAutoloads(
        array $packageMap,
        RootPackageInterface $rootPackage,
        Paths $excludedFiles
    ): void {
        foreach ($packageMap as [ $package, $installPath ]) {
            // Skip root package.
            if ($package === $rootPackage) {
                continue;
            }

            // Skip package if nothing is installed.
            if (null === $installPath) {
                continue;
            }

            $this->filterPackageAutoloads($package, $installPath, $excludedFiles);
        }
    }

    /**
     * Alters a package to exclude files required in "autoload.files" by
     * "extra.exclude-from-files".
     *
     * @param  PackageInterface $package       The package to filter.
     * @param  string           $installPath   The installation path of $package.
     * @param  Paths            $excludedFiles Collection of Path instances to exclude
     *     from the "files" autoload mechanism.
     * @return void
     */
    private function filterPackageAutoloads(
        PackageInterface $package,
        string $installPath,
        Paths $excludedFiles
    ): void {
        // Skip package if immutable.
        if (!\method_exists($package, 'setAutoload')) {
            return;
        }

        $type = self::INCLUDE_FILES_PROPERTY;

        /** @var array<string, string[]> */
        $autoload = $package->getAutoload();

        // Skip misconfigured packages
        if (empty($autoload[$type]) || !\is_array($autoload[$type])) {
            return;
        }

        if (null !== $package->getTargetDir()) {
            $installPath = \substr($installPath, 0, -\strlen('/' . $package->getTargetDir()));
        }

        $filtered = false;

        foreach ($autoload[$type] as $index => $localPath) {
            if ($package->getTargetDir() && !\is_readable($installPath.'/'.$localPath)) {
                // Add 'target-dir' from file paths that don't have it
                $localPath = $package->getTargetDir() . '/' . $localPath;
            }

            $absolutePath = $installPath . '/' . $localPath;
            $absolutePath = \strtr($absolutePath, '\\', '/');

            if ($excludedFiles->isMatch($absolutePath)) {
                $filtered = true;
                unset($autoload[$type][$index]);
            }
        }

        if ($filtered) {
            /**
             * @disregard P1013 Package method existance validated earlier.
             *     {@see https://github.com/bmewburn/vscode-intelephense/issues/952}.
             */
            $package->setAutoload($autoload);
        }
    }

    /**
     * Gets a parsed list of files the given package wants to exclude.
     *
     * @param  PackageInterface $package Root package instance.
     * @return Paths Retuns a collection of Path instances.
     */
    private function getExcludedFiles(PackageInterface $package): Paths
    {
        $type = self::EXCLUDE_FILES_PROPERTY;

        $extra = $package->getExtra();

        if (empty($extra[$type]) || !\is_array($extra[$type])) {
            return new Paths;
        }

        return Paths::create(
            new Filesystem(),
            $this->composer->getConfig(),
            $extra[$type]
        );
    }
}
