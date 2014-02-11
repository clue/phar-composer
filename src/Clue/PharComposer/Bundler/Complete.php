<?php

namespace Clue\PharComposer\Bundler;

use Clue\PharComposer\Package;

use Symfony\Component\Finder\Finder;
use Clue\PharComposer\Logger;
use Clue\PharComposer\TargetPhar;

class Complete implements BundlerInterface
{
    /**
     * package the bundler is for
     *
     * @type  Package
     */
    private $package;

    public function __construct(Package $package)
    {
        $this->package = $package;
    }

    public function build(TargetPhar $targetPhar, Logger $logger)
    {
        $iterator = Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->filter($this->package->getBlacklistFilter())
            ->exclude($this->package->getPathVendor())
            ->in($this->package->getDirectory());

        $logger->log('    Adding whole project directory "' . $this->package->getDirectory() . '"');
        $targetPhar->buildFromIterator($iterator);
    }
}
