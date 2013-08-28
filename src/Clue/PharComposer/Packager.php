<?php

namespace Clue\PharComposer;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;
use UnexpectedValueException;
use InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;

class Packager
{
    private $output;

    public function __construct()
    {
        $this->setOutput(true);
    }

    private function log($message)
    {
        $fn = $this->output;
        $fn($message . PHP_EOL);
    }

    public function setOutput($fn)
    {
        if ($fn instanceof OutputInterface) {
            $fn = function ($line) use ($fn) {
                $fn->write($line);
            };
        } elseif ($fn === true) {
            $fn = function ($line) {
                echo $line;
            };
        } elseif ($fn === false) {
            $fn = function () { };
        }
        $this->output = $fn;
    }

    public function coerceWritable()
    {
        try {
            $this->assertWritable();
        }
        catch (UnexpectedValueException $e) {
            if (!function_exists('pcntl_exec')) {
                $this->log('<error>' . $e->getMessage() . '</error>');
                return;
            }

            $this->log('<info>' . $e->getMessage() . ', trying to re-spawn with correct config</info>');
            sleep(1);

            $args = array_merge(array('php', '-d phar.readonly=off'), $_SERVER['argv']);
            if (pcntl_exec('/usr/bin/env', $args) === false) {
                $this->log('<error>Unable to switch into new configuration</error>');
                return;
            }
        }
    }

    public function assertWritable()
    {
        if (ini_get('phar.readonly') === '1') {
            throw new UnexpectedValueException('Your configuration disabled writing phar files (phar.readonly = On), please update your configuration or run with "php -d phar.readonly=off ' . $_SERVER['argv'][0].'"');
        }
    }

    public function getPharer($path, $version = null)
    {
        if ($version !== null) {
            // TODO: should be the other way around
            $path .= ':' . $version;
        }

        $this->assertWritable();

        $step = 1;
        $steps = 1;

        if ($this->isPackageUrl($path)) {
            $url = $path;
            $version = null;
            $steps = 3;

            if (preg_match('/(.+)\:((?:dev\-|v\d)\S+)$/i', $url, $match)) {
                $url = $match[1];
                $version = $match[2];
                if (substr($version, 0, 4) === 'dev-') {
                    $version = substr($version, 4);
                }
            }


            $path = $this->getDirTemporary();

            $finder = new ExecutableFinder();

            $git = $finder->find('git', '/usr/bin/git');

            $that = $this;
            $this->displayMeasure(
                '[' . $step++ . '/' . $steps.'] Cloning <info>' . $url . '</info> into temporary directory <info>' . $path . '</info>',
                function() use ($that, $url, $path, $version, $git) {
                    $that->exec($git . ' clone ' . escapeshellarg($url) . ' ' . escapeshellarg($path));

                    if ($version !== null) {
                        $this->exec($git . ' checkout ' . escapeshellarg($version) . ' 2>&1', $path);
                    }
                },
                'Cloning base repository completed'
            );

            $pharcomposer = new PharComposer($path . '/composer.json');
            $package = $pharcomposer->getPackageRoot()->getName();

            if (is_file('composer.phar')) {
                $command = $finder->find('php', '/usr/bin/php') . ' composer.phar';
            } else {
                $command = $finder->find('composer', '/usr/bin/composer');
            }
            $command .= ' install --no-dev --no-progress --no-scripts';

            $this->displayMeasure(
                '[' . $step++ . '/' . $steps.'] Installing dependencies for <info>' . $package . '</info> into <info>' . $path . '</info> (using <info>' . $command . '</info>)',
                function () use ($that, $command, $path) {
                    try {
                        $that->exec($command, $path);
                    }
                    catch (UnexpectedValueException $e) {
                        throw new UnexpectedValueException('Installing dependencies via composer failed', 0, $e);
                    }
                },
                'Downloading dependencies completed'
            );
        } elseif ($this->isPackageName($path)) {
            if (is_dir($path)) {
                $this->log('<info>There\'s also a directory with the given name</info>');
            }
            $steps = 2;
            $package = $path;

            $path = $this->getDirTemporary();

            $finder = new ExecutableFinder();
            if (is_file('composer.phar')) {
                $command = $finder->find('php', '/usr/bin/php') . ' composer.phar';
            } else {
                $command = $finder->find('composer', '/usr/bin/composer');
            }
            $command .= ' create-project ' . escapeshellarg($package) . ' ' . escapeshellarg($path) . ' --no-dev --no-progress --no-scripts';

            $that = $this;
            $this->displayMeasure(
                '[' . $step++ . '/' . $steps.'] Installing <info>' . $package . '</info> to temporary directory <info>' . $path . '</info> (using <info>' . $command . '</info>)',
                function () use ($that, $command) {
                    try {
                        $that->exec($command);
                    }
                    catch (UnexpectedValueException $e) {
                        throw new UnexpectedValueException('Installing package via composer failed', 0, $e);
                    }
                },
                'Downloading package completed'
            );
        }

        if (is_dir($path)) {
            $path = rtrim($path, '/') . '/composer.json';
        }
        if (!is_file($path)) {
            throw new InvalidArgumentException('The given path "' . $path . '" is not a readable file');
        }

        $pharer = new PharComposer($path);
        $pharer->setOutput($this->output);
        $pharer->setStep($step);

        $pathVendor = $pharer->getPathVendor();
        if (!is_dir($pathVendor)) {
            $this->log('<error>Project is not installed via composer. Run "composer install" manually</error>');
            return;
        }

        return $pharer;
    }

    public function measure($fn)
    {
        $time = microtime(true);

        $fn();

        return max(microtime(true) - $time, 0);
    }

    public function displayMeasure($title, $fn, $success)
    {
        $this->log($title);

        $time = $this->measure($fn);

        $this->log('');
        $this->log('    <info>OK</info> - ' . $success .' (after ' . round($time, 1) . 's)');
    }

    public function exec($cmd, $chdir = null)
    {
        $ok = true;
        $nl = true;

        //
        $output = $this->output;

        $process = new Process($cmd, $chdir);
        $process->start();
        $code = $process->wait(function($type, $data) use ($output, &$ok, &$nl) {
            if ($nl === true) {
                $data = "\n" . $data;
                $nl = false;
            }
            if (substr($data, -1) === "\n") {
                $nl = true;
                $data = substr($data, 0, -1);
            }
            $data = str_replace("\n", "\n    ", $data);

            if ($type === Process::OUT) {
                $output($data);
            } else {
                $output($data);
                $ok = false;
            }
        });
        if ($nl) {
            $this->log('');
        }

        if ($code !== 0) {
            throw new UnexpectedValueException('Error status code: ' . $process->getExitCodeText() . ' (code ' . $code . ')');
        }

        if (!$ok) {
            throw new UnexpectedValueException('Error output present');
        }
    }

    private function isPackageName($path)
    {
        return !!preg_match('/^[^\s\/]+\/[^\s\/]+(\:[^\s]+)?$/i', $path);
    }

    private function isPackageUrl($path)
    {
        return (strpos($path, '://') !== false && @parse_url($path) !== false);
    }

    private function getDirTemporary()
    {
        $path = sys_get_temp_dir() . '/phar-composer' . mt_rand(0,9);
        while (is_dir($path)) {
            $path .= mt_rand(0, 9);
        }

        return $path;
    }
}
