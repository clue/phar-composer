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

    public function testConstructorThrowsWhenPathIsNotFile()
    {
        $this->setExpectedException('InvalidArgumentException', 'Unable to parse given path');
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

    public function testGetMainThrowsWhenBinDoesNotExist()
    {
        $pharer = new PharComposer(__DIR__ . '/../fixtures/05-invalid-bin/composer.json');

        $this->setExpectedException('UnexpectedValueException', 'Bin file "bin/invalid" does not exist');
        $pharer->getMain();
    }

    public function testSetTargetWillAppendPackageShortNameWhenTargetIsDirectory()
    {
        $pharer = new PharComposer(__DIR__ . '/../../composer.json');

        $pharer->setTarget(__DIR__);
        $this->assertEquals(__DIR__ . '/phar-composer.phar', $pharer->getTarget());
    }

    public function testBuildThrowsWhen()
    {
        $pharer = new PharComposer(__DIR__ . '/../fixtures/01-empty/composer.json');
        $pharer->setOutput(false);
        $pharer->setTarget('/dev/null');

        $this->setExpectedException('RuntimeException', 'not properly installed');
        $pharer->build();
    }

    public function testBuildThrowsWhenTargetCanNotBeWritten()
    {
        if (!Phar::canWrite() || !file_exists('/dev/null')) {
            $this->markTestSkipped('Test required "phar.readonly=off" setting and /dev/null');
        }

        $pharer = new PharComposer(__DIR__ . '/../fixtures/03-project-with-phars/composer.json');
        $pharer->setOutput(false);
        $pharer->setTarget('/dev/null');

        $this->setExpectedException('RuntimeException', 'Unable to write phar:');
        $pharer->build();
    }

    public function testBundlePackageWithNoVendorReturnsEmptyBundle()
    {
        $pharer = new PharComposer(__DIR__ . '/../fixtures/06-dependency-without-dir/composer.json');

        $deps = $pharer->getPackagesDependencies();

        $this->assertCount(1, $deps);
        $this->assertInstanceOf('Clue\PharComposer\Package\Package', reset($deps));

        /* @var Clue\PharComposer\Package\Package $package */
        $package = reset($deps);

        $bundle = $package->bundle();

        $this->assertInstanceOf('Clue\PharComposer\Package\Bundle', $bundle);
        $this->assertSame(0, iterator_count($bundle));
    }

    public function testBundlePackageWithComposerV2()
    {
        $pharer = new PharComposer(__DIR__ . '/../fixtures/07-composer-v2/composer.json');

        $deps = $pharer->getPackagesDependencies();

        $this->assertCount(1, $deps);
        $this->assertInstanceOf('Clue\PharComposer\Package\Package', reset($deps));

        /* @var Clue\PharComposer\Package\Package $package */
        $package = reset($deps);
        $bundle = $package->bundle();

        $this->assertInstanceOf('Clue\PharComposer\Package\Bundle', $bundle);
        $this->assertSame(0, iterator_count($bundle));
    }

    private function getPathProjectAbsolute($path)
    {
        return realpath(__DIR__ . '/../../' . $path);
    }
}
