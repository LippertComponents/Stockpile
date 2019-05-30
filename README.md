# Stockpile

Stockpile is a plugin for MODX Revolution that caches on save. Also provides a snippet, getStockpile
to get resource from cache, no DB/xPDO queries. Saves all fields and TV values to cache. It can also save resources as static.
Similar to the StatCache extra.

## Install via Composer

Follow the [Local Orchestrator](https://github.com/LippertComponents/LocalOrchestrator) helper to place in the correct 
location and then edit the composer.json file. Place "lci/stockpile": "dev-master" in the require spot. And then 
put "lci/stockpile" in the auto-install array. And then run composer install.

## How to use

 - Use the getStockpile snippet to process a chunk form cached resource data or it can send the cached resource 
fields/tvs data to placeholders
 - Customize what is cached on saved via the OnStockpileSave event. Write your own plugin.
 - Passed from OnStockpileSave event: 
    - @var \LCI\MODX\Stockpile $stockpile
    - @var \modResource $resource
    - @var array $data - resource fields and TVs
    - can use $stockpile->setResourceData($data); after you make mods
 
## CLI

Command line:

 - ```cd /www/core/vendor/bin```
 - ```php orchestrator```
 
Options
 
 - `stockpile:build` ~ this will (re)cache all resources
 - `stockpile:build --type r -id 2` ~ this will (re)cache a resource ID
    - Short hand: `stockpile:build -t r -i 2`
 - `stockpile:remove` ~ Clear/Remove all stockpile cache
 - `stockpile:remove --type r -id 2` ~ clear/remove cache for that resource ID
    - Short hand: `stockpile:remove -t r -i 2`

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


