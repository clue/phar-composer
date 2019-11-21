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

    /** @var bool */
    private $isWindows;

    public function __construct(Packager $packager = null, $isWindows = null)
    {
        if ($packager === null) {
            $packager = new Packager();
        }
        if ($isWindows === null) {
            $isWindows = DIRECTORY_SEPARATOR === '\\';
        }
        $this->packager = $packager;
        $this->isWindows = $isWindows;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('install')
             ->setDescription('Install phar into system wide binary directory' . ($this->isWindows ? ' (not available on Windows)' : ''))
             ->addArgument('project', InputArgument::OPTIONAL, 'Project name or path', '.')
             ->addArgument('target', InputArgument::OPTIONAL, 'Path to install to', '/usr/local/bin');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->isWindows) {
            $output->writeln('<error>Command not available on this platform. Please use the "build" command and place Phar in your $PATH manually.</error>');
            return 1;
        }

        $this->packager->setOutput($output);
        $this->packager->coerceWritable();

        $pharer = $this->packager->getPharer($input->getArgument('project'));

        $path = $this->packager->getSystemBin($pharer->getPackageRoot(), $input->getArgument('target'));

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

        return 0;
    }
}
