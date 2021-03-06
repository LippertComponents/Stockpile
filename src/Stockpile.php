<?php
/**
 * Created by PhpStorm.
 * User: jgulledge
 * Date: 9/13/2017
 * Time: 1:37 PM
 */

namespace LCI\MODX\Stockpile;

use LCI\MODX\Stockpile\Helpers\Extras\Tagger;
use modX;
use Symfony\Component\Console\Style\SymfonyStyle;
use xPDO;

class Stockpile
{
    /** @var  \modX */
    protected $modx;

    /** @var SymfonyStyle */
    protected $symfonyStyle;

    /** @var array  */
    protected $cacheOptions = [
        xPDO::OPT_CACHE_KEY => 'stockpile'
    ];

    /** @var int $cache_life in seconds, 0 is forever */
    protected $cache_life = 0;

    /** @var int|bool $page_cache_life in seconds, for a given page instance, 0 is forever */
    protected $page_cache_life = false;

    /** @var array  */
    protected $resource_data = [];

    /** @var StaticGenerator */
    protected $staticGenerator;

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
     * @param modX $modx
     * @param array $config
     */
    public function __construct(&$modx, $config=[])
    {
        $this->modx = $modx;

        $this->staticGenerator = new StaticGenerator($this->modx);
    }

    /**
     * @return int
     */
    public function getCacheLife()
    {
        return $this->cache_life;
    }

    /**
     * @param int $cache_life ~ default cache_life
     * @return $this
     */
    public function setCacheLife(int $cache_life)
    {
        $this->cache_life = $cache_life;
        return $this;
    }

    /**
     * @return int
     */
    public function getPageCacheLife()
    {
        if ($this->page_cache_life !== false) {
            return $this->page_cache_life;
        } else {
            return $this->getCacheLife();
        }
    }

    /**
     * @param int $page_cache_life
     * @return $this
     */
    public function setPageCacheLife(int $page_cache_life)
    {
        $this->page_cache_life = $page_cache_life;
        return $this;
    }

    /**
     * @return $this
     */
    public function resetPageCacheLife()
    {
        $this->page_cache_life = false;
        return $this;
    }

    public function onDeleteResource(\modResource $resource)
    {
        $this->removeResourceCache($resource->get('id'));

        $this->staticGenerator->deleteStaticResourceFile($resource);

        // https://docs.modx.com/revolution/2.x/developing-in-modx/other-development-resources/class-reference/modx/modx.invokeevent
        $this->modx->invokeEvent(
            'OnStockpileAfterDeleteMakeQueLog',
            [
                'stockpile' => $this,
                'stockpileQue' => new StockpileQue($this->modx, $this),
                'resource' => $resource,
                'data' => $this->resource_data
            ]
        );
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
            $this->removeResourceCache($resource->get('id'));
            $this->staticGenerator->deleteStaticResourceFile($resource);
        }

        // https://docs.modx.com/revolution/2.x/developing-in-modx/other-development-resources/class-reference/modx/modx.invokeevent
        $this->modx->invokeEvent(
            'OnStockpileAfterSaveMakeQueLog',
            [
                'stockpile' => $this,
                'stockpileQue' => new StockpileQue($this->modx, $this),
                'resource' => $resource,
                'data' => $this->resource_data
            ]
        );
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
     * @param SymfonyStyle $symfonyStyle
     */
    public function setSymfonyStyle(SymfonyStyle $symfonyStyle)
    {
        $this->symfonyStyle = $symfonyStyle;
    }

    /**
     * @param \modResource $resource
     * @param string $mode - plugin or command
     *
     * @return mixed|array
     */
    public function cacheResource(\modResource $resource, $mode='plugin')
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
        if ($resource->get('class_key') == 'modWebLink') {
            $this->resource_data['content'] = $this->processModWebLink($resource->get('content'));
            $this->resource_data['full_url'] = $this->resource_data['content'];

        } else {
            $this->resource_data['full_url'] = $this->modx->makeUrl($resource->get('id'), $resource->get('context_key'), '', 'full');
        }

        $this->resource_data['tv'] = $tvs;

        $tagger = new Tagger($this->modx);
        if ($tagger->isInstalled()) {
            $this->resource_data['tagger'] = $tagger->getResourceTags($resource->get('id'));
        }

        // https://docs.modx.com/revolution/2.x/developing-in-modx/other-development-resources/class-reference/modx/modx.invokeevent
        $this->modx->invokeEvent(
            'OnStockpileSave',
            [
                'stockpile' => $this,
                'resource' => &$resource,
                'data' => &$this->resource_data
            ]
        );

        // now cache it:
        $this->modx->cacheManager->set(
            $this->getModxCacheKey($resource->get('id')),
            $this->resource_data,
            $this->getPageCacheLife(),
            $this->cacheOptions
        );

        if ($mode == 'plugin') {
            $this->staticGenerator->rebuildStaticResourceOnSave($resource);
        } else {
            $this->staticGenerator->rebuildStaticResource($resource);
        }

        return $this->resource_data;
    }

    /**
     * @param int $id
     * @param string $mode - event or command
     *
     * @return bool
     */
    public function removeResourceCache($id, $mode='event')
    {
        // Delete the MODX default cache as well:
        $resource = $this->modx->getObject('modResource', $id);
        if ($resource) {
            $resource->clearCache();
        }

        return $this->modx->cacheManager->delete($this->getModxCacheKey($id), $this->cacheOptions);
    }

    /**
     * @return string
     */
    public function getCachePath()
    {
        return $this->modx->getOption(xPDO::OPT_CACHE_PATH) . $this->cacheOptions[xPDO::OPT_CACHE_KEY];
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

    /**
     * @param \xPDOQuery|null $query
     * @return int
     */
    public function cacheAllResources(\xPDOQuery $query=null)
    {
        // get total number of items to cache
        $resources = $this->modx->getCollection('modResource', $query);

        $total = count($resources);
        $use_progress = false;
        if ($this->symfonyStyle instanceof SymfonyStyle) {
            $this->symfonyStyle->progressStart($total);
            $use_progress = true;
        }

        $count = 0;
        foreach ($resources as $resource) {
            if ($use_progress) {
                // $resource->get('id') . ' ' . $resource->get('pagetitle');
                $this->symfonyStyle->progressAdvance();
            }
            $count++;
            $this->cacheResource($resource, 'command');
            $this->resetPageCacheLife();
        }

        if ($use_progress) {
            $this->symfonyStyle->progressFinish();
        }

        return $count;
    }

    /**
     * @param int $id
     *
     * @return bool|null|array ~ if null then user does not have permissions
     */
    public function getResource($id)
    {
        $data = $this->modx->cacheManager->get($this->getModxCacheKey($id), $this->cacheOptions);
        if (!$data) {
            $resource = $this->modx->getObject('modResource', $id);

            if ($resource) {
                $data = $this->cacheResource($resource);
            }
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

    /**
     * Based on MODX -> core/model/modx/modweblink.class.php->process
     *
     * @param string $string
     * @return string
     */
    protected function processModWebLink($string)
    {
        if (!is_numeric($string)) {
            $this->modx->getParser();
            $maxIterations= isset ($this->modx->config['parser_max_iterations']) ? intval($this->modx->config['parser_max_iterations']) : 10;
            $this->modx->parser->processElementTags($tag='', $string, true, true, '[[', ']]', array(), $maxIterations);
        }

        if (is_numeric($string)) {
            return $this->modx->makeUrl(intval($string), '', '', 'full');
        }

        return $string;
    }
}
