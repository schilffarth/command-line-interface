<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Component\Interaction\Input;

use Schilffarth\CommandLineInterface\{
    Source\Component\Interaction\Output\Output
};

class Labeled extends AbstractInputObject
{

    /**
     * The label that will be displayed the next time a user input is requested with @see AbstractInputObject::nextLine
     */
    protected $label = '';

    /**
     * Verbosity level - At which verbosity to display / suppress the label
     */
    protected $labelVerbosity = Output::QUIET;

    public function create(string $label = '', $labelVerbosity = Output::QUIET): void
    {
        $this->label = $label;
        $this->labelVerbosity = $labelVerbosity;
    }

    public function request(): void
    {
        if (!$this->output->verbosityDisallowsOutput($this->labelVerbosity)) {
            $this->output->comment($this->label);
        }

        parent::request();
    }

}
