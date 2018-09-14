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
                'type',
                't',
                InputOption::VALUE_OPTIONAL,
                '(a)All, (r)Resource or (s)Select Resources',
                'a'
            )
            ->addOption(
                'id',
                'i',
                InputOption::VALUE_OPTIONAL,
                'If type is Resource then a valid Resource ID',
                '0'
            )
            ->addOption(
                'ids',
                's',
                InputOption::VALUE_OPTIONAL,
                'If type is Select Resources then a valid list of comma separated Resource IDs',
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

        $type = $input->getOption('type');
        $id = $input->getOption('id');
        $ids = $input->getOption('ids');

        $modx = $this->console->loadMODX();
        $stockpile = new Stockpile($modx);
        $stockpile->setSymfonyStyle($io);

        switch (strtolower($type)) {

            case 'r':
                // no break
            case 'resource':
                if (empty($id) || !is_numeric($id)) {
                    $output->writeln('Please pass a valid resource ID via the -i option');

                } else {
                    $resource = $modx->getObject('modResource', (int)$id);

                    if (!$resource) {
                        $output->writeln('Please pass a valid resource ID, '.$id. ' is invalid');

                    } else {
                        $stockpile->cacheResource($resource);
                        $output->writeln('Resource cached: '.$id.' '.$resource->get('pagetitle'));
                    }
                }
                break;

            case 's':
                // no break
            case 'select resources':
                if (empty($ids)) {
                    $output->writeln('Please pass valid resource IDs via the -s option');

                } else {

                    $resources = $modx->getCollection('modResource', ['id:IN' => explode(',', $ids)]);

                    if (!$resources) {
                        $output->writeln('Please pass valid resource IDs');

                    } else {
                        foreach ($resources as $resource) {
                            $stockpile->cacheResource($resource);
                            $output->writeln('Resource cached: ' . $resource->get('id') . ' ' . $resource->get('pagetitle'));
                        }
                    }
                }
                break;

            case 'a':
                // no break
            case 'all':
                // no break
            default:
                $count = $stockpile->cacheAllResources();
                $output->writeln('All ' . $count . ' resources have been cached');
                break;

        }
    }
}