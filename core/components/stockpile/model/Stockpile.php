<?php
/**
 * Created by PhpStorm.
 * User: jgulledge
 * Date: 9/13/2017
 * Time: 1:37 PM
 */

namespace Lci;


class Stockpile
{
    /** @var  \modx */
    protected $modx;

    /** @var array  */
    protected $cacheOptions = [
        \xPDO::OPT_CACHE_KEY => 'stockpile'
    ];

    /** @var int $cache_life in seconds, 0 is forever */
    protected $cache_life = 0;

    /** @var array  */
    protected $resource_data = [];
    /**
     * @param int $id
     *
     * @return string
     */
    protected function getModxCacheKey($id)
    {
        return 'stockpile-resource-'.$id;
    }

    /**
     * Stockpile constructor.
     *
     * @param Modx $modx
     * @param array $config
     */
    public function __construct(&$modx, $config=[])
    {
        $this->modx = $modx;
    }

    /**
     * @param \modResource $resource
     */
    public function onSaveResource(\modResource $resource)
    {
        // @TODO make this an option:
        if ($resource->get('published')) {
            $this->cacheResource($resource);
        } else {
            $this->removeResourceCache($resource);
        }
    }

    /**
     * @return array
     */
    public function getResourceData()
    {
        return $this->resource_data;
    }

    /**
     * @param array $resource_data
     */
    public function setResourceData(array $resource_data)
    {
        $this->resource_data = $resource_data;
    }

    /**
     * @param \modResource $resource
     *
     * @return mixed|array
     */
    public function cacheResource(\modResource $resource)
    {
        $tvs = [];// TemplateVarResources modTemplateVarResource
        // get Template:
        $template = $resource->getOne('Template');
        if (is_object($template)) {
            // get all TemplateValues
            // this way insures that all TVs have values/default not just what has been set/saved
            $tvTemplates = $template->getMany('TemplateVarTemplates');
            foreach ($tvTemplates as $tvTemplate) {
                $tv = $tvTemplate->getOne('TemplateVar');
                $tv_name = $tv->get('name');

                $tvs[$tv_name] = $resource->getTVValue($tv_name);
            }
        }

        $this->resource_data = $resource->toArray();
        $this->resource_data['tv'] = $tvs;

        // https://docs.modx.com/revolution/2.x/developing-in-modx/other-development-resources/class-reference/modx/modx.invokeevent
        $this->modx->invokeEvent(
            'OnStockpileSave',
            [
                'stockpile' => $this,
                'resource' => &$resource,
                'data' => &$this->resource_data
            ]
            );
        /**
         * phpmd.org
         * security.sensiolabs.org
         * phpunit.de
         */

        // now cache it:
        $this->modx->cacheManager->set(
            $this->getModxCacheKey($resource->get('id')),
            $this->resource_data,
            $this->cache_life,
            $this->cacheOptions
        );

        return $this->resource_data;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function removeResourceCache($id)
    {
        return $this->modx->cacheManager->delete($this->getModxCacheKey($id), $this->cacheOptions);
    }

    /**
     * @return string
     */
    public function getCachePath()
    {
        return $this->modx->getOption(\xPDO::OPT_CACHE_PATH) . $this->cacheOptions[\xPDO::OPT_CACHE_KEY];
    }
    /**
     * @return bool
     */
    public function removeAllResourceCache()
    {
        //$this->modx->cacheManager->getCachePath();
        $deleted = false;
        $extensions= $this->modx->getOption('extensions', [], ['.cache.php']);
        $cache_path = $this->getCachePath();

        if (file_exists($cache_path)) {
            if (is_dir($cache_path)) {
                $deleted = $this->modx->cacheManager->deleteTree($cache_path, ['deleteTop' => false, 'skipDirs' => false, 'extensions' => $extensions]);
            }
        }
        return $deleted;
    }
    // Should only be called via CLI
    public function cacheAllResources(\League\CLImate\CLImate $CLImate)
    {
        // get total number of items to cache
        $resources = $this->modx->getCollection('modResource');

        $total = count($resources);
        $progress = $CLImate->progress()->total($total);

        $count = 0;
        foreach ($resources as $resource) {
            $progress->current($count++, $resource->get('id').' '.$resource->get('pagetitle'));
            $this->cacheResource($resource);
        }

    }

    /**
     * @param int $id
     *
     * @return mixed|array
     */
    public function getResource($id)
    {
        $data = $this->modx->cacheManager->get($this->getModxCacheKey($id), $this->cacheOptions);
        if (!$data) {
            $resource = $this->modx->getObject('modResource', $id);
            $data = $this->cacheResource($resource);
        }
        return $data;
    }



    public function batcherHasRan()
    {
        // @TODO need to clear cache for all effected resources
        $this->buildQue();
    }

    public function tvHasBeenAddedToTemplate(modTemplate $template)
    {
        // @TODO need to clear cache for all effected resources
        $this->buildQue();
    }

    public function tvHasBeenRemovedFromTemplate(modTemplate $template)
    {
        // @TODO need to clear cache for all effected resources
        $this->buildQue();
    }

    public function tvHasBeenCreated(modTemplateVariable $templateVariable)
    {
        // @TODO need to clear cache for all effected resources
        $this->buildQue();
    }

    public function tvHasBeenUpdate(modTemplateVariable $templateVariable)
    {
        // @TODO need to clear cache for all effected resources
        $this->buildQue();
    }

    public function buildQue()
    {
        // @TODO que of what cache needs rebuilt, from the create/update TV issues
    }

    public function processQue()
    {
        // @TODO run as cron job every 5 minutes to process que
    }

}