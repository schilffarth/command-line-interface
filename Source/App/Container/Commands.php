<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\App\Container;

use Schilffarth\Console\{
    Exception\ArgumentNotFoundException,
    Source\App\State,
    Source\Component\Argument\AbstractArgumentObject,
    Source\Component\Command\AbstractCommand,
    Source\Component\Interaction\Output\Output
};

class Commands extends AbstractContainer
{

    /**
     * @see AbstractCommand::command
     */
    public $primary = 'command';

    /**
     * Either if the requested command is not found, or if no command was desired, fall back to the default command
     */
    public $defaultCommand = 'list';

    /**
     * The called command
     */
    public $exec = '';

    public function process(): void
    {
        $this->defineRunCommand();

        $exec = $this->getRunCommand();
        $exec->initializeArguments();

        foreach ($exec->arguments as &$argument) {
            $this->launchArgument($argument);
        }

        $exec->triggerArguments();
    }

    /**
     * Retrieve the run command
     */
    public function getRunCommand(): AbstractCommand
    {
        if (!isset($this->container[$this->exec])) {
            $this->output->error('Command has either not been defined or not been found in the registered commands.');
            exit;
        }

        return $this->container[$this->exec];
    }

    /**
     * Check addRequiredArgument / addExcludedArgument / dependencies for the arguments
     *
     * @see AbstractArgumentObject::addRequiredArgument()
     * @see AbstractArgumentObject::addExcludedArgument()
     * @throws \Schilffarth\Console\Exception\ArgumentNotFoundException
     *
     * todo Better code for this method
     * todo Probably just split this for each global options and command arguments validation
     * todo Just remove addRequiredArgument / addExcludedArgument?
     *
     * @param AbstractArgumentObject $args
     */
    public function validateArguments(array $args = [], array $argumentContainer = []): void
    {

        $this->output->info('TODO: \Schilffarth\Console\Source\App\Container\Commands::validateArguments');

        return;

        foreach ($args as $argument) {
            if (!$argument->passed) {
                continue;
            }

            foreach (['addExcludedArgument', 'addRequiredArgument'] as $key) {
                foreach ($argument[$key] as $check) {
                    $exists = $this->getArgumentKey($argumentContainer);
                    if ($exists === false) {
                        throw new ArgumentNotFoundException(sprintf('Argument %s is invalid.', $argument->name));
                    }
                }
            }
        }

        $exec = $this->getRunCommand();

        foreach ($exec->arguments as $argument) {
            if (!$argument->isPassed()) {
                continue;
            }

            foreach ($argument->getExcludes() as $excl) {
                $exists = $exec->getArgKeyByProperty('name', $excl);
                if ($exists === false) {
                    throw new ArgumentNotFoundException(
                        sprintf('Exclude %s for %s is not a valid argument.', $excl, $argument->getName())
                    );
                }
                if ($exec->arguments[$exists]->isPassed()) {
                    $this->output->error(sprintf('Argument %s addExcludedArgument argument %s, cannot set both.', $excl, $argument->getName()));
                    exit;
                }
            }

            foreach ($argument->getRequires() as $req) {
                $exists = $exec->getArgKeyByProperty('name', $req);
                if ($exists === false) {
                    throw new ArgumentNotFoundException(
                        sprintf('Require %s for %s is not a valid argument.', $req, $argument->getName())
                    );
                }
                if (!$exec->arguments[$exists]->isPassed()) {
                    $this->output->error(sprintf('Argument %s addRequiredArgument argument %s to be passed.', $argument->getName(), $req));
                    exit;
                }
            }
        }
    }

    /**
     * Retrieve the key for the desired argument
     * @return mixed The key of the found argument
     */
    private function getArgumentKey(array $argumentContainer)
    {
        return array_search('name', array_combine(
            array_keys($argumentContainer),
            array_column($argumentContainer, 'name')
        ));
    }

    /**
     * {@inheritdoc}
     * @return AbstractCommand[]
     */
    public function getContainer(): array
    {
        return parent::getContainer();
    }

    public function addObject(object $object, $order = 0): AbstractContainer
    {
        if ($order === 0) {
            /** @see Commands::primary */
            $order = $object->command;
        }

        return parent::addObject($object, $order);
    }

    private function defineRunCommand(): void
    {
        // First entry is always supposed to be the run command
        $desired = array_shift(State::$argv);

        if (isset($this->getContainer()[$desired])) {
            $this->exec = $desired;
        } else {
            $this->output->error(sprintf('Command "%s" not found.', $desired));
            $this->output->debug('todo Implement find-command logic, list all commands', Output::QUIET);
            // todo Implement "find-command" logic
            # $this->listRegisteredCommands();
            // todo Run default command for listing commands
            exit;
        }
    }

}
