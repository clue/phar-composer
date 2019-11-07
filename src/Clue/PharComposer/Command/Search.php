<?php


namespace Clue\PharComposer\Command;

use Clue\PharComposer\Phar\Packager;
use Packagist\Api\Client;
use Packagist\Api\Result\Package;
use Packagist\Api\Result\Package\Version;
use Packagist\Api\Result\Result;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Search extends Command
{
    /** @var Packager */
    private $packager;

    /** @var Client */
    private $packagist;

    public function __construct(Packager $packager = null, Client $packagist = null)
    {
        parent::__construct();

        if ($packager === null) {
            $packager = new Packager();
        }
        if ($packagist === null) {
            $packagist = new Client();
        }
        $this->packager = $packager;
        $this->packagist = $packagist;
    }

    protected function configure()
    {
        $this->setName('search')
             ->setDescription('Interactive search for project name')
             ->addArgument('project', InputArgument::OPTIONAL, 'Project name or path', null);
    }

    /**
     * @param OutputInterface      $output
     * @param string               $label
     * @param array<string,string> $choices
     * @param ?string              $abortable
     * @return ?string
     */
    protected function select(OutputInterface $output, $label, array $choices, $abortable = null)
    {
        $dialog = $this->getHelper('dialog');
        assert($dialog instanceof DialogHelper);

        if (!$choices) {
            $output->writeln('<error>No matching packages found</error>');
            return null;
        }

        // TODO: skip dialog, if exact match

        $select = array_merge(array(0 => $abortable), array_values($choices));
        if ($abortable === null) {
            unset($select[0]);
        }

        $index = $dialog->select($output, $label, $select);

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

        $dialog = $this->getHelper('dialog');
        assert($dialog instanceof DialogHelper);

        $project = $input->getArgument('project');

        do {
            if ($project === null) {
                // ask for input
                $project = $dialog->ask($output, 'Enter (partial) project name > ');
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

            $project = $this->select($output, 'Select matching package', $choices, 'Start new search');
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
            return 0;
        }

        $pharer = $this->packager->getPharer($project, $version);

        if ($action === 'install') {
            $path = $this->packager->getSystemBin($pharer);
            $this->packager->install($pharer, $path);
        } else {
            $pharer->build();
        }
    }
}
