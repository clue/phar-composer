<?php

namespace Clue\PharComposer\Command;

use Clue\PharComposer\Phar\Packager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Install extends Command
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
        $this->setName('install')
             ->setDescription('Install phar into system wide binary directory')
             ->addArgument('project', InputArgument::OPTIONAL, 'Project name or path', '.')
             ->addArgument('target', InputArgument::OPTIONAL, 'Path to install to', '/usr/local/bin');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->packager->setOutput($output);
        $this->packager->coerceWritable();

        $pharer = $this->packager->getPharer($input->getArgument('project'));

        $path = $this->packager->getSystemBin($pharer, $input->getArgument('target'));

        if (is_file($path)) {
            $helper = $this->getHelper('question');
            assert($helper instanceof QuestionHelper);

            $question = new ConfirmationQuestion('Overwrite existing file <info>' . $path . '</info>? [y] > ', true);
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('Aborting');
                return 0;
            }
        }

        $this->packager->install($pharer, $path);
    }
}
