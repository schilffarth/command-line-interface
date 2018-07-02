<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Component\Argument;

use Schilffarth\DependencyInjection\{
    Source\ObjectManager
};

class ArgumentFactory
{

    const ARGUMENT_SIMPLE = SimpleArgument::class;
    const ARGUMENT_COMPLEX = ComplexArgument::class;

    private $objectManager;

    public function __construct(
        ObjectManager $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    public function create(string $type, ...$args): AbstractArgumentObject
    {
        /** @var AbstractArgumentObject $object */
        $object = $this->objectManager->createObject($type);
        $object->create(...$args);

        return $object;
    }

}
