<?php

namespace Clue\PharComposer\Bundler;

use Clue\PharComposer\Package;

use Symfony\Component\Finder\Finder;
use Clue\PharComposer\PharComposer;
use Herrera\Box\Box;

class Complete implements BundlerInterface
{
    public function build(PharComposer $pharcomposer, Box $box, Package $package)
    {
        $iterator = Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->filter($package->getBlacklistFilter())
            ->exclude($package->getPathVendor())
            ->in($package->getDirectory());

        $pharcomposer->log('    Adding whole project directory "' . $package->getDirectory() . '"');
        $box->buildFromIterator($iterator, $pharcomposer->getBase());
    }
}
