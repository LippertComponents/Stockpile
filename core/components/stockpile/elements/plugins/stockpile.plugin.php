<?php
$eventName = $modx->event->name;

$corePath = $modx->getOption('stockpile.core_path', null, $modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/stockpile/');

require_once $corePath . 'model/Stockpile.php';

/** @var \Lci\Stockpile */
$stockpile = new \Lci\Stockpile($this->modx);

switch($eventName) {

    case 'OnDocFormSave':
        // no break;
    case 'OnDocPublished':
        // no break;
    case 'OnDocUnPublished':
        $stockpile->onSaveResource($resource);
        break;
    case 'OnDocFormDelete':
        $stockpile->removeResourceCache($id);
        break;

    case 'OnSiteRefresh':
        // @TODO system setting
        //$stockpile->removeAllResourceCache();

        /**
         *  @TODO TVs
         * OnTemplateVarSave
         * OnTemplateVarRemove
         *
         * No event for template save??
         */
}