<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Component\Argument\Types;

use Schilffarth\CommandLineInterface\{
    Source\Component\Argument\AbstractArgumentObject,
    Source\Component\Argument\ArgumentHelper,
    Source\Component\Interaction\Input\InputFactory,
    Source\Component\Interaction\Output\Output
};

/**
 * ComplexArgument arguments represent options that need to take a value, initialized by the users input
 *
 * Examples:
 * --filename   -f      If the user passes `--filename example.txt` this arguments value will be set to: example.txt
 * --locations  -l      Passing `-l "location_one, location_two"` this arguments value will be set to: location_one, location_two
 */
class ComplexArgument extends AbstractArgumentObject
{

    /**
     * The command won't run if argument is required but not passed
     */
    public $required = false;

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

    public function launch(array &$argv): void
    {
        if ($this->required && !$this->passed) {
            $this->output->error(sprintf('Argument %s is required but not specified!', $this->name));
            $this->output->comment($this->description);

            $input = $this->inputFactory->create(InputFactory::INPUT_LABELED, '<comment>Please specify the value...</comment>');
            $this->value = $input->request()->getValue();
            $this->passed = true;

            return;
        }

        if (!$this->passed) {
            return;
        }

        $valueKey = $this->consoleArgvKey + 1;
        $value = $argv[$valueKey] ?? null;

        if ($value === null) {
            $this->output->error(sprintf('No value specified for argument %s', $this->name));
            exit;
        }

        unset($argv[$valueKey]);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

}
