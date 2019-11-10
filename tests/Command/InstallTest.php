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

        $package = $this->getMockBuilder('Clue\PharComposer\Package\Package')->disableOriginalConstructor()->getMock();
        $pharer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();
        $pharer->expects($this->once())->method('getPackageRoot')->willReturn($package);

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($package, null)->willReturn('targetPath');
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

        $package = $this->getMockBuilder('Clue\PharComposer\Package\Package')->disableOriginalConstructor()->getMock();
        $pharer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();
        $pharer->expects($this->once())->method('getPackageRoot')->willReturn($package);

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($package, 'targetDir')->willReturn('targetPath');
        $packager->expects($this->once())->method('install')->with($pharer, 'targetPath');

        $command = new Install($packager);
        $command->run($input, $output);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testNotBlockedByLegacyInstallation()
    {
        // Symfony 5+ added parameter type declarations, so we can use this to check which version is installed
        $ref = new ReflectionMethod('Symfony\Component\Console\Command\Command', 'setName');
        $params = $ref->getParameters();
        if (PHP_VERSION_ID >= 70000 && isset($params[0]) && $params[0]->hasType()) {
            $this->markTestSkipped('Unable to run this test (mocked QuestionHelper) with legacy PHPUnit against Symfony v5+');
        }
    }

    /**
     * @depends testNotBlockedByLegacyInstallation
     */
    public function testExecuteInstallWillInstallPackagerWhenTargetPathAlreadyExistsAndDialogQuestionYieldsYes()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->exactly(2))->method('getArgument')->withConsecutive(
            array('project'),
            array('target')
        )->willReturnOnConsecutiveCalls('dir', null);
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $questionHelper = $this->getMock('Symfony\Component\Console\Helper\QuestionHelper');
        $questionHelper->expects($this->once())->method('ask')->willReturn(true);

        $helpers = new HelperSet(array(
            'question' => $questionHelper
        ));

        $package = $this->getMockBuilder('Clue\PharComposer\Package\Package')->disableOriginalConstructor()->getMock();
        $pharer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();
        $pharer->expects($this->once())->method('getPackageRoot')->willReturn($package);

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($package, null)->willReturn(__FILE__);
        $packager->expects($this->once())->method('install')->with($pharer, __FILE__);

        $command = new Install($packager);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    /**
     * @depends testNotBlockedByLegacyInstallation
     */
    public function testExecuteInstallWillNotInstallPackagerWhenTargetPathAlreadyExistsAndDialogQuestionShouldNotOverwrite()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->exactly(2))->method('getArgument')->withConsecutive(
            array('project'),
            array('target')
        )->willReturnOnConsecutiveCalls('dir', null);
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $output->expects($this->once())->method('writeln')->with('Aborting');

        $questionHelper = $this->getMock('Symfony\Component\Console\Helper\QuestionHelper');
        $questionHelper->expects($this->once())->method('ask')->willReturn(false);

        $helpers = new HelperSet(array(
            'question' => $questionHelper
        ));

        $package = $this->getMockBuilder('Clue\PharComposer\Package\Package')->disableOriginalConstructor()->getMock();
        $pharer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();
        $pharer->expects($this->once())->method('getPackageRoot')->willReturn($package);

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');
        $packager->expects($this->once())->method('setOutput')->with($output);
        $packager->expects($this->once())->method('getPharer')->with('dir')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($package, null)->willReturn(__FILE__);
        $packager->expects($this->never())->method('install');

        $command = new Install($packager);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }
}
