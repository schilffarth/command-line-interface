<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Component\Argument;

use Schilffarth\CommandLineInterface\{
    Source\App,
    Source\Component\Interaction\Output\Output,
    Source\Component\Interaction\Output\OutputFactory,
    Source\Component\Interaction\Output\Types\GridOutput
};

class ArgumentHelper
{

    /**
     * Used for "grid"-style output on argument help message
     */
    public const PAD_LENGTH_ARGUMENT = 20;
    public const PAD_LENGTH_ALIAS = 10;

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
    private $outputFactory;

    public function __construct(
        Output $output,
        OutputFactory $outputFactory
    ) {
        $this->output = $output;
        $this->outputFactory = $outputFactory;
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
        $grid = $this->createArgumentGrid();

        foreach (App::$appArguments as $argument) {
            $grid = $this->argumentGridRow($argument, $grid);
        }

        $this->output->nl()->info('Console options:')->nl();
        $grid->display();
    }

    /**
     * Create the basic grid structure for an argument table
     */
    public function createArgumentGrid(): GridOutput
    {
        /** @var GridOutput $grid */
        $grid = $this->outputFactory->create(OutputFactory::OUTPUT_GRID);

        $grid->addColumn('name', 'Argument', self::PAD_LENGTH_ARGUMENT)
            ->addColumn('aliases', 'Aliases', self::PAD_LENGTH_ALIAS)
            ->addColumn('description', 'Description')
            ->suppressColumnLabels()
            ->addColorScheme('name', 'comment', '')
            ->addColorScheme('aliases', 'comment', '')
            ->addColorScheme('description', 'comment', '');

        return $grid;
    }

    /**
     * Outputs a line in the argument-grid-style
     */
    public function argumentGridRow(AbstractArgumentObject $argument, GridOutput $grid): GridOutput
    {
        $aliasesStr = '';

        foreach ($argument->aliases as $alias) {
            $aliasesStr .= $alias . '  ';
        }

        $grid->addRow([
            'name' => $argument->name,
            'aliases' => $aliasesStr,
            'description' => $argument->description
        ]);

        return $grid;
    }

}
