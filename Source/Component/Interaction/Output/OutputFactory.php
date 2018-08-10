<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\Component\Interaction\Output;

use Schilffarth\Console\{
    Source\App\AbstractFactory,
    Source\Component\Interaction\Output\Types\Grid,
    Source\Component\Interaction\Output\Types\ProcessBar
};

class OutputFactory extends AbstractFactory
{

    public const OUTPUT_GRID = Grid::class;

    public const OUTPUT_PROCESS_BAR = ProcessBar::class;

}
