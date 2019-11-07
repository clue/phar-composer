<?php

namespace Clue\PharComposer;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;

class App extends BaseApplication
{
    private $isDefault = false;

    public function __construct()
    {
        parent::__construct('phar-composer', '@git_tag@');

        $this->add(new Command\Build());
        $this->add(new Command\Search());
        $this->add(new Command\Install());
    }

    /**
     * Gets the name of the command based on input.
     *
     * @param InputInterface $input The input interface
     *
     * @return string The command name
     */
    protected function getCommandName(InputInterface $input)
    {
        if ($input->getFirstArgument() === null && !$input->hasParameterOption(array('--help', '-h'))) {
            $this->isDefault = true;
            return 'search';
        }
        return parent::getCommandName($input);
    }

    /**
     * Overridden so that the application doesn't expect the command
     * name to be the first argument.
     */
    public function getDefinition()
    {
        $inputDefinition = parent::getDefinition();

        if ($this->isDefault) {
            // clear out the normal first argument, which is the command name
            $inputDefinition->setArguments();
        }

        return $inputDefinition;
    }
}
