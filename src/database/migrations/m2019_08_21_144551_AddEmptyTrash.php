<?php

/**
 * Auto Generated from Blender
 * Date: 2019/08/21 at 14:45:51 UTC +00:00
 */

use \LCI\Blend\Migrations;

class m2019_08_21_144551_AddEmptyTrash extends Migrations
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $plugin = $this->blender->getBlendableLoader()->getBlendablePlugin('Stockpile');
        $saved = $plugin
            ->setSeedsDir($this->getSeedsDir())
            ->setFieldCategory('Stockpile')
            ->setFieldDescription('Will save/remove Stockpile resource cache on events')
            ->setAsStatic('lci/stockpile/src/elements/plugins/stockpile.plugin.php', 'Orchestrator')
            ->attachOnEvent('OnDocFormSave')
            ->attachOnEvent('OnDocPublished')
            ->attachOnEvent('OnDocUnPublished')
            ->attachOnEvent('OnDocFormDelete')
            ->attachOnEvent('OnSiteRefresh')
            ->attachOnEvent('OnResourceAutoPublish')
            ->attachOnEvent('FredOnFredResourceSave')
            ->attachOnEvent('OnEmptyTrash')
            ->blend(true);

        if ($saved) {
            $this->blender->outSuccess('Stockpile Plugin has been installed');
        } else {
            $this->blender->out('Stockpile Plugin could not be installed', true);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 1. The plugin
        $plugin = $this->blender->getBlendableLoader()->getBlendablePlugin('Stockpile');
        $plugin->setSeedsDir($this->getSeedsDir());

        $saved = $plugin->revertBlend();

        if ($saved) {
            $this->blender->outSuccess('Stockpile Plugin has been reverted');
        } else {
            $this->blender->out('Stockpile Plugin could not be reverted', true);
        }
    }

    /**
     * Method is called on construct, please fill me in
     */
    protected function assignDescription()
    {
        $this->description = 'Listen to the OnEmptyTrash event';
    }

    /**
     * Method is called on construct, please fill me in
     */
    protected function assignVersion()
    {

    }

    /**
     * Method is called on construct, can change to only run this migration for those types
     */
    protected function assignType()
    {
        $this->type = 'master';
    }

    /**
     * Method is called on construct, Child class can override and implement this
     */
    protected function assignSeedsDir()
    {
        $this->seeds_dir = 'm2019_08_21_144551_AddEmptyTrash';
    }
}
