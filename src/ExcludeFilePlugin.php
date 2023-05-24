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
use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;
use RuntimeException;

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
        $composer = $this->composer;

        $package = $composer->getPackage();

        $excludedFiles = $this->parseExcludedFiles($this->getExcludedFiles($package));
        if (!$excludedFiles) {
            return;
        }

        $excludedFiles = array_fill_keys($excludedFiles, true);

        $generator  = $composer->getAutoloadGenerator();
        $packages   = $composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
        $packageMap = $generator->buildPackageMap($composer->getInstallationManager(), $package, $packages);

        $this->filterAutoloads($packageMap, $package, $excludedFiles);
    }

    /**
     * Alters packages to exclude files required in "autoload.files" by
     * "extra.exclude-from-files".
     *
     * @param  array<int, array{PackageInterface, ?string}> $packageMap
     *     List of packages and their installation paths.
     * @param  RootPackageInterface                         $rootPackage
     *     Root package instance.
     * @param  array<string, true>                          $excludedFiles
     *     Map of files to exclude from the "files" autoload mechanism.
     * @return void
     */
    private function filterAutoloads(
        array $packageMap,
        RootPackageInterface $rootPackage,
        array $excludedFiles
    ): void {
        foreach ($packageMap as [ $package, $installPath ]) {
            // Skip root package
            if ($package === $rootPackage) {
                continue;
            }

            // Skip immutable package
            if (!($package instanceof Package)) {
                continue;
            }

            // Skip packages that are not installed
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
     * @param  Package             $package       The package to filter.
     * @param  string              $installPath   The installation path of $package.
     * @param  array<string, true> $excludedFiles Map of files to exclude from
     *     the "files" autoload mechanism.
     * @return void
     */
    private function filterPackageAutoloads(
        Package $package,
        string $installPath,
        array $excludedFiles
    ): void {
        $type = self::INCLUDE_FILES_PROPERTY;

        $autoload = $package->getAutoload();

        // Skip misconfigured packages
        if (!isset($autoload[$type]) || !is_array($autoload[$type])) {
            return;
        }

        if (null !== $package->getTargetDir()) {
            $installPath = substr($installPath, 0, -strlen('/' . $package->getTargetDir()));
        }

        $filtered = false;

        foreach ($autoload[$type] as $key => $path) {
            if ($package->getTargetDir() && !is_readable($installPath.'/'.$path)) {
                // add target-dir from file paths that don't have it
                $path = $package->getTargetDir() . '/' . $path;
            }

            $resolvedPath = $installPath . '/' . $path;
            $resolvedPath = strtr($resolvedPath, '\\', '/');

            if (isset($excludedFiles[$resolvedPath])) {
                $filtered = true;
                unset($autoload[$type][$key]);
            }
        }

        if ($filtered) {
            $package->setAutoload($autoload);
        }
    }

    /**
     * Gets a list files the root package wants to exclude.
     *
     * @param  PackageInterface $package Root package instance.
     * @return string[] Retuns the list of excluded files.
     */
    private function getExcludedFiles(PackageInterface $package): array
    {
        $type = self::EXCLUDE_FILES_PROPERTY;

        $extra = $package->getExtra();

        if (isset($extra[$type]) && is_array($extra[$type])) {
            return $extra[$type];
        }

        return [];
    }

    /**
     * Prepends the vendor directory to each path in "extra.exclude-from-files".
     *
     * @param  string[] $paths Array of paths relative to the composer manifest.
     * @throws RuntimeException If the 'vendor-dir' path is unavailable.
     * @return string[] Retuns the array of paths, prepended with the vendor directory.
     */
    private function parseExcludedFiles(array $paths): array
    {
        if (!$paths) {
            return $paths;
        }

        $config    = $this->composer->getConfig();
        $vendorDir = $config->get('vendor-dir');
        if (!$vendorDir) {
            throw new RuntimeException(
                'Invalid value for \'vendor-dir\'. Expected string'
            );
        }

        $filesystem = new Filesystem();
        // Do not remove double realpath() calls.
        // Fixes failing Windows realpath() implementation.
        // See https://bugs.php.net/bug.php?id=72738
        /** @var string */
        $vendorPath = realpath(realpath($vendorDir));
        $vendorPath = $filesystem->normalizePath($vendorPath);

        foreach ($paths as &$path) {
            $path = preg_replace('{/+}', '/', trim(strtr($path, '\\', '/'), '/'));
            $path = $vendorPath . '/' . $path;
        }

        return $paths;
    }
}
