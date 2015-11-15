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
use Clue\PharComposer\Phar\Packager;

class Build extends Command
{
    protected function configure()
    {
        $this->setName('build')
             ->setDescription('Build phar for the given composer project')
             ->addArgument('path', InputArgument::OPTIONAL, 'Path to project directory or composer.json', '.')
             ->addArgument('target', InputArgument::OPTIONAL, 'Path to write phar output to (defaults to project name)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packager = new Packager();
        $packager->setOutput(function ($line) use ($output) {
            $output->write($line);
        });

        $packager->coerceWritable();

        $pharer = $packager->getPharer($input->getArgument('path'));

        $target = $input->getArgument('target');
        if ($target !== null) {
            $pharer->setTarget($target);
        }

        $pharer->build();
    }
}