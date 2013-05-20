<?php

namespace Clue\PharComposer;

use Herrera\Box\Box;
use Herrera\Box\StubGenerator;
use Clue\PharComposer\Bundler\BundlerInterface;
use Clue\PharComposer\Bundler\Explicit as ExplicitBundler;
use Clue\PharComposer\Bundler\Complete as CompleteBundler;
use UnexpectedValueException;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

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
            if (isset($this->package['extra']['phar']['bundler'])) {
                $bundler = $this->package['extra']['phar']['bundler'];
                if ($bundler === 'composer') {
                    $this->bundler = new ExplicitBundler();
                } elseif ($bundler === 'complete') {
                    $this->bundler = new CompleteBundler();
                } else {
                    throw new UnexpectedValueException('Invalid bundler "' . $bundler . '" specified');
                }
            } else {
                $this->bundler = new CompleteBundler();
            }
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
        $this->log('Start creating "'.$target.'"...');
        if (file_exists($target)) {
            $this->log('Remove existing file...');
            if(unlink($target) === false) {
                throw new UnexpectedValueException('Unable to remove existing phar archive "'.$target.'"');
            }
        }

        $box = Box::create($target);
        $box->getPhar()->startBuffering();

        $this->getBundler()->build($this, $box);

        $chmod = 0755;
        $main = $this->getMain();
        if ($main === null) {
            $this->log('WARNING: No main bin file defined! Resulting phar will NOT be executable');
        } else {
            $generator = StubGenerator::create()
                ->index($this->getPathLocalToBase($main))
                ->banner("Bundled by phar-composer with the help of php-box.\n\n@link https://github.com/clue/phar-composer");

            $lines = file($main, FILE_IGNORE_NEW_LINES);
            if (substr($lines[0], 0, 2) === '#!') {
                $this->log('Using referenced shebang "'. $lines[0] . '"');
                $generator->shebang($lines[0]);

                // remove shebang from main file and add (overwrite)
                unset($lines[0]);
                $box->addFromString($this->getPathLocalToBase($main), implode("\n", $lines));
            }

            $box->getPhar()->setStub($generator->generate());

            $chmod = octdec(substr(decoct(fileperms($main)),-4));
            $this->log('Using referenced chmod ' . sprintf('%04o', $chmod));
        }

        $box->getPhar()->stopBuffering();

        if ($chmod !== null) {
            $this->log('Applying chmod ' . sprintf('%04o', $chmod) . '...');
            if (chmod($target, $chmod) === false) {
                throw new UnexpectedValueException('Unable to chmod target file "' . $target .'"');
            }
        }
    }

    public function getPackageAutoload()
    {
        return isset($this->package['autoload']) ? $this->package['autoload'] : null;
    }

    public function getBlacklist()
    {
        return array(
            $this->getAbsolutePathForComposerPath('composer.phar'),
            $this->getAbsolutePathForComposerPath('phar-composer.phar')
        );
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

    public function log($message)
    {
        echo $message . PHP_EOL;
    }
}
