<?php

use Clue\PharComposer\Logger as Logger;

class LoggerTest extends TestCase
{
    /**
     * instance to test
     *
     * @type  Logger
     */
    private $logger;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->logger = new Logger();
    }

    /**
     * @test
     */
    public function echosToStdOutByDefault()
    {
        ob_start();
        $this->logger->log('some informational message');
        $this->assertEquals('some informational message' . PHP_EOL,
                            ob_get_contents()
        );
        ob_end_clean();
    }

    /**
     * @test
     */
    public function callsGivenOutputFunctionWhenSet()
    {
        $that = $this;
        $this->logger->setOutput(function($message) use($that) { $that->assertEquals('some informational message' . PHP_EOL, $message);});
        $this->logger->log('some informational message');
    }
}
