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

use Composer\Config;
use Composer\Util\Filesystem;

/**
 * Represents a collection of canonical paths or glob patterns
 * from "extra.exclude-from-files".
 */
final class Paths
{
    /**
     * @var Path[] List of paths.
     */
    private $paths = [];

    /**
     * @param string[] $paths Array of paths relative to the composer manifest.
     */
    public static function create(Filesystem $filesystem, Config $config, array $paths): self
    {
        $list = new self;

        if ($paths) {
            $vendorPath = Path::createVendorPath($filesystem, $config);

            foreach ($paths as $path) {
                $list->paths[] = Path::create($filesystem, $path, $vendorPath);
            }
        }

        return $list;
    }

    /**
     * Checks if the collection is empty.
     *
     * @return bool Returns true if the collection is empty, otherwise false.
     */
    public function isEmpty(): bool
    {
        return !isset($this->paths[0]);
    }

    /**
     * Checks if the given path (from "autoload.files")
     * matches any Path instance (from "extra.exclude-from-files").
     *
     * @param string $filename Path to the file or directory to check.
     */
    public function isMatch(string $filename): bool
    {
        foreach ($this->paths as $path) {
            if ($path->isMatch($filename)) {
                return true;
            }
        }

        return false;
    }
}
