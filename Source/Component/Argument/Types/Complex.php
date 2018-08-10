<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\Component\Argument\Types;

use Schilffarth\Console\{
    Source\App\State,
    Source\Component\Argument\AbstractArgumentObject,
    Source\Component\Argument\ArgumentHelper,
    Source\Component\Interaction\Input\InputFactory,
    Source\Component\Interaction\Output\Output
};

/**
 * Complex arguments represent options that need to take a value, initialized by the users input
 *
 * Examples:
 * --filename   -f      If the user passes `--filename example.txt` this arguments value will be set to: example.txt
 * --locations  -l      Passing `-l "location_one, location_two"` this arguments value will be set to: location_one, location_two
 */
class Complex extends AbstractArgumentObject
{

    /**
     * The command won't run if argument is required but not passed
     */
    protected $required = false;

    /**
     * If the argument is passed, this variable holds the initialized value
     */
    protected $value = '';

    private $inputFactory;

    public function __construct(
        ArgumentHelper $argumentHelper,
        Output $output,
        InputFactory $inputFactory
    ) {
        parent::__construct($argumentHelper, $output);

        $this->inputFactory = $inputFactory;
    }

    public function launch(): void
    {
        if ($this->isRequired() && !$this->isPassed()) {
            $this->output->error(sprintf('Argument %s is required but not specified!', $this->getName()));
            $this->output->comment($this->getDescription());

            $input = $this->inputFactory->create(InputFactory::INPUT_LABELED, '<comment>Please specify the value...</comment>');
            $this->setValue($input->request()->getValue())
                ->setPassed(true);

            return;
        }

        if (!$this->isPassed()) {
            return;
        }

        $valueKey = $this->getConsoleArgvKey() + 1;
        $value = State::$argv[$valueKey] ?? null;

        if ($value === null) {
            $this->output->error(sprintf('No value specified for argument %s', $this->getName()));
            exit;
        }

        unset(State::$argv[$valueKey]);

        $this->setValue($value);
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

}
