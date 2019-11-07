<?php

use Clue\PharComposer\Command\Install;
use Symfony\Component\Console\Helper\HelperSet;

class InstallTest extends TestCase
{
    public function testCtorCreatesPackager()
    {
        $command = new Install();

        $ref = new ReflectionProperty($command, 'packager');
        $ref->setAccessible(true);
        $packager = $ref->getValue($command);

        $this->assertInstanceOf('Clue\PharComposer\Phar\Packager', $packager);
    }

    public function testExecuteInstallWillInstallPackager()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->exactly(2))->method('getArgument')->withConsecutive(
            array('project'),
            array('target')
        )->willReturnOnConsecutiveCalls('dir', null);
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $pharer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($pharer, null)->willReturn('targetPath');
        $packager->expects($this->once())->method('install')->with($pharer, 'targetPath');

        $command = new Install($packager);
        $command->run($input, $output);
    }

    public function testExecuteInstallWillInstallPackagerWithExplicitTarget()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->exactly(2))->method('getArgument')->withConsecutive(
            array('project'),
            array('target')
        )->willReturnOnConsecutiveCalls('dir', 'targetDir');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $pharer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($pharer, 'targetDir')->willReturn('targetPath');
        $packager->expects($this->once())->method('install')->with($pharer, 'targetPath');

        $command = new Install($packager);
        $command->run($input, $output);
    }

    public function testExecuteInstallWillInstallPackagerWhenTargetPathAlreadyExistsAndDialogQuestionYieldsYes()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->exactly(2))->method('getArgument')->withConsecutive(
            array('project'),
            array('target')
        )->willReturnOnConsecutiveCalls('dir', null);
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $dialogHelper = $this->getMock('Symfony\Component\Console\Helper\DialogHelper');
        $dialogHelper->expects($this->once())->method('askConfirmation')->willReturn(true);

        $helpers = new HelperSet(array(
            'dialog' => $dialogHelper
        ));

        $pharer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($pharer, null)->willReturn(__FILE__);
        $packager->expects($this->once())->method('install')->with($pharer, __FILE__);

        $command = new Install($packager);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    public function testExecuteInstallWillNotInstallPackagerWhenTargetPathAlreadyExistsAndDialogQuestionShouldNotOverwrite()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->exactly(2))->method('getArgument')->withConsecutive(
            array('project'),
            array('target')
        )->willReturnOnConsecutiveCalls('dir', null);
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $output->expects($this->once())->method('writeln')->with('Aborting');

        $dialogHelper = $this->getMock('Symfony\Component\Console\Helper\DialogHelper');
        $dialogHelper->expects($this->once())->method('askConfirmation')->willReturn(false);

        $helpers = new HelperSet(array(
            'dialog' => $dialogHelper
        ));

        $pharer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($pharer, null)->willReturn(__FILE__);
        $packager->expects($this->never())->method('install');

        $command = new Install($packager);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }
}
