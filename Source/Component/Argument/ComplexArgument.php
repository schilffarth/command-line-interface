<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Component\Argument;

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
    public $value = '';

    public function launch(array &$argv): void
    {
        if ($this->required && !$this->passed) {
            $this->output->error(sprintf('Argument %s is required but not specified!', $this->name));
            exit;
        }

        if (!$this->passed) {
            return;
        }

        $value = array_shift($argv);
        if ($value === null) {
            // todo Create an InputObject that will let the user specify the arguments value
            $this->output->error(sprintf('No value specified for argument %s', $this->name));
            exit;
        }

        $this->value = $value;
    }

}
