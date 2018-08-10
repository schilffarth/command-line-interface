<?php
/**
* @author      Roland Schilffarth <roland@schilffarth.org>
* @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
*/

namespace Schilffarth\Console\Source\App;

class ErrorHandler
{

    /**
     * Specified labels for the error codes
     */
    private const LABELS = [
        E_ERROR => 'Fatal run-time error',
        E_WARNING => 'Run-time warning',
        E_PARSE => 'Compile-time parse error',
        E_NOTICE => 'Run-time notice',
        E_CORE_ERROR => 'Fatal error during initial setup',
        E_CORE_WARNING => 'Warning during initial setup',
        E_COMPILE_ERROR => 'Fatal compile-time error',
        E_COMPILE_WARNING => 'Compile-time warning',
        E_USER_ERROR => 'User-generated error',
        E_USER_WARNING => 'User-generated warning',
        E_USER_NOTICE => 'User-generated notice',
        E_RECOVERABLE_ERROR => 'Catchable fatal error',
        E_DEPRECATED => 'Deprecated notice',
        E_USER_DEPRECATED => 'User-generated deprecated notice'
    ];

    /**
     * Handle your error, override the function if you desire special treatment
     * Default will echo the output and exit the program
     */
    public function exit(\Exception $e): void
    {
        echo PHP_EOL . PHP_EOL
            . $e->getCode() . ' - ' . $this->getErrorLabel($e) . PHP_EOL . PHP_EOL
            . get_class($e) . ' - ' . $e->getMessage() . PHP_EOL . PHP_EOL
            . $e->getTraceAsString() . PHP_EOL . PHP_EOL;

        exit;
    }

    /**
     * Error handler for unhandled errors
     */
    public function handle(int $errNo, string $errStr): void
    {
        $this->exit(new \Exception($errStr, $errNo));
    }

    /**
     * Get the exceptions label by its error code
     */
    protected function getErrorLabel(\Exception $e): string
    {
        return self::LABELS[$e->getCode()] ?? '';
    }

}
