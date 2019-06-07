<?php

namespace LCI\MODX\Stockpile\Console\Command;

use LCI\MODX\Console\Command\BaseCommand;
use LCI\MODX\Stockpile\Stockpile;
use LCI\MODX\Stockpile\StockpileQue;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ProcessCacheQue extends BaseCommand
{
    public $loadMODX = true;

    protected function configure()
    {
        $this
            ->setName('stockpile:que')
            ->setDescription('Process Stockpile cache que, see Stockpile README')
            ->addOption(
                'batchLimit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Optionally set a custom the batch limit',
                20
            );
    }

    /**
     * Runs the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var SymfonyStyle $io */
        $io = new SymfonyStyle($input, $output);

        $batch_limit = $input->getOption('batchLimit');

        $modx = $this->console->loadMODX();
        $stockpile = new Stockpile($modx);
        $stockpile->setSymfonyStyle($io);

        $stockpileQue = new StockpileQue($modx, $stockpile);
        $stockpileQue->setSymfonyStyle($io);
        $count = $stockpileQue->processQueLog($batch_limit);
        $output->writeln($count . ' resources have been cached by the stockpile que');

        $output->writeln($this->getRunStats());
    }
}
