<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
 */

namespace Source\Component;

/**
 * todo Logger
 */
class Logger
{

    /**
     * File location for error logging
     */
    public const LOG_FILE = BASE . '/cli/log/error.log';

    /**
     * Log a message
     * Return true on success, false on failure
     */
    public function log(string $msg, string $file = self::LOG_FILE, bool $date = true): bool
    {
        if (file_put_contents($file, PHP_EOL . PHP_EOL . ($date ? $this->getDate() : '') . PHP_EOL . $msg, FILE_APPEND) === false) {
            // failure
            return false;
        } else {
            // success
            return true;
        }
    }

    /**
     * Logs an exception message and detailed information / backtrace to error.log
     */
    public function exception(\Exception $e)
    {
        $code = $e->getCode();
        $msg = $e->getMessage();
        $trace = $e->getTraceAsString();

        return $this->log($this->getDate() . ' - ERROR CODE ' . $code . PHP_EOL . $msg . PHP_EOL . $trace,
        self::LOG_FILE, false);
    }

    private function getDate(): string
    {
        return date('o-m-W H:i:s');
    }

}
