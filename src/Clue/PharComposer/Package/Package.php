<?php

namespace Clue\PharComposer\Package;

use Symfony\Component\Finder\Finder;

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
     * @return \Clue\PharComposer\Package\Bundle
     */
    public function bundle()
    {
        $bundle = new Bundle();

        // return empty bundle if this package does not define any files and directory does not exist
        if (empty($this->package['autoload']) && !is_dir($this->directory . $this->getPathVendor())) {
            return $bundle;
        }

        $iterator = Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->exclude(rtrim($this->getPathVendor(), '/'))
            ->notPath('/^composer\.phar/')
            ->notPath('/^phar-composer\.phar/')
            ->in($this->getDirectory());

        return $bundle->addDir($iterator);
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
