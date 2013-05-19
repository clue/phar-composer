<?php

namespace Clue\PharComposer\Bundler;

use Clue\PharComposer\PharComposer;
use Herrera\Box\Box;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

abstract class Base implements BundlerInterface
{
    /**
     *
     * @var Box
     */
    protected $box;

    /**
     *
     * @var PharComposer
     */
    protected $pharcomposer;

    public function build(PharComposer $pharcomposer, Box $box)
    {
        $this->pharcomposer = $pharcomposer;
        $this->box = $box;

        $this->bundle();
    }

    abstract protected function bundle();

    protected function addDirectory($dir)
    {
        $dir = rtrim($dir, '/') . '/';

        echo 'adding "' . $dir .'" as "' . $this->pharcomposer->getPathLocalToBase($dir).'"...';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $dir,
                FilesystemIterator::KEY_AS_PATHNAME
                | FilesystemIterator::CURRENT_AS_FILEINFO
                | FilesystemIterator::SKIP_DOTS
            )
        );

        $this->box->buildFromIterator($iterator, $this->pharcomposer->getBase());
        echo ' ok' . PHP_EOL;
    }

    protected function addFile($file)
    {
        $local = $this->pharcomposer->getPathLocalToBase($file);
        echo 'adding "' . $file .'" as "' . $local.'"...';
        $this->box->addFile($file, $local);
        echo ' ok' . PHP_EOL;
    }
}
