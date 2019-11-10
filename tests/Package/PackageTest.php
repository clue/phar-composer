<?php

use Clue\PharComposer\Package\Package;
use Clue\PharComposer\Package\Autoload;

class PackageTest extends TestCase
{
    public function testConstructorDefaults()
    {
        $package = new Package(array(), 'dir/');

        $this->assertEquals(new Autoload(array()), $package->getAutoload());
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

    public function testConstructorBundlerComposer()
    {
        $package = new Package(array(
            'extra' => array(
                'phar' => array(
                    'bundler' => 'composer'
                 )
            )
        ), 'dir/');

        $this->assertInstanceOf('Clue\PharComposer\Package\Bundler\Explicit',
                                $package->getBundler($this->createMockLogger())
        );
    }

    public function testConstructorBundlerCompleteWithExplicitConfig()
    {
        $package = new Package(array(
            'extra' => array(
                'phar' => array(
                    'bundler' => 'complete'
                 )
            )
        ), 'dir/');

        $this->assertInstanceOf('Clue\PharComposer\Package\Bundler\Complete',
                                $package->getBundler($this->createMockLogger())
        );
    }

    public function testConstructorBundlerCompleteAsDefault()
    {
        $package = new Package(array(), 'dir/');

        $this->assertInstanceOf('Clue\PharComposer\Package\Bundler\Complete',
                                $package->getBundler($this->createMockLogger())
        );
    }

    public function testConstructorBundlerInvalid()
    {
        $package = new Package(array(
            'name'  => 'cool-package',
            'extra' => array(
                'phar' => array(
                    'bundler' => 'foo'
                )
            )
        ), 'dir/');

        $mockLogger = $this->createMockLogger();
        $mockLogger->expects($this->once())
                   ->method('log')
                   ->with($this->equalTo('Invalid bundler "foo" specified in package "cool-package", will fall back to "complete" bundler'));
        $this->assertInstanceOf('Clue\PharComposer\Package\Bundler\Complete',
                                $package->getBundler($mockLogger)
        );
    }
}
