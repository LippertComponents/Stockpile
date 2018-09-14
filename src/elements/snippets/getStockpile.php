<?php

/**
 * Return/process for a single resource
 *
 * Will take chunk and process
 * OR
 * Send to placeholder
 *
 * Debug will output the available properties
 *
 * @snippet $scriptProperties
 *
 * @var  int resourceID - required a valid resource ID
 *
 * @var string item ~ chunk name to be be processed with resource data. If empty snippet will return empty
 * scriptProperty: &item=`myChunk`
 *
 * @var  bool sendToPlaceholders, default is true
 *
 * @var string prefix - for placeholders
 *  [[+prefix.pagetitle]]
 *
 * @var  bool published - require a resource to be published to complete, default is true, if 0 then will show all
 *
 * @var boolean debug
 */

$stockpileSnippet = new \LCI\MODX\Stockpile\GetStockpileSnippet($modx, $scriptProperties);
return $stockpileSnippet->runSnippet();
