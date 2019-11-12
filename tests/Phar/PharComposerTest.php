<?php

use Clue\PharComposer\Phar\PharComposer;

class PharComposerTest extends TestCase
{
    public function testConstructor()
    {
        $pharcomposer = new PharComposer(__DIR__ . '/../../composer.json');

        $this->assertEquals('bin/phar-composer', $pharcomposer->getMain());

        $this->assertInstanceOf('Clue\PharComposer\Package\Package', $pharcomposer->getPackageRoot());
        $this->assertNotCount(0, $pharcomposer->getPackagesDependencies());

        $this->assertEquals('vendor/', $pharcomposer->getPackageRoot()->getPathVendor());
        $this->assertEquals('phar-composer.phar', $pharcomposer->getTarget());

        return $pharcomposer;
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unable to parse given path
     */
    public function testConstructorThrowsWhenPathIsNotFile()
    {
        new PharComposer(__DIR__);
    }

    /**
     * @param PharComposer $pharcomposer
     * @depends testConstructor
     */
    public function testSetters(PharComposer $pharcomposer)
    {
        $pharcomposer->setMain('example/phar-composer.php');
        $this->assertEquals('example/phar-composer.php', $pharcomposer->getMain());

        $pharcomposer->setTarget('test.phar');
        $this->assertEquals('test.phar', $pharcomposer->getTarget());

        return $pharcomposer;
    }

    /**
     * @expectedException UnexpectedValueException
     * @expectedExceptionMessage Bin file "bin/invalid" does not exist
     */
    public function testGetMainThrowsWhenBinDoesNotExist()
    {
        $pharer = new PharComposer(__DIR__ . '/../fixtures/05-invalid-bin/composer.json');

        $pharer->getMain();
    }

    public function testSetTargetWillAppendPackageShortNameWhenTargetIsDirectory()
    {
        $pharer = new PharComposer(__DIR__ . '/../../composer.json');

        $pharer->setTarget(__DIR__);
        $this->assertEquals(__DIR__ . '/phar-composer.phar', $pharer->getTarget());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage not properly installed
     */
    public function testBuildThrowsWhen()
    {
        $pharer = new PharComposer(__DIR__ . '/../fixtures/01-empty/composer.json');
        $pharer->setOutput(false);
        $pharer->setTarget('/dev/null');
        $pharer->build();
    }

    /**
     * @expectedException RuntimeException
     * @expectedException Unable to write phar:
     */
    public function testBuildThrowsWhenTargetCanNotBeWritten()
    {
        if (!Phar::canWrite() || !file_exists('/dev/null')) {
            $this->markTestSkipped('Test required "phar.readonly=off" setting and /dev/null');
        }

        $pharer = new PharComposer(__DIR__ . '/../fixtures/03-project-with-phars/composer.json');
        $pharer->setOutput(false);
        $pharer->setTarget('/dev/null');
        $pharer->build();
    }

    private function getPathProjectAbsolute($path)
    {
        return realpath(__DIR__ . '/../../' . $path);
    }
}
