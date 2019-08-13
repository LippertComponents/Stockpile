<?php
/**
 * Created by PhpStorm.
 * User: joshgulledge
 * Date: 9/14/18
 * Time: 2:20 PM
 */

namespace LCI\MODX\Stockpile\Console\Command;


use LCI\MODX\Console\Command\BaseCommand;
use LCI\MODX\Orchestrator\Orchestrator;
use LCI\MODX\Stockpile\StaticGenerator;
use LCI\MODX\Stockpile\Stockpile;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BuildCache extends BaseCommand
{
    public $loadMODX = true;

    protected function configure()
    {
        $this
            ->setName('stockpile:build')
            ->setDescription('Build Stockpile cache for all or select Resources')
            ->addOption(
                'ids',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Optionally limit to specific resources IDs, pass a valid list of comma separated Resource IDs',
                '0'
            )
            ->addOption(
                'exclude',
                'e',
                InputOption::VALUE_OPTIONAL,
                'Optionally exclude resources that have this parent ID(s), pass a valid list of comma separated Resource IDs',
                '0'
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

        $ids = explode(',', trim($input->getOption('ids')));
        $exclude_parents = explode(',', trim($input->getOption('exclude')));

        $modx = $this->console->loadMODX();
        $stockpile = new Stockpile($modx);
        $stockpile->setSymfonyStyle($io);

        $staticGenerator = new StaticGenerator($modx);

        /** @var \xPDOQuery $query */
        $query = $modx->newQuery('modResource');
        if (count($ids) > 1 || (count($ids) == 1 && !empty($ids[0]))) {
            // select resources
            $query->where(['id:IN' => $ids]);
        }

        if (count($exclude_parents) > 1 || (count($exclude_parents) == 1 && !empty($exclude_parents[0]))) {
            $output->writeln('Excluding resources children with the parent ids: '.trim($input->getOption('exclude')));
            $query->where(['parent:NOT IN' => $exclude_parents]);
        }

        $count = $stockpile->cacheAllResources($query);
        $output->writeln($count . ' resources have been cached by stockpile');

        $output->writeln($this->getRunStats());
    }
}
