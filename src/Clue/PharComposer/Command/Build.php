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

class Build extends Command
{
    protected function configure()
    {
        $this->setName('build')
             ->setDescription('Build phar for the given composer project')
             ->addArgument('path', InputArgument::OPTIONAL, 'Path to project directory or composer.json', '.')
             ->addArgument('target', InputArgument::OPTIONAL, 'Path to write phar output to (defaults to project name)')
           /*->addOption('dev', null, InputOption::VALUE_NONE, 'If set, Whether require-dev dependencies should be shown') */;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        if (is_dir($path)) {
            $path = rtrim($path, '/') . '/composer.json';
        }
        if (!is_file($path)) {
            throw new InvalidArgumentException('The given path "' . $path . '" is not a readable file');
        }


        $output->getFormatter()->setStyle('warning', new OutputFormatterStyle('black', 'yellow'));

        $pharcomposer = new PharComposer($path);

        $pathVendor = $pharcomposer->getPathVendor();
        if (!is_dir($pathVendor)) {
//             if ($input->isInteractive()) {
//                 /** @var $dialog DialogHelper */
//                 $dialog = $this->getHelperSet()->get('dialog');

//                 $output->writeln('<warning>Vendor directory does not exist, looks like project was not properly installed via "composer install"</warning>');

//                 if ($dialog->askConfirmation($output, '<question>Install project via composer (execute "composer install")?</question>', true)) {
//                     $output->writeln('<info>Let\'s try to install..</info>');
//                 } else {
//                     $output->writeln('<info>Aborting...</info>');
//                     return;
//                 }
//             } else {
                $output->writeln('<error>Project is not installed via composer. Run "composer install" manually</error>');
                return;
//             }
        }

//         $timeinstalled = @filemtime($pathVendor . '/autoload.php');

//         if (filemtime($this->pathProject . '/composer.json') >= $timeinstalled) {
//             throw new RuntimeException('Looks like your "composer.json" was modified after the project was installed, try running "composer update"?');
//         }

        $target = $input->getArgument('target');
        if ($target !== null) {
            $pharcomposer->setTarget($target);
        }

        $pharcomposer->build();
    }
}