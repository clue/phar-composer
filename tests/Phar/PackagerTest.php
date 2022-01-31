<?php

use Clue\PharComposer\Package\Package;
use Clue\PharComposer\Phar\Packager;

class PackagerTest extends TestCase
{
    private $packager;

    /** @before */
    public function setUpPackager()
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
        $this->expectOutputString(str_replace("\n", PHP_EOL, $expectedOutput));

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
                'echo error>&2'
            ),
            array(
                "\n    mixed\n    errors\n",
                'php -r ' . escapeshellarg('fwrite(STDOUT, \'mixed\' . PHP_EOL);fwrite(STDERR,\'errors\' . PHP_EOL);')
            )
        );
    }

    public function testEmptyNotInstalled()
    {
        $this->setExpectedException('RuntimeException', 'not installed');
        $this->packager->getPharer(__DIR__ . '/../fixtures/01-empty');
    }

    public function testNoComposer()
    {
        $this->setExpectedException('InvalidArgumentException', 'not a readable file');
        $this->packager->getPharer(__DIR__ . '/../fixtures/02-no-composer');
    }

    public function testNoComposerMissing()
    {
        $this->setExpectedException('InvalidArgumentException', 'not a readable file');
        $this->packager->getPharer(__DIR__ . '/../fixtures/02-no-composer/composer.json');
    }

    public function testGetPharerTriesToExecuteGitStubInDirectoryWithSpaceAndThrowsWhenGitStubDoesNotCreateTargetDirectory()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Not supported on Windows');
        }

        $path = getenv('PATH');

        $temp = sys_get_temp_dir() . '/test phar-composer-' . mt_rand();
        mkdir($temp);
        symlink(exec('which echo'), $temp . '/git');

        putenv('PATH=' . $temp);

        try {
            $this->packager->setOutput(false);
            $this->packager->getPharer('user@git.example.com:user/project.git');

            $this->fail();
        } catch (Exception $e) {
            putenv('PATH=' . $path);
            unlink($temp . '/git');
            rmdir($temp);

            $this->assertStringMatchesFormat('Unable to parse given path "/%s/phar-composer%d/composer.json"', $e->getMessage());
        }
    }

    public function testGetSystemBinDefaultsToPackageNameInBin()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Not supported on Windows');
        }

        $package = new Package(array(
            'name' => 'clue/phar-composer'
        ), '');

        $this->assertEquals('/usr/local/bin/phar-composer', $this->packager->getSystemBin($package, null));
    }

    public function testGetSystemBinReturnsPackageDirectoryBinWhenNameIsNotSet()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Not supported on Windows');
        }

        $package = new Package(array(), __DIR__);

        $this->assertEquals('/usr/local/bin/Phar', $this->packager->getSystemBin($package, null));
    }

    public function testGetSystemBinReturnsPackageDirectoryRealNameInBinWhenNameIsNotSet()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Not supported on Windows');
        }

        $package = new Package(array(), __DIR__ . '/../');

        $this->assertEquals('/usr/local/bin/tests', $this->packager->getSystemBin($package, null));
    }

    public function testGetSystemBinReturnsCustomPackageInBin()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Not supported on Windows');
        }

        $package = new Package(array(
            'name' => 'clue/phar-composer'
        ), '');

        $this->assertEquals('/usr/local/bin/foo', $this->packager->getSystemBin($package, 'foo'));
    }

    public function testGetSystemBinReturnsCustomTargetPath()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Not supported on Windows');
        }

        $package = new Package(array(
            'name' => 'clue/phar-composer'
        ), '');

        $this->assertEquals('/home/me/foo', $this->packager->getSystemBin($package, '/home/me/foo'));
    }

    public function testGetSystemBinReturnsDefaultPackageNameInCustomBin()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Not supported on Windows');
        }

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
            array('/home/alice/Desktop/package/acme.json'),
            array('C:\Users\Alice\Desktop\package\acme.json')
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
