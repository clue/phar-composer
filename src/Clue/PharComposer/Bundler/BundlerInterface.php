<?php

namespace Clue\PharComposer\Bundler;

use Clue\PharComposer\PharComposer;
use Herrera\Box\Box;

interface BundlerInterface
{
    public function build(PharComposer $pharcomposer, Box $box);
}
