<?php

namespace Clue\PharComposer;

use Symfony\Component\Finder\SplFileInfo;

use Clue\PharComposer\Bundler\Explicit as ExplicitBundler;
use Clue\PharComposer\Bundler\Complete as CompleteBundler;
use Clue\PharComposer\Package\Autoload;

class Package
{
    private $blacklist;

    public function __construct(array $package, $directory)
    {
        $this->package = $package;
        $this->directory = $directory;
    }

    public function getName()
    {
        return isset($this->package['name']) ? $this->package['name'] : 'unknown';
    }

    public function getPathVendor()
    {
        $vendor = 'vendor';
        if (isset($this->package['config']['vendor-dir'])) {
            $vendor = $this->package['config']['vendor-dir'];
        }

        return $this->getAbsolutePath($vendor . '/');
    }

    public function getDirectory()
    {
        return $this->directory;
    }

    public function getBundler(Logger $logger)
    {
        $bundlerName = 'complete';
        if (isset($this->package['extra']['phar']['bundler'])) {
            $bundlerName = $this->package['extra']['phar']['bundler'];
        }

        if ($bundlerName === 'composer') {
            return new ExplicitBundler($this, $logger);
        } elseif ($bundlerName === 'complete') {
            return new CompleteBundler($this, $logger);
        } else {
            $logger->log('Invalid bundler "' . $bundlerName . '" specified in package "' . $this->getName() . '", will fall back to "complete" bundler');
            return new CompleteBundler($this, $logger);
        }
    }

    public function getAutoload()
    {
        return new Autoload(isset($this->package['autoload']) ? $this->package['autoload'] : array());
    }

    public function getBins()
    {
        if (!isset($this->package['bin'])) {
            return array();
        }

        $bins = array();
        foreach ($this->package['bin'] as $bin) {
            $bins []= $this->getAbsolutePath($bin);
        }

        return $bins;
    }

    /**
     * checks if given resource is blacklisted which means it should not be added to the target phar
     *
     * @param   string  $resource
     * @return  bool
     */
    public function isBlacklisted($resource)
    {
        if (in_array($resource, $this->getBlacklist())) {
            return true;
        }

        foreach ($this->getBlacklist() as $path) {
            if (substr($resource, 0, strlen($path)) === $path) {
                return true;
            }
        }

        return false;
    }

    public function getBlacklist()
    {
        if (null === $this->blacklist) {
            $blacklist   = $this->getAdditionalExcludes();
            $blacklist[] = 'composer.phar';
            $blacklist[] = 'phar-composer.phar';
            $this->blacklist = array_map(array($this, 'getAbsolutePath'), $blacklist);
        }

        return $this->blacklist;
    }

    private function getAdditionalExcludes()
    {
        if (isset($this->package['extra']['phar']['exclude'])) {
            if (!is_array($this->package['extra']['phar']['exclude'])) {
                return array($this->package['extra']['phar']['exclude']);
            }

            return $this->package['extra']['phar']['exclude'];
        }

        return array();
    }

    /**
     *
     * @return Closure
     * @uses self::getBlacklist()
     */
    public function getBlacklistFilter()
    {
        $blacklist = $this->getBlacklist();

        return function (SplFileInfo $file) use ($blacklist) {
            return in_array($file->getPathname(), $blacklist) ? false : null;
        };
    }

    public function getAbsolutePath($path)
    {
        return $this->directory . ltrim($path, '/');
    }
}
