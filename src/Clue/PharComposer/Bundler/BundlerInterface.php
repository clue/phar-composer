<?php

namespace Clue\PharComposer\Bundler;

use Clue\PharComposer\PharComposer;
use Herrera\Box\Box;
use Clue\PharComposer\Package;

interface BundlerInterface
{
    public function build(PharComposer $pharcomposer, Box $box, Package $package);
}
