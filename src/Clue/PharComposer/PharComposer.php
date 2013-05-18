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

    public function build()
    {
        // var_dump($this->package);

        $box = Box::create($this->getTarget());

        $main = $this->getMain();
        if ($main !== null) {
            $box->addFile($main);
            $box->getPhar()->setStub(
                StubGenerator::create()
                ->alias('default.phar')
                ->index($main)
                ->generate()
            );
        }

        if (isset($this->package['autoload'])) {
            if (isset($this->package['autoload']['psr-0'])) {
                foreach ($this->package['autoload']['psr-0'] as $path) {
                    $this->addDirectory($box,$path);
                }
            }
            // TODO: other autoloaders
        }

        $this->addDirectory($box, 'vendor');
    }

    private function addDirectory(Box $box, $dir)
    {
        $path = $this->path($dir);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $dir,
                FilesystemIterator::KEY_AS_PATHNAME
                | FilesystemIterator::CURRENT_AS_FILEINFO
                | FilesystemIterator::SKIP_DOTS
            )
        );

        $box->buildFromIterator($iterator, realpath($dir . '/../'));
    }

    private function path($path) {
        // return $path;
        return $this->pathProject . rtrim($path, '/');
    }
}
