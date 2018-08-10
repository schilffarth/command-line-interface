<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\Component\Argument\Types;

use Schilffarth\Console\{
    Source\Component\Argument\AbstractArgumentObject
};

/**
 * Flag arguments represent levers and present a boolean as value, whether they have been passed or not, nothing else
 */
class Flag extends AbstractArgumentObject
{

    public function launch(): void {}

}
