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
    private $target;

    public function __construct($path)
    {
        $path = realpath($path);

        $this->package = json_decode(file_get_contents($path), true);
        if ($this->package === null) {
            var_dump(json_last_error(), JSON_ERROR_SYNTAX);
            throw new InvalidArgumentException('Unable to parse given path "' . $path . '"');
        }
        $this->target = str_replace('/', '-', $this->package['name']) . '.phar';
        $this->pathProject = dirname($path) . '/';
    }

    public function build()
    {
        // var_dump($this->package);

        $box = Box::create($this->target);

        $main = null;
        foreach ($this->package['bin'] as $path) {
            if (!file_exists($path)) {
                throw new UnexpectedValueException('Bin file "' . $path . '" does not exist');
            }
            $main = $path;
            break;
        }

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
