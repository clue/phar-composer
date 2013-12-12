<?php

namespace Clue\PharComposer\Bundler;

use Clue\PharComposer\Package;

use Symfony\Component\Finder\Finder;
use Clue\PharComposer\PharComposer;
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

    public function build(PharComposer $pharcomposer, TargetPhar $targetPhar)
    {
        $iterator = Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->filter($this->package->getBlacklistFilter())
            ->exclude($this->package->getPathVendor())
            ->in($this->package->getDirectory());

        $pharcomposer->log('    Adding whole project directory "' . $this->package->getDirectory() . '"');
        $targetPhar->buildFromIterator($iterator);
    }
}
