<?php

use Clue\PharComposer\Phar\PharComposer;

class PharComposerTest extends TestCase
{
    public function testConstructor()
    {
        $pharcomposer = new PharComposer(__DIR__ . '/../../composer.json');

        $this->assertEquals($this->getPathProjectAbsolute('/') . '/', $pharcomposer->getBase());
        $this->assertEquals($this->getPathProjectAbsolute('bin/phar-composer'), $pharcomposer->getMain());

        $this->assertInstanceOf('Clue\PharComposer\Package\Package', $pharcomposer->getPackageRoot());
        $this->assertNotCount(0, $pharcomposer->getPackagesDependencies());

        $this->assertEquals($this->getPathProjectAbsolute('vendor') . '/', $pharcomposer->getPathVendor());
        $this->assertEquals('phar-composer.phar', $pharcomposer->getTarget());

        return $pharcomposer;
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

    private function getPathProjectAbsolute($path)
    {
        return realpath(__DIR__ . '/../../' . $path);
    }
}
