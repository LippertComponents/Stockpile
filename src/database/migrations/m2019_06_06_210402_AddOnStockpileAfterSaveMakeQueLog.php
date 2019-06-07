<?php

/**
 * Auto Generated from Blender
 * Date: 2019/06/06 at 21:04:02 UTC +00:00
 */

use \LCI\Blend\Migrations;
use LCI\MODX\Stockpile\Stockpile;
use LCI\MODX\Stockpile\StockpileQue;

class m2019_06_06_210402_AddOnStockpileAfterSaveMakeQueLog extends Migrations
{
    protected $system_events = [
        'OnStockpileAfterSaveMakeQueLog',
        'OnStockpileAfterDeleteMakeQueLog'
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // This will do a modx->addPackage for custom xpdo model
        $stockpileQue = new StockpileQue($this->modx, (new Stockpile($this->modx)));

        $manager = $this->modx->getManager();

        // create the table from the object name
        $manager->createObjectContainer('StockpileQueLog');

        // New System Events:
        foreach ($this->system_events as $name) {
            $event = $this->modx->getObject('modEvent', ['name' => $name]);
            if (is_object($event)) {
                $this->blender->out($name . ' event has already been installed');

            } else {
                /** @var \modEvent $event */
                $event = $this->modx->newObject('modEvent');
                $event->set('name', $name);
                $event->set('service', 1);
                $event->set('groupname', 'Resources');

                if ($event->save()) {
                    $this->blender->out($name . ' event has been installed');

                } else {
                    $this->blender->out($name . ' event did not install', true);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->system_events as $name) {
            $event = $this->modx->getObject('modEvent', ['name' => $name]);
            if (is_object($event) && $event->remove()) {
                $this->blender->out($name . ' event has been removed');

            } else {
                $this->blender->out($name . ' event was not removed', true);
            }
        }

        // This will do a modx->addPackage for custom xpdo model
        $stockpileQue = new StockpileQue($this->modx, (new Stockpile($this->modx)));

        $manager = $this->modx->getManager();

        // drop the table from the object name
        $manager->removeObjectContainer('StockpileQueLog');
    }

    /**
     * Method is called on construct, please fill me in
     */
    protected function assignDescription()
    {
        $this->description = 'Add StockpileQueLog xPDO class & table plus OnStockpileAfterSaveMakeQueLog and '.
            'OnStockpileAfterDeleteMakeQueLog events that Stockpile will fire after save';
    }

    /**
     * Method is called on construct, please fill me in
     */
    protected function assignVersion()
    {
        $this->version = '1.3.0';
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
        $this->seeds_dir = 'm2019_06_06_210402_AddOnStockpileAfterSaveMakeQueLog';
    }
}
