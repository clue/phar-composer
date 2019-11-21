<?php

use Clue\PharComposer\App;

class AppTest extends TestCase
{
    public function testAppReportsDevVersion()
    {
        $app = new App();

        $this->assertEquals('@dev', $app->getVersion());
    }

    public function testAppHasExpectedCommands()
    {
        $app = new App();

        $this->assertTrue($app->has('build'));
        $this->assertTrue($app->has('install'));
        $this->assertTrue($app->has('search'));
    }
}
