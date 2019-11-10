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
     * @expectedException RuntimeException
     * @expectedExceptionMessage stop1
     * @depends testNotBlockedByLegacyInstallation
     */
    public function testExecuteWithoutProjectWillAskForProjectAndRunSearch()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn(null);
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $questionHelper = $this->getMock('Symfony\Component\Console\Helper\QuestionHelper');
        $questionHelper->expects($this->once())->method('ask')->willReturn('foo');

        $helpers = new HelperSet(array(
            'question' => $questionHelper
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
     * @depends testNotBlockedByLegacyInstallation
     */
    public function testExecuteWithProjectWillRunSearchWithoutAskingForProject()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $questionHelper = $this->getMock('Symfony\Component\Console\Helper\QuestionHelper');
        $questionHelper->expects($this->never())->method('ask');

        $helpers = new HelperSet(array(
            'question' => $questionHelper
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
     * @depends testNotBlockedByLegacyInstallation
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

        $questionHelper = $this->getMock('Symfony\Component\Console\Helper\QuestionHelper');
        $questionHelper->expects($this->once())->method('ask')->willThrowException(new RuntimeException('stop1'));

        $helpers = new HelperSet(array(
            'question' => $questionHelper
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
     * @depends testNotBlockedByLegacyInstallation
     */
    public function testExecuteWithProjectAndSearchReturnsOneMatchWillAskForProject()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $questionHelper = $this->getMock('Symfony\Component\Console\Helper\QuestionHelper');
        $questionHelper->expects($this->once())->method('ask')->willThrowException(new RuntimeException('stop1'));

        $helpers = new HelperSet(array(
            'question' => $questionHelper
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
     * @depends testNotBlockedByLegacyInstallation
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

        $questionHelper = $this->getMock('Symfony\Component\Console\Helper\QuestionHelper');
        $questionHelper->expects($this->once())->method('ask')->willReturn(
            '<info>foo</info>/bar                                  (⤓)'
        );

        $helpers = new HelperSet(array(
            'question' => $questionHelper
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

    /**
     * @depends testNotBlockedByLegacyInstallation
     */
    public function testExecuteWithProjectAndVersionSelectedWillQuitWhenAskedForActionYieldsQuit()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $questionHelper = $this->getMock('Symfony\Component\Console\Helper\QuestionHelper');
        $questionHelper->expects($this->exactly(3))->method('ask')->willReturnOnConsecutiveCalls(
            '<info>foo</info>/bar                                  (⤓)',
            'dev-master (<error>no executable bin</error>)',
            'Quit'
        );

        $helpers = new HelperSet(array(
            'question' => $questionHelper
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

    /**
     * @depends testNotBlockedByLegacyInstallation
     */
    public function testExecuteWithProjectAndVersionSelectedWillBuildWhenAskedForActionYieldsBuild()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $questionHelper = $this->getMock('Symfony\Component\Console\Helper\QuestionHelper');
        $questionHelper->expects($this->exactly(3))->method('ask')->willReturnOnConsecutiveCalls(
            '<info>foo</info>/bar                                  (⤓)',
            'dev-master (<error>no executable bin</error>)',
            'Build project'
        );

        $helpers = new HelperSet(array(
            'question' => $questionHelper
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

    /**
     * @depends testNotBlockedByLegacyInstallation
     */
    public function testExecuteWithProjectAndVersionSelectedWillInstallWhenAskedForActionYieldsInstall()
    {
        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $questionHelper = $this->getMock('Symfony\Component\Console\Helper\QuestionHelper');
        $questionHelper->expects($this->exactly(3))->method('ask')->willReturnOnConsecutiveCalls(
            '<info>foo</info>/bar                                  (⤓)',
            'dev-master (<error>no executable bin</error>)',
            'Install project system-wide'
        );

        $helpers = new HelperSet(array(
            'question' => $questionHelper
        ));

        $package = $this->getMockBuilder('Clue\PharComposer\Package\Package')->disableOriginalConstructor()->getMock();
        $pharer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();
        $pharer->expects($this->once())->method('getPackageRoot')->willReturn($package);
        $pharer->expects($this->never())->method('build');

        $packager = $this->getMock('Clue\PharComposer\Phar\Packager');
        $packager->expects($this->once())->method('getPharer')->with('foo/bar', 'dev-master')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($package)->willReturn('targetPath');
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
