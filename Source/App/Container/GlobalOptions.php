<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\App\Container;

use Schilffarth\Console\{
    Source\Component\Argument\Types\GlobalOption,
};

class GlobalOptions extends AbstractContainer
{

    /**
     * @see GlobalOption::name
     */
    public $primary = 'name';

    /**
     * Process the container
     */
    public function process(): void
    {
        foreach ($this->getContainer() as &$globalOption) {
            // Check whether the option is passed in the run console
            $this->launchArgument($globalOption);
        }

        $this->triggerGlobalOptionCallbacks(false);
    }

    /**
     * Process lazy container objects
     */
    public function processLazy(): void
    {
        $this->triggerGlobalOptionCallbacks(true);
    }

    /**
     * {@inheritdoc}
     * @return GlobalOption[]
     */
    public function getContainer(): array
    {
        return parent::getContainer();
    }

    public function addObject(object $object, $order = 0): AbstractContainer
    {
        if ($order === 0) {
            /** @see GlobalOptions::primary */
            $order = $object->getOrder();
        }

        return parent::addObject($object, $order);
    }

    /**
     * Trigger passed options
     */
    private function triggerGlobalOptionCallbacks(bool $lazy): void
    {
        foreach ($this->getContainer() as $globalOption) {
            if ($globalOption->isPassed() && $globalOption->isLazy() === $lazy) {
                // Trigger callbacks for passed global options, ignore those which haven't been passed
                $globalOption->trigger();
            }
        }
    }

}
