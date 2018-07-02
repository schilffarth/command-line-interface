<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Component\Interaction\Input;

use Schilffarth\DependencyInjection\{
    Source\ObjectManager
};

class InputFactory
{

    const INPUT_LABELED = Labeled::class;

    private $objectManager;

    public function __construct(
        ObjectManager $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    public function create(string $type, ...$args): AbstractInputObject
    {
        /** @var AbstractInputObject $object */
        $object = $this->objectManager->createObject($type);
        $object->create(...$args);

        return $object;
    }

}
