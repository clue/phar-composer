<?php

namespace Clue\PharComposer\Package\Bundler;

use Clue\PharComposer\Package\Bundle;
use Clue\PharComposer\Logger;
use Clue\PharComposer\Package\Package;
use Symfony\Component\Finder\Finder;

/**
 * The default Bundler instance which bundles the whole package directory
 */
class Complete implements BundlerInterface
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
        $iterator = Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->filter($this->package->getBlacklistFilter())
            ->exclude($this->package->getPathVendorRelative())
            ->in($this->package->getDirectory());
        $this->logger->log('    Adding whole project directory "' . $this->package->getDirectory() . '"');
        return $bundle->addDir($iterator);
    }
}
