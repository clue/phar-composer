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

        $collected = '';

        $mock = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $mock->expects($this->any())
             ->method('write')
             ->will($this->returnCallback(function ($chunk) use (&$collected) {
                 $collected .= $chunk;
             }));

        $packager->setOutput($mock);

        $packager->exec($command);

        $this->assertEquals($expectedOutput, $collected);
    }

    public function provideExecCommands()
    {
        return array(
            array("\n    output\n", 'echo output'),
        );
    }
}
