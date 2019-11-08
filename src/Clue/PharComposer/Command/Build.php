<?php

namespace Clue\PharComposer\Command;

use Clue\PharComposer\Phar\Packager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Build extends Command
{
    /** @var Packager */
    private $packager;

    public function __construct(Packager $packager = null)
    {
        parent::__construct();

        if ($packager === null) {
            $packager = new Packager();
        }
        $this->packager = $packager;
    }

    protected function configure()
    {
        $this->setName('build')
             ->setDescription('Build phar for the given composer project')
             ->addArgument('project', InputArgument::OPTIONAL, 'Path to project directory or composer.json', '.')
             ->addArgument('target', InputArgument::OPTIONAL, 'Path to write phar output to (defaults to project name)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->packager->setOutput($output);
        $this->packager->coerceWritable();

        $pharer = $this->packager->getPharer($input->getArgument('project'));

        $target = $input->getArgument('target');
        if ($target !== null) {
            $pharer->setTarget($target);
        }

        $pharer->build();

        return 0;
    }
}
