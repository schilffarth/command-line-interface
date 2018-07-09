<?php
namespace Test\Command;

use Schilffarth\CommandLineInterface\{
    Source\Component\Argument\Types\ComplexArgument,
    Source\Component\Command\AbstractCommand,
    Source\Component\Argument\ArgumentFactory,
    Source\Component\Interaction\Input\InputFactory
};

class Example extends AbstractCommand
{

    public $command = 'run-me';

    public $help = 'The command is an example of how to add your custom command with Schilffarth command line interface application.';

    public function initCommandArgs(): void
    {
        /** @var ComplexArgument $test */
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
        try {
            $this->error(sprintf('Here is --test / test arguments value: %s', $this->getArgument('test')->getValue()));

            $testInput = $this->inputFactory->create(InputFactory::INPUT_LABELED, 'Please type in something...');
            $this->writeln('<debug>We created an input object...</debug>');
            $testInput->request();
            $this->writeln('Value is stored in the input object successfully. Here is its value: "' . $testInput->getValue() . '"');

            $this->output->nl()->writeln('Have a nice day!');
            // Success! Return false on failure
            return true;
        } catch (\Exception $e) {
            $this->errorHandler->exit($e);
            exit;
        }
    }

}
