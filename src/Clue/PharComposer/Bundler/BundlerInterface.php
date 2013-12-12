<?php

namespace Clue\PharComposer\Bundler;

use Clue\PharComposer\Logger;
use Clue\PharComposer\TargetPhar;

interface BundlerInterface
{
    public function build(TargetPhar $targetPhar, Logger $logger);
}
