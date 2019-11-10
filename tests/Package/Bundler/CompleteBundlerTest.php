<?php

use Clue\PharComposer\Package\Bundler\Complete;
use Clue\PharComposer\Package\Package;

class CompleteBundlerTest extends TestCase
{
    public function testBundleWillContainComposerJsonButNotVendor()
    {
        $dir = realpath(__DIR__ . '/../../fixtures/03-project-with-phars') . '/';
        $package = new Package(array(), $dir);
        $logger = $this->getMock('Clue\PharComposer\Logger');
        $logger->expects($this->once())->method('log');

        $bundler = new Complete($package, $logger);
        $bundle = $bundler->bundle();

        $this->assertTrue($bundle->contains($dir . 'composer.json'));
        $this->assertFalse($bundle->contains($dir . 'vendor/autoload.php'));
    }

    public function testBundleWillNotContainComposerPharInRoot()
    {
        $dir = realpath(__DIR__ . '/../../fixtures/03-project-with-phars') . '/';
        $package = new Package(array(), $dir);
        $logger = $this->getMock('Clue\PharComposer\Logger');
        $logger->expects($this->once())->method('log');

        $bundler = new Complete($package, $logger);
        $bundle = $bundler->bundle();

        $this->assertFalse($bundle->contains($dir . 'composer.phar'));
        $this->assertFalse($bundle->contains($dir . 'phar-composer.phar'));
    }

    public function testBundleWillContainComposerPharFromSrc()
    {
        $dir = realpath(__DIR__ . '/../../fixtures/04-project-with-phars-in-src') . '/';
        $package = new Package(array(), $dir);
        $logger = $this->getMock('Clue\PharComposer\Logger');
        $logger->expects($this->once())->method('log');

        $bundler = new Complete($package, $logger);
        $bundle = $bundler->bundle();

        $this->assertTrue($bundle->contains($dir . 'composer.json'));
        $this->assertTrue($bundle->contains($dir . 'src/composer.phar'));
        $this->assertTrue($bundle->contains($dir . 'src/phar-composer.phar'));
    }
}
