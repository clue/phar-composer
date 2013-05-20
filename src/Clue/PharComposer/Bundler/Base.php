<?php

namespace Clue\PharComposer\Bundler;

use Symfony\Component\Finder\Finder;
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

        $iterator = Finder::create()
            ->files()
            //->filter($this->getBlacklistFilter())
            ->ignoreVCS(true)
            ->in($dir);

        $this->pharcomposer->log('adding "' . $dir .'" as "' . $this->pharcomposer->getPathLocalToBase($dir).'"...');
        $this->box->buildFromIterator($iterator, $this->pharcomposer->getBase());
    }

    protected function addFile($file)
    {
        $local = $this->pharcomposer->getPathLocalToBase($file);
        $this->pharcomposer->log('adding "' . $file .'" as "' . $local.'"...');
        $this->box->addFile($file, $local);
    }
}
