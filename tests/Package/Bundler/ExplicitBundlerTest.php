<?php

use Clue\PharComposer\Package\Bundler\Explicit as ExplicitBundler;
use Clue\PharComposer\Package\Package;

class ExplicitBundlerTest extends TestCase
{
    /**
     * instance to test
     *
     * @type  ExplicitBundler
     */
    private $explicitBundler;

    private $package;

    private $path;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->path    = realpath(__DIR__ . '/../../../');
        $this->package = new Package(array('bin'      => array('bin/example'),
                                           'autoload' => array('files'    => array('foo.php'),
                                                               'classmap' => array('src/Example/SomeClass.php'),
                                                               'psr-0'    => array('Clue' => 'src')
                                                         ),
                                     ),
                                     $this->path . '/'
                         );
        $this->explicitBundler = new ExplicitBundler($this->package, $this->createMock('Clue\PharComposer\Logger'));
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
        $this->assertTrue($this->explicitBundler->bundle()->contains($this->path . '/bin/example'),
                          'Failed asserting that "bin/example" is contained in bundle'
        );
    }

    /**
     * @test
     */
    public function addsFilesDefinedByAutoload()
    {

        $this->assertTrue($this->explicitBundler->bundle()->contains($this->path . '/foo.php'),
                          'Failed asserting that "foo.php" is contained in bundle'
        );
    }

    /**
     * @test
     */
    public function addsFilesDefinedByClassmap()
    {
        $this->assertTrue($this->explicitBundler->bundle()->contains($this->path . '/src/Example/SomeClass.php'),
                          'Failed asserting that "src/Example/SomeClass.php" is contained in bundle'
        );
    }

    /**
     * @test
     */
    public function addsAllPathesDefinedByPsr0WithSinglePath()
    {
        $this->assertTrue($this->explicitBundler->bundle()->contains($this->path . '/src/Clue/'),
                          'Failed asserting that ' . $this->path . '/src/Clue/ is contained in bundle'
        );
    }

    /**
     * @test
     */
    public function addsAllPathesDefinedByPsr0WithSeveralPathes()
    {
        $this->package = new Package(array('autoload' => array('psr-0' => array('Clue' => array('src/',
                                                                                                'src/'
                                                                                          )
                                                                          )
                                                         )
                                     ),
                                     $this->path . '/'
                         );
        $this->explicitBundler = new ExplicitBundler($this->package, $this->createMock('Clue\PharComposer\Logger'));
        $bundle = $this->explicitBundler->bundle();
        $this->assertTrue($bundle->contains($this->path . '/src'),
                          'Failed asserting that ' . $this->path . '/src' . ' is contained in bundle'
        );
    }
}
