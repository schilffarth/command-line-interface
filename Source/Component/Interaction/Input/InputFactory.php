<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Component\Interaction\Input;

use Schilffarth\CommandLineInterface\{
    Source\AbstractFactory,
    Source\Component\Interaction\Input\Types\LabeledInput
};

class InputFactory extends AbstractFactory
{

    public const INPUT_LABELED = LabeledInput::class;

}
