<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\App;

use Schilffarth\Console\DependencyInjection\ObjectManager;

abstract class AbstractFactory
{

    protected $objectManager;

    public function __construct(
        ObjectManager $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Create a new object $type
     */
    public function create(string $type, ...$args): object
    {
        $object = $this->objectManager->createObject($type);

        if (method_exists($object, 'create')) {
            $object->create(...$args);
        }

        return $object;
    }

}
