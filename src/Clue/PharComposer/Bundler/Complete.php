<?php

namespace Clue\PharComposer\Bundler;

use Symfony\Component\Finder\Finder;
use Clue\PharComposer\PharComposer;
use Herrera\Box\Box;

class Complete implements BundlerInterface
{
    public function build(PharComposer $pharcomposer, Box $box)
    {
        $iterator = Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->in($pharcomposer->getBase());

        $box->buildFromIterator($iterator, $pharcomposer->getBase());
    }
}
