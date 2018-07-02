<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://www.gnu.org/licenses/gpl-3.0.de.html General Public License (GNU 3.0)
 */

namespace Test\Commands;

use Schilffarth\CommandLineInterface\{
    Source\Command\AbstractCommand,
    Source\Component\Argument\ArgumentFactory,
    Source\Component\Interaction\Input\InputFactory
};

class Example extends AbstractCommand
{

    public $command = 'run-me';

    public $help = 'The command is an example of how to add your custom command with Schilffarth command line interface application. Just check out its source at: ' . __FILE__;

    public function init(): void
    {
        $test = $this->argumentFactory->create(
            ArgumentFactory::ARGUMENT_COMPLEX,
            'test',
            'Just a sample argument. This is a test description for the test arg.',
            't'
        );
        $this->setArgument($test);
    }

    public function run(): bool
    {
        $this->writeln('Running example command! You did it! Run the command with --help or -h arguments supplied for more information.');

        $this->writeln('Let us test an input object, okay?');
        $testInput = $this->inputFactory->create(InputFactory::INPUT_LABELED, 'Please type in something...');
        $this->writeln('<debug>We created an input object - Now you will see how easy it is to get request some user input for continuing with processing your command...</debug>');
        $testInput->request();
        $this->writeln('Guess what, we got it! Value is stored in the input object successfully. Here is its value: "' . $testInput->getValue() . '"');

        $this->writeln('I want to show you all the awesome colored output you can create. Take care of it at the output object!');
        $this->writeln('<comment>This represents some commentary</comment>');
        $this->writeln('<info>This represents some information that should be highlighted</info>');
        $this->writeln('<error>ERROR - Be careful. If you see red colored text you should always read what is written there!</error>');
        $this->writeln('<debug>Debug messages are used to give more information about what is happening while your command is being executed.</debug>');

        $this->info('We are done here. Thank you for testing the example command! If you would like to discover more, give all the arguments listed at --help a try or check out the source of this CLI');
        $this->output->nl()->writeln('Have a nice day!');
        // Success! Return false on failure
        return true;
    }

}
