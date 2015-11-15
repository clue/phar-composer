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

class Search extends Command
{
    protected function configure()
    {
        $this->setName('search')
             ->setDescription('Interactive search for project name')
             ->addArgument('name', InputArgument::OPTIONAL, 'Project name or path', null);
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

        $dialog = $this->getHelperSet()->get('dialog');
        /* @var $dialog DialogHelper */

        $name = $input->getArgument('name');

        do {
            if ($name === null) {
                // ask for input
                $name = $dialog->ask($output, 'Enter (partial) project name > ');
            } else {
                $output->writeln('Searching for <info>' . $name . '</info>...');
            }

            $packagist = new Client();

            $choices = array();
            foreach ($packagist->search($name) as $package) {
                /* @var $package Result */

                $label = str_pad($package->getName(), 39) . ' ';
                $label = str_replace($name, '<info>' . $name . '</info>', $label);
                $label .= $package->getDescription();

                $label .= ' (⤓' . $package->getDownloads() . ')';

                $choices[$package->getName()] = $label;
            }

            $name = $this->select($output, 'Select matching package', $choices, 'Start new search');
        } while ($name === null);

        $output->writeln('Selected <info>' . $name . '</info>, listing versions...');

        $package = $packagist->get($name);
        /* @var $package Package */

        $choices = array();
        foreach ($package->getVersions() as $version) {
            /* @var $version Version */

            $label = $version->getVersion();

            $bin = $version->getBin();
            if ($bin === null) {
                $label .= ' (<error>no executable bin</error>)';
            } else {
                $label .= ' (☑ executable bin)';
            }

            $choices[$version->getVersion()] = $label;
        }

        $version = $this->select($output, 'Select available version', $choices);

        $action = $this->select(
            $output,
            'Action',
            array(
                'build'   => 'Build project',
                'install' => 'Install project system-wide'
            ),
            'Quit'
        );

        if ($action === null) {
            return;
        }



        $pharer = $packager->getPharer($name, $version);


        if ($action === 'install') {
            $path = $packager->getSystemBin($pharer);
            $packager->install($pharer, $path);
        } else {
            $pharer->build();
        }
    }
}
