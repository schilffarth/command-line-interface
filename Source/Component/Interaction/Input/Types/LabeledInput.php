<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Component\Interaction\Input\Types;

use Schilffarth\CommandLineInterface\{
    Source\Component\Interaction\Input\AbstractInputObject,
    Source\Component\Interaction\Output\Output
};

class LabeledInput extends AbstractInputObject
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

    public function request(): parent
    {
        if (!$this->output->verbosityDisallowsOutput($this->labelVerbosity)) {
            $this->output->writeln($this->label);
        }

        return parent::request();
    }

}
