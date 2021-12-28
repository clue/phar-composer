<?php

use Clue\PharComposer\Package\Bundle;
use Clue\PharComposer\Phar\TargetPhar;
use Clue\PharComposer\Package\Package;

class TargetPharTest extends TestCase
{
    /**
     * instance to test
     *
     * @ype  TargetPhar
     */
    private $targetPhar;

    private $mockPhar;

    private $mockPharComposer;

    /**
     * set up test environment
     *
     * @before
     */
    public function setUpPhar()
    {
        if (PHP_VERSION_ID >= 50400 && PHP_VERSION_ID <= 50600) {
            $this->markTestSkipped('Unable to mock \Phar on PHP 5.4/5.5');
        }

        $this->mockPhar = $this->getMockBuilder('\Phar')->disableOriginalConstructor()->getMock();
        $this->mockPharComposer = $this->getMockBuilder('Clue\PharComposer\Phar\PharComposer')->disableOriginalConstructor()->getMock();
        $this->targetPhar       = new TargetPhar($this->mockPhar, $this->mockPharComposer);
    }

    /**
     * @test
     */
    public function addFileCalculatesLocalPartForBox()
    {
        $this->mockPharComposer->expects($this->once())
                               ->method('getPathLocalToBase')
                               ->with($this->equalTo('path/to/package/file.php'))
                               ->will($this->returnValue('file.php'));
        $this->mockPhar->expects($this->once())
                      ->method('addFile')
                      ->with($this->equalTo('path/to/package/file.php'), $this->equalTo('file.php'));
        $this->targetPhar->addFile('path/to/package/file.php');
    }

    /**
     * @test
     */
    public function buildFromIteratorProvidesBasePathForBox()
    {
        $mockPackage = new Package(array(), 'path/to/package');
        $mockTraversable = $this->getMockBuilder('\Iterator')->getMock();
        $this->mockPharComposer->expects($this->once())
                               ->method('getPackageRoot')
                               ->willReturn($mockPackage);
        $this->mockPhar->expects($this->once())
                      ->method('buildFromIterator')
                      ->with($this->equalTo($mockTraversable), $this->equalTo('path/to/package/'));
        $this->targetPhar->buildFromIterator($mockTraversable);
    }

    /**
     * @test
     */
    public function addPackageAddsResourcesFromCalculatedBundle()
    {
        $bundle = new Bundle();
        $bundle->addFile('path/to/package/file.php');
        $this->mockPharComposer->expects($this->once())
                               ->method('getPathLocalToBase')
                               ->with($this->equalTo('path/to/package/file.php'))
                               ->will($this->returnValue('file.php'));
        $this->mockPhar->expects($this->once())
                      ->method('addFile')
                      ->with($this->equalTo('path/to/package/file.php'), $this->equalTo('file.php'));
        $mockFinder = $this->getMockBuilder('Symfony\Component\Finder\Finder')->disableOriginalConstructor()->getMock();
        $bundle->addDir($mockFinder);
        $mockPackage = new Package(array(), 'path/to/package');
        $this->mockPharComposer->expects($this->once())
                               ->method('getPackageRoot')
                               ->willReturn($mockPackage);
        $this->mockPhar->expects($this->once())
                      ->method('buildFromIterator')
                      ->with($this->equalTo($mockFinder), $this->equalTo('path/to/package/'));
        $this->targetPhar->addBundle($bundle);
    }

    /**
     * @test
     */
    public function setsStubOnUnderlyingPhar()
    {
        $this->mockPhar->expects($this->once())
                       ->method('setStub')
                       ->with($this->equalTo('some stub code'));
        $this->targetPhar->setStub('some stub code');
    }

    /**
     * @test
     */
    public function stopBufferingStopsBufferingOnUnderlyingPhar()
    {
        $this->mockPhar->expects($this->once())
                       ->method('stopBuffering');
        $this->targetPhar->stopBuffering();
    }

    /**
     * @test
     */
    public function addFromStringOnUnderlyingPhar()
    {
        $this->mockPhar->expects($this->once())
                       ->method('addFromString')
                       ->with('path/file', 'contents');
        $this->targetPhar->addFromString('path/file', 'contents');
    }
}
