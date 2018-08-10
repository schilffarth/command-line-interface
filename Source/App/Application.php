<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\App;

use Schilffarth\Console\{
    Source\App\Container\Commands,
    Source\App\Container\GlobalOptions,
    Source\Component\Argument\ArgumentHelper,
    Source\Component\Command\AbstractCommand,
    Source\Component\Interaction\Output\Output,
    Source\Component\Interaction\Output\OutputFactory,
    Source\Component\Interaction\Output\Types\Grid
};

/**
 * todo
 * - Place any "core" commands to Source/Data/Commands
 * - Add auto-completion: @see https://github.com/yiisoft/yii2/issues/7974
 * - Add a "find-command" feature that suggests commands if the desired command was not found
 */
class Application extends Containers
{

    /**
     * Used for "grid"-style output on command list
     */
    public const PAD_LENGTH_COMMAND = 25;

    private $argumentHelper;
    private $errorHandler;
    private $output;
    private $outputFactory;
    private $state;

    public function __construct(
        ArgumentHelper $argumentHelper,
        Commands $commands,
        ErrorHandler $errorHandler,
        GlobalOptions $globalOptions,
        Output $output,
        OutputFactory $outputFactory,
        State $state
    ) {
        parent::__construct($commands, $globalOptions);

        $this->argumentHelper = $argumentHelper;
        $this->errorHandler = $errorHandler;
        $this->output = $output;
        $this->outputFactory = $outputFactory;
        $this->state = $state;
    }

    /**
     * Get an application instance
     */
    public static function create(): self
    {
        $objectManager = new \Schilffarth\Console\DependencyInjection\ObjectManager();
        /** @var Application $app */
        $app = $objectManager->getSingleton(self::class);

        return $app;
    }

    /**
     * Display a list describing all general arguments on APP-scope
     */
    public function outputGlobalOptionsHelp(): void
    {
        $grid = $this->argumentHelper->createArgumentGrid();

        foreach ($this->getGlobalOptionsContainer()->getContainer() as $argument) {
            $grid = $this->argumentHelper->argumentGridRow($argument, $grid);
        }

        $this->output->nl()->info('Console options:')->nl();
        $grid->display();
    }

    /**
     * Handle a command, general execution
     */
    public function execute(array $argv): void
    {
        register_shutdown_function([$this, 'end']);
        State::$argv = $argv;

        if (strncmp(strtoupper(PHP_OS), 'WIN', 3) === 0) {
            // Windows consoles might not support colored output, notice the user
            $this->output->writeln('Warning: CLI application run on windows might not support colored output! You can use --color-disable');
        }

        // Initialize application
        $this->initialize();

        // Execute the command
        if ($this->commands->getRunCommand()->run()) {
            State::$success = true;
        }

        $this->unprocessedArgv();
    }

    /**
     * This is the actual part where the app is run
     */
    private function initialize(): void
    {
        $this->coreDataSetup();

        // Register all container objects
        $this->globalOptions->initIncludes();
        $this->commands->initIncludes();

        // Verify $argv
        $this->initializeConsoleArgs();

        // Process cli input
        $this->globalOptions->process();
        $this->commands->process();
        $this->globalOptions->processLazy();

        try {
            $this->getCommandsContainer()->validateArguments();
        } catch (\Exception $e) {
            $this->errorHandler->exit($e);
        }
    }

    /**
     * Add default arguments / global options and commands
     */
    private function coreDataSetup(): void
    {
        $dataDir = dirname(__DIR__) . '/Data/';
        $dataNamespace = 'Schilffarth\Console\Source\Data\\';

        $this->getGlobalOptionsContainer()->addIncludeDir($dataDir . 'GlobalOptions', $dataNamespace . 'GlobalOptions');
        $this->getCommandsContainer()->addIncludeDir($dataDir . 'Commands', $dataNamespace . 'Commands');
    }

    /*
     * After a command has run successfully, but before application is end, check for unprocessed entries in $argv
     */
    private function unprocessedArgv(): void
    {
        if (!empty(State::$argv)) {
            $this->output->error('The following argument(s) could not be resolved:');
            foreach (State::$argv as $arg) {
                $this->output->writeln($arg, Output::QUIET);
            }
        }
    }

    /**
     * Initialize the arguments that have been passed to the console and create all available args from each commands
     */
    private function initializeConsoleArgs(): void
    {
        if (empty(State::$argv)) {
            // User didn't request any command to be run
            $this->output->error('No command desired to be run.');
            $this->listRegisteredCommands();
            State::$success = true;
            exit;
        }

        $this->splitAliases();
    }

    /**
     * If combined aliases have been passed, split and register them as each single alias and unset original combined
     * alias
     */
    private function splitAliases(): void
    {
        foreach (State::$argv as $key => $arg) {
            if ($this->argumentHelper->argIsAlias($arg) && strlen($arg) > 2) {
                // It's a combined alias, such as -dh, it will be split to separate arguments -d -h
                $sequences = str_split($arg);

                // First entry is the hyphen / alias identifier
                unset($sequences[0]);
                unset(State::$argv[$key]);

                foreach ($sequences as $sequence) {
                    State::$argv[] = $this->argumentHelper->trimProperty($sequence, ArgumentHelper::STR_LEN_ALIAS);
                }
            }
        }
    }

    /**
     * todo Move to a "default list command"
     * The list command should take care of a possibly "find-command" logic that might be added in the future
     * Lists all available commands that have been registered successfully
     */
    private function listRegisteredCommands(): void
    {
        /** @var Grid $commandGrid */
        $commandGrid = $this->outputFactory->create(OutputFactory::OUTPUT_GRID);

        $commandGrid->addColumn('command', 'Command', self::PAD_LENGTH_COMMAND)
            ->addColumn('description', 'Description')
            ->suppressColumnLabels()
            ->addColorScheme('command', 'comment', '')
            ->addColorScheme('description', 'comment', '');

        /** @var AbstractCommand $command */
        foreach ($this->getCommandsContainer()->getContainer() as $order => $command) {
            $commandGrid->addRow(['command' => $command->command, 'description' => $command->help]);
        }

        $this->output->nl()->info('Available commands:')->nl();
        $commandGrid->display();
        $this->outputGlobalOptionsHelp();
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

}
