<?php

/**
 * Auto Generated from Blender
 * Date: 2018/09/14 at 17:58:31 UTC +00:00
 */

use \LCI\Blend\Migrations;

class InstallLCIStockpile extends Migrations
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. The plugin
        $plugin = $this->blender->getBlendablePlugin('Stockpile');
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
            ->blend();

        if ($saved) {
            $this->blender->outSuccess('Stockpile Plugin has been installed');
        } else {
            $this->blender->out('Stockpile Plugin could not be installed', true);
        }

        // 2. New System Event:
        // OnStockpileSave
        $name = 'OnStockpileSave';
        $event = $this->modx->getObject('modEvent', ['name' => $name]);
        if (is_object($event)) {
            $this->blender->out($name.' event has already been installed');

        } else {
            /** @var \modEvent $event */
            $event = $this->modx->newObject('modEvent');
            $event->set('name', $name);
            $event->set('service', 1);
            $event->set('groupname', 'Resources');

            if ($event->save()) {
                $this->blender->out($name.' event has been installed');

            } else {
                $this->blender->out($name.' event did not install', true);
            }
        }

        // 3. The snippets:
        // Snippets
        $name = 'getStockpile';

        $snippet = $this->blender->getBlendableSnippet($name);
        $saved = $snippet
            ->setSeedsDir($this->getSeedsDir())
            ->setFieldCategory('Stockpile')
            ->setFieldDescription('Return/process for a single resource'.PHP_EOL.
                'Will take chunk and process AND/OR Send to placeholders'.PHP_EOL.
                'Debug will output the available properties')
            ->setAsStatic('lci/stockpile/src/elements/snippets/getStockpile.php', 'Orchestrator')
            ->blend();

        if ($saved) {
            $this->blender->outSuccess($name . ' Snippet has been installed');
        } else {
            $this->blender->out($name . ' Snippet could not be installed', true);
        }

        // now register the commands:
        $console = new \LCI\MODX\Console\Console();
        $console->registerPackageCommands('LCI\MODX\Stockpile\Console\ActivePackageCommands');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 1. The plugin
        $plugin = $this->blender->getBlendablePlugin('Stockpile');
        $saved = $plugin->revertBlend();

        if ($saved) {
            $this->blender->outSuccess('Stockpile Plugin has been reverted');
        } else {
            $this->blender->out('Stockpile Plugin could not be reverted', true);
        }

        // 2. New System Event:
        // OnStockpileSave
        $name = 'OnStockpileSave';
        $event = $this->modx->getObject('modEvent', ['name' => $name]);
        if (is_object($event) && $event->remove()) {
            $this->blender->out($name.' event has been removed');

        } else {
            $this->blender->out($name.' event was not removed', true);
        }

        // 3. The snippets:
        // Snippets
        $name = 'getStockpile';

        $snippet = $this->blender->getBlendableSnippet($name);
        $saved = $snippet->revertBlend();

        if ($saved) {
            $this->blender->outSuccess($name . ' Snippet has been reverted');
        } else {
            $this->blender->out($name . ' Snippet could not be reverted', true);
        }

        // remove the commands
        $console = new \LCI\MODX\Console\Console();
        $console->cancelRegistrationPackageCommands('LCI\MODX\Stockpile\Console\ActivePackageCommands');
    }

    /**
     * Method is called on construct, please fill me in
     */
    protected function assignDescription()
    {
        $this->description = 'Install Stockpile';
    }

    /**
     * Method is called on construct, please fill me in
     */
    protected function assignVersion()
    {
        $this->version = '1.0.0';
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
        $this->seeds_dir = 'InstallLCIStockpile';
    }
}