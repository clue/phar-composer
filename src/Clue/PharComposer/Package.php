<?php

namespace Clue\PharComposer;

use Symfony\Component\Finder\SplFileInfo;

use Clue\PharComposer\Bundler\BundlerInterface;
use Clue\PharComposer\Bundler\Explicit as ExplicitBundler;
use Clue\PharComposer\Bundler\Complete as CompleteBundler;
use UnexpectedValueException;

class Package
{
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

    public function getBundler()
    {
        $bundlerName = $this->getBundlerName();
        if ($bundlerName === 'composer') {
            return new ExplicitBundler($this);
        } elseif ($bundlerName === 'complete') {
            return new CompleteBundler($this);
        } else {
            // TODO: instead of failing, just return a default bundler
            throw new UnexpectedValueException('Invalid bundler "' . $bundlerName . '" specified');
        }
    }

    private function getBundlerName()
    {
        if (isset($this->package['extra']['phar']['bundler'])) {
            return $this->package['extra']['phar']['bundler'];
        }

        return 'complete';
    }

    public function getAdditionalIncludes()
    {
        if (isset($this->package['extra']['phar']['include'])) {
            if (!is_array($this->package['extra']['phar']['include'])) {
                return array($this->package['extra']['phar']['include']);
            }

            return $this->package['extra']['phar']['include'];
        }

        return array();
    }

    public function getAutoload()
    {
        return isset($this->package['autoload']) ? $this->package['autoload'] : null;
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

    public function getBlacklist()
    {
        $blacklist   = $this->getAdditionalExcludes();
        $blacklist[] = 'composer.phar';
        $blacklist[] = 'phar-composer.phar';
        return array_map(array($this, 'getAbsolutePath'), $blacklist);
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
