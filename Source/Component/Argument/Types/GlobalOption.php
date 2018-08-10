<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\Component\Argument\Types;

use Schilffarth\Console\{
    Source\Component\Argument\AbstractArgumentObject,
    Source\Component\Argument\ArgumentHelper
};

abstract class GlobalOption extends AbstractArgumentObject
{

    /**
     * Order in which the option is registered / launched / called
     */
    protected $order = 0;

    /**
     * Whether to run before or after command initialization
     */
    protected $lazy = false;

    protected $scope = ArgumentHelper::ARGUMENT_SCOPE_GLOBAL_OPTION;

    public function launch(): void
    {
        $this->registerHandler([$this, 'call']);
    }

    abstract public function call(): void;

    /**
     * Set argument process order
     */
    public function setOrder(int $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get the arguments process order
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * Whether to process global option before or after command initialization
     */
    public function setLazy(bool $lazy): self
    {
        $this->lazy = $lazy;

        return $this;
    }

    /**
     * Retrieve the global options processing order, lazy or not
     */
    public function isLazy(): bool
    {
        return $this->lazy;
    }

}
