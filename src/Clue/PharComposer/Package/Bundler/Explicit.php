<?php

namespace Clue\PharComposer\Package\Bundler;

use Clue\PharComposer\Package\Bundle;
use Clue\PharComposer\Logger;
use Clue\PharComposer\Package\Package;
use Clue\PharComposer\Package\Autoload;
use Symfony\Component\Finder\Finder;

/**
 * Only bundle files explicitly defined in this package's bin and autoload section
 */
class Explicit implements BundlerInterface
{
    /**
     * package the bundler is for
     *
     * @type  Package
     */
    private $package;
    /**
     *
     * @type  Logger
     */
    private $logger;

    public function __construct(Package $package, Logger $logger)
    {
        $this->package = $package;
        $this->logger  = $logger;
    }

    /**
     * returns a bundle
     *
     * @return  Bundle
     */
    public function bundle()
    {
        $bundle = new Bundle();
        $this->bundleBins($bundle);

        $autoload = $this->package->getAutoload();
        $this->bundlePsr0($bundle, $autoload);
        $this->bundleClassmap($bundle, $autoload);
        $this->bundleFiles($bundle, $autoload);

        return $bundle;
    }

    private function bundleBins(Bundle $bundle)
    {
        foreach ($this->package->getBins() as $bin) {
            $this->logger->log('    adding "' . $bin . '"');
            $bundle->addFile($bin);
        }
    }

    private function bundlePsr0(Bundle $bundle, Autoload $autoload)
    {
        foreach ($autoload->getPsr0() as $path) {
            $this->addDir($bundle, $path);
        }
    }

    private function bundleClassmap(Bundle $bundle, Autoload $autoload)
    {
        foreach($autoload->getClassmap() as $path) {
            $this->addFile($bundle, $path);
        }
    }

    private function bundleFiles(Bundle $bundle, Autoload $autoload)
    {
        foreach($autoload->getFiles() as $path) {
            $this->addFile($bundle, $path);
        }
    }

    private function addFile(Bundle $bundle, $file)
    {
        $this->logger->log('    adding "' . $file . '"');
        $bundle->addFile($this->package->getAbsolutePath($file));
    }

    private function addDir(Bundle $bundle, $dir)
    {
        $dir = $this->package->getAbsolutePath(rtrim($dir, '/') . '/');
        $this->logger->log('    adding "' . $dir . '"');
        $bundle->addDir(Finder::create()
                              ->files()
                              //->filter($this->getBlacklistFilter())
                              ->ignoreVCS(true)
                              ->in($dir));
    }
}
