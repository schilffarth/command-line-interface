<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\App;

use Schilffarth\Console\{
    Source\App\Container\Commands,
    Source\App\Container\GlobalOptions,
    Source\Component\Argument\Types\GlobalOption,
    Source\Component\Command\AbstractCommand
};

/**
 * Containers for the console application:
 * - Commands
 * - Global options
 */
class Containers
{

    protected $commands;
    protected $globalOptions;

    public function __construct(
        Commands $commands,
        GlobalOptions $globalOptions
    ) {
        $this->commands = $commands;
        $this->globalOptions = $globalOptions;
    }

    /**
     * Retrieve the command container instance
     */
    public function getCommandsContainer(): Commands
    {
        return $this->commands;
    }

    /**
     * Add a command to the console
     */
    public function addCommand(AbstractCommand $command): self
    {
        $this->commands->addObject($command, $command->command);

        return $this;
    }

    /**
     * Destroy a command by name
     */
    public function removeCommand(string $name): self
    {
        $this->commands->removeObject($name);

        return $this;
    }

    /**
     * Add an include path for scanning / initializing available commands
     */
    public function includeCommandDir(string $path, string $namespace): self
    {
        $this->commands->addIncludeDir($path, $namespace);

        return $this;
    }

    /**
     * Retrieve the global option container instance
     */
    public function getGlobalOptionsContainer(): GlobalOptions
    {
        return $this->globalOptions;
    }

    /**
     * Adds an command application option
     */
    public function addGlobalOption(GlobalOption $option): self
    {
        $this->globalOptions->addObject($option, $option->getOrder());

        return $this;
    }

    /**
     * Destroy a global option by name
     */
    public function removeGlobalOption(string $name): self
    {
        $this->globalOptions->removeObject($name);

        return $this;
    }

    /**
     * Add an include path for scanning / initializing available commands
     */
    public function includeGlobalOptionDir(string $path, string $namespace): self
    {
        $this->globalOptions->addIncludeDir($path, $namespace);

        return $this;
    }

}
