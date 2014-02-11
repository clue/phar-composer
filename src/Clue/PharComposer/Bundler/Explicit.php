<?php
namespace Clue\PharComposer\Bundler;

use Clue\PharComposer\Bundle;
use Clue\PharComposer\Logger;
use Clue\PharComposer\Package;
use Symfony\Component\Finder\Finder;

class Explicit implements BundlerInterface
{
    /**
     * package the bundler is for
     *
     * @type  Package
     */
    private $package;
    /**
     *
     * @type  Logger
     */
    private $logger;

    public function __construct(Package $package, Logger $logger)
    {
        $this->package = $package;
        $this->logger  = $logger;
    }

    private function logFile($file)
    {
        $this->logger->log('    adding "' . $file . '"');
    }

    /**
     * returns a bundle
     *
     * @return  Bundle
     */
    public function bundle()
    {
        $bundle = new Bundle();
        $this->bundleBins($bundle);
        $autoload = $this->package->getAutoload();
        if ($autoload !== null) {
            $this->bundlePsr0($bundle, $autoload);
            $this->bundleClassmap($bundle, $autoload);
            $this->bundleFiles($bundle, $autoload);
        }

        return $bundle;
    }

    private function bundleBins(Bundle $bundle)
    {
        foreach ($this->package->getBins() as $bin) {
            $this->logFile($bin);
            $bundle->addFile($bin);
        }
    }

    private function bundlePsr0(Bundle $bundle, array $autoload)
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
                $dir = $this->package->getAbsolutePath($this->buildNamespacePath($namespace, $path));
                $bundle->addDir($this->createDirectory($dir));
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

    private function bundleClassmap(Bundle $bundle, array $autoload)
    {
        if (!isset($autoload['classmap'])) {
            return;
        }

        foreach($autoload['classmap'] as $path) {
            $this->addPath($bundle, $this->package->getAbsolutePath($path));
        }
    }

    private function bundleFiles(Bundle $bundle, array $autoload)
    {
        if (isset($autoload['files'])) {
            foreach($autoload['files'] as $path) {
                $this->logFile($path);
                $bundle->addFile($this->package->getAbsolutePath($path));
            }
        }
    }


    private function addPath(Bundle $bundle, $path)
    {
        if (is_dir($path)) {
            $bundle->addDir($this->createDirectory($path));
        } else {
            $bundle->addFile($path);
        }
    }

    private function createDirectory($dir)
    {
        $dir = rtrim($dir, '/') . '/';
        $this->logger->log('    adding "' . $dir . '"');
        return Finder::create()
            ->files()
            //->filter($this->getBlacklistFilter())
            ->ignoreVCS(true)
            ->in($dir);
    }
}
