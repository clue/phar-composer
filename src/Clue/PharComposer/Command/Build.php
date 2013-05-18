<?php

namespace Clue\PharComposer\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Clue\PharComposer\PharComposer;

class Build extends Command
{
    protected function configure()
    {
        $this->setName('build')
             ->setDescription('Build phar for the given composer project')
             ->addArgument('path', InputArgument::OPTIONAL, 'Path to project directory or composer.json', '.')
           /*->addOption('dev', null, InputOption::VALUE_NONE, 'If set, Whether require-dev dependencies should be shown') */;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');

        $pharcomposer = new PharComposer($path);
        $pharcomposer->build();
    }
}