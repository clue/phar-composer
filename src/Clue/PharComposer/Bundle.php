<?php

namespace Clue\PharComposer;

use Symfony\Component\Finder\Finder;
/**
 * A bundle represents all resources from a package that should be bundled into
 * the target phar.
 */
class Bundle
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
     * returns list of resources
     *
     * @return  \Traversable
     */
    public function getResources()
    {
        return $this->resources;
    }
}