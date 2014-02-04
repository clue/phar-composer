<?php

use Clue\PharComposer\Bundle;
use Clue\PharComposer\Bundler\Explicit as ExplicitBundler;

class ExplicitBundlerTest extends TestCase
{
    /**
     * instance to test
     *
     * @type  ExplicitBundler
     */
    private $explicitBundler;

    private $mockPackage;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockPackage      = $this->createMock('Clue\PharComposer\Package');
        $this->explicitBundler  = new ExplicitBundler($this->mockPackage, $this->createMock('Clue\PharComposer\Logger'));
    }

    private function createMock($class)
    {
        return $this->getMockBuilder($class)
                    ->disableOriginalConstructor()
                    ->getMock();
    }

    private function mockIncludes(array $includes = array())
    {
        $this->mockPackage->expects($this->once())
                          ->method('getAdditionalIncludes')
                          ->will($this->returnValue($includes));
    }

    private function assertBundleContainsFile($file, Bundle $bundle)
    {
        $this->assertContains($file, $bundle->getResources());
    }

    private function assertBundleContainsDirectory($directory, Bundle $bundle)
    {
        foreach ($bundle->getResources() as $resource) {
            if ($resource instanceof Symfony\Component\Finder\Finder) {
                foreach ($resource as $containedDirectory) {
                    /* @var $containedDirectory \SplFileInfo */
                    if (substr($containedDirectory->getRealPath(), 0, strlen($directory)) === $directory) {
                        return true;
                    }
                }
            }
        }

        $this->fail('Directory ' . $directory . ' is not contained in bundle');
    }

    /**
     * @test
     */
    public function addsBinariesDefinedByPackage()
    {
        $this->mockIncludes();
        $this->mockPackage->expects($this->once())
                          ->method('getBins')
                          ->will($this->returnValue(array('bin/example')));
        $this->assertBundleContainsFile('bin/example', $this->explicitBundler->bundle());
    }

    /**
     * @test
     */
    public function addsFilesDefinedByAutoload()
    {
        $this->mockIncludes();
        $this->mockPackage->expects($this->once())
                          ->method('getBins')
                          ->will($this->returnValue(array()));
        $this->mockPackage->expects($this->once())
                          ->method('getAutoload')
                          ->will($this->returnValue(array('files' => array('foo.php'))));
        $this->mockPackage->expects($this->once())
                          ->method('getAbsolutePath')
                          ->with($this->equalTo('foo.php'))
                          ->will($this->returnValue('foo.php'));
        $this->assertBundleContainsFile('foo.php', $this->explicitBundler->bundle());
    }

    /**
     * @test
     */
    public function addsFilesDefinedByClassmap()
    {
        $this->mockIncludes();
        $this->mockPackage->expects($this->once())
                          ->method('getBins')
                          ->will($this->returnValue(array()));
        $this->mockPackage->expects($this->once())
                          ->method('getAutoload')
                          ->will($this->returnValue(array('classmap' => array('Example/SomeClass.php'))));
        $this->mockPackage->expects($this->once())
                          ->method('getAbsolutePath')
                          ->with($this->equalTo('Example/SomeClass.php'))
                          ->will($this->returnValue('src/Example/SomeClass.php'));
        $this->assertBundleContainsFile('src/Example/SomeClass.php', $this->explicitBundler->bundle());
    }

    /**
     * @test
     */
    public function addsDirectoriesDefinedByClassmap()
    {
        $this->mockIncludes();
        $this->mockPackage->expects($this->once())
                          ->method('getBins')
                          ->will($this->returnValue(array()));
        $this->mockPackage->expects($this->once())
                          ->method('getAutoload')
                          ->will($this->returnValue(array('classmap' => array(__DIR__))));
        $this->mockPackage->expects($this->once())
                          ->method('getAbsolutePath')
                          ->with($this->equalTo(__DIR__))
                          ->will($this->returnValue(__DIR__));
        $this->assertBundleContainsDirectory(__DIR__, $this->explicitBundler->bundle());
    }

    /**
     * @test
     */
    public function addsAllPathesDefinedByPsr0WithSinglePath()
    {
        $this->mockIncludes();
        $this->mockPackage->expects($this->once())
                          ->method('getBins')
                          ->will($this->returnValue(array()));
        $path = realpath(__DIR__ . '/../src');
        $this->mockPackage->expects($this->once())
                          ->method('getAutoload')
                          ->will($this->returnValue(array('psr-0' => array('Clue' => $path))));
        $this->mockPackage->expects($this->once())
                          ->method('getAbsolutePath')
                          ->with($this->equalTo($path . '/Clue'))
                          ->will($this->returnValue($path . '/Clue'));
        $this->assertBundleContainsDirectory($path, $this->explicitBundler->bundle());
    }

    /**
     * @test
     */
    public function addsAllPathesDefinedByPsr0WithSeveralPathes()
    {
        $this->mockIncludes();
        $this->mockPackage->expects($this->once())
                          ->method('getBins')
                          ->will($this->returnValue(array()));
        $path = realpath(__DIR__ . '/../src');
        $this->mockPackage->expects($this->once())
                          ->method('getAutoload')
                          ->will($this->returnValue(array('psr-0' => array('Clue' => array($path, $path)))));
        $this->mockPackage->expects($this->exactly(2))
                          ->method('getAbsolutePath')
                          ->with($this->equalTo($path . '/Clue'))
                          ->will($this->onConsecutiveCalls($path . '/Clue/PharComposer/Bundler', $path . '/Clue/PharComposer/Command'));
        $bundle = $this->explicitBundler->bundle();
        $this->assertBundleContainsDirectory($path . '/Clue/PharComposer/Bundler', $bundle);
        $this->assertBundleContainsDirectory($path . '/Clue/PharComposer/Command', $bundle);
    }

    /**
     * @test
     */
    public function addsFilesFromAdditionalIncludes()
    {
        $this->mockIncludes(array('another.php'));
        $this->mockPackage->expects($this->once())
                          ->method('getBins')
                          ->will($this->returnValue(array()));

        $this->mockPackage->expects($this->once())
                          ->method('getAbsolutePath')
                          ->with($this->equalTo('another.php'))
                          ->will($this->returnValue('another.php'));
        $this->assertBundleContainsFile('another.php', $this->explicitBundler->bundle());
    }

    /**
     * @test
     */
    public function addsDirectoriesFromAdditionalIncludes()
    {
        $this->mockIncludes(array(__DIR__));
        $this->mockPackage->expects($this->once())
                          ->method('getBins')
                          ->will($this->returnValue(array()));
        $this->mockPackage->expects($this->once())
                          ->method('getAbsolutePath')
                          ->with($this->equalTo(__DIR__))
                          ->will($this->returnValue(__DIR__));
        $this->assertBundleContainsDirectory(__DIR__, $this->explicitBundler->bundle());
    }
}
