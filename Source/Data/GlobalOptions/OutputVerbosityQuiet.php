<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\Data\GlobalOptions;

use Schilffarth\Console\{
    Source\Component\Argument\ArgumentHelper,
    Source\Component\Argument\Types\GlobalOption,
    Source\App\State,
    Source\Component\Interaction\Output\Output
};

class OutputVerbosityQuiet extends GlobalOption
{

    public function __construct(
        ArgumentHelper $argumentHelper,
        Output $output
    ) {
        parent::__construct($argumentHelper, $output);

        $this->setName('quiet')
            ->setDescription('Suppress both normal and debugging messages. Only errors will be outputted.')
            ->addAlias('q')
            ->addExcludedArgument('debug');
    }

    public function call(): void
    {
        State::$verbosity = Output::QUIET;
    }

}
