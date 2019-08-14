# Stockpile

Stockpile is a plugin for MODX Revolution that caches on save. Also provides a snippet, getStockpile
to get resource from cache, no DB/xPDO queries. Saves all fields and TV values to cache. It can also save resources as static.
Similar to the StatCache extra.

## Install via Composer

Follow the [Local Orchestrator](https://github.com/LippertComponents/LocalOrchestrator) helper to place in the correct 
location and then edit the composer.json file. Place "lci/stockpile": "dev-master" in the require spot. And then 
put "lci/stockpile" in the auto-install array. And then run composer install.

## What is installed?

### Snippet

**getStockpile**

Use the getStockpile snippet to process a chunk form cached resource data or it can send the cached resource 
fields/tvs data to placeholders

| Property | Description | Default |
|--- |--- |--- |
| resourceID| int required,  a valid resource ID | |
| item | string chunk name to be be processed with resource data. If empty snippet will return empty ex: &item=`myChunk` | |
| sendToPlaceholders | boolean - send the data to placeholders | 1 |
| prefix | string - for placeholders, example: &prefix=`sp` [[+sp.pagetitle]] | |
| published | bool - require a resource to be published to complete request, if 0 then will show all | 1 |
| debug | bool - show property values | 0 |

### Plugin

stockpile plugin is installed and is will save data on the following events

| Event | Description |
| --- | --- |
| OnBeforeSaveWebPageCache | It will do $staticGenerator->makeResourceStaticFileOnWebCache($modx->resource); is related option is true |
| OnResourceAutoPublish | It will cache every published resource and remove cache for every unpublished resource |
| OnDocFormSave, OnDocPublished, OnResourceUndelete | It will cache resource |
| OnDocUnPublished | Remove cache |
| OnResourceDelete | Delete resource cache if remaining |
| OnSiteRefresh | Rebuild static cache if LCI_STOCKPILE_REGENERATE_ON_CLEAR_CACHE=1 and static cache is enabled |
| FredOnFredResourceSave | If you use Fred, you will need to manually attach this event to the stockpile plugin after you have installed Fred |

### xPDO, database table

stockpile_cache_que table with class name StockpileQueLog

### Events to write custom plugins

Easily customize what is cached on saved via the OnStockpileSave event. Write your own plugin.

`OnStockpileSave` event: 
    - @var \LCI\MODX\Stockpile $stockpile
    - @var \modResource $resource
    - @var array $data - resource fields and TVs
    - can use $stockpile->setResourceData($data); after you make modifications to save the data to the cache file

`OnStockpileAfterSaveMakeQueLog` and `OnStockpileAfterDeleteMakeQueLog`

#### Example to create a cache buster

```php
use LCI\MODX\Stockpile\Stockpile;
use LCI\MODX\Stockpile\StockpileQue;

$eventName = $modx->event->name;

/**
 * This plugin should only be called in the OnStockpileAfterSaveMakeQueLog and OnStockpileAfterDeleteMakeQueLog event
 * LCI1.com custom plugin to bust the stockpile static cache for related resources
 *
 * @param Stockpile $stockpile
 * @param StockpileQue $stockpileQue
 * @param modResource $resource
 * @param array $data - the resource data as a stockpile array
 */

switch($eventName) {
    case 'OnStockpileAfterDeleteMakeQueLog':
        // no break
    case 'OnStockpileAfterSaveMakeQueLog':
        if ($resource->get('id') == 10) {
            $stockpileQue->rebuildAll();
        }
        
        // Blog:
        $stockpileQue->rebuildParentWhenResourceHasParentID($resource, 10);

        // Products that can show up on a lot of product category pages
        $parent = $resource->get('parent');
        if (in_array($parent, [50, 75])) {
            $stockpileQue->rebuildResourceIDs([20, 22, 30, 45, 63]);
        }

        // Siblings
        if ($resource->get('id') == 15) {
            $stockpileQue->rebuildResourceID(16);
        }
        if ($resource->get('id') == 16) {
            $stockpileQue->rebuildResourceID(15);
        }
        break;
}
```

## CLI Command line

 - ```cd /www/core/vendor/bin```
 - ```php orchestrator``` this will show a complete list of options
 
 _Note_ 
You can run the orchestrator commands from anywhere in the path. For example on MODXCloud this works:  
```php /www/core/vendor/bin/orchestrator ``` 
AND
```
cd /www/core/
php vendor/bin/orchestrator 
```
 
### Stockpile commands
 
 - `php orchestrator stockpile:build` ~ this will (re)cache all resources
 - `php orchestrator stockpile:build --ids 2,3` ~ this will (re)cache a comma separated list of resource IDs
    - Short hand: `php orchestrator stockpile:build -i 2,3`
 - `php orchestrator stockpile:remove` ~ Clear/Remove all stockpile cache
 - `php orchestrator stockpile:remove --ids 2,3` ~ clear/remove cache for that resource ID
    - Short hand: `php orchestrator stockpile:remove -i 2,3`
 - `php orchestrator stockpile:que`
    Process Stockpile cache que, if using a custom plugin then it is suggested to set a cron job to run this process 
    every 5 to 15 minutes depending on how fast you want related content pages to be recached.

## Static Generator 

Based on StatCache: https://github.com/opengeek/statcache, configure with the .env settings below.

### Nginx rules

Example on MODXCloud

*Remove*
```
location / {
    try_files $uri $uri/ @modx-rewrite;
}
```

*Replace with*
```
# Start Stockpile Static Generator
set $cache_prefix 'core/cache/static';
if ($http_user_agent = 'MODX RegenCache') {
    set $cache_prefix 'no cache';
}

location / {
    try_files /$cache_prefix$uri~index.html /$cache_prefix$uri~.html /$cache_prefix$uri $uri $uri/ @modx-rewrite;
}

# END Stockpile Static Generator
```

## Configure with .env

If you have not created an .env in the MODX/core directory do so and optionally add the following

| Name | Default | Description | 
| --- | --- | --- |
| LCI_STOCKPILE_ENABLE_STATIC | 0 | Set to 1 or true to enable caching resources to static | 
| LCI_STOCKPILE_CACHE_PATH | core/cache/static/ | The path to cache static resources appended to MODX_BASE_PATH | 
| LCI_STOCKPILE_EXCLUDE_TV_NAME |  | Optional, if set and TV exists and resource has a value of 1 or true it will not be cached. | 
| LCI_STOCKPILE_CONTENT_TYPES |  | If specified and non-empty, only cache Resources with the specified ContentType id's. Accepts a comma-delimited list of ContentType id's. | 
| LCI_STOCKPILE_CONTEXTS |  | If specified and non-empty, only cache Resources in the specified Contexts. Accepts a comma-delimited list of Context keys. | 
| LCI_STOCKPILE_MIME_TYPES |  | If specified and non-empty, only cache Resources with the specified mime-types. Accepts a comma-delimited list of mime-types. | 
| LCI_STOCKPILE_EXCLUDE_BINARY_CONTENT_TYPES | 1 | Skip Resources that have a binary Content Type. | 
| LCI_STOCKPILE_EXCLUDE_REMAINING_TAGS | 1 | Exclude Resources that have tags remaining in the content that is being cached for the Resource. | 
| LCI_STOCKPILE_REGENERATE_ON_CLEAR_CACHE | 0 | If 1 will attempt to regenerate static files when clearing site cache. | 
| LCI_STOCKPILE_REGENERATE_ON_SAVE | 1 | Regenerate an existing static file when a Resource is saved in the manager. | 
| LCI_STOCKPILE_REGENERATE_USERAGENT | MODX RegenCache | The User-Agent HTTP header to send when regenerating static files. Your web server should be configured to not serve the static files when the User-Agent equals the value specified here. | 
| LCI_STOCKPILE_USE_URL_SCHEME |  | If enabled, includes the url_scheme (without the ://) as part of the `statcache_path`. Useful for sites using multiple Contexts for sub-domains or multiple domains. NOTE: using this requires changes to your web server rewrites rules. | 
| LCI_STOCKPILE_USE_HTTP_HOST |  | If enabled, includes the http_host as part of the `statcache_path`. Useful for sites using multiple Contexts for sub-domains or multiple domains. NOTE: using this requires changes to your web server rewrites rules. | 


