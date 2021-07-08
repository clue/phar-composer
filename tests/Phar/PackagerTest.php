<?php

use Clue\PharComposer\Package\Package;
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

        // Travis CI occasionally discards (parts of) the output, so wrap in shell and add some delay just in case
        if (getenv('TRAVIS') === 'true') {
            $command = 'exec sh -c ' . escapeshellarg($command . '; sleep 0.1');
        }

        $this->packager->exec($command);
    }

    public function provideExecCommands()
    {
        return array(
            array(
                "\n    output\n",
                'echo output'
            ),
            array(
                "\n    error\n",
                'echo error >&2'
            ),
            array(
                "\n    mixed\n    errors\n",
                'echo mixed && echo errors >&1'
            )
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

    public function testGetSystemBinDefaultsToPackageNameInBin()
    {
        $package = new Package(array(
            'name' => 'clue/phar-composer'
        ), '');

        $this->assertEquals('/usr/local/bin/phar-composer', $this->packager->getSystemBin($package, null));
    }

    public function testGetSystemBinReturnsPackageDirectoryBinWhenNameIsNotSet()
    {
        $package = new Package(array(), __DIR__);

        $this->assertEquals('/usr/local/bin/Phar', $this->packager->getSystemBin($package, null));
    }

    public function testGetSystemBinReturnsPackageDirectoryRealNameInBinWhenNameIsNotSet()
    {
        $package = new Package(array(), __DIR__ . '/../');

        $this->assertEquals('/usr/local/bin/tests', $this->packager->getSystemBin($package, null));
    }

    public function testGetSystemBinReturnsCustomPackageInBin()
    {
        $package = new Package(array(
            'name' => 'clue/phar-composer'
        ), '');

        $this->assertEquals('/usr/local/bin/foo', $this->packager->getSystemBin($package, 'foo'));
    }

    public function testGetSystemBinReturnsCustomTargetPath()
    {
        $package = new Package(array(
            'name' => 'clue/phar-composer'
        ), '');

        $this->assertEquals('/home/me/foo', $this->packager->getSystemBin($package, '/home/me/foo'));
    }

    public function testGetSystemBinReturnsDefaultPackageNameInCustomBin()
    {
        $package = new Package(array(
            'name' => 'clue/phar-composer'
        ), '');

        $this->assertEquals('/usr/bin/phar-composer', $this->packager->getSystemBin($package, '/usr/bin'));
    }

    public function provideValidPackageUrl()
    {
        return array(
            array('https://github.com/clue/phar-composer.git'),
            array('git@github.com:clue/phar-composer.git'),
            array('github.com:clue/phar-composer.git')
        );
    }

    /**
     * @param string $path
     * @dataProvider provideValidPackageUrl
     */
    public function testIsPackageUrlReturnsTrue($path)
    {
        $this->assertTrue($this->packager->isPackageUrl($path));
    }

    public function provideInvalidPackageUrl()
    {
        return array(
            array('clue/phar-composer'),
            array('clue/phar-composer:^1.0'),
            array('clue/phar-composer:~1.0'),
            array('clue/packagewithoutdashes'),
            array('clue/packagewithoutdashes:1.2.34'),
            array('clue/packagewithoutdashes:^1.2.34'),
            array('clue/packagewithoutdashes:~1.2.34'),
            array('phar-composer.git'),
            array('github.com/clue/phar-composer.git'),
            array('git @github.com:clue/phar-composer.git'),
            array('-invalid@github.com:clue/phar-composer.git'),
            array(':clue/phar-composer.git'),
        );
    }

    /**
     * @param string $path
     * @dataProvider provideInvalidPackageUrl
     */
    public function testIsPackageUrlReturnsFalseForInvalidUrl($path)
    {
        $this->assertFalse($this->packager->isPackageUrl($path));
    }
}
