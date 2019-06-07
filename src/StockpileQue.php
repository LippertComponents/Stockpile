<?php


namespace LCI\MODX\Stockpile;

use modX;
use modResource;
use Symfony\Component\Console\Style\SymfonyStyle;

class StockpileQue
{
    /** @var modX */
    protected $modx;

    /** @var Stockpile */
    protected $stockpile;

    /** @var SymfonyStyle */
    protected $symfonyStyle;

    /**
     * StockpileQue constructor.
     * @param modX $modx
     * @param Stockpile $stockpile
     */
    public function __construct(modX $modx, Stockpile $stockpile)
    {
        $this->modx = $modx;
        $this->stockpile = $stockpile;

        // add package
        // the xPDO model as it does not follow PSR standards
        $this->modx->addPackage('stockpile', __DIR__ . '/model/');
    }

    public function processQueLog($batch_limit=20)
    {
        // get total number of items to cache
        $total = $this->modx->getCount('StockpileQueLog',['processed' => 0]);

        $use_progress = false;
        if ($this->symfonyStyle instanceof SymfonyStyle) {
            $this->symfonyStyle->progressStart($total);
            $use_progress = true;
        }

        $count = $this->batchLog($batch_limit, 0, $use_progress);

        if ($use_progress) {
            $this->symfonyStyle->progressFinish();
        }

        return $count;
    }

    /**
     *
     */
    public function rebuildAll()
    {
        $query = $this->modx->newQuery('modResource');

        $query->select('modResource.id');

        $query->prepare();
        $sql = $query->toSql();

        $results = $this->modx->query($sql);

        if ($results) {
            while ($row = $results->fetch(\PDO::FETCH_ASSOC)) {
                $this->rebuildResourceID($row['id']);
            }
        }
    }

    /**
     * @param int $resource_id
     * @return bool
     */
    public function rebuildResourceID($resource_id)
    {
        $queLog = $this->modx->getObject('StockpileQueLog', ['resource_id' => $resource_id, 'processed' => 0]);

        if ($queLog) {
            return true;
        }

        $queLog = $this->modx->newObject('StockpileQueLog');
        $queLog->set('resource_id', $resource_id);
        $queLog->set('processed', 0);
        $queLog->set('request_date', date('Y-m-d H:i:s'));

        return $queLog->save();
    }

    /**
     * @param array $resource_ids - [1,2,3...]
     * @return bool
     */
    public function rebuildResourceIDs(array $resource_ids)
    {
        $logged = false;
        foreach ($resource_ids as $resource_id) {
            $logged = $this->rebuildResourceID($resource_id);
        }

        return $logged;
    }

    /**
     * @param modResource $resource
     * @param $parent_id
     */
    public function rebuildParentWhenResourceHasParentID(modResource $resource, $parent_id)
    {
        if ($resource->get('parent') == $parent_id) {
            $this->rebuildResourceID($parent_id);
        }
    }

    /**
     * @param SymfonyStyle $symfonyStyle
     */
    public function setSymfonyStyle(SymfonyStyle $symfonyStyle)
    {
        $this->symfonyStyle = $symfonyStyle;
    }

    /**
     * @param int $limit
     * @param int $count
     * @param bool $use_progress
     * @return int
     */
    protected function batchQueLog($limit, $count, $use_progress=false)
    {
        /** @var array|bool $queLogResources - false if empty or [\StockpileQueLog, ...] */
        $queLogResources = $this->modx->getCollection('StockpileQueLog', ['processed' => 0]);

        $queLogResources->sortBy('id', 'ASC');
        $queLogResources->limit($limit);

        $total = count($queLogResources);

        /** @var \StockpileQueLog $queLog */
        foreach ($queLogResources as $queLog) {
            if ($use_progress) {
                $this->symfonyStyle->progressAdvance();
            }

            $resource = $this->modx->getObject('modResource', $queLog->get('resource_id'));
            $count++;
            if ($resource) {
                $this->stockpile->cacheResource($resource, 'command');
                $this->stockpile->resetPageCacheLife();
            }

            $queLog->set('processed', 1);
            $queLog->set('processed_date', date('Y-m-d H:i:s'));
        }

        if ($total >= $limit) {
            $this->batchLog($limit, $count, $use_progress);
        }

        return $count;
    }
}
