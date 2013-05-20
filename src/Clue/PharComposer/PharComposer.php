<?php

namespace Clue\PharComposer;

use Herrera\Box\Box;
use Herrera\Box\StubGenerator;
use Clue\PharComposer\Bundler\BundlerInterface;
use Clue\PharComposer\Bundler\Explicit as ExplicitBundler;
use UnexpectedValueException;
use InvalidArgumentException;
use RuntimeException;

class PharComposer
{
    private $pathProject;
    private $package;
    private $main = null;
    private $target = null;
    private $bundler = null;

    public function __construct($path)
    {
        $path = realpath($path);

        $this->package = json_decode(file_get_contents($path), true);
        if ($this->package === null) {
            var_dump(json_last_error(), JSON_ERROR_SYNTAX);
            throw new InvalidArgumentException('Unable to parse given path "' . $path . '"');
        }

        $this->pathProject = dirname($path) . '/';
    }

    public function getTarget()
    {
        if ($this->target === null) {
            if (isset($this->package['name'])) {
                // skip vendor name from package name
                $this->target = substr($this->package['name'], strpos($this->package['name'], '/') + 1);
            } else {
                $this->target = basename($this->pathProject);
            }
            $this->target .= '.phar';
        }
        return $this->target;
    }

    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }

    public function getMain()
    {
        if ($this->main === null) {
            if (isset($this->package['bin'])) {
                foreach ($this->package['bin'] as $path) {
                    $path = $this->getAbsolutePathForComposerPath($path);
                    if (!file_exists($path)) {
                        throw new UnexpectedValueException('Bin file "' . $path . '" does not exist');
                    }
                    $this->main = $path;
                    break;
                }
            }
        }
        return $this->main;
    }

    public function setMain($main)
    {
        $this->main = $main;
        return $this;
    }

    /**
     * base project path. all files MUST BE relative to this location
     *
     * @return string
     */
    public function getBase()
    {
        return $this->pathProject;
    }

    /**
     * get absolute path to vendor directory
     *
     * @return string
     */
    public function getPathVendor()
    {
        $vendor = 'vendor';
        if (isset($this->package['config']['vendor-dir'])) {
            $vendor = $this->package['config']['vendor-dir'];
        }
        return $this->getAbsolutePathForComposerPath($vendor) . '/';
    }

    public function getBundler()
    {
        if ($this->bundler === null) {
            $this->bundler = new ExplicitBundler();
        }
        return $this->bundler;
    }

    public function setBundler(BundlerInterface $bundler)
    {
        $this->bundler = $bundler;

        return $this;
    }

    public function build()
    {
        $pathVendor = $this->getPathVendor();
        if (!is_dir($pathVendor)) {
            throw new RuntimeException('Directory "' . $pathVendor . '" not properly installed, did you run "composer install"?');
        }

        $target = $this->getTarget();
        echo 'Start creating "'.$target.'"...' . PHP_EOL;
        if (file_exists($target)) {
            echo 'Remove existing file...';
            if(unlink($target) === false) {
                throw new UnexpectedValueException('Unable to remove existing phar archive "'.$target.'"');
            } else {
                echo ' ok'. PHP_EOL;
            }
        }

        $box = Box::create($target);

        $main = $this->getMain();
        if ($main === null) {
            echo 'WARNING: No main bin file defined! Resulting phar will NOT be executable' . PHP_EOL;
        } else {
            $generator = StubGenerator::create()
                ->alias('default.phar') // TODO: remove me?
                ->index($this->getPathLocalToBase($main))
                ->banner("Bundled by phar-composer with the help of php-box.\n\n@link https://github.com/clue/phar-composer");

            $box->getPhar()->setStub($generator->generate());
        }

        $this->getBundler()->build($this, $box);
    }

    public function getPackageAutoload()
    {
        return isset($this->package['autoload']) ? $this->package['autoload'] : null;
    }

    /**
     *
     * @param string $path
     * @return string
     */
    public function getAbsolutePathForComposerPath($path)
    {
        // return $path;
        return $this->pathProject . rtrim($path, '/');
    }

    public function getPathLocalToBase($path)
    {
        if (strpos($path, $this->pathProject) !== 0) {
            throw new UnexpectedValueException('Path "' . $path . '" is not within base project path "' . $this->pathProject . '"');
        }
        return substr($path, strlen($this->pathProject));
    }
}
