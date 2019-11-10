<?php

namespace Clue\PharComposer\Package;;

use Clue\PharComposer\Package\Bundler\BundlerInterface;
use Clue\PharComposer\Package\Bundler\Complete as CompleteBundler;
use Clue\PharComposer\Package\Bundler\Explicit as ExplicitBundler;
use Clue\PharComposer\Logger;

/**
 * The package represents either the main/root package or one of the vendor packages.
 */
class Package
{
    private $package;
    private $directory;

    /**
     * Instantiate package
     *
     * @param array  $package   package information (parsed composer.json)
     * @param string $directory base directory of this package
     */
    public function __construct(array $package, $directory)
    {
        $this->package = $package;
        $this->directory = rtrim($directory, '/') . '/';
    }

    /**
     * get package name as defined in composer.json
     *
     * @return ?string
     */
    public function getName()
    {
        return isset($this->package['name']) ? $this->package['name'] : null;
    }

    /**
     * @return string
     */
    public function getShortName()
    {
        // skip vendor name from package name or default to last directory component
        $name = $this->getName();
        if ($name === null) {
            $name = realpath($this->directory);
            if ($name === false) {
                $name = $this->directory;
            }
        }
        return basename($name);
    }

    /**
     * Get path to vendor directory (relative to package directory, always ends with slash)
     *
     * @return string
     */
    public function getPathVendor()
    {
        $vendor = 'vendor';
        if (isset($this->package['config']['vendor-dir'])) {
            $vendor = $this->package['config']['vendor-dir'];
        }
        return $vendor . '/';
    }

    /**
     * Get package directory (the directory containing its composer.json, always ends with slash)
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
     * Get list of files defined as "bin" (relative to package directory)
     *
     * @return string[]
     */
    public function getBins()
    {
        return isset($this->package['bin']) ? $this->package['bin'] : array();
    }
}
