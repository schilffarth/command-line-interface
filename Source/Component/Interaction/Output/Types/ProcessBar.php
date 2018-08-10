<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\Component\Interaction\Output\Types;

use Schilffarth\Console\{
    Source\Component\Interaction\Output\AbstractOutputObject,
    Source\Component\Interaction\Output\Output
};

class ProcessBar extends AbstractOutputObject
{

    /**
     * Size of the progress bar
     */
    private $units = 0;

    /**
     * How many units are done
     */
    private $done = 0;

    /**
     * Keeps the currently displayed process bar string
     */
    private $previous = '';

    private $output;

    public function __construct(
        Output $output
    ) {
        $this->output = $output;
    }

    public function create(string $units): void
    {
        $this->units = (int) $units;
    }

    public function start(): void
    {
        $this->display();
    }

    public function tick(int $amount = 1): void
    {
        $this->done++;
        $this->display($this->done);
    }

    public function finish(): void
    {

    }

    public function cancel(): void
    {

    }

    private function display(int $done = 0): void
    {
        $startStr = '[';
        $endStr = ']';
        $state = '';

        for ($i = 0; $i < $done; $i++) {
            $state .= '=';
        }

        for ($i = $done; $i < $this->units; $i++) {
            $state .= '-';
        }

        $bar = $startStr . $state . $endStr;

        $this->output->write(sprintf("\r%s", $bar));

        $this->previous = $bar;
    }

}
