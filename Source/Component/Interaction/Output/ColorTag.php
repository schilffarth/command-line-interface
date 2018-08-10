<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\Component\Interaction\Output;

/**
 * todo
 */
class ColorTag
{

    private $node;

    private $code;

    public function setNode(string $node): self
    {
        $this->node = $node;
        return $this;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    /**
     * Get the valid format for color code for proper console output
     */
    public function getFormatted(): string
    {
        return sprintf("\033[%sm", $this->code);
    }

}
