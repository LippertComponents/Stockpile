<?php
use LCI\MODX\Stockpile\Stockpile;
use LCI\MODX\Stockpile\StaticGenerator;

$eventName = $modx->event->name;

$corePath = $modx->getOption('stockpile.core_path', null, $modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/stockpile/');

/** @var Stockpile */
$stockpile = new Stockpile($modx);

/** @var StaticGenerator $staticGenerator */
$staticGenerator = new StaticGenerator($modx);

switch($eventName) {
    case 'OnBeforeSaveWebPageCache':
        $staticGenerator->makeResourceStaticFileOnWebCache($modx->resource);
        break;

    case 'OnResourceAutoPublish':
        if (isset($results) && isset($results['published_resources']) && is_array($results['published_resources'])) {
            foreach ($results['published_resources'] as $row) {
                $publishedResource = $modx->getObject('modResource', $row['id']);
                if (is_object($publishedResource)) {
                    $stockpile->onSaveResource($publishedResource);

                    $staticGenerator->rebuildStaticResourceOnSave($publishedResource);
                }
            }
        }

        if (isset($results) && isset($results['unpublished_resources']) && is_array($results['unpublished_resources'])) {
            foreach ($results['unpublished_resources'] as $row) {
                $unpublishedResource = $modx->getObject('modResource', $row['id']);
                if (is_object($unpublishedResource)) {
                    $stockpile->onSaveResource($unpublishedResource);

                    $staticGenerator->deleteStaticResourceFile($unpublishedResource);
                }
            }
        }

        break;
    case 'OnDocFormSave':
        // no break;
    case 'OnDocPublished':
        // no break;
    case 'OnDocUnPublished':
        // no break
    case 'OnResourceUndelete':
        /** @var modResource $cleanResource removes extra/dirty data that is passed via the manager */
        $cleanResource = $modx->getObject('modResource', $resource->get('id'));
        $stockpile->onSaveResource($cleanResource);

        $staticGenerator->rebuildStaticResourceOnSave($resource);
        break;
    //case 'OnDocFormDelete':
    case 'OnResourceDelete':
        if(!$stockpile->removeResourceCache($id)) {
            $modx->log(modX::LOG_LEVEL_ERROR, 'Stockpile could not delete cache for resource ID: '.$id.' OnDocFormDelete');
        }

        //$staticGenerator->deleteStaticResourceFile($unpublishedResource);
        break;

    case 'OnSiteRefresh':
        $staticGenerator->rebuildAllResourcesOnClearCache();

        //$stockpile->removeAllResourceCache();

        /**
         *  @TODO TVs
         * OnTemplateVarSave
         * OnTemplateVarRemove
         *
         * No event for template save??
         */
}