<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Component\Argument;

use Schilffarth\CommandLineInterface\{
    Source\AbstractFactory,
    Source\Component\Argument\Types\ComplexArgument,
    Source\Component\Argument\Types\GlobalArgument,
    Source\Component\Argument\Types\SimpleArgument
};

class ArgumentFactory extends AbstractFactory
{

    public const ARGUMENT_SIMPLE = SimpleArgument::class;

    public const ARGUMENT_COMPLEX = ComplexArgument::class;

    public const ARGUMENT_GLOBAL = GlobalArgument::class;

}
