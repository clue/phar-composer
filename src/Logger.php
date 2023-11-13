<?php

namespace Clue\PharComposer;

/**
 * Interface for logging out.
 *
 * TODO: should be used in the Command classes as well
 */
class Logger
{
    private $output = true;

    /**
     * set output function to use to output log messages
     *
     * @param callable|boolean $output callable that receives a single $line argument or boolean echo
     *
     * TODO: think about whether this should be a constructor instead
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    public function log($message)
    {
        $this->output($message . PHP_EOL);
    }

    private function output($message)
    {
        if ($this->output === true) {
            echo $message;
        } elseif ($this->output !== false) {
            call_user_func($this->output, $message);
        }
    }
}
