<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Component\Argument\Types;

use Schilffarth\CommandLineInterface\{
    Source\Component\Argument\AbstractArgumentObject,
    Source\Component\Argument\ArgumentHelper
};

/**
 * SimpleArgument arguments represent levers and present a boolean as value - Whether they have been passed or not
 *
 * Examples:
 * --debug  -d      Enables verbose output
 * --help   -h      Triggers the help message for the run command
 */
class GlobalArgument extends AbstractArgumentObject
{

    protected $scope = ArgumentHelper::ARGUMENT_SCOPE_APP;

    public function launch(array &$argv): void {}

}
