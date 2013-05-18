<?php

namespace Clue\PharComposer;

use Symfony\Component\Console\Application as BaseApplication;

class App extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('phar-composer', '@git_tag@');

        $this->add(new Command\Build());
    }
}
