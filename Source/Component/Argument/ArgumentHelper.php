<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Component\Argument;

use Schilffarth\CommandLineInterface\Source\App;
use Schilffarth\CommandLineInterface\Source\Component\Interaction\Output\Output;

class ArgumentHelper
{

    public const PAD_LENGTH_ARGUMENT = 30;

    public const PAD_LENGTH_ALIAS = 20;

    /**
     * Arguments defined at scope APP will be processed before any command-related stuff is done
     */
    public const ARGUMENT_SCOPE_APP = 1;

    /**
     * Arguments defined at scope COMMAND will be processed after the run command has been calculated and arguments have
     * been validated
     */
    public const ARGUMENT_SCOPE_COMMAND = 2;

    /**
     * Used for unified and consistent format of @see AbstractArgumentObject::name
     */
    public const STR_LEN_CODE = 2;

    /**
     * Used for unified and consistent format for each alias of @see AbstractArgumentObject::aliases
     */
    public const STR_LEN_ALIAS = 1;

    private $output;

    public function __construct(
        Output $output
    ) {
        $this->output = $output;
    }

    /**
     * Retrieve codes for argument aliases and names without applying preceding hyphens yourself
     * This method provides unified and consistent format for both aliases and argument names
     */
    public function trimProperty(string $name, int $type = self::STR_LEN_CODE): string
    {
        if ($type === self::STR_LEN_CODE) {
            $cmp = '--';
        } else {
            // $type === self::STR_LEN_ALIAS
            $cmp = '-';
        }

        return strncmp($name, $cmp, $type) === 0 ? $name : $cmp . $name;
    }

    /**
     * Aliases begin with single hyphen '-', argument names begin with double hyphen '--'
     */
    public function argIsAlias(string $name): bool
    {
        if (strncmp($name, '-', self::STR_LEN_ALIAS) === 0 && strncmp($name, '--', self::STR_LEN_CODE) !== 0) {
            return true;
        }

        return false;
    }

    /**
     * Display a list describing all general arguments on APP-scope
     */
    public function outputAppScopeArgumentsHelp(): void
    {
        $this->output->nl()->writeln('Console options:')->nl();

        foreach (App::$appArguments as $argument) {
            $this->argumentGridRow($argument);
        }
    }

    /**
     * todo Move this output style to an output object
     * Outputs a line in the argument-grid-style
     */
    public function argumentGridRow(AbstractArgumentObject $argument): void
    {
        $aliasesStr = "\t";

        foreach ($argument->aliases as $alias) {
            $aliasesStr .= '  ' . $alias;
        }

        $aliasesStr .= "\t";

        // Supposed to be a grid ;-)
        $this->output->writeln(
            str_pad(sprintf('<info>%s</info>', $argument->name), self::PAD_LENGTH_ARGUMENT)
            . str_pad($aliasesStr, self::PAD_LENGTH_ALIAS)
            . $argument->description
        );
    }

}
