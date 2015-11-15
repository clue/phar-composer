<?php

namespace Clue\PharComposer\Package\Bundler;

/**
 * The Bundler is responsible for creating a Bundle containing all files which belong to a given package
 */
interface BundlerInterface
{
   /**
     * returns a bundle
     *
     * @return  Bundle
     */
    public function bundle();
}
