<?php

namespace Clue\PharComposer;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;

class App extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('phar-composer', '@git_tag@');

        $this->add(new Command\Build());
        $this->add(new Command\Search());
        $this->add(new Command\Install());

        $this->setDefaultCommand('search');
    }
}
