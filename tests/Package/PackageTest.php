<?php

use Clue\PharComposer\Package\Package;

class PackageTest extends TestCase
{
    public function testConstructorDefaults()
    {
        $package = new Package(array(), 'dir/');

        $this->assertEquals(array(), $package->getBins());
        $this->assertEquals('dir/', $package->getDirectory());
        $this->assertEquals(null, $package->getName());
        $this->assertEquals('dir', $package->getShortName());
        $this->assertEquals('vendor/', $package->getPathVendor());
    }

    public function testGetShortNameReturnsLastPathComponentWhenNameIsUnknown()
    {
        $package = new Package(array(), __DIR__);

        $this->assertEquals('Package', $package->getShortName());
    }

    public function testConstructorData()
    {
        $package = new Package(array(
            'name' => 'acme/test',
            'bin' => array('bin/main', 'bin2'),
            'config' => array(
                'vendor-dir' => 'src/vendors'
            )
        ), 'dir/');

        $this->assertEquals(array('bin/main', 'bin2'), $package->getBins());
        $this->assertEquals('acme/test', $package->getName());
        $this->assertEquals('test', $package->getShortName());
        $this->assertEquals('src/vendors/', $package->getPathVendor());
    }

    private function createMockLogger()
    {
        return $this->getMockBuilder('Clue\PharComposer\Logger')
                    ->disableOriginalConstructor()
                    ->getMock();
    }

    public function testBundleWillContainComposerJsonButNotVendor()
    {
        $dir = realpath(__DIR__ . '/../fixtures/03-project-with-phars') . DIRECTORY_SEPARATOR;
        $package = new Package(array(), $dir);
        $bundle = $package->bundle();

        $this->assertTrue($bundle->contains($dir . 'composer.json'));
        $this->assertFalse($bundle->contains($dir . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php'));
    }

    public function testBundleWillNotContainComposerPharInRoot()
    {
        $dir = realpath(__DIR__ . '/../fixtures/03-project-with-phars') . DIRECTORY_SEPARATOR;
        $package = new Package(array(), $dir);
        $bundle = $package->bundle();

        $this->assertFalse($bundle->contains($dir . 'composer.phar'));
        $this->assertFalse($bundle->contains($dir . 'phar-composer.phar'));
    }

    public function testBundleWillContainComposerPharFromSrc()
    {
        $dir = realpath(__DIR__ . '/../fixtures/04-project-with-phars-in-src') . DIRECTORY_SEPARATOR;
        $package = new Package(array(), $dir);
        $bundle = $package->bundle();

        $this->assertTrue($bundle->contains($dir . 'composer.json'));
        $this->assertTrue($bundle->contains($dir . 'src' . DIRECTORY_SEPARATOR . 'composer.phar'));
        $this->assertTrue($bundle->contains($dir . 'src' . DIRECTORY_SEPARATOR . 'phar-composer.phar'));
    }
}
