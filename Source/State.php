<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source;

use Schilffarth\CommandLineInterface\{
    Source\Component\Interaction\Output\Output
};

class State
{

    /**
     * Command exit status - Whether it has run successfully or failed
     */
    public static $success = false;

    /**
     * The current output verbosity level for the run command
     * @see Output::verbosityDisallowsOutput()
     */
    public static $verbosity = Output::NORMAL;

    /**
     * Whether to color / highlight output dependent on message level or not
     */
    public static $colorDisabled = false;

    /**
     * Used to calculate the duration of script execution
     */
    private static $startTime = 0;

    public function __construct()
    {
        self::$startTime = microtime(true);
    }

    /**
     * How long the script has run yet (in seconds)
     */
    public function getExecutionDuration(): string
    {
        return round(microtime(true) - self::$startTime, 3) . ' seconds';
    }

}
