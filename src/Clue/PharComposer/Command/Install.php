<?php

namespace Clue\PharComposer\Command;

use Symfony\Component\Console\Command\Command;
use Clue\PharComposer\Phar\Packager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\DialogHelper;

class Install extends Command
{
    protected function configure()
    {
        $this->setName('install')
             ->setDescription('Install phar into system wide binary directory')
             ->addArgument('name', InputArgument::OPTIONAL, 'Project name or path', '.')
             ->addArgument('path', InputArgument::OPTIONAL, 'Path to install to', '/usr/local/bin');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packager = new Packager();
        $packager->setOutput($output);
        $packager->coerceWritable();

        $pharer = $packager->getPharer($input->getArgument('name'));

        $path = $packager->getSystemBin($pharer, $input->getArgument('path'));

        if (is_file($path)) {
            $dialog = $this->getHelperSet()->get('dialog');
            /* @var $dialog DialogHelper */

            if (!$dialog->askConfirmation($output, 'Overwrite existing file <info>' . $path . '</info>? [y] > ')) {
                $output->writeln('Aborting');
                return;
            }
        }

        $packager->install($pharer, $path);
    }
}
