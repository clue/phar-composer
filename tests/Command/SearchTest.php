<?php

use Clue\PharComposer\Command\Search;
use Symfony\Component\Console\Helper\HelperSet;

class SearchTest extends TestCase
{
    public function testCtorCreatesPackagerAndPackagist()
    {
        $command = new Search();

        $ref = new ReflectionProperty($command, 'packager');
        $ref->setAccessible(true);
        $packager = $ref->getValue($command);

        $ref = new ReflectionProperty($command, 'packagist');
        $ref->setAccessible(true);
        $packagist = $ref->getValue($command);

        $this->assertInstanceOf('Clue\PharComposer\Phar\Packager', $packager);
        $this->assertInstanceOf('Packagist\Api\Client', $packagist);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage stop1
     */
    public function testExecuteWithoutProjectWillAskForProjectAndRunSearch()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn(null);
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $dialogHelper = $this->getMock('Symfony\Component\Console\Helper\DialogHelper');
        $dialogHelper->expects($this->once())->method('ask')->willReturn('foo');

        $helpers = new HelperSet(array(
            'dialog' => $dialogHelper
        ));

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');

        $packagist = $this->getMock('Packagist\Api\Client');
        $packagist->expects($this->once())->method('search')->with('foo')->willThrowException(new RuntimeException('stop1'));

        $command = new Search($packager, $packagist);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage stop1
     */
    public function testExecuteWithProjectWillRunSearchWithoutAskingForProject()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $dialogHelper = $this->getMock('Symfony\Component\Console\Helper\DialogHelper');
        $dialogHelper->expects($this->never())->method('ask');

        $helpers = new HelperSet(array(
            'dialog' => $dialogHelper
        ));

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');

        $packagist = $this->getMock('Packagist\Api\Client');
        $packagist->expects($this->once())->method('search')->with('foo')->willThrowException(new RuntimeException('stop1'));

        $command = new Search($packager, $packagist);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage stop1
     */
    public function testExecuteWithProjectAndSearchReturnsNoMatchesWillReportAndAskForOtherProject()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $output->expects($this->exactly(2))->method('writeln')->withConsecutive(
            array('Searching for <info>foo</info>...'),
            array('<error>No matching packages found</error>')
        );

        $dialogHelper = $this->getMock('Symfony\Component\Console\Helper\DialogHelper');
        $dialogHelper->expects($this->once())->method('ask')->willThrowException(new RuntimeException('stop1'));

        $helpers = new HelperSet(array(
            'dialog' => $dialogHelper
        ));

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');

        $packagist = $this->getMock('Packagist\Api\Client');
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn(array());

        $command = new Search($packager, $packagist);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage stop1
     */
    public function testExecuteWithProjectAndSearchReturnsOneMatchWillAskForProject()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $dialogHelper = $this->getMock('Symfony\Component\Console\Helper\DialogHelper');
        $dialogHelper->expects($this->once())->method('select')->willThrowException(new RuntimeException('stop1'));
        $dialogHelper->expects($this->never())->method('ask');

        $helpers = new HelperSet(array(
            'dialog' => $dialogHelper
        ));

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');

        $result = $this->getMock('Packagist\Api\Result\Result');

        $packagist = $this->getMock('Packagist\Api\Client');
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn(array($result));

        $command = new Search($packager, $packagist);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage stop1
     */
    public function testExecuteWithProjectSelectedWillSearchVersions()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $output->expects($this->exactly(2))->method('writeln')->withConsecutive(
            array('Searching for <info>foo</info>...'),
            array('Selected <info>foo/bar</info>, listing versions...')
        );

        $dialogHelper = $this->getMock('Symfony\Component\Console\Helper\DialogHelper');
        $dialogHelper->expects($this->once())->method('select')->willReturn(1);
        $dialogHelper->expects($this->never())->method('ask');

        $helpers = new HelperSet(array(
            'dialog' => $dialogHelper
        ));

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');

        $result = $this->getMock('Packagist\Api\Result\Result');
        $result->expects($this->exactly(2))->method('getName')->willReturn('foo/bar');

        $packagist = $this->getMock('Packagist\Api\Client');
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn(array($result));
        $packagist->expects($this->once())->method('get')->with('foo/bar')->willThrowException(new RuntimeException('stop1'));

        $command = new Search($packager, $packagist);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    public function testExecuteWithProjectAndVersionSelectedWillQuitWhenAskedForActionYieldsQuit()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');


        $dialogHelper = $this->getMock('Symfony\Component\Console\Helper\DialogHelper');
        $dialogHelper->expects($this->exactly(3))->method('select')->willReturnOnConsecutiveCalls(1, 1, 0);

        $helpers = new HelperSet(array(
            'dialog' => $dialogHelper
        ));

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');

        $result = $this->getMock('Packagist\Api\Result\Result');
        $result->expects($this->exactly(2))->method('getName')->willReturn('foo/bar');

        $version = $this->getMock('Packagist\Api\Result\Package\Version');
        $version->expects($this->exactly(2))->method('getVersion')->willReturn('dev-master');

        $package = $this->getMock('Packagist\Api\Result\Package');
        $package->expects($this->once())->method('getVersions')->willReturn(array($version));

        $packagist = $this->getMock('Packagist\Api\Client');
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn(array($result));
        $packagist->expects($this->once())->method('get')->with('foo/bar')->willReturn($package);

        $command = new Search($packager, $packagist);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    public function testExecuteWithProjectAndVersionSelectedWillBuildWhenAskedForActionYieldsBuild()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $dialogHelper = $this->getMock('Symfony\Component\Console\Helper\DialogHelper');
        $dialogHelper->expects($this->exactly(3))->method('select')->willReturnOnConsecutiveCalls(1, 1, 1);

        $helpers = new HelperSet(array(
            'dialog' => $dialogHelper
        ));

        $pharer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();
        $pharer->expects($this->once())->method('build');

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');
        $packager->expects($this->once())->method('getPharer')->with('foo/bar', 'dev-master')->willReturn($pharer);

        $result = $this->getMock('Packagist\Api\Result\Result');
        $result->expects($this->exactly(2))->method('getName')->willReturn('foo/bar');

        $version = $this->getMock('Packagist\Api\Result\Package\Version');
        $version->expects($this->exactly(2))->method('getVersion')->willReturn('dev-master');

        $package = $this->getMock('Packagist\Api\Result\Package');
        $package->expects($this->once())->method('getVersions')->willReturn(array($version));

        $packagist = $this->getMock('Packagist\Api\Client');
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn(array($result));
        $packagist->expects($this->once())->method('get')->with('foo/bar')->willReturn($package);

        $command = new Search($packager, $packagist);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    public function testExecuteWithProjectAndVersionSelectedWillInstallWhenAskedForActionYieldsInstall()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $dialogHelper = $this->getMock('Symfony\Component\Console\Helper\DialogHelper');
        $dialogHelper->expects($this->exactly(3))->method('select')->willReturnOnConsecutiveCalls(1, 1, 2);

        $helpers = new HelperSet(array(
            'dialog' => $dialogHelper
        ));

        $pharer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();
        $pharer->expects($this->never())->method('build');

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');
        $packager->expects($this->once())->method('getPharer')->with('foo/bar', 'dev-master')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($pharer)->willReturn('targetPath');
        $packager->expects($this->once())->method('install')->with($pharer, 'targetPath');

        $result = $this->getMock('Packagist\Api\Result\Result');
        $result->expects($this->exactly(2))->method('getName')->willReturn('foo/bar');

        $version = $this->getMock('Packagist\Api\Result\Package\Version');
        $version->expects($this->exactly(2))->method('getVersion')->willReturn('dev-master');

        $package = $this->getMock('Packagist\Api\Result\Package');
        $package->expects($this->once())->method('getVersions')->willReturn(array($version));

        $packagist = $this->getMock('Packagist\Api\Client');
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn(array($result));
        $packagist->expects($this->once())->method('get')->with('foo/bar')->willReturn($package);

        $command = new Search($packager, $packagist);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }
}
