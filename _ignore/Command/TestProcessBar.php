<?php
namespace Test\Command;

use Schilffarth\Console\{
    Source\Component\Argument\Types\Complex,
    Source\Component\Command\AbstractCommand,
    Source\Component\Argument\ArgumentFactory,
    Source\Component\Interaction\Output\OutputFactory,
    Source\Component\Interaction\Output\Types\ProcessBar
};

class TestProcessBar extends AbstractCommand
{

    public $command = 'test-process-bar';

    public $help = 'Run a test process bar.';

    public function initializeArguments(): void
    {
        /** @var Complex $test */
        $test = $this->argumentFactory->create(
            ArgumentFactory::ARGUMENT_COMPLEX,
            'amount',
            'How many units to display in the process bar.',
            'a'
        );
        $this->setArgument($test);
    }

    public function run(): bool
    {
        try {
            /** @var ProcessBar $bar */
            $bar = $this->outputFactory->create(OutputFactory::OUTPUT_PROCESS_BAR, $this->getArgument('amount')->getValue());

            $bar->start();

            for ($i = 0; $i < 100; $i++) {
                $bar->tick();
                sleep(1);
            }

            return true;
        } catch (\Exception $e) {
            $this->errorHandler->exit($e);
            exit;
        }
    }

}
