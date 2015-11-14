<?php

namespace Clue\PharComposer\Package;;

use Symfony\Component\Finder\SplFileInfo;
use Clue\PharComposer\Package\Bundler\Explicit as ExplicitBundler;
use Clue\PharComposer\Package\Bundler\Complete as CompleteBundler;
use Clue\PharComposer\Package\Autoload;
use Clue\PharComposer\Logger;

/**
 * The package represents either the main/root package or one of the vendor packages.
 */
class Package
{
    /**
     * Instantiate package
     *
     * @param array  $package   package information (parsed composer.json)
     * @param string $directory base directory of this package
     */
    public function __construct(array $package, $directory)
    {
        $this->package = $package;
        $this->directory = $directory;
    }

    /**
     * get package name as defined in composer.json
     *
     * @return string
     */
    public function getName()
    {
        return isset($this->package['name']) ? $this->package['name'] : 'unknown';
    }

    /**
     * Get path to vendor directory (relative to package directory)
     *
     * @return string
     */
    public function getPathVendorRelative()
    {
        $vendor = 'vendor';
        if (isset($this->package['config']['vendor-dir'])) {
            $vendor = $this->package['config']['vendor-dir'];
        }
        return $vendor;
    }

    /**
     * Get absolute path to vendor directory
     *
     * @return string
     */
    public function getPathVendor()
    {
        return $this->getAbsolutePath($this->getPathVendorRelative() . '/');
    }

    /**
     * Get package directory (the directory containing its composer.json)
     *
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Get Bundler instance to bundle this package
     *
     * @param Logger $logger
     * @return BundlerInterface
     */
    public function getBundler(Logger $logger)
    {
        $bundlerName = 'complete';
        if (isset($this->package['extra']['phar']['bundler'])) {
            $bundlerName = $this->package['extra']['phar']['bundler'];
        }

        if ($bundlerName === 'composer') {
            return new ExplicitBundler($this, $logger);
        } elseif ($bundlerName === 'complete') {
            return new CompleteBundler($this, $logger);
        } else {
            $logger->log('Invalid bundler "' . $bundlerName . '" specified in package "' . $this->getName() . '", will fall back to "complete" bundler');
            return new CompleteBundler($this, $logger);
        }
    }

    /**
     * Get Autoload instance containing all autoloading information
     *
     * Only used for ExplicitBundler at the moment.
     *
     * @return Autoload
     */
    public function getAutoload()
    {
        return new Autoload(isset($this->package['autoload']) ? $this->package['autoload'] : array());
    }

    /**
     * Get list of files defined as "bin" (absolute paths)
     *
     * @return string[]
     */
    public function getBins()
    {
        if (!isset($this->package['bin'])) {
            return array();
        }

        $bins = array();
        foreach ($this->package['bin'] as $bin) {
            $bins []= $this->getAbsolutePath($bin);
        }

        return $bins;
    }

    /**
     * Get blacklisted files which are not to be included
     *
     * Hardcoded to exclude composer.phar and phar-composer.phar at the moment.
     *
     * @return string[]
     */
    public function getBlacklist()
    {
        return array(
            $this->getAbsolutePath('composer.phar'),
            $this->getAbsolutePath('phar-composer.phar')
        );
    }

    /**
     * Gets a filter function to exclude blacklisted files
     *
     * Only used for CompleteBundler at the moment
     *
     * @return Closure
     * @uses self::getBlacklist()
     */
    public function getBlacklistFilter()
    {
        $blacklist = $this->getBlacklist();

        return function (SplFileInfo $file) use ($blacklist) {
            return in_array($file->getPathname(), $blacklist) ? false : null;
        };
    }

    /**
     * Get absolute path for the given package-relative path
     *
     * @param string $path
     * @return string
     */
    public function getAbsolutePath($path)
    {
        return $this->directory . ltrim($path, '/');
    }
}
