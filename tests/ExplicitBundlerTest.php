<?php

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

    private $mockTargetPhar;

    private $mockLogger;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockPackage      = $this->createMock('Clue\PharComposer\Package');
        $this->mockTargetPhar   = $this->createMock('Clue\PharComposer\TargetPhar');
        $this->mockLogger       = $this->createMock('Clue\PharComposer\Logger');
        $this->explicitBundler  = new ExplicitBundler($this->mockPackage);
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

    /**
     * @test
     */
    public function addsBinariesDefinedByPackageToBox()
    {
        $this->mockIncludes();
        $this->mockPackage->expects($this->once())
                          ->method('getBins')
                          ->will($this->returnValue(array('bin/example')));
        $this->mockTargetPhar->expects($this->once())
                             ->method('addFile')
                             ->with($this->equalTo('bin/example'));
        $this->explicitBundler->build($this->mockTargetPhar, $this->mockLogger);
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
        $this->mockTargetPhar->expects($this->once())
                             ->method('addFile')
                             ->with($this->equalTo('foo.php'));
        $this->explicitBundler->build($this->mockTargetPhar, $this->mockLogger);
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
        $this->mockTargetPhar->expects($this->once())
                             ->method('addFile')
                             ->with($this->equalTo('src/Example/SomeClass.php'));
        $this->explicitBundler->build($this->mockTargetPhar, $this->mockLogger);
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
        $this->mockTargetPhar->expects($this->once())
                             ->method('buildFromIterator');
        $this->explicitBundler->build($this->mockTargetPhar, $this->mockLogger);
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
        $this->mockTargetPhar->expects($this->once())
                             ->method('buildFromIterator');
        $this->explicitBundler->build($this->mockTargetPhar, $this->mockLogger);
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
                          ->will($this->returnValue($path . '/Clue'));
        $this->mockTargetPhar->expects($this->exactly(2))
                             ->method('buildFromIterator');
        $this->explicitBundler->build($this->mockTargetPhar, $this->mockLogger);
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
        $this->mockTargetPhar->expects($this->once())
                             ->method('addFile')
                             ->with($this->equalTo('another.php'));
        $this->explicitBundler->build($this->mockTargetPhar, $this->mockLogger);
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
        $this->mockTargetPhar->expects($this->once())
                             ->method('buildFromIterator');
        $this->explicitBundler->build($this->mockTargetPhar, $this->mockLogger);
    }
}
