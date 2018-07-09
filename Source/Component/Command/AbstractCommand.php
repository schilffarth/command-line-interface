<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Component\Command;

use Schilffarth\CommandLineInterface\{
    Exceptions\ArgumentNotFoundException,
    Source\App,
    Source\Component\Argument\AbstractArgumentObject,
    Source\Component\Argument\ArgumentFactory,
    Source\Component\Argument\ArgumentHelper,
    Source\Component\Argument\Types\SimpleArgument,
    Source\Component\Interaction\Input\InputFactory,
    Source\Component\Interaction\Output\Output,
    Source\State
};

/**
 * Extend this class and define your command by the given properties.
 */
abstract class AbstractCommand
{

    /**
     * Command initialization
     */
    public $command = '';

    /**
     * General message for your command that is shown in the summary help message of all available commands
     */
    public $help = '';

    /**
     * Arguments executed @see AbstractCommand::triggerArguments()
     * @var AbstractArgumentObject[]
     */
    public $arguments = [];

    protected $app;
    protected $argumentFactory;
    protected $argumentHelper;
    protected $inputFactory;
    protected $output;

    public function __construct(
        App $app,
        ArgumentFactory $argumentFactory,
        ArgumentHelper $argumentHelper,
        InputFactory $inputFactory,
        Output $output
    ) {
        $this->app = $app;
        $this->argumentFactory = $argumentFactory;
        $this->argumentHelper = $argumentHelper;
        $this->inputFactory = $inputFactory;
        $this->output = $output;
    }

    /**
     * This method is run AFTER arguments and commands are processed or triggered
     */
    abstract public function run(): bool;

    /**
     * Register arguments and associated handlers for APP scope
     * This method is run BEFORE command-specific arguments or commands themselves are processed or triggered
     */
    public function initAppArgs(): void
    {
        /** COLORED OUTPUT - Whether to display console colored output */
        /** @var SimpleArgument $disableColoredOutput */
        $disableColoredOutput = $this->argumentFactory->create(
            ArgumentFactory::ARGUMENT_GLOBAL,
            'color-disable',
            'Disable colors displayed with the console output. Recommended for Windows PowerShell.'
        );
        $disableColoredOutput->registerHandler([$this, 'disableColoredOutput']);
        // Set the key -99 in order to prioritize this argument as the very first
        $this->setArgument($disableColoredOutput, -99);

        /** HELP */
        /** @var SimpleArgument $help */
        $help = $this->argumentFactory->create(
            ArgumentFactory::ARGUMENT_GLOBAL,
            'help',
            'Get detailed help for the command. Displays further information and example usages.',
            'h'
        );
        $help->registerHandler([$this, 'triggerHelp']);
        // Set the key -98 in order to prioritize this argument secondly
        $this->setArgument($help, -98);

        /** VERBOSITY LEVELS */
        /** @var SimpleArgument $debug */
        // Debug
        $debug = $this->argumentFactory->create(
            ArgumentFactory::ARGUMENT_GLOBAL,
            'debug',
            'Enable debugging. Displays all messages.',
            'd'
        );
        $debug->registerHandler([$this, 'setVerbosityDebug'])
            ->excludes('quiet');
        $this->setArgument($debug);
        /** @var SimpleArgument $quiet */
        // Quiet
        $quiet = $this->argumentFactory->create(
            ArgumentFactory::ARGUMENT_GLOBAL,
            'quiet',
            'Suppress both normal and debugging messages. Only errors will be outputted.',
            'q'
        );
        $quiet->registerHandler([$this, 'setVerbosityQuiet'])
            ->excludes('debug');
        $this->setArgument($quiet);
    }

    /**
     * Register arguments and associated handlers for COMMAND scope
     */
    public function initCommandArgs(): void
    {
    }

    /**
     * Callback if --help is passed
     */
    public function triggerHelp(): void
    {
        $this->output->nl()->writeln('Command arguments:')->nl();

        foreach ($this->arguments as $argument) {
            $this->argumentHelper->argumentGridRow($argument);
        }

        $this->argumentHelper->outputAppScopeArgumentsHelp();

        // Do not run any
        State::$success = true;
        exit;
    }

    /**
     * Callback if --debug is passed
     */
    public function setVerbosityDebug(): void
    {
        State::$verbosity = Output::DEBUG;
    }

    /**
     * Callback if --quiet is passed
     */
    public function setVerbosityQuiet(): void
    {
        State::$verbosity = Output::QUIET;
    }

    /**
     * Callback if --color-disable is passed
     */
    public function disableColoredOutput(): void
    {
        State::$colorDisabled = true;
    }

    /**
     * Trigger all initialized arguments
     */
    public function triggerArguments(): void
    {
        foreach ($this->arguments as $argument) {
            if ($argument->passed) {
                $argument->trigger();
            }
        }
    }

    /**
     * Retrieve the key for the desired argument
     * @param string $property The property to filter
     * @param string $find The value to find in $property
     *
     * Example:
     *
     * $property = 'name';
     * $find = 'help';
     *
     * => This will try to find an argument with the name 'help' - Match would be Argument->name === 'help';
     *
     * @return false|int|mixed The key of the found argument
     */
    public function getArgKeyByProperty(string $property, string $find)
    {
        return array_search($find, array_combine(
            array_keys($this->arguments),
            array_column($this->arguments, $property)
        ));
    }

    /**
     * Set an argument for the command
     * You can set the process order with $order
     */
    protected function setArgument(AbstractArgumentObject $arg, int $order = null): self
    {
        if ($arg->isScopeApp()) {
            $arg->argContainer = &App::$appArguments;
        } else {
            $arg->argContainer = &$this->arguments;
        }

        $arg->command = $this;

        if ($order === null) {
            // Just apply argument to the end
            $arg->argContainer[] = $arg;
        } else {
            if (isset($arg->argContainer[$order])) {
                $this->error(sprintf('Cannot initialize argument %s properly ordered as %d. The given order has already been set.', $arg->name, $order));
                $arg->argContainer[] = $arg;
            } else {
                // Add argument at desired order
                $arg->argContainer[$order] = $arg;
            }
        }

        return $this;
    }

    /**
     * Retrieve an argument by its name
     * @throws ArgumentNotFoundException
     */
    protected function getArgument(string $name): AbstractArgumentObject
    {
        $name = $this->getArgKeyByProperty('name', $this->argumentHelper->trimProperty($name));

        if (!isset($this->arguments[$name])) {
            throw new ArgumentNotFoundException(sprintf('%s could not be found as a registered argument. Please make sure you do not have any typos.', $name));
        }

        return $this->arguments[$name];
    }

    /**
     * Remove / kill / destroy an argument
     */
    protected function destroyArgument(string $name): self
    {
        $trimmedName = $this->argumentHelper->trimProperty($name);
        $key = $this->getArgKeyByProperty('name', $trimmedName);

        if ($key === false) {
            $this->output->error(sprintf('Argument %s is not set and cannot be destroyed!', $trimmedName));
            exit;
        } else {
            unset($this->arguments[$key]);
        }

        return $this;
    }

    /*******************
     * Console output *
     ******************/

    protected function writeln(string $message = '', $verbosity = Output::NORMAL): Output
    {
        return $this->output->writeln($message, $verbosity);
    }

    protected function error(string $message = '', $verbosity = Output::QUIET): Output
    {
        return $this->output->error($message, $verbosity);
    }

    protected function info(string $message = '', $verbosity = Output::NORMAL): Output
    {
        return $this->output->info($message, $verbosity);
    }

    protected function debug(string $message = '', $verbosity = Output::DEBUG): Output
    {
        return $this->output->debug($message, $verbosity);
    }

}
