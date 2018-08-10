<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\DependencyInjection;

class ObjectManager
{

    public $injector;

    public function __construct()
    {
        $injector = new DependencyInjector();
        $injector->registerSingleton(self::class, $this);
        $this->injector = $injector;
    }

    /**
     * Return singleton of the given class
     */
    public function getSingleton(string $class): object
    {
        return $this->injector->inject($class);
    }

    /**
     * Return a newly created object of the given class
     */
    public function createObject(string $class, $createDependencies = false): object
    {
        if ($createDependencies) {
            // Additionally, all dependencies of the given class will be instantiated as new objects as well
            $creationLevel = DependencyInjector::CREATION_LEVEL_RECREATE_DEPENDENCIES;
        } else {
            // Only the given class is instantiated as a new object, but for the classes dependencies the registered DI
            // singletons are returned
            $creationLevel = DependencyInjector::CREATION_LEVEL_NEW_OBJECT;
        }

        return $this->injector->inject($class, $creationLevel);
    }

}
