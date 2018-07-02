<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Command;

use Schilffarth\CommandLineInterface\{
    Source\App,
    Source\Component\Argument\AbstractArgumentObject,
    Source\Component\Argument\ArgumentFactory,
    Source\Component\Interaction\Input\InputFactory,
    Source\Component\Interaction\Output\Output
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

    protected $argumentFactory;
    protected $inputFactory;
    protected $output;

    public function __construct(
        ArgumentFactory $argumentFactory,
        InputFactory $inputFactory,
        Output $output
    ) {
        $this->argumentFactory = $argumentFactory;
        $this->inputFactory = $inputFactory;
        $this->output = $output;
    }

    /**
     * Register arguments and associated handlers
     * For example usage @see AbstractCommand::setDefaultArgs()
     *
     * This method is run BEFORE arguments or commands are processed or triggered
     */
    abstract public function init(): void;

    /**
     * This method is run AFTER arguments and commands are processed or triggered
     */
    abstract public function run(): bool;

    /**
     * Register default arguments
     * For defining custom default arguments, use @see AbstractCommand::init()
     */
    public function setDefaultArgs(): void
    {
        /** COLORED OUTPUT - Whether to display console colored output */
        $disableColoredOutput = $this->argumentFactory->create(
            ArgumentFactory::ARGUMENT_SIMPLE,
            'color-disable',
            'Disable colors displayed with the console output. Recommended for Windows PowerShell.'
        );
        $disableColoredOutput->registerHandler([$this, 'disableColoredOutput']);
        // Set the key -99 in order to prioritize this argument as the very first
        $this->setArgument($disableColoredOutput, -99);

        /** HELP */
        $help = $this->argumentFactory->create(
            ArgumentFactory::ARGUMENT_SIMPLE,
            'help',
            'Get detailed help for the command. Displays further information and example usages.',
            'h'
        );
        $help->registerHandler([$this, 'triggerHelp']);
        // Set the key -98 in order to prioritize this argument secondly
        $this->setArgument($help, -98);

        /** VERBOSITY LEVELS */
        // Debug
        $debug = $this->argumentFactory->create(
            ArgumentFactory::ARGUMENT_SIMPLE,
            'debug',
            'Enable debugging. Displays all messages.',
            'd'
        );
        $debug->registerHandler([$this, 'setVerbosityDebug'])
            ->excludes('quiet');
        $this->setArgument($debug);
        // Quiet
        $quiet = $this->argumentFactory->create(
            ArgumentFactory::ARGUMENT_SIMPLE,
            'quiet',
            'Suppress both normal and debugging messages. Only errors will be outputted.',
            'q'
        );
        $quiet->registerHandler([$this, 'setVerbosityQuiet'])
            ->excludes('debug');
        $this->setArgument($quiet);
    }

    /**
     * Callback if --help is passed
     */
    public function triggerHelp(): void
    {
        $this->output->info($this->command)->nl();

        foreach ($this->arguments as $argument) {
            // Display detailed help for the command
            $aliasesStr = '';

            foreach ($argument->aliases as $alias) {
                $aliasesStr .= "  " . $alias;
            }

            // Supposed to be a grid ;-)
            $this->output->writeln(
                str_pad($argument->name, App::PAD_LENGTH['arguments'])
                . "\t" . str_pad($aliasesStr, App::PAD_LENGTH['aliases'])
                . "\t" . $argument->description
            );
        }

        // Do not run any
        exit;
    }

    /**
     * Callback if --debug is passed
     */
    public function setVerbosityDebug(): void
    {
        $this->output->verbosity = Output::DEBUG;
    }

    /**
     * Callback if --quiet is passed
     */
    public function setVerbosityQuiet(): void
    {
        $this->output->verbosity = Output::QUIET;
    }

    /**
     * Callback if --color-disable is passed
     */
    public function disableColoredOutput(): void
    {
        $this->output->colorDisabled = true;
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
        if ($order === null) {
            // Just apply argument to the end
            $this->arguments[] = $arg;
        } else {
            if (isset($this->arguments[$order])) {
                $this->error(sprintf('Cannot initialize argument %s properly ordered as %d. The given order has already been set.', $arg->name, $order));
                $this->arguments[] = $arg;
            } else {
                // Add argument at desired order
                $this->arguments[$order] = $arg;
            }
        }

        return $this;
    }

    /**
     * Retrieve an argument by its name
     */
    protected function getArgument(string $name): AbstractArgumentObject
    {
        $name = $this->getArgKeyByProperty('name', AbstractArgumentObject::trimProperty($name));

        if (!isset($this->arguments[$name])) {
            $this->error(sprintf('%s could not be found as a registered argument. Please make sure you do not have any typos.', $name));
        }

        return $this->arguments[$name];
    }

    /**
     * Remove / kill / destroy an argument
     * Should be used in @see AbstractCommand::init() only
     */
    protected function destroyArgument(string $name): self
    {
        unset($this->arguments[$this->getArgKeyByProperty('name', AbstractArgumentObject::trimProperty($name))]);

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
