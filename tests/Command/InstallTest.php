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
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->getMock();
        $input->expects($this->exactly(2))->method('getArgument')->withConsecutive(
            array('project'),
            array('target')
        )->willReturnOnConsecutiveCalls('dir', null);
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->getMock();

        $package = $this->getMockBuilder('Clue\PharComposer\Package\Package')->disableOriginalConstructor()->getMock();
        $pharer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();
        $pharer->expects($this->once())->method('getPackageRoot')->willReturn($package);

        $packager = $this->getMockBuilder('Clue\PharComposer\Phar\Packager')->getMock();
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($package, null)->willReturn('targetPath');
        $packager->expects($this->once())->method('install')->with($pharer, 'targetPath');

        $command = new Install($packager, false);
        $command->run($input, $output);
    }

    public function testExecuteInstallWillInstallPackagerWithExplicitTarget()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->getMock();
        $input->expects($this->exactly(2))->method('getArgument')->withConsecutive(
            array('project'),
            array('target')
        )->willReturnOnConsecutiveCalls('dir', 'targetDir');
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->getMock();

        $package = $this->getMockBuilder('Clue\PharComposer\Package\Package')->disableOriginalConstructor()->getMock();
        $pharer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();
        $pharer->expects($this->once())->method('getPackageRoot')->willReturn($package);

        $packager = $this->getMockBuilder('Clue\PharComposer\Phar\Packager')->getMock();
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($package, 'targetDir')->willReturn('targetPath');
        $packager->expects($this->once())->method('install')->with($pharer, 'targetPath');

        $command = new Install($packager, false);
        $command->run($input, $output);
    }

    public function testExecuteInstallWillInstallPackagerWhenTargetPathAlreadyExistsAndDialogQuestionYieldsYes()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->getMock();
        $input->expects($this->exactly(2))->method('getArgument')->withConsecutive(
            array('project'),
            array('target')
        )->willReturnOnConsecutiveCalls('dir', null);
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->getMock();

        $questionHelper = $this->getMockBuilder('Symfony\Component\Console\Helper\QuestionHelper')->getMock();
        $questionHelper->expects($this->once())->method('ask')->willReturn(true);

        $helpers = new HelperSet(array(
            'question' => $questionHelper
        ));

        $package = $this->getMockBuilder('Clue\PharComposer\Package\Package')->disableOriginalConstructor()->getMock();
        $pharer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();
        $pharer->expects($this->once())->method('getPackageRoot')->willReturn($package);

        $packager = $this->getMockBuilder('Clue\PharComposer\Phar\Packager')->getMock();
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($package, null)->willReturn(__FILE__);
        $packager->expects($this->once())->method('install')->with($pharer, __FILE__);

        $command = new Install($packager, false);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    public function testExecuteInstallWillNotInstallPackagerWhenTargetPathAlreadyExistsAndDialogQuestionShouldNotOverwrite()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->getMock();
        $input->expects($this->exactly(2))->method('getArgument')->withConsecutive(
            array('project'),
            array('target')
        )->willReturnOnConsecutiveCalls('dir', null);
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->getMock();
        $output->expects($this->once())->method('writeln')->with('Aborting');

        $questionHelper = $this->getMockBuilder('Symfony\Component\Console\Helper\QuestionHelper')->getMock();
        $questionHelper->expects($this->once())->method('ask')->willReturn(false);

        $helpers = new HelperSet(array(
            'question' => $questionHelper
        ));

        $package = $this->getMockBuilder('Clue\PharComposer\Package\Package')->disableOriginalConstructor()->getMock();
        $pharer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();
        $pharer->expects($this->once())->method('getPackageRoot')->willReturn($package);

        $packager = $this->getMockBuilder('Clue\PharComposer\Phar\Packager')->getMock();
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($package, null)->willReturn(__FILE__);
        $packager->expects($this->never())->method('install');

        $command = new Install($packager, false);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    public function testExecuteInstallWillReportErrorOnWindows()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->getMock();
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->getMock();
        $output->expects($this->once())->method('writeln')->with($this->stringContains('platform'));

        $command = new Install(null, true);

        $this->assertStringEndsWith(' (not available on Windows)', $command->getDescription());

        $ret = $command->run($input, $output);

        $this->assertEquals(1, $ret);
    }
}
