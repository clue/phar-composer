<?php

use Clue\PharComposer\Package;

class PackageTest extends TestCase
{
    public function testConstructorDefaults()
    {
        $package = new Package(array(), 'dir/');

        $this->assertEquals(null, $package->getAutoload());
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

    public function testConstructorBundlerComposerWithoutAdditionalIncludes()
    {
        $package = new Package(array(
            'extra' => array(
                'phar' => array(
                    'bundler' => 'composer'
                 )
            )
        ), 'dir/');

        $this->assertInstanceOf('Clue\PharComposer\Bundler\Explicit',
                                $package->getBundler($this->createMockLogger())
        );
    }

    public function testConstructorBundlerComposerWithAdditionalInclude()
    {
        $package = new Package(array(
            'extra' => array(
                'phar' => array(
                    'bundler'  => 'composer',
                    'includes' => 'another.php'
                 )
            )
        ), 'dir/');

        $this->assertInstanceOf('Clue\PharComposer\Bundler\Explicit',
                                $package->getBundler($this->createMockLogger())
        );
    }

    public function testConstructorBundlerComposerWithAdditionalIncludes()
    {
        $package = new Package(array(
            'extra' => array(
                'phar' => array(
                    'bundler'  => 'composer',
                    'include' => array('another.php', __DIR__)
                 )
            )
        ), 'dir/');

        $this->assertInstanceOf('Clue\PharComposer\Bundler\Explicit',
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

        $this->assertInstanceOf('Clue\PharComposer\Bundler\Complete',
                                $package->getBundler($this->createMockLogger())
        );
    }

    public function testConstructorBundlerCompleteAsDefault()
    {
        $package = new Package(array(), 'dir/');

        $this->assertInstanceOf('Clue\PharComposer\Bundler\Complete',
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
        $this->assertInstanceOf('Clue\PharComposer\Bundler\Complete',
                                $package->getBundler($mockLogger)
        );
    }

    public function testBlacklistContainsComposerAndPharComposerByDefault()
    {
        $package = new Package(array(), 'dir/');
        $this->assertEquals(array('dir/composer.phar',
                                  'dir/phar-composer.phar'
                            ),
                            $package->getBlacklist()
        );
    }

    public function testBlacklistContainsAdditionalExcludeFromConfig()
    {
        $package = new Package(array(
            'extra' => array(
                'phar' => array(
                    'exclude' => 'phpunit.xml.dist'
                )
            )
        ), 'dir/');
        $this->assertEquals(array('dir/phpunit.xml.dist',
                                  'dir/composer.phar',
                                  'dir/phar-composer.phar'
                            ),
                            $package->getBlacklist()
        );
    }

    public function testBlacklistContainsAdditionalExcludesFromConfig()
    {
        $package = new Package(array(
            'extra' => array(
                'phar' => array(
                    'exclude' => array('phpunit.xml.dist', '.travis.yml')
                )
            )
        ), 'dir/');
        $this->assertEquals(array('dir/phpunit.xml.dist',
                                  'dir/.travis.yml',
                                  'dir/composer.phar',
                                  'dir/phar-composer.phar'
                            ),
                            $package->getBlacklist()
        );
    }
}
