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
 * Represents a canonical path or glob pattern
 * from "extra.exclude-from-files".
 */
final class Path
{
    /**
     * @var string The path to exclude.
     */
    private $path;

    /**
     * @var bool Whether the path to exclude has wildcards.
     */
    private $glob;

    public static function create(Filesystem $filesystem, string $path, self $basePath = null): self
    {
        $path = $filesystem->normalizePath($path);
        /** @todo Replace with {@see \str_contains()} when we drop support for PHP 7. */
        $glob = (false !== \strpos($path, '*'));

        if ($basePath) {
            $path = $basePath . '/' . $path;
        }

        return new self($path, $glob);
    }

    /**
     * Create a new Path instance from Composer's
     * {@link https://getcomposer.org/doc/06-config.md#vendor-dir 'vendor-dir'}.
     */
    public static function createVendorPath(Filesystem $filesystem, Config $config): self
    {
        // Do not remove double realpath() calls.
        // Fixes failing Windows realpath() implementation.
        // See https://bugs.php.net/bug.php?id=72738
        $vendorPath = \realpath(\realpath($config->get('vendor-dir')));

        return self::create($filesystem, (string) $vendorPath);
    }

    /**
     * Checks if the given path (from "autoload.files")
     * matches the Path instance (from "extra.exclude-from-files").
     *
     * @param string $filename Path to the file or directory to check.
     */
    public function isMatch(string $filename): bool
    {
        if ($this->glob) {
            $pattern = \str_replace(
                [ '\*', '\?' ],
                [ '.*', '.' ],
                \preg_quote($this->path, '!')
            );

            return (bool) \preg_match('!^'.$pattern.'$!is', $filename);
        }

        return ($this->path === $filename);
    }

    public function __toString(): string
    {
        return $this->path;
    }

    private function __construct(string $path, bool $glob = false)
    {
        $this->path = $path;
        $this->glob = $glob;
    }
}
