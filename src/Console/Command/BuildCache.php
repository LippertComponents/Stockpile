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
                's',
                InputOption::VALUE_OPTIONAL,
                'Optionally limit to specific resources IDs, pass a valid list of comma separated Resource IDs',
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

        $ids = explode(',', $input->getOption('ids'));

        $modx = $this->console->loadMODX();
        $stockpile = new Stockpile($modx);
        $stockpile->setSymfonyStyle($io);

        $staticGenerator = new StaticGenerator($modx);

        if (count($ids) > 1 || (count($ids) == 1 && !empty(count($ids)))) {
            // select resources
            $resources = $modx->getCollection('modResource', ['id:IN' => $ids]);

            if (!$resources) {
                $output->writeln('Please pass valid resource IDs');

            } else {
                foreach ($resources as $resource) {
                    $stockpile->cacheResource($resource);

                    $staticGenerator->rebuildStaticResourceOnSave($resource);
                    $output->writeln('Resource cached: ' . $resource->get('id') . ' ' . $resource->get('pagetitle'));
                }
            }

        } else {
            // all
            $count = $stockpile->cacheAllResources();
            $output->writeln('All ' . $count . ' resources have been cached by stockpile');
        }

        $output->writeln($this->getRunStats());
    }
}
