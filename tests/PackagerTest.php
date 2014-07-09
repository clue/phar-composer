<?php

use Clue\PharComposer\Packager;
class PackagerTest extends TestCase
{
    /**
     *
     * @param string $expectedOutput
     * @param string $command
     * @dataProvider provideExecCommands
     */
    public function testExec($expectedOutput, $command)
    {
        $packager = new Packager();

        $this->expectOutputString($expectedOutput);

        $packager->exec($command);
    }

    public function provideExecCommands()
    {
        return array(
            array("\n    output\n", 'echo output'),
            array("\n    error\n", 'echo error >&2'),
            array("\n    mixed\n    errors\n", 'echo mixed && echo errors >&1'),
        );
    }
}
