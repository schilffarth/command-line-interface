<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\CommandLineInterface\Source\Component\Interaction\Input;

use Schilffarth\CommandLineInterface\{
    Source\Component\Interaction\Output\Output
};

class AbstractInputObject
{

    /**
     * User input
     */
    public $value = '';

    protected $output;

    public function __construct(
        Output $output
    ) {
        $this->output = $output;
    }

    /**
     * The input object is created with @see InputFactory::create
     */
    public function create(): void {}

    /**
     * Use this function to display your input object in the console and retrieve the user input
     */
    public function request(): void
    {
        $this->value = $this->nextLine();
    }

    /**
     * Get the next line the user inputted
     */
    public function nextLine(): string
    {
        return trim(fgets(STDIN));
    }

    /**
     * Get the next integer the user inputted
     */
    public function nextInt(): int
    {
        $input = $this->nextLine();

        if (is_numeric($input)) {
            return $input;
        }

        return $this->nextInt();
    }

    /**
     * Get the next boolean the user inputted
     */
    public function nextBool(): bool
    {
        return filter_var($this->nextLine(), FILTER_VALIDATE_BOOLEAN);
    }

}
