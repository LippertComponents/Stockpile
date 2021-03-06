<?php
/**
 * Created by PhpStorm.
 * User: joshgulledge
 * Date: 2019-03-15
 * Time: 06:17
 */

namespace LCI\MODX\Stockpile;

use modResource;
use modX;
use Symfony\Component\Console\Style\SymfonyStyle;

class StaticGenerator
{
    /** @var  \modX */
    protected $modx;

    /** @var \modCacheManager */
    protected $cacheManager;

    /** @var array  */
    protected $config = [];

    /**
     * StaticGenerator constructor.
     * @param $modx
     */
    public function __construct($modx)
    {
        $this->modx = $modx;
        $this->cacheManager = $modx->getCacheManager();

        $config = getenv();

        $this->config = [
            'LCI_STOCKPILE_ENABLE_STATIC' => (bool)$this->modx->getOption('LCI_STOCKPILE_ENABLE_STATIC', $config, true),
            'LCI_STOCKPILE_CACHE_PATH' => $this->modx->getOption('LCI_STOCKPILE_CACHE_PATH', $config, MODX_BASE_PATH .'core/cache/static/'),
            'LCI_STOCKPILE_CACHE_TV_NAME' => $this->modx->getOption('LCI_STOCKPILE_CACHE_TV_NAME', $config, ''),
            'LCI_STOCKPILE_CONTENT_TYPES' => $this->getAsArray($this->modx->getOption('LCI_STOCKPILE_CONTENT_TYPES', $config, '')),
            'LCI_STOCKPILE_CONTEXTS' => $this->getAsArray($this->modx->getOption('LCI_STOCKPILE_CONTEXTS', $config, '')),
            'LCI_STOCKPILE_MIME_TYPES' => $this->getAsArray($this->modx->getOption('LCI_STOCKPILE_MIME_TYPES', $config, '')),
            'LCI_STOCKPILE_EXCLUDE_BINARY_CONTENT_TYPES' => (bool)$this->modx->getOption('LCI_STOCKPILE_EXCLUDE_BINARY_CONTENT_TYPES', $config, true),
            'LCI_STOCKPILE_EXCLUDE_REMAINING_TAGS' => (bool)$this->modx->getOption('LCI_STOCKPILE_EXCLUDE_REMAINING_TAGS', $config, true),
            'LCI_STOCKPILE_REGENERATE_ON_CLEAR_CACHE' => (bool)$this->modx->getOption('LCI_STOCKPILE_REGENERATE_ON_CLEAR_CACHE', $config, false),
            'LCI_STOCKPILE_REGENERATE_ON_SAVE' =>(bool) $this->modx->getOption('LCI_STOCKPILE_REGENERATE_ON_SAVE', $config, true),
            'LCI_STOCKPILE_REGENERATE_USERAGENT' => $this->modx->getOption('LCI_STOCKPILE_REGENERATE_USERAGENT', $config, 'MODX RegenCache'),
            'LCI_STOCKPILE_USE_URL_SCHEME' => (bool)$this->modx->getOption('LCI_STOCKPILE_USE_URL_SCHEME', $config, false),
            'LCI_STOCKPILE_USE_HTTP_HOST' => (bool)$this->modx->getOption('LCI_STOCKPILE_USE_HTTP_HOST', $config, false)
        ];
    }

    /**
     * @return mixed
     */
    public function deleteAllStaticResourcesFiles()
    {
        $this->cacheManager->deleteTree(
            rtrim($this->config['LCI_STOCKPILE_CACHE_PATH'], '/').'-remaining-tags/',
            [
                'deleteTop' => false,
                'skipDirs' => false,
                'extensions' => [],
            ]
        );

        return $this->cacheManager->deleteTree(
            $this->config['LCI_STOCKPILE_CACHE_PATH'],
            [
                'deleteTop' => false,
                'skipDirs' => false,
                'extensions' => [],
                // #20:
                //'delete_exclude_items' => explode(',', $modx->getOption('statcache_delete_exclude', $scriptProperties, ''))
            ]
        );
    }

    /**
     * @param modResource $resource
     * @return bool
     */
    public function deleteStaticResourceFile(modResource $resource)
    {
        $deleted = false;
        $static_resource_file = $this->getStaticResourcePath($resource);

        if (is_readable($static_resource_file)) {
            if ($deleted = unlink($static_resource_file) === false) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, "Error removing static file {$static_resource_file}", '', __METHOD__);
            }
        }

        $static_remaining_tags_resource_file = $this->getStaticResourcePath($resource, true);

        if (is_readable($static_remaining_tags_resource_file)) {
            unlink($static_remaining_tags_resource_file);
        }

        return $deleted;
    }

    /**
     * @param modResource $resource
     */
    public function rebuildStaticResource(modResource $resource)
    {
        $this->callResourceViaHTTPToCache($resource);
    }

    /**
     * @param modResource $resource
     */
    public function rebuildStaticResourceOnSave(modResource $resource)
    {
        if ($this->config['LCI_STOCKPILE_REGENERATE_ON_SAVE']) {
            $this->callResourceViaHTTPToCache($resource);
        }
    }

    /**
     *
     */
    public function rebuildAllResourcesOnClearCache()
    {
        if ($this->config['LCI_STOCKPILE_REGENERATE_ON_CLEAR_CACHE']) {

            $collection = $this->getEligibleResources();

            foreach ($collection as $resource) {
                $this->callResourceViaHTTPToCache($resource);
            }
        }
    }

    /**
     * @param SymfonyStyle $symfonyStyle
     * @return int
     */
    public function rebuildAllResources(SymfonyStyle $symfonyStyle)
    {
        $collection = $this->getEligibleResources();

        $count = 0;
        $total = count($collection);
        $symfonyStyle->progressStart($total);

        foreach ($collection as $resource) {
            if ($this->callResourceViaHTTPToCache($resource)) {
                $count++;
            }

            $symfonyStyle->progressAdvance();
        }

        $symfonyStyle->progressFinish();

        return $count;
    }

    /**
     * @param modResource $resource
     * @return bool
     */
    public function makeResourceStaticFileOnWebCache(modResource $resource)
    {
        if (!$this->config['LCI_STOCKPILE_ENABLE_STATIC'] || !$this->canSaveAsStatic($resource) || $resource->_output == '') {
            return false;
        }

        $static_resource_file = $this->getStaticResourcePath($resource);

        /* attempt to write the complete Resource output to the static file */
        if (!$written = $this->modx->cacheManager->writeFile($static_resource_file, $resource->_output)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, "Error caching output from Resource {$resource->get('id')} to static file {$static_resource_file}", '', __METHOD__);
        }

        return $written;
    }

    /**
     * @param modResource $resource
     * @param bool $add_remaining_tags
     * @return string
     */
    public function getStaticResourcePath(modResource $resource, $add_remaining_tags=false)
    {
        if ($resource->Context->config === null) {
            $resource->Context->prepare();
        }

        $resourceContext = $resource->Context;


        $path = rtrim($this->config['LCI_STOCKPILE_CACHE_PATH'], '/').($add_remaining_tags ? '-remaining-tags' : '').'/';

        /* generate an absolute URI representation of the Resource to append to the path */
        if ($resource->get('id') === (integer)$resourceContext->getOption('site_start', 1)) {
            $uri  = $resourceContext->getOption('base_url', MODX_BASE_URL);
            /* use ~index.html to represent the site_start Resource */
            $uri .= '~index.html';

        } else {
            $uri = $this->modx->makeUrl($resource->get('id'), $resource->get('context_key'), '', 'abs');
            if (strpos($uri, $resourceContext->getOption('url_scheme') . $resourceContext->getOption('http_host')) === 0) {
                /* remove url_scheme and http_host from any full URLs generated by MODX automatically */
                $uri = substr($uri, strlen($resourceContext->getOption('url_scheme') . $resourceContext->getOption('http_host')));

            } elseif (!empty($resourceContext->getOption('site_url')) && strpos($uri, $resourceContext->getOption('site_url')) === 0) {
                /* remove url_scheme and http_host from any full URLs generated by MODX automatically */
                $uri = substr($uri, strlen($resourceContext->getOption('site_url')));
            }

            if (substr($uri, strlen($uri) - 1) === '/' && $resource->ContentType->get('mime_type') == 'text/html') {
                /* if Resource is HTML and ends with a /, use ~index.html for the filename */
                $uri .= '~index.html';

            } elseif ($resource->ContentType->get('mime_type') == 'text/html' && $resource->ContentType->get('file_extensions') == '' ) {
                $uri .= '~.html';
            }

        }

        if ($this->config['LCI_STOCKPILE_USE_URL_SCHEME']) {
            $path .= str_replace('://', '', $resourceContext->getOption('url_scheme')).'/';
        }
        if ($this->config['LCI_STOCKPILE_USE_HTTP_HOST']) {
            $path .= rtrim($resourceContext->getOption('http_host'), '/').'/';
        }

        return $path . ltrim($uri, '/');
    }

    /**
     * @param modResource $resource
     * @return bool
     */
    protected function canSaveAsStatic(modResource $resource)
    {
        if ($resource->get('deleted') || !$resource->get('cacheable') || !$resource->get('published') || $resource->get('class_key') == 'modWebLink') {
            return false;
        }

        if (!empty($this->config['LCI_STOCKPILE_EXCLUDE_TV_NAME']) && (bool)$resource->getTVValue($this->config['LCI_STOCKPILE_EXCLUDE_TV_NAME'])) {
            return false;
        }

        /* optionally skip binary content types */
        if ($this->config['LCI_STOCKPILE_EXCLUDE_BINARY_CONTENT_TYPES'] && $resource->ContentType->get('binary')) {
            return false;
        }

        /* do not cache if the cacheable content still contains unprocessed tags */
        $matches = [];

        if ($this->config['LCI_STOCKPILE_EXCLUDE_REMAINING_TAGS'] &&
            $this->modx->parser->collectElementTags($resource->_content, $matches)) {
            $this->makeRemainingTagsUncacheableStaticFileOnWebCache($resource);
            return false;
        }

        /* if specified, limit caching by mime-type */
        if (count($this->config['LCI_STOCKPILE_MIME_TYPES']) &&
            !in_array(strtolower($resource->ContentType->get('mime_type')), $this->config['LCI_STOCKPILE_MIME_TYPES'])) {
            return false;
        }

        /* if specified, limit caching by ContentTypes */
        if (count($this->config['LCI_STOCKPILE_CONTENT_TYPES']) &&
            !in_array($resource->ContentType->get('id'), $this->config['LCI_STOCKPILE_CONTENT_TYPES'])) {
            return false;
        }

        if (count($this->config['LCI_STOCKPILE_CONTEXTS']) && !in_array($resource->get('context_key'), $this->config['LCI_STOCKPILE_CONTEXTS'])) {
            return false;
        }

        return true;
    }

    /**
     * @param modResource $resource
     * @return bool
     */
    protected function callResourceViaHTTPToCache(modResource $resource)
    {
        $this->deleteStaticResourceFile($resource);

        if ($this->config['LCI_STOCKPILE_ENABLE_STATIC'] && $this->canSaveAsStatic($resource)) {
            /**
             * @TODO Guzzle:
             */
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_USERAGENT, $this->config['LCI_STOCKPILE_REGENERATE_USERAGENT']);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_NOBODY, true);
            $url = $this->modx->makeUrl($resource->get('id'), $resource->get('context_key'), '', 'full');
            if (!empty($url)) {
                $this->modx->log(modX::LOG_LEVEL_INFO, "Requesting Resource at {$url}");
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_exec($curl);
                $this->modx->log(modX::LOG_LEVEL_INFO, "Updated cache for resource at {$url}");
            }
            curl_close($curl);

            return true;
        }

        return false;
    }

    /**
     * @param string $input
     * @return array|bool
     */
    protected function getAsArray($input)
    {
        $input = explode(',', $input);
        if ($input === false) {
            return [];
        }
        if (is_array($input)) {
            array_walk($input, 'trim');
        }

        return [];
    }
    /**
     * @return boolean|array
     */
    protected function getEligibleResources()
    {
        //$this->deleteAllStaticResourcesFiles();

        $query = $this->modx->newQuery('modResource');
        $query->where([
            'cacheable' => true,
            'class_key:!=' => 'modWebLink',
            'deleted' => 0,
            'published' => 1,
        ]);

        return $this->modx->getCollection('modResource', $query);
    }

    /**
     * @param modResource $resource
     * @return mixed
     */
    public function makeRemainingTagsUncacheableStaticFileOnWebCache(modResource $resource)
    {
        $static_resource_file = $this->getStaticResourcePath($resource, true);

        /* attempt to write the complete Resource output to the static file */
        if (!$written = $this->modx->cacheManager->writeFile($static_resource_file, $resource->_content)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, "Error caching _content from Resource {$resource->get('id')} to static file {$static_resource_file}", '', __METHOD__);
        }

        return $written;
    }

}