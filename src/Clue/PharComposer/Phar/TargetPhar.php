<?php

namespace Clue\PharComposer\Phar;

use Herrera\Box\Box;
use Traversable;
use Clue\PharComposer\Package\Bundle;

/**
 * Represents the target phar to be created.
 *
 * TODO: replace PharComposer with a new BasePath class
 */
class TargetPhar
{
    /**
     *
     * @type  PharComposer
     */
    private $pharComposer;
    /**
     *
     * @type  Box
     */
    private $box;

    /**
     * constructor
     *
     * @param  Box           $box
     * @param  PharComposer  $pharComposer
     */
    public function __construct(Box $box, PharComposer $pharComposer)
    {
        $this->box = $box;
        $this->box->getPhar()->startBuffering();
        $this->pharComposer = $pharComposer;
    }

    /**
     * create new instance in target path
     *
     * @param   string        $target
     * @param   PharComposer  $pharComposer
     * @return  TargetPhar
     */
    public static function create($target, PharComposer $pharComposer)
    {
        return new self(Box::create($target), $pharComposer);
    }

    /**
     * finalize writing of phar file
     */
    public function finalize()
    {
        $this->box->getPhar()->stopBuffering();
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
     * Adds a file to the Phar, after compacting it and replacing its
     * placeholders.
     *
     * @param string $file  The file name.
     */
    public function addFile($file)
    {
        $this->box->addFile($file, $this->pharComposer->getPathLocalToBase($file));
    }

     /**
     * Similar to Phar::buildFromIterator(), except the files will be compacted
     * and their placeholders replaced.
     *
     * @param Traversable $iterator The iterator.
     */
    public function buildFromIterator(Traversable $iterator)
    {
        $this->box->buildFromIterator($iterator, $this->pharComposer->getBase());
    }

    /**
     * Used to set the PHP loader or bootstrap stub of a Phar archive
     *
     * @param  string $stub
     */
    public function setStub($stub)
    {
        $this->box->getPhar()->setStub($stub);
    }

    public function addFromString($local, $contents)
    {
        $this->box->addFromString($local, $contents);
    }
}
