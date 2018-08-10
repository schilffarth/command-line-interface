<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\Component\Command;

use Schilffarth\Console\{
    Exception\ArgumentNotFoundException,
    Source\App\Application,
    Source\Component\Argument\AbstractArgumentObject,
    Source\Component\Argument\ArgumentFactory,
    Source\Component\Argument\ArgumentHelper,
    Source\Component\Interaction\Input\InputFactory,
    Source\Component\Interaction\Output\Output,
    Source\Component\Interaction\Output\OutputFactory,
    Source\App\ErrorHandler
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
    protected $errorHandler;
    protected $inputFactory;
    protected $output;
    protected $outputFactory;

    public function __construct(
        Application $app,
        ArgumentFactory $argumentFactory,
        ArgumentHelper $argumentHelper,
        ErrorHandler $errorHandler,
        InputFactory $inputFactory,
        Output $output,
        OutputFactory $outputFactory
    ) {
        $this->app = $app;
        $this->argumentFactory = $argumentFactory;
        $this->argumentHelper = $argumentHelper;
        $this->errorHandler = $errorHandler;
        $this->inputFactory = $inputFactory;
        $this->output = $output;
        $this->outputFactory = $outputFactory;
    }

    /**
     * This method is run AFTER arguments and commands are processed or triggered
     */
    abstract public function run(): bool;

    /**
     * Register arguments and associated handlers for COMMAND scope
     * This method is run BEFORE command-specific arguments or commands themselves are processed or triggered
     */
    public function initializeArguments(): void
    {
    }

    /**
     * Trigger all initialized arguments
     */
    public function triggerArguments(): void
    {
        foreach ($this->arguments as $argument) {
            if ($argument->isPassed()) {
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
        // Get the container for given argument
        if ($arg->isGlobalOption()) {
            $container = &$this->app->getGlobalOptionsContainer()->getContainer();

            foreach ($container as $argument) {
                if ($argument->getName() === $arg->getName()) {
                    // The argument has already been registered, skip it
                    return $this;
                }
            }
        } else {
            $container = &$this->app->getCommandsContainer()->getRunCommand()->arguments;
        }

        // Set arg
        if ($order === null) {
            // Just apply argument to the end
            $container[] = $arg;
        } else {
            if (isset($container[$order])) {
                $this->error(sprintf('Cannot initialize argument %s properly ordered as %d. The given order has already been set.', $arg->getName(), $order));
                $container[] = $arg;
            } else {
                // Add argument at desired order
                $container[$order] = $arg;
            }
        }

        return $this;
    }

    /**
     * Retrieve an argument by its name
     *
     * @throws \Schilffarth\Console\Exception\ArgumentNotFoundException
     */
    protected function getArgument(string $name): AbstractArgumentObject
    {
        $name = $this->getArgKeyByProperty('name', $this->argumentHelper->trimProperty($name));

        if (!isset($this->arguments[$name])) {
            throw new ArgumentNotFoundException(
                sprintf('%s could not be found as a registered argument. Please make sure you do not have any typos.', $name)
            );
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
