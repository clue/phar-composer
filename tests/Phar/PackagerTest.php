<?php

use Clue\PharComposer\Phar\Packager;

class PackagerTest extends TestCase
{
    private $packager;

    public function setUp()
    {
        $this->packager = new Packager();
    }

    /**
     *
     * @param string $expectedOutput
     * @param string $command
     * @dataProvider provideExecCommands
     */
    public function testExec($expectedOutput, $command)
    {
        $this->expectOutputString($expectedOutput);

        $this->packager->exec($command);
    }

    public function provideExecCommands()
    {
        return array(
            array("\n    output\n", 'echo output'),
            array("\n    error\n", 'echo error >&2'),
            array("\n    mixed\n    errors\n", 'echo mixed && echo errors >&1'),
        );
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage not installed
     */
    public function testEmptyNotInstalled()
    {
        $this->packager->getPharer(__DIR__ . '/../fixtures/01-empty');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage not a readable file
     */
    public function testNoComposer()
    {
        $this->packager->getPharer(__DIR__ . '/../fixtures/02-no-composer');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage not a readable file
     */
    public function testNoComposerMissing()
    {
        $this->packager->getPharer(__DIR__ . '/../fixtures/02-no-composer/composer.json');
    }
}
