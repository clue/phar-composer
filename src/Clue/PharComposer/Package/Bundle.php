<?php

namespace Clue\PharComposer\Package;

use Symfony\Component\Finder\Finder;
use Clue\PharComposer\Logger;

/**
 * A bundle represents all resources from a package that should be bundled into
 * the target phar.
 */
class Bundle implements \IteratorAggregate
{
    /**
     * list of resources in this bundle
     *
     * @type  array
     */
    private $resources = array();

    /**
     * create bundle from given package
     *
     * @param   Package  $package
     * @param   Logger  $logger
     * @return  Bundle
     */
    public static function from(Package $package, Logger $logger)
    {
        return $package->getBundler($logger)->bundle();
    }

    /**
     * add given file to bundle
     *
     * @param   string  $file
     * @return  Bundle
     */
    public function addFile($file)
    {
        $this->resources[] = $file;
        return $this;
    }

    /**
     * add given directory to bundle
     *
     * @param   Finder  $dir
     * @return  Bundle
     */
    public function addDir(Finder $dir)
    {
        $this->resources[] = $dir;
        return $this;
    }

    /**
     * checks if a bundle contains given resource
     *
     * @param   string  $resource
     * @return  bool
     */
    public function contains($resource)
    {
        foreach ($this->resources as $containedResource) {
            if (is_string($containedResource) && $containedResource == $resource) {
                return true;
            }

            if ($containedResource instanceof Finder && $this->directoryContains($containedResource, $resource)) {
                return true;
            }
        }

        return false;
    }

    /**
     * checks if given directory contains given resource
     *
     * @param   Finder  $dir
     * @param   string  $resource
     * @return  bool
     */
    private function directoryContains(Finder $dir, $resource)
    {
        foreach ($dir as $containedResource) {
            /* @var $containedResource \SplFileInfo */
            if (substr($containedResource->getRealPath(), 0, strlen($resource)) == $resource) {
                return true;
            }
        }

        return false;
    }

    /**
     * returns list of resources
     *
     * @return  \Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->resources);
    }
}
