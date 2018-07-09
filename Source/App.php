<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source;

use Schilffarth\CommandLineInterface\{
    Exceptions\ArgumentNotFoundException,
    Source\Component\Argument\ArgumentHelper,
    Source\Component\Command\AbstractCommand,
    Source\Component\Argument\AbstractArgumentObject,
    Source\Component\Interaction\Output\Output
};
use Schilffarth\DependencyInjection\{
    Source\ObjectManager
};
use Schilffarth\Exception\{
    Handling\ErrorHandler
};

class App
{

    public const PAD_LENGTH_COMMAND = 50;

    /**
     * These arguments are treated as global arguments
     * They are processed and validated even before the run command is determined (example: --help)
     * @var AbstractArgumentObject[]
     */
    public static $appArguments = [];

    /**
     * The called command
     * @var AbstractCommand
     */
    public $exec;

    /**
     * Include paths to check in order to create a list of all available commands
     * @var string[]
     * ['file_path.php' => 'PhpClass']
     */
    private $includes = [];

    /**
     * All initialized commands @see App::initializeCommands()
     * @var AbstractCommand[]
     */
    private $commands = [];

    /**
     * Console args
     * @var string[]
     */
    private $argv;

    private $argumentHelper;
    private $errorHandler;
    private $output;
    private $objectManager;
    private $state;

    public function __construct(
        ArgumentHelper $argumentHelper,
        ErrorHandler $errorHandler,
        Output $output,
        ObjectManager $objectManager,
        State $state
    ) {
        $this->argumentHelper = $argumentHelper;
        $this->errorHandler = $errorHandler;
        $this->output = $output;
        $this->objectManager = $objectManager;
        $this->state = $state;
    }

    /**
     * Handle a command, general execution
     */
    public function execute(array $argv): void
    {
        register_shutdown_function([$this, 'end']);
        $this->argv = $argv;

        if (strncmp(strtoupper(PHP_OS), 'WIN', 3) === 0) {
            $this->output->writeln('Warning: CLI application run on windows might not support colored output! You can use --color-disable');
        }

        // Initialize all commands
        $this->initializeCommands();
        // Initialize arguments for the called command
        $this->initializeArguments();
        // Trigger all initialized arguments, APP-scope first
        $this->triggerAppArgs();
        // Run all initialized arguments on COMMAND-scope
        $this->exec->triggerArguments();

        // Execute the command
        if ($this->exec->run()) {
            State::$success = true;
        }
    }

    /**
     * Add an include path for scanning / initializing available commands
     */
    public function includeCommandDir(string $path, string $namespace): self
    {
        $this->includes[$path] = $namespace;

        return $this;
    }

    /**
     * It's recommended to add your command to an existing command directory
     * Anyway, if you really want to add a single command manually to the App-instance, use this method
     */
    public function addCommand(AbstractCommand $command): self
    {
        $this->commands[] = $command;

        return $this;
    }

    /**
     * Command finished
     */
    public function end(): void
    {
        if (State::$success) {
            $this->output->nl()->info('Success after ' . $this->state->getExecutionDuration(), Output::QUIET);
        } else {
            $this->output->nl()->error('Failure after ' . $this->state->getExecutionDuration());
        }

        exit;
    }

    /**
     * Scan all include paths and initialize all valid commands
     */
    private function initializeCommands(): void
    {
        foreach ($this->includes as $includePath => $namespace) {
            foreach (scandir($includePath) as $path) {
                $file = $includePath . DIRECTORY_SEPARATOR . $path;
                if (is_file($file)) {
                    try {
                        $this->registerCommand($file, $namespace);
                    } catch (\Exception $e) {
                        $this->errorHandler->exit($e);
                        exit;
                    }
                }
            }
        }
    }

    /**
     * Register the currently iterated command
     */
    private function registerCommand(string $file, string $namespace): void
    {
        require_once $file;

        $register = $this->objectManager->getSingleton($namespace . '\\' . basename($file, '.php'));

        $this->commands[$register->command] = $register;
    }

    /**
     * Initialize the arguments that have been passed to the console and create all available args from each commands
     */
    private function initializeArguments(): void
    {
        if (empty($this->argv)) {
            $this->output->error('No command desired to be run.');
            $this->listRegisteredCommands();
            State::$success = true;
            exit;
        }

        $this->splitAliases();

        // Process all commands on APP-scope
        foreach ($this->commands as $command) {
            $command->initAppArgs();
        }
        foreach (self::$appArguments as &$argument) {
            $this->registerArgument($argument);
        }

        // Everything at COMMAND-scope
        $this->defineRunCommand();
        $this->exec->initCommandArgs();
        foreach ($this->exec->arguments as &$argument) {
            $this->registerArgument($argument);
        }

        try {
            $this->validateArguments();
        } catch (\Exception $e) {
            $this->errorHandler->exit($e);
        }

        if (!empty($this->argv)) {
            $this->output->error('The following argument(s) could not be resolved:');
            foreach ($this->argv as $arg) {
                $this->output->writeln($arg, Output::QUIET);
            }
        }
    }

    /**
     * The run command is stored in @see App::exec
     */
    private function defineRunCommand(): void
    {
        // First entry is always supposed to be the run command
        $command = array_shift($this->argv);

        if (isset($this->commands[$command])) {
            $this->exec = $this->commands[$command];
        } else {
            $this->output->error(sprintf('Command "%s" not found.', $command));
            $this->listRegisteredCommands();
            exit;
        }
    }

    /*
     * Process and register single argument
     */
    private function registerArgument(AbstractArgumentObject &$argument): void
    {
        $scans = [$argument->name];

        foreach ($argument->aliases as $alias) {
            $scans[] = $alias;
        }

        foreach ($scans as $scan) {
            // Scan whether the argument has been passed
            $found = array_search($scan, $this->argv, true);
            if ($found !== false) {
                if ($argument->passed) {
                    // Argument is passed multiple times
                    $this->output->error(sprintf('Argument %s is passed more than once. Please make sure your command does not contain any typos.', $argument->name));
                    exit;
                }

                $argument->passed = true;
                $argument->consoleArgvKey = $found;
                unset($this->argv[$found]);
            }
        }

        $argument->launch($this->argv);
    }

    /**
     * If combined aliases have been passed, split and register them as each single alias and unset original combined
     * alias
     */
    private function splitAliases(): void
    {
        foreach ($this->argv as $key => $arg) {
            if ($this->argumentHelper->argIsAlias($arg) && strlen($arg) > 2) {
                // It's a combined alias, such as -dh, it will be split to separate arguments -d -h
                $sequences = str_split($arg);

                // First entry is the hyphen / alias identifier
                unset($sequences[0]);
                unset($this->argv[$key]);

                foreach ($sequences as $sequence) {
                    $this->argv[] = $this->argumentHelper->trimProperty($sequence, ArgumentHelper::STR_LEN_ALIAS);
                }
            }
        }
    }

    /**
     * Check requires / excludes / dependencies for the arguments
     * For more information:
     * @see AbstractArgumentObject::requires()
     * @see AbstractArgumentObject::excludes()
     * @throws ArgumentNotFoundException
     */
    private function validateArguments(): void
    {
        foreach ($this->exec->arguments as $argument) {
            if (!$argument->passed) {
                continue;
            }

            foreach ($argument->excludes as $excl) {
                $exists = $this->exec->getArgKeyByProperty('name', $excl);
                if ($exists === false) {
                    throw new ArgumentNotFoundException(
                        sprintf('Exclude %s for %s is not a valid argument.', $excl, $argument->name)
                    );
                }
                if ($this->exec->arguments[$exists]->passed) {
                    $this->output->error(sprintf('Argument %s excludes argument %s, cannot set both.', $excl, $argument->name));
                    exit;
                }
            }

            foreach ($argument->requires as $req) {
                $exists = $this->exec->getArgKeyByProperty('name', $req);
                if ($exists === false) {
                    throw new ArgumentNotFoundException(
                        sprintf('Require %s for %s is not a valid argument.', $req, $argument->name)
                    );
                }
                if (!$this->exec->arguments[$exists]->passed) {
                    $this->output->error(sprintf('Argument %s requires argument %s to be passed.', $argument->name, $req));
                    exit;
                }
            }
        }
    }

    private function triggerAppArgs(): void
    {
        foreach (self::$appArguments as &$argument) {
            if ($argument->passed) {
                $argument->trigger();
            }
        }
    }

    /**
     * Lists all available commands that have been registered successfully
     */
    private function listRegisteredCommands(): void
    {
        $this->output->nl()->writeln('Available commands:')->nl();

        foreach ($this->commands as $command => $instance) {
            $this->output->writeln(str_pad(sprintf('<info>%s</info>', $command), self::PAD_LENGTH_COMMAND) . $instance->help, Output::QUIET);
        }

        $this->argumentHelper->outputAppScopeArgumentsHelp();
    }

}
