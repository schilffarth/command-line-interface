<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\Component\Interaction\Input;

use Schilffarth\Console\{
    Source\App\AbstractFactory,
    Source\Component\Interaction\Input\Types\Labeled
};

class InputFactory extends AbstractFactory
{

    public const INPUT_LABELED = Labeled::class;

}
