<?php

use Clue\PharComposer\Package;

class PackageTest extends TestCase
{
    public function testConstructorDefaults()
    {
        $package = new Package(array(), 'dir/');

        $this->assertEquals(null, $package->getAutoload());
        $this->assertEquals(array(), $package->getBins());
        $this->assertInstanceOf('Clue\PharComposer\Bundler\Complete', $package->getBundler());
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

    public function testConstructorBundlerComposer()
    {
        $package = new Package(array(
            'extra' => array(
                'phar' => array(
                    'bundler' => 'composer'
                 )
            )
        ), 'dir/');

        $this->assertInstanceOf('Clue\PharComposer\Bundler\Explicit', $package->getBundler());
    }
}
