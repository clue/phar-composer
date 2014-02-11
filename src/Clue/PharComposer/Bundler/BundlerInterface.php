<?php

namespace Clue\PharComposer\Bundler;

use Clue\PharComposer\PharComposer;
use Clue\PharComposer\TargetPhar;

interface BundlerInterface
{
    public function build(PharComposer $pharcomposer, TargetPhar $targetPhar);
}
