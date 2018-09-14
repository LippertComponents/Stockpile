# Stockpile

Stockpile is a plugin for MODX Revolution that caches on save. Also provides a snippet, getStockpile
to get resource from cache, no DB/xPDO queries. Saves all fields and TV values to cache.

## Install via Composer

Follow the Local Orchestrator helper to place in the correct location and then edit the composer.json file. Place 
"lci/stockpile": "dev-master" in the require spot. And then put "lci/stockpile" in the auto-install array. And then 
run composer install.

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
 
 - stockpile:build ~ this will (re)cache all resources
 - stockpile:build -t r -i ID ~ this will (re)cache a resource ID
 - stockpile:remove ~ Clear/Remove all stockpile cache
 - stockpile:remove -t r -i ID ~ clear/remove cache for that resource ID