<?php

namespace Clue\PharComposer\Bundler;

use Clue\PharComposer\Package;
use Symfony\Component\Finder\Finder;
use Clue\PharComposer\PharComposer;
use Herrera\Box\Box;

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
     * @var Box
     */
    protected $box;

    /**
     *
     * @var PharComposer
     */
    protected $pharcomposer;

    public function __construct(Package $package)
    {
        $this->package = $package;
    }

    public function build(PharComposer $pharcomposer, Box $box)
    {
        $this->pharcomposer = $pharcomposer;
        $this->box          = $box;
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

        $this->pharcomposer->log('    adding "' . $dir .'" as "' . $this->pharcomposer->getPathLocalToBase($dir) . '"');
        $this->box->buildFromIterator($iterator, $this->pharcomposer->getBase());
    }

    private function addFile($file)
    {
        $local = $this->pharcomposer->getPathLocalToBase($file);
        $this->pharcomposer->log('    adding "' . $file .'" as "' . $local . '"');
        $this->box->addFile($file, $local);
    }
}
