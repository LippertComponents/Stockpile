<?php
$eventName = $modx->event->name;

$corePath = $modx->getOption('stockpile.core_path', null, $modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/stockpile/');

/** @var \LCI\MODX\Stockpile\Stockpile */
$stockpile = new \LCI\MODX\Stockpile\Stockpile($modx);

switch($eventName) {

    case 'OnResourceAutoPublish':
        if (isset($results) && isset($results['published_resources']) && is_array($results['published_resources'])) {
            foreach ($results['published_resources'] as $row) {
                $publishedResource = $modx->getObject('modResource', $row['id']);
                if (is_object($publishedResource)) {
                    $stockpile->onSaveResource($publishedResource);
                }
            }
        }

        if (isset($results) && isset($results['unpublished_resources']) && is_array($results['unpublished_resources'])) {
            foreach ($results['unpublished_resources'] as $row) {
                $unpublishedResource = $modx->getObject('modResource', $row['id']);
                if (is_object($unpublishedResource)) {
                    $stockpile->onSaveResource($unpublishedResource);
                }
            }
        }

        break;
    case 'OnDocFormSave':
        // no break;
    case 'OnDocPublished':
        // no break;
    case 'OnDocUnPublished':
        /** @var modResource $cleanResource removes extra/dirty data that is passed via the manager */
        $cleanResource = $modx->getObject('modResource', $resource->get('id'));
        $stockpile->onSaveResource($cleanResource);
        break;
    case 'OnDocFormDelete':
        if(!$stockpile->removeResourceCache($id)) {
            $modx->log(modX::LOG_LEVEL_ERROR, 'Stockpile could not delete cache for resource ID: '.$id.' OnDocFormDelete');
        }
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