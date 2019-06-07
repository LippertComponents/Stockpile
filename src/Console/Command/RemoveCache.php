<?php
/**
 * Created by PhpStorm.
 * User: joshgulledge
 * Date: 9/14/18
 * Time: 2:20 PM
 */

namespace LCI\MODX\Stockpile\Console\Command;


use LCI\MODX\Console\Command\BaseCommand;
use LCI\MODX\Stockpile\StaticGenerator;
use LCI\MODX\Stockpile\Stockpile;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RemoveCache extends BaseCommand
{
    public $loadMODX = true;

    protected function configure()
    {
        $this
            ->setName('stockpile:remove')
            ->setDescription('Remove Stockpile cache for all or select Resources')
            ->addOption(
                'ids',
                'i',
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

        $ids = explode(',', $input->getOption('ids'));

        $modx = $this->console->loadMODX();
        $stockpile = new Stockpile($modx);
        $stockpile->setSymfonyStyle($io);

        $staticGenerator = new StaticGenerator($modx);

        if (count($ids) > 1 || (count($ids) == 1 && !empty(count($ids)))) {
            $resources = $modx->getCollection('modResource', ['id:IN' => $ids]);

            if (!$resources) {
                $output->writeln('Please pass valid resource IDs');

            } else {
                foreach ($resources as $resource) {
                    $stockpile->removeResourceCache($resource->get('id'));
                    $staticGenerator->deleteStaticResourceFile($resource);
                    $output->writeln('Resource cache has been removed: ' . $resource->get('id') . ' ' . $resource->get('pagetitle'));
                }
            }

        } else {
            $count = $stockpile->removeAllResourceCache();
            $output->writeln('All ' . $count . ' resources cache has been removed');

            $count = $staticGenerator->deleteAllStaticResourcesFiles();
            $output->writeln('All ' . $count . ' static generated resources cache has been removed');
        }

        $output->writeln($this->getRunStats());
    }
}
