<?php

namespace Clue\PharComposer;

use Symfony\Component\Finder\Finder;

use Herrera\Box\Box;
use Herrera\Box\StubGenerator;
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

    public function __construct($path)
    {
        $path = realpath($path);

        $this->package = $this->loadJson($path);
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
            foreach ($this->getPackageRoot()->getBins() as $path) {
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

    /**
     * get absolute path to vendor directory
     *
     * @return string
     */
    public function getPathVendor()
    {
        return $this->getPackageRoot()->getPathVendor();
    }

    /**
     *
     * @return Package
     */
    public function getPackageRoot()
    {
        return new Package($this->package, $this->pathProject);
    }

    /**
     *
     * @return Package[]
     */
    public function getPackagesDependencies()
    {
        $packages = array();

        $pathVendor = $this->getPathVendor();

        // load all installed packages (use installed.json which also includes version instead of composer.lock)
        if (is_file($pathVendor . 'composer/installed.json')) {
            // file does not exist if there's nothing to be installed
            $installed = $this->loadJson($pathVendor . 'composer/installed.json');

            foreach ($installed as $package) {
                $dir = $package['name'] . '/';
                if (isset($package['target-dir'])) {
                    $dir .= trim($package['target-dir'], '/') . '/';
                }

                $dir = $pathVendor . $dir;
                $packages []= new Package($package, $dir);
            }
        }

        return $packages;
    }

    public function build()
    {
        $pathVendor = $this->getPathVendor();
        if (!is_dir($pathVendor)) {
            throw new RuntimeException('Directory "' . $pathVendor . '" not properly installed, did you run "composer install"?');
        }

        $target = $this->getTarget();
        $this->log('Start creating "'.$target.'"');
        if (file_exists($target)) {
            $this->log('  - Remove existing file');
            if(unlink($target) === false) {
                throw new UnexpectedValueException('Unable to remove existing phar archive "'.$target.'"');
            }
        }

        $box = Box::create($target);
        $box->getPhar()->startBuffering();

        $this->log('  - Adding main package');
        $this->addPackage($this->getPackageRoot(), $box);

        $this->log('  - Adding composer base files');
        // explicitly add composer autoloader
        $box->addFile($pathVendor . 'autoload.php');

        // TODO: check for vendor/bin !

        // only add composer base directory (no sub-directories!)
        $box->buildFromIterator(new \GlobIterator($pathVendor . 'composer/*.*', \FilesystemIterator::KEY_AS_FILENAME), $this->getBase());

        foreach ($this->getPackagesDependencies() as $package) {
            $this->log('  - Adding dependency "' . $package->getName() . '" from "' . $this->getPathLocalToBase($package->getDirectory()) . '"');
            $this->addPackage($package, $box);
        }


        $this->log('  - Setting main/stub');
        $chmod = 0755;
        $main = $this->getMain();
        if ($main === null) {
            $this->log('    WARNING: No main bin file defined! Resulting phar will NOT be executable');
        } else {
            $generator = StubGenerator::create()
                ->index($this->getPathLocalToBase($main))
                ->banner("Bundled by phar-composer with the help of php-box.\n\n@link https://github.com/clue/phar-composer");

            $lines = file($main, FILE_IGNORE_NEW_LINES);
            if (substr($lines[0], 0, 2) === '#!') {
                $this->log('    Using referenced shebang "'. $lines[0] . '"');
                $generator->shebang($lines[0]);

                // remove shebang from main file and add (overwrite)
                unset($lines[0]);
                $box->addFromString($this->getPathLocalToBase($main), implode("\n", $lines));
            }

            $box->getPhar()->setStub($generator->generate());

            $chmod = octdec(substr(decoct(fileperms($main)),-4));
            $this->log('    Using referenced chmod ' . sprintf('%04o', $chmod));
        }

        $box->getPhar()->stopBuffering();

        if ($chmod !== null) {
            $this->log('    Applying chmod ' . sprintf('%04o', $chmod));
            if (chmod($target, $chmod) === false) {
                throw new UnexpectedValueException('Unable to chmod target file "' . $target .'"');
            }
        }
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

    private function loadJson($path)
    {
        $ret = json_decode(file_get_contents($path), true);
        if ($ret === null) {
            var_dump(json_last_error(), JSON_ERROR_SYNTAX);
            throw new InvalidArgumentException('Unable to parse given path "' . $path . '"');
        }
        return $ret;
    }

    private function addPackage(Package $package, Box $box)
    {
        $package->getBundler()->build($this, $box, $package);
    }
}
