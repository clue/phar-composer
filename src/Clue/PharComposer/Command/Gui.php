<?php

namespace Clue\PharComposer\Command;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Clue\PharComposer\PharComposer;
use InvalidArgumentException;
use UnexpectedValueException;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;
use Packagist\Api\Client;
use Packagist\Api\Result\Result;
use Packagist\Api\Result\Package;
use Packagist\Api\Result\Package\Version;
use Clue\PharComposer\Packager;
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
        foreach (explode(PATH_SEPARATOR, getenv('PATH')) as $path) {
            $path = rtrim($path, '/') . '/zenity';
            if (file_exists($path)) {
                return true;
            }
        }
        return false;
    }

    protected function select(OutputInterface $output, $label, array $choices, $abortable = null)
    {
        $dialog = $this->getHelperSet()->get('dialog');
        /* @var $dialog DialogHelper */

        if (!$choices) {
            $output->writeln('<error>No matching packages found</error>');
            return;
        }

        // TODO: skip dialog, if exact match

        if ($abortable === true) {
            $abortable = '<hl>Abort</hl>';
        } elseif ($abortable === false) {
            $abortable = null;
        }

        $select = array_merge(array(0 => $abortable), array_values($choices));
        if ($abortable === null) {
            unset($select[0]);
        }

        $index = $dialog->select($output, $label, $select);

        if ($index == 0) {
            return null;
        }

        $indices = array_keys($choices);
        return $indices[$index - 1];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packager = new Packager();
        $packager->setOutput($output);
        $packager->coerceWritable();

        $loop = Factory::create();
        $launcher = new Launcher($loop);
        $builder = new Builder($launcher);

        $menu = $builder->listMenu(array('Search package online', 'Select local package', 'About clue/phar-composer'), 'Action');
        $menu->setTitle('clue/phar-composer');
        $menu->setWindowIcon('info');
        $menu->setCancelLabel('Quit');
        $selection = $menu->waitReturn();

        if ($selection === false) {
            return;
        }

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

            $table = $builder->table($choices, array('Name', 'Description', 'Downloads'), 'Select matching package');
            $table->setTitle('Select matching package');
            $table->setCancelLabel('Back to Search');
            $table->setWidth(1000);
            $table->setHeight(600);

            $name = $table->waitReturn();
        } while ($name === false);

        $pulsate = $builder->pulsate('Selected <info>' . $name . '</info>, listing versions...');
        $pulsate->setNoCancel(true);
        $pulsate->run();

        $package = $packagist->get($name);
        /* @var $package Package */

        $choices = array();
        foreach ($package->getVersions() as $version) {
            /* @var $version Version */

            $choices[$version->getVersion()] = array(
                $version->getVersion(),
                ($version->getBin() === null) ? 'no executable bin' : '☑'
            );
        }

        $pulsate->close();

        $dialog = $builder->table($choices, array('Version', 'Binary'), 'Select available version');
        $dialog->setWidth(800);
        $dialog->setHeight(300);
        $version = $dialog->waitReturn();

        if ($version === false) {
            return;
        }

        $action = $builder->listMenu(
            array(
                'build'   => 'Build project',
                'install' => 'Install project system-wide'
            ),
            'Action (' . $name . ':' . $version .')' /*,
            'Quit' */
        )->waitReturn();

        if ($action === false) {
            return;
        }

        $pulsate = $builder->pulsate('Installing to temporary directory...')->run();
        $pharer = $packager->getPharer($name, $version);
        $pulsate->close();

        if ($action === 'install') {
            $path = $packager->getSystemBin($pharer);
            $packager->install($pharer, $path);
        } else {
            $save = $builder->fileSave('Location to write file to', $pharer->getTarget());

            $target = $save->waitReturn();

            if ($target === false) {
                return;
            }

            $pharer->setTarget($target);
            $pharer->build();
        }

        $builder->info('Successfully built ' . $pharer->getTarget() . '!')->waitReturn();
    }
}
