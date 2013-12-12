<?php

namespace Clue\PharComposer\Bundler;

use Clue\PharComposer\Package;
use Symfony\Component\Finder\Finder;
use Clue\PharComposer\Logger;
use Clue\PharComposer\TargetPhar;

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
     * @var TargetPhar
     */
    protected $targetPhar;

    /**
     *
     * @var Logger
     */
    protected $logger;

    public function __construct(Package $package)
    {
        $this->package = $package;
    }

    public function build(TargetPhar $targetPhar, Logger $logger)
    {
        $this->logger     = $logger;
        $this->targetPhar = $targetPhar;
        $this->bundleBins();
        $autoload = $this->package->getAutoload();

        if ($autoload !== null) {
            $this->bundlePsr0($autoload);
            $this->bundleClassmap($autoload);
            $this->bundleFiles($autoload);
        }

        $this->bundleAdditionalIncludes();
    }

    private function bundleBins()
    {
        foreach ($this->package->getBins() as $bin) {
            $this->addFile($bin);
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
            $this->addPath($this->package->getAbsolutePath($path));
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

    private function bundleAdditionalIncludes()
    {
        foreach ($this->package->getAdditionalIncludes() as $additionalInclude) {
            $this->addPath($this->package->getAbsolutePath($additionalInclude));
        }
    }

    private function addPath($path)
    {
        if (is_dir($path)) {
            $this->addDirectory($path);
        } else {
            $this->addFile($path);
        }
    }

    private function addDirectory($dir)
    {
        $dir = rtrim($dir, '/') . '/';

        $iterator = Finder::create()
            ->files()
            //->filter($this->getBlacklistFilter())
            ->ignoreVCS(true)
            ->in($dir);

        $this->logger->log('    adding "' . $dir);
        $this->targetPhar->buildFromIterator($iterator);
    }

    private function addFile($file)
    {
        $this->logger->log('    adding "' . $file);
        $this->targetPhar->addFile($file);
    }
}
