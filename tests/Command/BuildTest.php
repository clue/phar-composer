<?php

use Clue\PharComposer\Command\Build;

class BuildTest extends TestCase
{
    public function testCtorCreatesPackager()
    {
        $command = new Build();

        $ref = new ReflectionProperty($command, 'packager');
        $ref->setAccessible(true);
        $packager = $ref->getValue($command);

        $this->assertInstanceOf('Clue\PharComposer\Phar\Packager', $packager);
    }

    public function testExecuteBuildWillBuildPharer()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->exactly(2))->method('getArgument')->withConsecutive(
            array('project'),
            array('target')
        )->willReturnOnConsecutiveCalls('dir', null);
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $pharer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();
        $pharer->expects($this->never())->method('setTarget');
        $pharer->expects($this->once())->method('build');

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);

        $command = new Build($packager);
        $command->run($input, $output);
    }

    public function testExecuteBuildWillBuildPharerWithExplicitTarget()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->exactly(2))->method('getArgument')->withConsecutive(
            array('project'),
            array('target')
        )->willReturnOnConsecutiveCalls('dir', 'targetDir');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $pharer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();
        $pharer->expects($this->once())->method('setTarget')->with('targetDir');
        $pharer->expects($this->once())->method('build');

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);

        $command = new Build($packager);
        $command->run($input, $output);
    }
}
