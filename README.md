# Stockpile

Stockpile is a cache on save caching plugin for MODX Revolution. Also provides a snippet, getStockpile
to get resource from cache, no DB/xPDO queries. Saves all fields and TV values to cache.

# Install

 - Copy files to MODX core
 - Using your command line tool of choice and do ```cd /www/core/components/stockpile/model```
 - Then run PHP ```composer install``` 
 - Now ```cd /www/core/components/stockpile/cli```
 - Finally ```php StockpileCli.php -i```

# How to use

 - Use snippet to process a chunk or set resource fields/tvs to placeholders
 - Customize what is cached on saved via the OnStockpileSave event. Write your own plugin 
 
# CLI

Command line:

 - ```cd /www/core/components/stockpile/cli```
 - ```php StockpileCli.php```
 
Options
 
 - Stockpile -a (re)cache all resources
 - Stockpile -r ID (re)cache a resource ID
 - Stockpile -c Clear/Remove cache, if resource ID passed then just that one otherwise all
 - Stockpile -i install will install plugin and snippet