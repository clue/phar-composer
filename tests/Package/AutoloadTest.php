<?php

use Clue\PharComposer\Package\Autoload;

class AutoloadTest extends TestCase
{
    private function createAutoload(array $autoload)
    {
        return new Autoload($autoload);
    }

    /**
     * @test
     */
    public function returnsEmptyPsr0ListIfNotDefined()
    {
        $this->assertEquals(array(),
                            $this->createAutoload(array())->getPsr0()
        );
    }

    /**
     * @test
     */
    public function returnsAllPathesDefinedByPsr0WithSinglePath()
    {
        $path = 'path/to/src';
        $this->assertEquals(array($path . '/Clue'),
                            $this->createAutoload(array('psr-0' => array('Clue' => $path)))
                                 ->getPsr0()
        );
    }

    /**
     * @test
     */
    public function returnsAllPathesDefinedByPsr0WithSeveralPathes()
    {
        $path = 'path/to/src';
        $this->assertEquals(array($path . '/Clue', $path . '/Clue'),
                            $this->createAutoload(array('psr-0' => array('Clue' => array($path, $path))))
                                 ->getPsr0()
        );
    }

    /**
     * @test
     */
    public function returnsEmptyPsr4ListIfNotDefined()
    {
        $this->assertEquals(array(),
            $this->createAutoload(array())->getPsr4()
        );
    }

    /**
     * @test
     */
    public function returnsAllPathesDefinedByPsr4WithSinglePath()
    {
        $path = 'path/to/src';
        $autoloadConfig = array(
            'psr-4' => array(
                'Clue' => $path
            )
        );

        $this->assertEquals(array($path),
            $this->createAutoload($autoloadConfig)
                ->getPsr4()
        );
    }

    /**
     * @test
     */
    public function returnsAllPathesDefinedByPsr4WithSeveralPathes()
    {
        $cluePaths = array(
            'path/to/src',
            'path/to/lib'
        );

        $autoloadConfig = array(
            'psr-4' => array(
                'Clue' => $cluePaths
            )
        );

        $this->assertEquals($cluePaths,
            $this->createAutoload($autoloadConfig)
                ->getPsr4()
        );
    }

    /**
     * @test
     */
    public function testAllPathsDefinedByPsr4WithMixedStructure()
    {

        $cluePaths = array(
            'path/to/src',
            'path/to/lib'
        );

        $fooBarPath = 'path/to/foo';

        $autoloadConfig = array(
            'psr-4' => array(
                'Clue' => $cluePaths,    // Multiple paths
                'Foo\\Bar' => $fooBarPath   // Single path
            )
        );

        $this->assertEquals(array_merge($cluePaths, array($fooBarPath)),
            $this->createAutoload($autoloadConfig)
            ->getPsr4()
        );
    }

    /**
     * @test
     */
    public function returnsEmptyClassmapIfNotDefined()
    {
        $this->assertEquals(array(),
                            $this->createAutoload(array())->getClassmap()
        );
    }

    /**
     * @test
     */
    public function returnsClassmapAsDefined()
    {
        $this->assertEquals(array('Example/SomeClass' => 'src/Example/SomeClass.php'),
                            $this->createAutoload(array('classmap' => array('Example/SomeClass' => 'src/Example/SomeClass.php')))
                                 ->getClassmap()
        );
    }

    /**
     * @test
     */
    public function returnsEmptyFilelistIfNotDefined()
    {
        $this->assertEquals(array(),
                            $this->createAutoload(array())->getFiles()
        );
    }

    /**
     * @test
     */
    public function returnsFilelistAsDefined()
    {
        $this->assertEquals(array('foo.php', 'bar.php'),
                            $this->createAutoload(array('files' => array('foo.php', 'bar.php')))->getFiles()
        );
    }
}
