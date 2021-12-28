<?php

use Clue\PharComposer\Command\Search;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Question\ChoiceQuestion;

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

    public function testExecuteWithoutProjectWillAskForProjectAndRunSearch()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->getMock();
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn(null);
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->getMock();

        $questionHelper = $this->getMockBuilder('Symfony\Component\Console\Helper\QuestionHelper')->getMock();
        $questionHelper->expects($this->once())->method('ask')->willReturn('foo');

        $helpers = new HelperSet(array(
            'question' => $questionHelper
        ));

        $packager = $this->getMockBuilder('Clue\PharComposer\Phar\Packager')->getMock();

        $packagist = $this->getMockBuilder('Packagist\Api\Client')->getMock();
        $packagist->expects($this->once())->method('search')->with('foo')->willThrowException(new RuntimeException('stop1'));

        $command = new Search($packager, $packagist, false);
        $command->setHelperSet($helpers);

        $this->setExpectedException('RuntimeException', 'stop1');
        $command->run($input, $output);
    }

    public function testExecuteWithProjectWillRunSearchWithoutAskingForProject()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->getMock();
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->getMock();

        $questionHelper = $this->getMockBuilder('Symfony\Component\Console\Helper\QuestionHelper')->getMock();
        $questionHelper->expects($this->never())->method('ask');

        $helpers = new HelperSet(array(
            'question' => $questionHelper
        ));

        $packager = $this->getMockBuilder('Clue\PharComposer\Phar\Packager')->getMock();

        $packagist = $this->getMockBuilder('Packagist\Api\Client')->getMock();
        $packagist->expects($this->once())->method('search')->with('foo')->willThrowException(new RuntimeException('stop1'));

        $command = new Search($packager, $packagist, false);
        $command->setHelperSet($helpers);

        $this->setExpectedException('RuntimeException', 'stop1');
        $command->run($input, $output);
    }

    public function testExecuteWithProjectAndSearchReturnsNoMatchesWillReportAndAskForOtherProject()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->getMock();
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->getMock();
        $output->expects($this->exactly(2))->method('writeln')->withConsecutive(
            array('Searching for <info>foo</info>...'),
            array('<error>No matching packages found</error>')
        );

        $questionHelper = $this->getMockBuilder('Symfony\Component\Console\Helper\QuestionHelper')->getMock();
        $questionHelper->expects($this->once())->method('ask')->willThrowException(new RuntimeException('stop1'));

        $helpers = new HelperSet(array(
            'question' => $questionHelper
        ));

        $packager = $this->getMockBuilder('Clue\PharComposer\Phar\Packager')->getMock();

        $packagist = $this->getMockBuilder('Packagist\Api\Client')->getMock();
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn(array());

        $command = new Search($packager, $packagist, false);
        $command->setHelperSet($helpers);

        $this->setExpectedException('RuntimeException', 'stop1');
        $command->run($input, $output);
    }

    public function testExecuteWithProjectAndSearchReturnsOneMatchWillAskForProject()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->getMock();
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->getMock();

        $questionHelper = $this->getMockBuilder('Symfony\Component\Console\Helper\QuestionHelper')->getMock();
        $questionHelper->expects($this->once())->method('ask')->willThrowException(new RuntimeException('stop1'));

        $helpers = new HelperSet(array(
            'question' => $questionHelper
        ));

        $packager = $this->getMockBuilder('Clue\PharComposer\Phar\Packager')->getMock();

        $result = $this->getMockBuilder('Packagist\Api\Result\Result')->getMock();
        $result->expects($this->exactly(2))->method('getName')->willReturn('foo/bar');

        $packagist = $this->getMockBuilder('Packagist\Api\Client')->getMock();
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn(array($result));

        $command = new Search($packager, $packagist, false);
        $command->setHelperSet($helpers);

        $this->setExpectedException('RuntimeException', 'stop1');
        $command->run($input, $output);
    }

    public function testExecuteWithProjectSelectedWillSearchVersions()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->getMock();
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->getMock();
        $output->expects($this->exactly(2))->method('writeln')->withConsecutive(
            array('Searching for <info>foo</info>...'),
            array('Selected <info>foo/bar</info>, listing versions...')
        );

        $questionHelper = $this->getMockBuilder('Symfony\Component\Console\Helper\QuestionHelper')->getMock();
        $questionHelper->expects($this->once())->method('ask')->willReturn(
            '<info>foo</info>/bar                                  (⤓)'
        );

        $helpers = new HelperSet(array(
            'question' => $questionHelper
        ));

        $packager = $this->getMockBuilder('Clue\PharComposer\Phar\Packager')->getMock();

        $result = $this->getMockBuilder('Packagist\Api\Result\Result')->getMock();
        $result->expects($this->exactly(2))->method('getName')->willReturn('foo/bar');

        $packagist = $this->getMockBuilder('Packagist\Api\Client')->getMock();
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn(array($result));
        $packagist->expects($this->once())->method('get')->with('foo/bar')->willThrowException(new RuntimeException('stop1'));

        $command = new Search($packager, $packagist, false);
        $command->setHelperSet($helpers);

        $this->setExpectedException('RuntimeException', 'stop1');
        $command->run($input, $output);
    }

    public function testExecuteWithProjectAndVersionSelectedWillQuitWhenAskedForActionYieldsQuit()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->getMock();
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->getMock();

        $questionHelper = $this->getMockBuilder('Symfony\Component\Console\Helper\QuestionHelper')->getMock();
        $questionHelper->expects($this->exactly(3))->method('ask')->willReturnOnConsecutiveCalls(
            '<info>foo</info>/bar                                  (⤓)',
            'dev-master (<error>no executable bin</error>)',
            'Quit'
        );

        $helpers = new HelperSet(array(
            'question' => $questionHelper
        ));

        $packager = $this->getMockBuilder('Clue\PharComposer\Phar\Packager')->getMock();

        $result = $this->getMockBuilder('Packagist\Api\Result\Result')->getMock();
        $result->expects($this->exactly(2))->method('getName')->willReturn('foo/bar');

        $version = $this->getMockBuilder('Packagist\Api\Result\Package\Version')->getMock();
        $version->expects($this->exactly(2))->method('getVersion')->willReturn('dev-master');

        $package = $this->getMockBuilder('Packagist\Api\Result\Package')->getMock();
        $package->expects($this->once())->method('getVersions')->willReturn(array($version));

        $packagist = $this->getMockBuilder('Packagist\Api\Client')->getMock();
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn(array($result));
        $packagist->expects($this->once())->method('get')->with('foo/bar')->willReturn($package);

        $command = new Search($packager, $packagist, false);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    public function testExecuteWithProjectAndVersionSelectedWillBuildWhenAskedForActionYieldsBuild()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->getMock();
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->getMock();

        $questionHelper = $this->getMockBuilder('Symfony\Component\Console\Helper\QuestionHelper')->getMock();
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

        $packager = $this->getMockBuilder('Clue\PharComposer\Phar\Packager')->getMock();
        $packager->expects($this->once())->method('getPharer')->with('foo/bar', 'dev-master')->willReturn($pharer);

        $result = $this->getMockBuilder('Packagist\Api\Result\Result')->getMock();
        $result->expects($this->exactly(2))->method('getName')->willReturn('foo/bar');

        $version = $this->getMockBuilder('Packagist\Api\Result\Package\Version')->getMock();
        $version->expects($this->exactly(2))->method('getVersion')->willReturn('dev-master');

        $package = $this->getMockBuilder('Packagist\Api\Result\Package')->getMock();
        $package->expects($this->once())->method('getVersions')->willReturn(array($version));

        $packagist = $this->getMockBuilder('Packagist\Api\Client')->getMock();
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn(array($result));
        $packagist->expects($this->once())->method('get')->with('foo/bar')->willReturn($package);

        $command = new Search($packager, $packagist, false);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    public function testExecuteWithProjectAndVersionSelectedWillInstallWhenAskedForActionYieldsInstall()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->getMock();
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->getMock();

        $questionHelper = $this->getMockBuilder('Symfony\Component\Console\Helper\QuestionHelper')->getMock();
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

        $packager = $this->getMockBuilder('Clue\PharComposer\Phar\Packager')->getMock();
        $packager->expects($this->once())->method('getPharer')->with('foo/bar', 'dev-master')->willReturn($pharer);
        $packager->expects($this->once())->method('getSystemBin')->with($package)->willReturn('targetPath');
        $packager->expects($this->once())->method('install')->with($pharer, 'targetPath');

        $result = $this->getMockBuilder('Packagist\Api\Result\Result')->getMock();
        $result->expects($this->exactly(2))->method('getName')->willReturn('foo/bar');

        $version = $this->getMockBuilder('Packagist\Api\Result\Package\Version')->getMock();
        $version->expects($this->exactly(2))->method('getVersion')->willReturn('dev-master');

        $package = $this->getMockBuilder('Packagist\Api\Result\Package')->getMock();
        $package->expects($this->once())->method('getVersions')->willReturn(array($version));

        $packagist = $this->getMockBuilder('Packagist\Api\Client')->getMock();
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn(array($result));
        $packagist->expects($this->once())->method('get')->with('foo/bar')->willReturn($package);

        $command = new Search($packager, $packagist, false);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }

    public function testExecuteWithProjectAndVersionSelectedOnWindowsWillNotOfferInstallWhenAskedForAction()
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')->getMock();
        $input->expects($this->once())->method('getArgument')->with('project')->willReturn('foo');
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->getMock();

        $questionHelper = $this->getMockBuilder('Symfony\Component\Console\Helper\QuestionHelper')->getMock();
        $questionHelper->expects($this->exactly(3))->method('ask')->withConsecutive(
            array($this->anything()),
            array($this->anything()),
            array(
                $input,
                $output,
                $this->callback(function (ChoiceQuestion $question) {
                    return count($question->getChoices()) === 2;
                })
            )
        )->willReturnOnConsecutiveCalls(
            '<info>foo</info>/bar                                  (⤓)',
            'dev-master (<error>no executable bin</error>)',
            'Quit'
        );

        $helpers = new HelperSet(array(
            'question' => $questionHelper
        ));

        $package = $this->getMockBuilder('Clue\PharComposer\Package\Package')->disableOriginalConstructor()->getMock();

        $packager = $this->getMockBuilder('Clue\PharComposer\Phar\Packager')->getMock();

        $result = $this->getMockBuilder('Packagist\Api\Result\Result')->getMock();
        $result->expects($this->exactly(2))->method('getName')->willReturn('foo/bar');

        $version = $this->getMockBuilder('Packagist\Api\Result\Package\Version')->getMock();
        $version->expects($this->exactly(2))->method('getVersion')->willReturn('dev-master');

        $package = $this->getMockBuilder('Packagist\Api\Result\Package')->getMock();
        $package->expects($this->once())->method('getVersions')->willReturn(array($version));

        $packagist = $this->getMockBuilder('Packagist\Api\Client')->getMock();
        $packagist->expects($this->once())->method('search')->with('foo')->willReturn(array($result));
        $packagist->expects($this->once())->method('get')->with('foo/bar')->willReturn($package);

        $command = new Search($packager, $packagist, true);
        $command->setHelperSet($helpers);
        $command->run($input, $output);
    }
}
