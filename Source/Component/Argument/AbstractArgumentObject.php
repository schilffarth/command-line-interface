<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Component\Argument;

use Schilffarth\CommandLineInterface\{
    Source\App,
    Source\Component\Argument\Types\ComplexArgument,
    Source\Component\Command\AbstractCommand,
    Source\Component\Interaction\Output\Output
};

/**
 * Create an argument object, including its handlers
 *
 * Please note that arguments are always supposed to start with double hyphens: --
 * If the argument starts with a single hyphen, it is assumed to be an alias for a valid and registered argument
 * For sticking to this convention and not confusing anyone, use @see ArgumentHelper::trimProperty
 */
abstract class AbstractArgumentObject
{

    /**
     * Command where this argument is initialized for
     * @var AbstractCommand
     */
    public $command;

    /**
     * Command object this argument has been defined at, usually same as @see App::exec
     * @var AbstractArgumentObject[]
     */
    public $argContainer = [];

    /**
     * The name / code of the argument
     */
    public $name = '';

    /**
     * The arguments description is displayed when your command is called with the parameter --help or its alias -h
     */
    public $description = '';

    /**
     * All registered aliases for the argument
     * @var string[]
     */
    public $aliases = [];

    /**
     * Specified arguments that will be excluded by the usage of this argument
     * @var string[]
     */
    public $excludes = [];

    /**
     * Arguments that are required to be present in combination with this argument
     * @var string[]
     */
    public $requires = [];

    /**
     * Boolean, whether the command is called with this argument or not
     */
    public $passed = false;

    /**
     * If the argument is passed, this property is set to the argument's key of the global $argv array
     */
    public $consoleArgvKey = 0;

    /**
     * Scope of the argument, arguments bound to the app scope will be processed before the desired command is
     * determined or any other arguments are processed
     * Examples: Output verbosity levers (--quiet, --debug), --help
     */
    protected $scope = ArgumentHelper::ARGUMENT_SCOPE_COMMAND;

    /**
     * Contains all registered callbacks
     * Handlers will be called one by one, unless a priority has been defined
     * @var callable[]
     */
    private $handler = [];

    protected $app;
    protected $argumentHelper;
    protected $output;

    public function __construct(
        App $app,
        ArgumentHelper $argumentHelper,
        Output $output
    ) {
        $this->app = $app;
        $this->argumentHelper = $argumentHelper;
        $this->output = $output;
    }

    /**
     * Initialize / build up your argument - This method is only called if $this->passed is set to TRUE
     * With this function you can introduce special logic for your argument, such as complex type handling
     * For an example @see ComplexArgument::launch()
     */
    abstract public function launch(array &$argv): void;

    /**
     * Argument should be created with @see ArgumentFactory::create()
     * Aliases must match size of 1 character (excluding hyphen char)
     */
    public function create(string $name, string $description, string ...$aliases)
    {
        $this->name = $this->argumentHelper->trimProperty($name);
        $this->description = $description;

        foreach ($aliases as $alias) {
            $trimmedAlias = $this->argumentHelper->trimProperty($alias, ArgumentHelper::STR_LEN_ALIAS);

            if (strlen($trimmedAlias) > 2) {
                $this->output->error(sprintf('Failed to create argument %s. Passed alias %s exceeds maximum size of one character (excluding hyphen identifier).', $this->name, $trimmedAlias));
                exit;
            } elseif (strlen($trimmedAlias) < 2) {
                $this->output->error(sprintf('Failed to create argument %s. Passed alias %s must match size of one character (excluding hyphen identifier).', $this->name, $trimmedAlias));
                exit;
            }

            $this->aliases[] = $trimmedAlias;
        }
    }

    /**
     * Register a function that is called whenever the argument is supplied
     * You can set up multiple handlers with priority - Handlers can be added after the argument has been initialized
     */
    public function registerHandler(callable $handler, int $priority = 0): self
    {
        if ($priority === 0) {
            $this->handler[] = $handler;
        } else {
            // Add the handler, but make sure to keep existing handlers and not override any
            if (isset($this->handler[$priority])) {
                $this->output->error(sprintf('Cannot initialize argument handler properly at index %d. The given priority has already been set.', $priority));
                $this->handler[] = $handler;
            } else {
                $this->handler[$priority] = $handler;
            }
        }

        return $this;
    }

    /**
     * Set an exclude for this argument - None of the initialized exclude arguments are valid to be passed
     */
    public function excludes(string $argName): self
    {
        $this->excludes[] = $this->argumentHelper->trimProperty($argName);

        return $this;
    }

    /**
     * Set a requirement when this argument is passed
     */
    public function requires(string $argName): self
    {
        $this->requires[] = $this->argumentHelper->trimProperty($argName);

        return $this;
    }

    /**
     * Trigger / call all registered handlers
     */
    public function trigger(): void
    {
        foreach ($this->handler as $handle) {
            call_user_func($handle);
        }
    }

    /**
     * Whether the arguments scope is APP or COMMAND
     */
    public function isScopeApp(): bool
    {
        return $this->scope === ArgumentHelper::ARGUMENT_SCOPE_APP;
    }

    /**
     * Retrieve the arguments container (app or command scoped)
     */
    public function getArgContainer(): array
    {
        if (!$this->argContainer) {
            if ($this->isScopeApp()) {
                $this->argContainer = &App::$appArguments;
            } else {
                $this->argContainer = &$this->command->arguments;
            }
        }

        return $this->argContainer;
    }

}
