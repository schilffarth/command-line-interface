<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\Component\Argument;

use Schilffarth\Console\{
    Source\App\AbstractFactory,
    Source\Component\Argument\Types\Complex,
    Source\Component\Argument\Types\GlobalOption,
    Source\Component\Argument\Types\Flag
};

class ArgumentFactory extends AbstractFactory
{

    public const ARGUMENT_SIMPLE = Flag::class;

    public const ARGUMENT_COMPLEX = Complex::class;

    public const ARGUMENT_GLOBAL = GlobalOption::class;

}
