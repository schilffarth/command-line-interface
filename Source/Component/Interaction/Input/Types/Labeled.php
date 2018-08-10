<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\Component\Interaction\Input\Types;

use Schilffarth\Console\{
    Source\Component\Interaction\Input\AbstractInputObject,
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

    public function request(): parent
    {
        $this->output->writeln($this->label, $this->labelVerbosity);
        return parent::request();
    }

}
