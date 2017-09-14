<?php

/**
 * Return/process for a single resource
 *
 * Will take chunk and process
 * OR
 * Send to placeholder
 *
 * Debug will output the available properties
 */

/** @var  int $resource_id - required a valid resource ID */
$resource_id = (int) $modx->getOption('resourceID', $scriptProperties, 0);

/**
 * @var string $item ~ chunk name to be be processed with resource data. If empty snippet will return empty
 * scriptProperty: &item=`myChunk`
 */
$item = $modx->getOption('item', $scriptProperties, '');

/** @var  bool $send_to_placeholders, default is true */
$send_to_placeholders = (bool) $modx->getOption('sendToPlaceholders', $scriptProperties, true);

/**
 * @var string $prefix - for placeholders
 *
 *  [[+prefix.pagetitle]]
 */
$prefix = $modx->getOption('prefix', $scriptProperties, '');

/** @var  bool $published - require a resource to be published to complete, default is true, if 0 then will show all */
$published = (bool) $modx->getOption('published', $scriptProperties, true);

/** @var boolean $debug */
$debug = (bool) $modx->getOption('debug', $scriptProperties, false);

if ( $debug ) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

$corePath = $modx->getOption('stockpile.core_path', null, $modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/stockpile/');
require_once $corePath . 'model/Stockpile.php';

/** @var \Lci\Stockpile */
$stockpile = new \Lci\Stockpile($modx);

$stockpile_resource = $stockpile->getResource($resource_id);

$debug_message = '';
if ($debug) {
    $debug_message = '<pre>'.PHP_EOL.
        '## ScriptProperties ##';

    $new_line = PHP_EOL.str_pad('- ', 4, ' ', STR_PAD_LEFT);

    $debug_message .= $new_line.'resourceID: '.$resource_id
        . $new_line.'item: '.$item
        . $new_line.'sendToPlaceholders :'.$send_to_placeholders
        . $new_line.'prefix: '.$prefix
        . $new_line.'published: '.$published
        . $new_line.'debug: '.$debug;

    $debug_message .= PHP_EOL.PHP_EOL.
        '## Resource Fields, available placeholders ##';

    foreach ($stockpile_resource as $name => $value) {

        if (is_array($value)) {
            foreach ($value as $n => $v) {
                $debug_message .= $new_line.$name.'.'.$n.': '.htmlentities($v);
            }

        } else {
            $debug_message .= $new_line.$name.': '.htmlentities($value);
        }
    }
    $debug_message .= '</pre>';
}

$output = '';
if (!empty($item)) {
    $output = $modx->getChunk($item, $stockpile_resource);
}

if ($send_to_placeholders) {
    $modx->toPlaceholders($stockpile_resource, $prefix);
}

return $debug_message.$output;