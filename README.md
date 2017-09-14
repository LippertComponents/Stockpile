# Stockpile

Stockpile as a cache on save caching plugin for MODX Revolution. Will only cache published resources.

# Install

 - Copy files to MODX core
 - Using your command line tool of choice and do ```cd /www/core/components/stockpile/model```
 - Then run PHP ```composer install``` 
 - Now ```cd /www/core/components/stockpile/cli```
 - Finally ```php StockpileCli.php -i```

# How to use

 - Customize
 
# CLI

Command line:

 - ```cd /www/core/components/stockpile/cli```
 - ```php StockpileCli.php```
 
Options
 
 - Stockpile -a (re)cache all resources
 - Stockpile -r ID (re)cache a resource ID
 - Stockpile -c Clear/Remove cache, if resource ID passed then just that one otherwise all
 - Stockpile -i install will install plugin and snippet