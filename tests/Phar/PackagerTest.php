<?php

use Clue\PharComposer\Phar\Packager;

class PackagerTest extends TestCase
{
    private $packager;

    public function setUp(): void
    {
        $this->packager = new Packager();
    }

    /**
     *
     * @param string $expectedOutput
     * @param string $command
     * @dataProvider provideExecCommands
     */
    public function testExec($expectedOutput, $command)
    {
        $this->expectOutputString($expectedOutput);

        $this->packager->exec($command);
    }

    public function provideExecCommands()
    {
        return array(
            array("\n    output\n", 'echo output'),
            array("\n    error\n", 'echo error >&2'),
            array("\n    mixed\n    errors\n", 'echo mixed && echo errors >&1'),
        );
    }

    public function testEmptyNotInstalled()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not installed');

        $this->packager->getPharer(__DIR__ . '/../fixtures/01-empty');
    }

    public function testNoComposer()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not a readable file');

        $this->packager->getPharer(__DIR__ . '/../fixtures/02-no-composer');
    }

    public function testNoComposerMissing()
    {
      $this->expectException(InvalidArgumentException::class);
      $this->expectExceptionMessage('not a readable file');

      $this->packager->getPharer(__DIR__ . '/../fixtures/02-no-composer/composer.json');
    }
}
