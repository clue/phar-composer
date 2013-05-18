<?php

namespace Clue\PharComposer;

use UnexpectedValueException;
use InvalidArgumentException;
use Herrera\Box\Box;
use Herrera\Box\StubGenerator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class PharComposer
{
    private $pathProject;
    private $package;
    private $main = null;
    private $target = null;

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
                $this->target = str_replace('/', '-', $this->package['name']);
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
            foreach ($this->package['bin'] as $path) {
                $path = $this->getAbsolutePathForComposerPath($path);
                if (!file_exists($path)) {
                    throw new UnexpectedValueException('Bin file "' . $path . '" does not exist');
                }
                $this->main = $path;
                break;
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

    public function build()
    {
        // var_dump($this->package);

        $target = $this->getTarget();
        echo 'Start creating "'.$target.'"...' . PHP_EOL;
        $box = Box::create($target);

        $main = $this->getMain();
        if ($main !== null) {
            $this->addFile($box, $main);
            // $box->addFile($main);
            $box->getPhar()->setStub(
                StubGenerator::create()
                ->alias('default.phar')
                ->index($this->getPathLocalToBase($main))
                ->generate()
            );
        }

        if (isset($this->package['autoload'])) {
            if (isset($this->package['autoload']['psr-0'])) {
                foreach ($this->package['autoload']['psr-0'] as $path) {
                    $this->addDirectory($box,$this->getAbsolutePathForComposerPath($path));
                }
            }
            // TODO: other autoloaders
        }

        $this->addDirectory($box, $this->getAbsolutePathForComposerPath('vendor'));
    }

    private function addDirectory(Box $box, $dir)
    {
        $dir = rtrim($dir, '/') . '/';

        echo 'adding "' . $dir .'" as "' . $this->getPathLocalToBase($dir).'"...';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $dir,
                FilesystemIterator::KEY_AS_PATHNAME
                | FilesystemIterator::CURRENT_AS_FILEINFO
                | FilesystemIterator::SKIP_DOTS
            )
        );

        $box->buildFromIterator($iterator, (realpath($dir . '/../')));
        echo ' ok' . PHP_EOL;
    }

    private function addFile(Box $box, $file)
    {
        $local = $this->getPathLocalToBase($file);
        echo 'adding "' . $file .'" as "' . $local.'"...';
        $box->addFile($file, $local);
        echo ' ok' . PHP_EOL;
    }

    /**
     *
     * @param string $path
     * @return string
     */
    private function getAbsolutePathForComposerPath($path)
    {
        // return $path;
        return $this->pathProject . rtrim($path, '/');
    }

    private function getPathLocalToBase($path)
    {
        if (strpos($path, $this->pathProject) !== 0) {
            throw new UnexpectedValueException('Path "' . $path . '" is not within base project path "' . $this->pathProject . '"');
        }
        return substr($path, strlen($this->pathProject));
    }
}
