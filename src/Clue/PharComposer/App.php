<?php

namespace Clue\PharComposer;

use Symfony\Component\Console\Application as BaseApplication;

class App extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('phar-composer', '@dev');

        $this->add(new Command\Build());
        $this->add(new Command\Search());
        $this->add(new Command\Install());

        $this->setDefaultCommand('search');
    }
}
