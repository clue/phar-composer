<?php

use Clue\PharComposer\Bundler\Explicit as ExplicitBundler;
use Clue\PharComposer\Package\Autoload;

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

    /**
     * @test
     */
    public function addsBinariesDefinedByPackage()
    {
        $this->mockPackage->expects($this->once())
                          ->method('getBins')
                          ->will($this->returnValue(array('bin/example')));
        $this->mockPackage->expects($this->once())
                          ->method('getAutoload')
                          ->will($this->returnValue(new Autoload(array())));
        $this->assertTrue($this->explicitBundler->bundle()->contains('bin/example'),
                          'Failed asserting that "bin/example" is contained in bundle'
        );
    }

    /**
     * @test
     */
    public function addsFilesDefinedByAutoload()
    {
        $this->mockPackage->expects($this->once())
                          ->method('getBins')
                          ->will($this->returnValue(array()));
        $this->mockPackage->expects($this->once())
                          ->method('getAutoload')
                          ->will($this->returnValue(new Autoload(array('files' => array('foo.php')))));
        $this->mockPackage->expects($this->once())
                          ->method('getAbsolutePath')
                          ->with($this->equalTo('foo.php'))
                          ->will($this->returnValue('foo.php'));
        $this->assertTrue($this->explicitBundler->bundle()->contains('foo.php'),
                          'Failed asserting that "foo.php" is contained in bundle'
        );
    }

    /**
     * @test
     */
    public function addsFilesDefinedByClassmap()
    {
        $this->mockPackage->expects($this->once())
                          ->method('getBins')
                          ->will($this->returnValue(array()));
        $this->mockPackage->expects($this->once())
                          ->method('getAutoload')
                          ->will($this->returnValue(new Autoload(array('classmap' => array('Example/SomeClass.php')))));
        $this->mockPackage->expects($this->once())
                          ->method('getAbsolutePath')
                          ->with($this->equalTo('Example/SomeClass.php'))
                          ->will($this->returnValue('src/Example/SomeClass.php'));
        $this->assertTrue($this->explicitBundler->bundle()->contains('src/Example/SomeClass.php'),
                          'Failed asserting that "src/Example/SomeClass.php" is contained in bundle'
        );
    }

    /**
     * @test
     */
    public function addsDirectoriesDefinedByClassmap()
    {
        $this->mockPackage->expects($this->once())
                          ->method('getBins')
                          ->will($this->returnValue(array()));
        $this->mockPackage->expects($this->once())
                          ->method('getAutoload')
                          ->will($this->returnValue(new Autoload(array('classmap' => array(__DIR__)))));
        $this->mockPackage->expects($this->once())
                          ->method('getAbsolutePath')
                          ->with($this->equalTo(__DIR__))
                          ->will($this->returnValue(__DIR__));
        $this->assertTrue($this->explicitBundler->bundle()->contains(__DIR__),
                          'Failed asserting that ' . __DIR__ . ' is contained in bundle'
        );
    }

    /**
     * @test
     */
    public function addsAllPathesDefinedByPsr0WithSinglePath()
    {
        $this->mockPackage->expects($this->once())
                          ->method('getBins')
                          ->will($this->returnValue(array()));
        $path = realpath(__DIR__ . '/../src');
        $this->mockPackage->expects($this->once())
                          ->method('getAutoload')
                          ->will($this->returnValue(new Autoload(array('psr-0' => array('Clue' => $path)))));
        $this->mockPackage->expects($this->once())
                          ->method('getAbsolutePath')
                          ->with($this->equalTo($path . '/Clue'))
                          ->will($this->returnValue($path . '/Clue'));
        $this->assertTrue($this->explicitBundler->bundle()->contains($path),
                          'Failed asserting that ' . $path . ' is contained in bundle'
        );
    }

    /**
     * @test
     */
    public function addsAllPathesDefinedByPsr0WithSeveralPathes()
    {
        $this->mockPackage->expects($this->once())
                          ->method('getBins')
                          ->will($this->returnValue(array()));
        $path = realpath(__DIR__ . '/../src');
        $this->mockPackage->expects($this->once())
                          ->method('getAutoload')
                          ->will($this->returnValue(new Autoload(array('psr-0' => array('Clue' => array($path, $path))))));
        $this->mockPackage->expects($this->exactly(2))
                          ->method('getAbsolutePath')
                          ->with($this->equalTo($path . '/Clue'))
                          ->will($this->onConsecutiveCalls($path . '/Clue/PharComposer/Bundler', $path . '/Clue/PharComposer/Command'));
        $bundle = $this->explicitBundler->bundle();
        $this->assertTrue($bundle->contains($path . '/Clue/PharComposer/Bundler'),
                          'Failed asserting that ' . $path . '/Clue/PharComposer/Bundler' . ' is contained in bundle'
        );
        $this->assertTrue($bundle->contains($path . '/Clue/PharComposer/Command'),
                          'Failed asserting that ' . $path . '/Clue/PharComposer/Command' . ' is contained in bundle'
        );
    }
}
