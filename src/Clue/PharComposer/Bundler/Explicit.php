<?php

namespace Clue\PharComposer\Bundler;

use Herrera\Box\Box;

class Explicit extends Base
{
    protected function bundle()
    {
        foreach ($this->package->getBins() as $bin) {
            $this->addFile($bin);
        }

        $autoload = $this->package->getAutoload();

        if ($autoload !== null) {
            $this->bundlePsr0($autoload);
            $this->bundleClassmap($autoload);
            $this->bundleFiles($autoload);
        }
    }

    private function bundlePsr0(array $autoload)
    {
        if (!isset($autoload['psr-0'])) {
            return;
        }

        foreach ($autoload['psr-0'] as $namespace => $paths) {
            if (!is_array($paths)) {
                // PSR autoloader may define a single or multiple paths
                $paths = array($paths);
            }
            foreach($paths as $path) {
                // TODO: this is not correct actually... should work for most repos nevertheless
                // TODO: we have to take target-dir into account

                $this->addDirectory($this->package->getAbsolutePath($this->buildNamespacePath($namespace, $path)));
            }
        }
    }

    private function buildNamespacePath($namespace, $path)
    {
        if ($namespace === '') {
            return $path;
        }

        $namespace = str_replace('\\', '/', $namespace);
        if ($path === '') {
            // namespace in project root => namespace is path
            return $namespace;
        }

        // namespace in sub-directory => add namespace to path
        return rtrim($path, '/') . '/' . $namespace;
    }

    private function bundleClassmap(array $autoload)
    {
        if (!isset($autoload['classmap'])) {
            return;
        }

        foreach($autoload['classmap'] as $path) {
            $path = $this->package->getAbsolutePath($path);
            if (is_dir($path)) {
                $this->addDirectory($path);
            } else {
                $this->addFile($path);
            }
        }
    }

    private function bundleFiles(array $autoload)
    {
        if (isset($autoload['files'])) {
            foreach($autoload['files'] as $path) {
                $this->addFile($this->package->getAbsolutePath($path));
            }
        }
    }
}
