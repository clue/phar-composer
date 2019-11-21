<?php


namespace Clue\PharComposer\Command;

use Clue\PharComposer\Phar\Packager;
use Packagist\Api\Client;
use Packagist\Api\Result\Package;
use Packagist\Api\Result\Package\Version;
use Packagist\Api\Result\Result;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class Search extends Command
{
    /** @var Packager */
    private $packager;

    /** @var Client */
    private $packagist;

    /** @var bool */
    private $isWindows;

    public function __construct(Packager $packager = null, Client $packagist = null, $isWindows = null)
    {
        if ($packager === null) {
            $packager = new Packager();
        }
        if ($packagist === null) {
            $packagist = new Client();
        }
        if ($isWindows === null) {
            $isWindows = DIRECTORY_SEPARATOR === '\\';
        }
        $this->packager = $packager;
        $this->packagist = $packagist;
        $this->isWindows = $isWindows;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('search')
             ->setDescription('Interactive search for project name')
             ->addArgument('project', InputArgument::OPTIONAL, 'Project name or path', null);
    }

    /**
     * @param InputInterface       $input
     * @param OutputInterface      $output
     * @param string               $label
     * @param array<string,string> $choices
     * @param ?string              $abortable
     * @return ?string
     */
    protected function select(InputInterface $input, OutputInterface $output, $label, array $choices, $abortable = null)
    {
        $helper = $this->getHelper('question');
        assert($helper instanceof QuestionHelper);

        if (!$choices) {
            $output->writeln('<error>No matching packages found</error>');
            return null;
        }

        // use numeric keys for all options
        $select = array_merge(array(0 => $abortable), array_values($choices));
        if ($abortable === null) {
            unset($select[0]);
        }

        $question = new ChoiceQuestion($label, $select);
        $index = array_search($helper->ask($input, $output, $question), $select);

        if ($index === 0) {
            return null;
        }

        $indices = array_keys($choices);
        return $indices[$index - 1];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->packager->setOutput($output);
        $this->packager->coerceWritable();

        $helper = $this->getHelper('question');
        assert($helper instanceof QuestionHelper);

        $project = $input->getArgument('project');

        do {
            if ($project === null) {
                // ask for input
                $question = new Question('Enter (partial) project name > ', '');
                $project = $helper->ask($input, $output, $question);
            } else {
                $output->writeln('Searching for <info>' . $project . '</info>...');
            }

            $choices = array();
            foreach ($this->packagist->search($project) as $result) {
                assert($result instanceof Result);

                $label = str_pad($result->getName(), 39) . ' ';
                $label = str_replace($project, '<info>' . $project . '</info>', $label);
                $label .= $result->getDescription();

                $label .= ' (⤓' . $result->getDownloads() . ')';

                $choices[$result->getName()] = $label;
            }

            $project = $this->select($input, $output, 'Select matching package', $choices, 'Start new search');
        } while ($project === null);

        $output->writeln('Selected <info>' . $project . '</info>, listing versions...');

        $package = $this->packagist->get($project);
        assert($package instanceof Package);

        $choices = array();
        foreach ($package->getVersions() as $version) {
            assert($version instanceof Version);

            $label = $version->getVersion();

            /* @var ?string $bin */
            $bin = $version->getBin();
            $label .= $bin !== null ? ' (☑ executable bin)' : ' (<error>no executable bin</error>)';

            $choices[$version->getVersion()] = $label;
        }

        $version = $this->select($input, $output, 'Select available version', $choices);

        $action = $this->select(
            $input,
            $output,
            'Action',
            array_filter(array(
                'build'   => 'Build project',
                'install' => $this->isWindows ? null : 'Install project system-wide'
            )),
            'Quit'
        );

        if ($action === null) {
            return 0;
        }

        $pharer = $this->packager->getPharer($project, $version);

        if ($action === 'install') {
            $path = $this->packager->getSystemBin($pharer->getPackageRoot());
            $this->packager->install($pharer, $path);
        } else {
            $pharer->build();
        }

        return 0;
    }
}
