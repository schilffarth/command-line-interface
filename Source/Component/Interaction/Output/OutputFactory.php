<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Component\Interaction\Output;

use Schilffarth\CommandLineInterface\{
    Source\AbstractFactory,
    Source\Component\Interaction\Output\Types\GridOutput
};

class OutputFactory extends AbstractFactory
{

    public const OUTPUT_GRID = GridOutput::class;

}
