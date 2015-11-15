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
        $this->assertEquals('unknown', $package->getName());
        $this->assertEquals('dir/vendor/', $package->getPathVendor());
    }

    public function testConstructorData()
    {
        $package = new Package(array(
            'name' => 'test/test',
            'bin' => array('bin/main', 'bin2'),
            'config' => array(
                'vendor-dir' => 'src/vendors'
            )
        ), 'dir/');

        $this->assertEquals(array('dir/bin/main', 'dir/bin2'), $package->getBins());
        $this->assertEquals('test/test', $package->getName());
        $this->assertEquals('dir/src/vendors/', $package->getPathVendor());
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
