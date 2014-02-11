<?php

namespace Clue\PharComposer\Bundler;

interface BundlerInterface
{
   /**
     * returns a bundle
     *
     * @return  Bundle
     */
    public function bundle();
}
