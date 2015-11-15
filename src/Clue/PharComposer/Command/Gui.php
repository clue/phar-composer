<?php

namespace Clue\PharComposer\Command;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Clue\PharComposer\Phar\PharComposer;
use InvalidArgumentException;
use UnexpectedValueException;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;
use Packagist\Api\Client;
use Packagist\Api\Result\Result;
use Packagist\Api\Result\Package;
use Packagist\Api\Result\Package\Version;
use Clue\PharComposer\Phar\Packager;
use React\EventLoop\Factory;
use Clue\Zenity\React\Launcher;
use Clue\Zenity\React\Builder;

class Gui extends Command
{
    protected function configure()
    {
        $this->setName('gui')
             ->setDescription('Interactive GUI (requires Zenity, likely only on Linux/etc.)');
    }

    public function hasZenity()
    {
        return $this->hasBin('zenity');
    }

    private function hasBin($bin)
    {
        foreach (explode(PATH_SEPARATOR, getenv('PATH')) as $path) {
            $path = rtrim($path, '/') . '/' . $bin;
            if (file_exists($path)) {
                return true;
            }
        }
        return false;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loop = Factory::create();
        $launcher = new Launcher($loop);
        $builder = new Builder($launcher);

        $packager = new Packager();
        $packager->setOutput(function ($line) use ($builder) {
            $builder->info(strip_tags($line))->waitReturn();
        });
        $packager->coerceWritable(0);

        foreach (array('gksudo', 'kdesudo', 'cocoasudo', 'sudo') as $bin) {
            if ($this->hasBin($bin)) {
                $packager->setBinSudo($bin);
                break;
            }
        }

        $packager->setOutput($output);


        $menu = $builder->listMenu(
            array(
                'search' => 'Search package online',
                'local'  => 'Select local package',
                'url'    => 'Build from git-Repository',
                'about'  => 'About clue/phar-composer'
            ),
            'Action'
        );
        $menu->setTitle('clue/phar-composer');
        $menu->setWindowIcon('info');
        $menu->setCancelLabel('Quit');
        $selection = $menu->waitReturn();

        if ($selection === 'search') {
            $pharer = $this->doSearch($builder, $packager);
        } elseif ($selection === 'local') {
            do {
                $dir = $builder->directorySelection()->waitReturn();
                if ($dir === false) {
                    return;
                }
                try {
                    $pharer = $packager->getPharer($dir);
                    break;
                }
                catch (\Exception $e) {
                    $builder->error('Could not initialize composer package:' . PHP_EOL . PHP_EOL . $e->getMessage())->waitReturn();
                }
            } while(true);
        } elseif ($selection === 'url') {
            $pharer = $this->doUrl($builder, $packager);
        } else {
            return;
        }

        if ($pharer === null) {
            return;
        }

        $action = $builder->listMenu(
            array(
                'build'   => 'Build project',
                'install' => 'Install project system-wide'
            ),
            'Action for "' . $pharer->getPackageRoot()->getName() .'"' /*,
            'Quit' */
        )->waitReturn();

        if ($action === 'build') {
            $this->doBuild($builder, $packager, $pharer);
        } elseif ($action ==='install') {
            $this->doInstall($builder, $packager, $pharer);
        } else {
            return;
        }

        $builder->info('Successfully built ' . $pharer->getTarget() . '!')->waitReturn();
    }

    protected function doSearch(Builder $builder, Packager $packager)
    {
        $oldname = null;

        do {
            $dialog = $builder->entry('Search (partial) project name', $oldname);
            $dialog->setTitle('Search project name');
            $name = $dialog->waitReturn();
            if ($name === false) {
                return;
            }
            $oldname = $name;

            $pulsate = $builder->pulsate('Searching for "' . $name . '"...');
            $pulsate->setNoCancel(true);
            $pulsate->run();

            $packagist = new Client();

            $choices = array();
            foreach ($packagist->search($name) as $package) {
                /* @var $package Result */

                $choices[$package->getName()] = array(
                    $package->getName(),
                    mb_strimwidth($package->getDescription(), 0, 80, '…', 'utf-8'),
                    $package->getDownloads()
                );
            }

            $pulsate->close();

            if (!$choices) {
                $builder->warning('No package matching "' . $name .'" found!')->waitReturn();
                $name = false;
                continue;
            }

            $table = $builder->table($choices, array('Name', 'Description', 'Downloads'), 'Select matching package');
            $table->setTitle('Select matching package');
            $table->setCancelLabel('Back to Search');
            $table->setWidth(1000);
            $table->setHeight(600);

            $name = $table->waitReturn();
        } while (is_bool($name));

        $pulsate = $builder->pulsate('Selected <info>' . $name . '</info>, listing versions...');
        $pulsate->setNoCancel(true);
        $pulsate->run();

        $package = $packagist->get($name);
        /* @var $package Package */

        $choices = array();
        foreach ($package->getVersions() as $version) {
            /* @var $version Version */

            $time = new \DateTime($version->getTime());
            $time = $time->format('Y-m-d H:i:s');

            $bin = $version->getBin();
            if ($bin) {
                $bin = '☑ ' . array_shift($bin);
            } else {
                $bin = '☐ no executable bin';
            }

            $choices[$version->getVersion()] = array(
                $version->getVersion(),
                $time,
                $bin
            );
        }

        $pulsate->close();

        if (!$choices) {
            $builder->warning('No versions for package "' . $name .'" found!')->waitReturn();
            return;
        }

        $dialog = $builder->table($choices, array('Version', 'Date', 'Binary'), 'Select available version');
        $dialog->setWidth(800);
        $dialog->setHeight(300);
        $version = $dialog->waitReturn();

        if (is_bool($version)) {
            return;
        }

        $pulsate = $builder->pulsate('Installing to temporary directory...')->run();
        $pharer = $packager->getPharer($name, $version);
        $pulsate->close();

        return $pharer;
    }

    protected function doUrl(Builder $builder, Packager $packager)
    {
        do {
            $url = $builder->entry('Git URL to clone')->waitReturn();
            if ($url === false) {
                return;
            }
            $pulsate = $builder->pulsate('Cloning and installing from git...')->run();
            try {
                $pharer = $packager->getPharer($url);
                $pulsate->close();

                return $pharer;
            }
            catch (\Exception $e) {
                $pulsate->close();

                $builder->error('Unable to clone repository:' . PHP_EOL . PHP_EOL . $e->getMessage())->waitReturn();
            }
        } while(true);
    }

    protected function doInstall(Builder $builder, Packager $packager, PharComposer $pharer)
    {
        $pulsate = $builder->pulsate('Installing...')->run();

        $path = $packager->getSystemBin($pharer);
        $packager->install($pharer, $path);

        $pulsate->close();
    }

    protected function doBuild(Builder $builder, Packager $packager, PharComposer $pharer)
    {
        $pulsate = $builder->pulsate('Waiting for target file name...')->run();

        $save = $builder->fileSave('Location to write file to', $pharer->getTarget());

        $target = $save->waitReturn();

        if ($target === false) {
            return;
        }

        $pulsate->close();
        $pulsate = $builder->pulsate('Building target file...')->run();

        $pharer->setTarget($target);
        $pharer->build();

        $pulsate->close();
    }
}
