<?php

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
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;

class ExcludeFilePlugin implements
    PluginInterface,
    EventSubscriberInterface
{
    const INCLUDE_FILES_PROPERTY = 'files';
    const EXCLUDE_FILES_PROPERTY = 'exclude-from-files';

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
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
    }

    /**
     * Gets a list of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to.
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::PRE_AUTOLOAD_DUMP => 'parseAutoloads'
        ];
    }

    /**
     * Parse the vendor 'files' to be included before the autoloader is dumped.
     *
     * @return void
     */
    public function parseAutoloads()
    {
        /** @var Composer $composer */
        $composer = $this->composer;

        /** @var Package $package */
        $package = $composer->getPackage();
        if (!$package) {
            return;
        }

        $exclude = $this->getExcludedFiles($package);

        if (!$exclude) {
            return;
        }

        $filesystem = new Filesystem();
        $config     = $composer->getConfig();
        $vendorPath = $filesystem->normalizePath(realpath(realpath($config->get('vendor-dir'))));
        $exclude    = $this->parseExcludedFiles($exclude, $vendorPath);

        $generator  = $composer->getAutoloadGenerator();
        $packages   = $composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
        $packageMap = $generator->buildPackageMap($composer->getInstallationManager(), $package, $packages);

        $this->filterAutoloads($packageMap, $package, $exclude);
    }

    /**
     * Alters packages to exclude files required in "autoload.files" by "extra.exclude-from-files".
     *
     * @param  array            $packageMap  Array of `[ package, installDir-relative-to-composer.json) ]`.
     * @param  PackageInterface $mainPackage Root package instance.
     * @param  string[]         $blacklist   The files to exclude from the "files" autoload mechanism.
     * @return void
     */
    private function filterAutoloads(array $packageMap, PackageInterface $mainPackage, array $blacklist = null)
    {
        $type = self::INCLUDE_FILES_PROPERTY;

        $blacklist = array_flip($blacklist);

        foreach ($packageMap as $item) {
            list($package, $installPath) = $item;

            // Skip root package
            if ($package === $mainPackage) {
                continue;
            }

            $autoload = $package->getAutoload();

            // Skip misconfigured packages
            if (!isset($autoload[$type]) || !is_array($autoload[$type])) {
                continue;
            }

            if (null !== $package->getTargetDir()) {
                $installPath = substr($installPath, 0, -strlen('/' . $package->getTargetDir()));
            }

            foreach ($autoload[$type] as $key => $path) {
                if ($package->getTargetDir() && !is_readable($installPath.'/'.$path)) {
                    // add target-dir from file paths that don't have it
                    $path = $package->getTargetDir() . '/' . $path;
                }

                $resolvedPath = $installPath . '/' . $path;
                $resolvedPath = strtr($resolvedPath, '\\', '/');

                if (isset($blacklist[$resolvedPath])) {
                    unset($autoload[$type][$key]);
                }
            }

            $package->setAutoload($autoload);
        }
    }

    /**
     * Gets a list files the root package wants to exclude.
     *
     * @param  PackageInterface $package Root package instance.
     * @return array|null       Retuns the list of excluded files otherwise NULL if misconfigured or undefined.
     */
    private function getExcludedFiles(PackageInterface $package)
    {
        $type = self::EXCLUDE_FILES_PROPERTY;

        $autoload = $package->getAutoload();

        // Skip misconfigured or empty packages
        if (isset($autoload[$type]) && is_array($autoload[$type])) {
            return $autoload[$type];
        }

        $extra = $package->getExtra();

        // Skip misconfigured or empty packages
        if (isset($extra[$type]) && is_array($extra[$type])) {
            return $extra[$type];
        }

        return null;
    }

    /**
     * Prepends the vendor directory to each path in "extra.exclude-from-files".
     *
     * @param  string[] $paths      Array of paths absolute from the vendor directory.
     * @param  string   $vendorPath The directory for installed dependencies.
     * @return array    Retuns the list of excluded files, prepended with the vendor directory.
     */
    private function parseExcludedFiles(array $paths, $vendorPath)
    {
        foreach ($paths as &$path) {
            $path = preg_replace('{/+}', '/', trim(strtr($path, '\\', '/'), '/'));
            $path = $vendorPath . '/' . $path;
        }

        return $paths;
    }
}
