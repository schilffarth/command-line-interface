<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\Component\Argument;

use Schilffarth\Console\{
    Source\Component\Argument\Types\Complex,
    Source\Component\Interaction\Output\Output
};

/**
 * Create an argument object, including its handlers
 *
 * Please note that arguments are always supposed to start with double hyphens: --
 * If the argument starts with a single hyphen, it is assumed to be an alias for a valid and registered argument
 * For sticking to this convention and not confusing anyone, use @see ArgumentHelper::trimProperty()
 */
abstract class AbstractArgumentObject
{

    /**
     * The name / code of the argument
     */
    protected $name = '';

    /**
     * The arguments description is displayed when your command is called with the parameter --help or its alias -h
     */
    protected $description = '';

    /**
     * All registered aliases for the argument
     * @var string[]
     */
    protected $aliases = [];

    /**
     * Specified arguments that will be excluded by the usage of this argument
     * @var string[]
     */
    protected $excludes = [];

    /**
     * Arguments that are required to be present in combination with this argument
     * @var string[]
     */
    protected $requires = [];

    /**
     * Boolean, whether the command is called with this argument or not
     */
    protected $passed = false;

    /**
     * If the argument is passed, this property is set to the argument's key of the global $argv array
     */
    protected $consoleArgvKey = 0;

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
    protected $handler = [];

    protected $argumentHelper;
    protected $output;

    public function __construct(
        ArgumentHelper $argumentHelper,
        Output $output
    ) {
        $this->argumentHelper = $argumentHelper;
        $this->output = $output;
    }

    /**
     * Initialize / build up your argument - $this->passed is set before launch is called
     * With this function you can introduce special logic for your argument, such as complex type handling
     * For an example @see Complex::launch()
     */
    abstract public function launch(): void;

    /**
     * Argument should be created with @see ArgumentFactory::create()
     * Aliases must match size of 1 character (excluding hyphen char)
     */
    public function create(string $name, string $description, string ...$aliases)
    {
        $this->setName($name)
            ->setDescription($description);

        foreach ($aliases as $alias) {
            $this->addAlias($alias);
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
     * Set the name of your argument
     */
    public function setName(string $name): self
    {
        $this->name = $this->argumentHelper->trimProperty($name);

        return $this;
    }

    /**
     * Retrieve the argument name, preceded by double hyphen
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setAlias(string $alias): self
    {
        $this->aliases[] = $alias;

        return $this;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Add an alias for the argument
     */
    public function addAlias(string $alias): self
    {
        $trimmedAlias = $this->argumentHelper->trimProperty($alias, ArgumentHelper::STR_LEN_ALIAS);

        if (strlen($trimmedAlias) > 2) {
            $this->output->error(sprintf('Failed to create argument %s. Passed alias %s exceeds maximum size of one character (excluding hyphen identifier).', $this->getName(), $trimmedAlias));
            exit;
        } elseif (strlen($trimmedAlias) < 2) {
            $this->output->error(sprintf('Failed to create argument %s. Passed alias %s must match size of one character (excluding hyphen identifier).', $this->getName(), $trimmedAlias));
            exit;
        }

        $this->setAlias($trimmedAlias);

        return $this;
    }

    /**
     * Removes an alias from the argument
     * This can be useful when you want to remove an predefined alias for example debug mode (d) and assign the alias d
     * to a custom argument that you've set for your command
     */
    public function removeAlias(string $alias): self
    {
        $key = array_search($alias, $this->aliases);

        if ($key !== false) {
            // Alias exists, remove it
            unset($this->aliases[$key]);
        }

        return $this;
    }

    /**
     * Set an exclude for this argument - None of the initialized exclude arguments are valid to be passed
     */
    public function addExcludedArgument(string $argName): self
    {
        $this->excludes[] = $this->argumentHelper->trimProperty($argName);

        return $this;
    }

    public function getExcludes(): array
    {
        return $this->excludes;
    }

    /**
     * Set a requirement when this argument is passed
     */
    public function addRequiredArgument(string $argName): self
    {
        $this->requires[] = $this->argumentHelper->trimProperty($argName);

        return $this;
    }

    public function getRequires(): array
    {
        return $this->requires;
    }

    /**
     * Update whether argument has been passed or not
     */
    public function setPassed(bool $passed): self
    {
        $this->passed = $passed;

        return $this;
    }

    /**
     * Check whether the argument has been passed to the run command
     */
    public function isPassed(): bool
    {
        return $this->passed;
    }

    public function setConsoleArgvKey(int $consoleArgvKey): self
    {
        $this->consoleArgvKey = $consoleArgvKey;

        return $this;
    }

    /**
     * The key of this argument in the global $argv array
     */
    public function getConsoleArgvKey(): int
    {
        return $this->consoleArgvKey;
    }

    public function getScope(): int
    {
        return $this->scope;
    }

    /**
     * Whether the arguments scope is APP or COMMAND
     */
    public function isGlobalOption(): bool
    {
        return $this->scope === ArgumentHelper::ARGUMENT_SCOPE_GLOBAL_OPTION;
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

}
