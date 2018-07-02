<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source;

use Schilffarth\CommandLineInterface\{
    Source\Command\AbstractCommand,
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

    const PAD_LENGTH = [
        'commands' => 50,
        'arguments' => 30,
        'aliases' => 15
    ];

    /**
     * Used to calculate the duration of script execution
     */
    private $startTime = 0;

    /**
     * Whether the command has run successfully
     */
    private $success = false;

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
     * The called command
     * @var AbstractCommand
     */
    private $exec;

    /**
     * Console args
     * @var string[]
     */
    private $argv;

    private $errorHandler;
    private $output;
    private $objectManager;

    public function __construct(
        ErrorHandler $errorHandler,
        Output $output,
        ObjectManager $objectManager
    ) {
        $this->errorHandler = $errorHandler;
        $this->output = $output;
        $this->objectManager = $objectManager;
    }

    /**
     * Handle a command, general execution
     */
    public function execute(array $argv): void
    {
        register_shutdown_function([$this, 'end']);
        $this->startTime = microtime(true);
        $this->argv = $argv;

        if (strncmp(strtoupper(PHP_OS), 'WIN', 3) === 0) {
            $this->output->comment('Warning: CLI application run on windows might not support colored output! You can use --disable-colors');
        }

        // Initialize all commands
        $this->initializeCommands();
        // Initialize arguments for the called command
        $this->initializeArguments();
        // Run all initialized arguments
        $this->exec->triggerArguments();

        // Execute the command
        if ($this->exec->run()) {
            $this->success = true;
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
        if ($this->success) {
            $this->output->nl(2)->info('Success after ' . $this->getExecutionDuration());
        } else {
            $this->output->nl(2)->error('Failure after ' . $this->getExecutionDuration());
        }

        exit;
    }

    /**
     * How long the script has run yet (in seconds)
     */
    public function getExecutionDuration(): string
    {
        return round(microtime(true) - $this->startTime, 3) . ' seconds';
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
     * Initialize the arguments that have been passed to the console
     */
    private function initializeArguments(): void
    {
        if (empty($this->argv)) {
            $this->output->error('No command desired to be run.');
            $this->listRegisteredCommands();
            $this->success = true;
            exit;
        }

        // First entry is always supposed to be the run command
        $command = array_shift($this->argv);

        if (isset($this->commands[$command])) {
            $this->exec = $this->commands[$command];
            $this->exec->setDefaultArgs();

            // Let the command do stuff that needs to be done before the passed console arguments are processed
            $this->exec->init();

            $this->registerArguments();
            $this->validateArguments();
            $this->launchArguments();

            if (!empty($this->argv)) {
                $this->output->error('The following argument(s) could not be resolved:');
                foreach ($this->argv as $arg) {
                    $this->output->writeln($arg);
                }
            }
        } else {
            $this->output->error(sprintf('Command "%s" not found.', $command));
            $this->listRegisteredCommands();
            exit;
        }
    }

    /*
     * Process and register command args
     */
    private function registerArguments(): void
    {
        foreach ($this->exec->arguments as $argument) {
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
                    unset($this->argv[$found]);
                }
            }
        }
    }

    /**
     * Check requires / excludes / dependencies for the arguments
     * For more information:
     *
     * @see AbstractArgumentObject::requires()
     * @see AbstractArgumentObject::excludes()
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
                    $this->output->error(sprintf('Exclude %s for %s is not a valid argument.', $excl, $argument->name));
                    exit;
                }
                if ($this->exec->arguments[$exists]->passed) {
                    $this->output->error(sprintf('Cannot set both %s and %s! The arguments exclude each other.', $excl, $argument->name));
                    exit;
                }
            }

            foreach ($argument->requires as $req) {
                $exists = $this->exec->getArgKeyByProperty('name', $req);
                if ($exists === false) {
                    $this->output->error(sprintf('Require %s for %s is not a valid argument.', $excl, $argument->name));
                    exit;
                }
                if (!$this->exec->arguments[$exists]->passed) {
                    $this->output->error(sprintf('%s requires %s.', $argument->name, $req));
                    exit;
                }
            }
        }
    }

    /**
     * Launch all arguments that have been passed and validated
     */
    private function launchArguments(): void
    {
        foreach ($this->exec->arguments as $argument) {
            if ($argument->passed) {
                $argument->launch($this->argv);
            }
        }
    }

    /**
     * Lists all available commands that have been registered successfully
     */
    private function listRegisteredCommands(): void
    {
        $this->output->nl()->writeln('Here is a list of all available commands:')->nl();

        foreach ($this->commands as $command => $instance) {
            $this->output->writeln(str_pad(sprintf('<info>%s</info>', $command), self::PAD_LENGTH['commands']) . $instance->help);
        }
    }

}
