<?php

namespace Clue\PharComposer\Phar;

use Clue\PharComposer\Package\Bundle;

/**
 * Represents the target phar to be created.
 */
class TargetPhar
{
    /** @var \Phar */
    private $phar;

    /** @var  PharComposer */
    private $pharComposer;

    public function __construct(\Phar $phar, PharComposer $pharComposer)
    {
        $phar->startBuffering();
        $this->phar = $phar;
        $this->pharComposer = $pharComposer;
    }

    /**
     * finalize writing of phar file
     */
    public function stopBuffering()
    {
        $this->phar->stopBuffering();
    }

    /**
     * adds given list of resources to phar
     *
     * @param  Bundle  $bundle
     */
    public function addBundle(Bundle $bundle)
    {
        foreach ($bundle as $resource) {
            if (is_string($resource)) {
                $this->addFile($resource);
            } else {
                $this->buildFromIterator($resource);
            }
        }
    }

     /**
     * Adds a file to the Phar
     *
     * @param string $file  The file name.
     */
    public function addFile($file)
    {
        $this->phar->addFile($file, $this->pharComposer->getPathLocalToBase($file));
    }

    public function buildFromIterator(\Traversable $iterator)
    {
        $this->phar->buildFromIterator($iterator, $this->pharComposer->getPackageRoot()->getDirectory());
    }

    /**
     * Used to set the PHP loader or bootstrap stub of a Phar archive
     *
     * @param  string $stub
     */
    public function setStub($stub)
    {
        $this->phar->setStub($stub);
    }

    public function addFromString($local, $contents)
    {
        $this->phar->addFromString($local, $contents);
    }
}
