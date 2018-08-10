<?php
/**
 * @author      Roland Schilffarth <roland@schilffarth.org>
 * @license     https://opensource.org/licenses/GPL-3.0 General Public License (GNU 3.0)
 */

namespace Schilffarth\Console\Source\Data\GlobalOptions;

use Schilffarth\Console\{
    Source\App\Application,
    Source\Component\Argument\ArgumentHelper,
    Source\Component\Argument\Types\GlobalOption,
    Source\Component\Interaction\Output\Output,
    Source\App\State
};

class Help extends GlobalOption
{

    private $app;

    public function __construct(
        ArgumentHelper $argumentHelper,
        Output $output,
        Application $app
    ) {
        parent::__construct($argumentHelper, $output);

        $this->app = $app;

        $this->setOrder(-98) // // Order to be processed secondly, after --disable-color
            ->setLazy(true)
            ->setName('help')
            ->setDescription('Get detailed help for the command. Displays further information and example usages.')
            ->addAlias('h');
    }

    public function call(): void
    {
        $container = $this->app->getCommandsContainer();
        $commandArguments = $container->getContainer()[$container->exec]->arguments;

        $this->output->nl();

        if (!$commandArguments) {
            $this->output->info(sprintf('Command %s does not accept any arguments!', $container->exec));
        } else {
            $grid = $this->argumentHelper->createArgumentGrid();

            foreach ($commandArguments as $argument) {
                $this->argumentHelper->argumentGridRow($argument, $grid);
            }

            $this->output->info('Command arguments:')->nl();
            $grid->display();
        }

        $this->app->outputGlobalOptionsHelp();

        // Exit successfully
        State::$success = true;
        exit;
    }

}
