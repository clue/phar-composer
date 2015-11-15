<?php

namespace Clue\PharComposer\Phar;

use Symfony\Component\Finder\Finder;
use Herrera\Box\StubGenerator;
use UnexpectedValueException;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;
use Clue\PharComposer\Logger;
use Clue\PharComposer\Package\Bundle;
use Clue\PharComposer\Package\Package;

/**
 * The PharComposer is responsible for collecting options and then building the target phar
 */
class PharComposer
{
    private $pathProject;
    private $package;
    private $main = null;
    private $target = null;
    private $logger;
    private $step = '?';

    public function __construct($path)
    {
        $path = realpath($path);

        $this->package = $this->loadJson($path);
        $this->pathProject = dirname($path) . '/';
        $this->logger = new Logger();
    }

    /**
     * set output function to use to output log messages
     *
     * @param callable|boolean $output callable that receives a single $line argument or boolean echo
     */
    public function setOutput($output)
    {
        $this->logger->setOutput($output);
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
        // path is actually a directory => append package name
        if (is_dir($target)) {
            $this->target = null;
            $target = rtrim($target, '/') . '/' . $this->getTarget();
        }
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
        $this->log('[' . $this->step . '/' . $this->step.'] Creating phar <info>' . $this->getTarget() . '</info>');
        $time = microtime(true);

        $pathVendor = $this->getPathVendor();
        if (!is_dir($pathVendor)) {
            throw new RuntimeException('Directory "' . $pathVendor . '" not properly installed, did you run "composer install"?');
        }

        $target = $this->getTarget();
        if (file_exists($target)) {
            $this->log('  - Remove existing file <info>' . $target . '</info> (' . $this->getSize($target) . ')');
            if(unlink($target) === false) {
                throw new UnexpectedValueException('Unable to remove existing phar archive "'.$target.'"');
            }
        }

        $targetPhar = TargetPhar::create($target, $this);
        $this->log('  - Adding main package');
        $targetPhar->addBundle(Bundle::from($this->getPackageRoot(), $this->logger));

        $this->log('  - Adding composer base files');
        // explicitly add composer autoloader
        $targetPhar->addFile($pathVendor . 'autoload.php');

        // TODO: check for vendor/bin !

        // only add composer base directory (no sub-directories!)
        $targetPhar->buildFromIterator(new \GlobIterator($pathVendor . 'composer/*.*', \FilesystemIterator::KEY_AS_FILENAME));

        foreach ($this->getPackagesDependencies() as $package) {
            $this->log('  - Adding dependency "' . $package->getName() . '" from "' . $this->getPathLocalToBase($package->getDirectory()) . '"');
            $targetPhar->addBundle(Bundle::from($package, $this->logger));
        }


        $this->log('  - Setting main/stub');
        $chmod = 0755;
        $main = $this->getMain();
        if ($main === null) {
            $this->log('    WARNING: No main bin file defined! Resulting phar will NOT be executable');
        } else {
            $generator = StubGenerator::create()
                ->index($this->getPathLocalToBase($main))
                ->extract(true)
                ->banner("Bundled by phar-composer with the help of php-box.\n\n@link https://github.com/clue/phar-composer");

            $lines = file($main, FILE_IGNORE_NEW_LINES);
            if (substr($lines[0], 0, 2) === '#!') {
                $this->log('    Using referenced shebang "'. $lines[0] . '"');
                $generator->shebang($lines[0]);

                // remove shebang from main file and add (overwrite)
                unset($lines[0]);
                $targetPhar->addFromString($this->getPathLocalToBase($main), implode("\n", $lines));
            }

            $targetPhar->setStub($generator->generate());

            $chmod = octdec(substr(decoct(fileperms($main)),-4));
            $this->log('    Using referenced chmod ' . sprintf('%04o', $chmod));
        }

        $targetPhar->finalize();

        if ($chmod !== null) {
            $this->log('    Applying chmod ' . sprintf('%04o', $chmod));
            if (chmod($target, $chmod) === false) {
                throw new UnexpectedValueException('Unable to chmod target file "' . $target .'"');
            }
        }

        $time = max(microtime(true) - $time, 0);



        $this->log('');
        $this->log('    <info>OK</info> - Creating <info>' . $this->getTarget() .'</info> (' . $this->getSize($this->getTarget()) . ') completed after ' . round($time, 1) . 's');
    }

    private function getSize($path)
    {
        return round(filesize($path) / 1024, 1) . ' KiB';
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
        $this->logger->log($message);
    }

    public function setStep($step)
    {
        $this->step = $step;
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
}
