<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\App\Container;

use Schilffarth\Console\{
    DependencyInjection\ObjectManager,
    Source\App\ErrorHandler,
    Source\App\State,
    Source\Component\Argument\AbstractArgumentObject,
    Source\Component\Interaction\Output\Output
};

abstract class AbstractContainer
{

    /**
     * The primary field / identifier of the contained objects
     */
    public $primary = '';

    /**
     * The container for all objects
     * [(int) <order> => (object) <instance>]
     */
    protected $container = [];

    /**
     * Directories to scan for registering additional objects in the container
     * @var string[]
     * [(string) <path> => (string) <namespace>]
     */
    protected $includes = [];

    protected $errorHandler;
    protected $objectManager;
    protected $output;

    public function __construct(
        ErrorHandler $errorHandler,
        ObjectManager $objectManager,
        Output $output
    ) {
        $this->errorHandler = $errorHandler;
        $this->objectManager = $objectManager;
        $this->output = $output;
    }

    /**
     * Handle the containers objects
     */
    abstract public function process(): void;

    /**
     * Adds a directory to be scanned for additional objects to be registered in the container
     */
    public function addIncludeDir(string $dir, string $namespace): self
    {
        $this->includes[$dir] = $namespace;

        return $this;
    }

    /**
     * Add a php file / class as new registered object to its app container, either global option or command
     */
    public function initIncludes(): self
    {
        foreach ($this->includes as $path => $namespace) {
            foreach (scandir($path) as $file) {
                $file = $path . DIRECTORY_SEPARATOR . $file;
                if (is_file($file)) {
                    try {
                        require_once $file;
                        $class = $this->objectManager->getSingleton($namespace . '\\' . basename($file, '.php'));
                        $this->addObject($class);
                    } catch (\Exception $e) {
                        $this->errorHandler->exit($e);
                        exit;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Adds an object to the given application container, either global option or command
     *
     * todo HEAVY TODO:
     * Do not avoid using type hints. Do anything else, but definitely not this.
     * Currently this is necessary, because command "orders" are not existent, command keys are their command ids / names
     * CHANGE THIS todo ASAP
     *
     * @var string|int $order
     */
    public function addObject(object $object, $order = 0): self
    {
        if ($order === 0 || $order === null) {
            // Just apply to the end
            $this->container[] = $object;
        } else {
            if (isset($this->container[$order])) {
                $this->output->error(sprintf('Cannot initialize object %s ordered at %d. The given order has already been set.', get_class($object), $order));
                $this->container[] = $object;
            } else {
                // Add at desired order
                $this->container[$order] = $object;
            }
        }

        return $this;
    }

    /**
     * Remove an object from the given application container, either global option or command
     */
    public function removeObject(string $name): self
    {
        $key = array_search($name, array_combine(
            array_keys($this->container),
            array_column($this->container, $this->primary)
        ));

        unset($this->container[$key]);

        return $this;
    }

    /**
     * Retrieve all registered objects
     */
    public function getContainer(): array
    {
        return $this->container;
    }

    /*
     * Process and register a single argument
     */
    protected function launchArgument(AbstractArgumentObject &$argument): void
    {
        $scans = [$argument->getName()];

        foreach ($argument->getAliases() as $alias) {
            $scans[] = $alias;
        }

        foreach ($scans as $scan) {
            // Scan whether the argument has been passed
            $found = array_search($scan, State::$argv, true);
            if ($found !== false) {
                if ($argument->isPassed()) {
                    // Argument is passed multiple times
                    $this->output->error(sprintf('Argument %s is passed more than once. Please make sure your command does not contain any typos.', $argument->getName()));
                    exit;
                }

                $argument->setPassed(true)
                    ->setConsoleArgvKey($found);
                unset(State::$argv[$found]);
            }
        }

        $argument->launch();
    }

}
