<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\App;

class PreparationHelper
{

    /**
     * When running the application like "php execute.php example-command --arg", the first entry of the $argv array is
     * going to be the filename (execute.php), not the desired command (example-command). Ensure the first $argv entry
     * represents the command desired to be run by the console application.
     */
    public static function prepareArgv(array &$argv, string $executable): void
    {
        if (basename($argv[0]) === basename($executable)) {
            // First entry is the called file
            unset($argv[0]);
        }
    }

}
