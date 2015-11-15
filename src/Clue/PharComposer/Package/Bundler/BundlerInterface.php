<?php

namespace Clue\PharComposer\Package\Bundler;

interface BundlerInterface
{
   /**
     * returns a bundle
     *
     * @return  Bundle
     */
    public function bundle();
}
