<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\DependencyInjection;

use Schilffarth\Console\{
    Exception\ClassNotInstantiableException,
    Exception\RecursiveDependencyException,
    Source\App\ErrorHandler
};

class DependencyInjector
{

    /**
     * The di will return singletons for the given class and its dependencies
     */
    public const CREATION_LEVEL_SINGLETON = 1;

    /**
     * The di will return a new object of the given class, but returns singletons for its dependencies
     */
    public const CREATION_LEVEL_NEW_OBJECT = 2;

    /**
     * The di will return an entirely newly created dependency tree, instantiating new objects of given class and all of
     * its dependencies
     */
    public const CREATION_LEVEL_RECREATE_DEPENDENCIES = 3;

    /**
     * Used to store singleton instances
     */
    public $loaded = [];

    /**
     * todo
     * Class name of instantiated
     */
    private $primalClasses = [];

    private $errorHandler;

    public function __construct()
    {
        $this->errorHandler = new ErrorHandler();
        $this->registerSingleton(ErrorHandler::class, $this->errorHandler);
    }

    /**
     * Register a instance for further access in the di
     * Cannot override any existing singleton (might change in future)
     */
    public function registerSingleton(string $class, object $instance): bool
    {
        $class = $this->stripClass($class);

        if (isset($this->loaded[$class])) {
            return false;
        }

        $this->loaded[$class] = $instance;

        return true;
    }

    /**
     * Build an instance of the given class
     * For effects of the set level of $creationLevel, check constant documentation:
     * @see DependencyInjector::CREATION_LEVEL_SINGLETON
     * @see DependencyInjector::CREATION_LEVEL_NEW_OBJECT
     * @see DependencyInjector::CREATION_LEVEL_RECREATE_DEPENDENCIES
     */
    public function inject(string $class, int $creationLevel = self::CREATION_LEVEL_SINGLETON): object
    {
        $obj = $this->run($class, $creationLevel);

        // todo
        $this->primalClasses = [];

        return $obj;
    }

    /**
     * Run injection for a class
     */
    private function run(string $class, int $creationLevel): object
    {
        try {
            // todo
            $this->primalClasses[] = $this->stripClass($class);

            if (isset($this->loaded[$class]) && $creationLevel === self::CREATION_LEVEL_SINGLETON) {
                // Singleton requested and it has already been instantiated
                return $this->loaded[$class];
            }

            $reflector = new \ReflectionClass($class);

            if (!$reflector->isInstantiable()) {
                // Class is not instantiable
                throw new ClassNotInstantiableException(sprintf('%s is not instantiable.', $class));
            }

            // Build an instance with the given reflector
            // Injects objects of the desired instance for class constructor arguments and return the instantiated class
            $args = $this->injectConstructorArgs($reflector, $class, $creationLevel);
            $class = $this->getClass($class, $args, $creationLevel);
        } catch (\Exception $e) {
            // \ReflectionException
            // ClassNotInstantiableException
            $this->errorHandler->exit($e);
        }

        return $class;
    }

    /**
     * Instantiate new constructor parameters
     * Constructor arguments are supposed to have either a valid class type hint or a default value
     *
     * If the class constructor has a dependency specified and is injected with @see DependencyInjector::inject(), the
     * instantiated class itself and the constructors dependencies are stored for usage as singletons in
     * @see DependencyInjector::loaded
     */
    private function injectConstructorArgs(\ReflectionClass $reflector, string $class, int $creationLevel): object
    {
        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            // Class doesn't have a declared constructor, no need to inject any dependencies
            return $this->getClass($class, null, $creationLevel);
        }

        try {
            // Get an array of the constructor parameters' dependencies
            $dependencies = $this->getDependencies($constructor->getParameters(), $class, $creationLevel);
        } catch (RecursiveDependencyException $e) {
            $this->errorHandler->exit($e);
        }

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * If class has already been instantiated, return its singleton
     * Otherwise create new object and return its instance
     * if $create is true, always create new object
     */
    private function getClass(string $class, object $instance = null, int $creationLevel): object
    {
        $class = $this->stripClass($class);

        if (!isset($this->loaded[$class]) || $creationLevel !== self::CREATION_LEVEL_SINGLETON) {
            // The instance does either not exist or is desired to be re-created for a new object
            $toInject = $instance === null ? new $class : $instance;

            if ($creationLevel !== self::CREATION_LEVEL_SINGLETON) {
                // New object is desired
                return $toInject;
            } else {
                // Register singleton
                $this->loaded[$class] = $toInject;
            }
        }

        return $this->loaded[$class];
    }

    /**
     * Build up a list of dependencies for given parameters
     * @var \ReflectionParameter[] $parameters
     * @throws RecursiveDependencyException
     */
    private function getDependencies(array $parameters, string $class, int $creationLevel): array
    {
        $dependencies = [];

        foreach ($parameters as $param) {
            $dependency = $param->getClass();

            if (in_array($dependency->name, $this->primalClasses)) {
                // todo
                #throw new RecursiveDependencyException(sprintf('Dependency %s defined to be injected in %s is a recursion.', $dependency->name, $class));
            }

            if ($dependency === null) {
                // No class type hint for the parameter available
                $dependencies[] = $this->injectNonClass($param);
            } else {
                // Class is available
                // $instance defaults to null regarding the registered singletons
                $instance = null;
                $dependencyName = $dependency->name;

                if ($creationLevel === self::CREATION_LEVEL_NEW_OBJECT) {
                    // Decrease creation level, to ensure the difference between NEW_OBJECT and RECREATE_DEPENDENCIES
                    $creationLevel = self::CREATION_LEVEL_SINGLETON;
                }

                if (!isset($this->loaded[$dependencyName]) || $creationLevel !== self::CREATION_LEVEL_SINGLETON) {
                    // Create a new instance of the class
                    $instance = $this->run($dependencyName, $creationLevel);
                }

                // Add object to list of instantiated constructor dependencies
                $dependencies[] = $this->getClass($dependencyName, $instance, $creationLevel);
            }
        }

        return $dependencies;
    }

    /**
     * No class type hint for the parameter available
     * Return its default value or null
     * @return mixed
     */
    private function injectNonClass(\ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        } else {
            // No default value available, return null
            return null;
        }
    }

    /**
     * Avoid duplicated registrations of a class
     */
    private function stripClass(string $class): string
    {
        while ($class && $class[0] === "\\") {
            // Make sure the class name is not preceded by a backslash (Would result in "duplicated" objects, which
            // breaks the purpose of using singletons)
            $class = substr($class, 1);
        }

        return $class;
    }

}
