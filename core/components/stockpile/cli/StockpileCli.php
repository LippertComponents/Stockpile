<?php
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))).DIRECTORY_SEPARATOR.'config.core.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

class StockpileCli
{
    public $modx;

    /** @var bool  */
    protected $run = false;

    protected $climate;

    /** @var \Lci\Stockpile  */
    protected $stockpile;

    /**
     * @param DECIMAL $begin_time
     */
    protected $begin_time = null;

    function __construct()
    {
        $this->begin_time = microtime(true);

        $this->modx = new modX();

        $this->modx->initialize('mgr');

        $corePath = $this->modx->getOption('stockpile.core_path', null, $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/stockpile/');

        require_once $corePath . 'model/Stockpile.php';
        require_once $corePath . 'model/vendor/autoload.php';

        $this->climate = new League\CLImate\CLImate;

        $this->climate->description('Stockpile Cache Management for MODX Revolution');
        $this->buildAllowableArgs();
        //$this->climate->arguments->parse();

        /** @var \Lci\Stockpile */
        $this->stockpile = new \Lci\Stockpile($this->modx);
    }

    /**
     *
     */
    public function run()
    {
        $this->climate->out('Cache path: '.$this->stockpile->getCachePath());

        if ( $this->climate->arguments->get('all') ) {
            $this->stockpile->cacheAllResources($this->climate);
            $this->climate->out('All resources have been cached');

        } elseif ( $this->climate->arguments->get('cache') ) {

            $id = $this->climate->arguments->get('cache');
            if (empty($id) || !is_numeric($id)) {
                $this->climate->error('Please pass a valid resource ID');
            } else {
                $resource = $this->modx->getObject('modResource', (int)$id);
                if (!$resource) {
                    $this->climate->error('Please pass a valid resource ID, '.$id. ' is invalid');
                } else {
                    $this->stockpile->cacheResource($resource);
                    $this->climate->out('Resource cached: '.$id.' '.$resource->get('pagetitle'));
                }
            }

        } elseif ( $this->climate->arguments->defined('remove') ) {

            $id = $this->climate->arguments->get('remove');
            if (empty($id) || !is_numeric($id)) {
                if ($this->stockpile->removeAllResourceCache()) {
                    $this->climate->out('Cache has been removed');
                } else {
                    $this->climate->error('Please pass a valid resource ID');
                }

            } else {
                if ($this->stockpile->removeResourceCache($id)) {
                    $this->climate->out('Cache has been removed for '. $id);
                } else {
                    $this->climate->error('Cache could not be removed for '.$id);
                }

            }

        } elseif ( $this->climate->arguments->defined('install') ) {
            $this->install();

        } else {
            $this->getUsage();
        }

        $this->climate->out('Completed in '.(microtime(true)-$this->begin_time).' seconds')->br();
    }

    /**
     *
     */
    protected function install()
    {
        // plugin:
        //$corePath = $this->modx->getOption('stockpile.core_path', null, $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/stockpile/');
        $corePath = 'core/components/stockpile/';
        $name = 'Stockpile';
        $desc = 'Will save/remove Stockpile resource cache on events';

        $plugin = $this->modx->getObject('modPlugin', ['name' => $name]);
        if (is_object($plugin)) {
            $this->climate->error($name.' plugin has already been installed');
        } else {
            /** @var \modPlugin $plugin */
            $plugin = $this->modx->newObject('modPlugin');
            $plugin->set('name', $name);
            $plugin->set('description', $desc);
            $plugin->set('source', 1);
            $plugin->set('static', 1);
            $plugin->set('static_file', $corePath.'elements/plugins/stockpile.plugin.php');

            $on_event_names = [
                'OnDocFormSave',
                'OnDocPublished',
                'OnDocUnPublished',
                'OnDocFormDelete',
                'OnSiteRefresh'
            ];

            $events=[];
            foreach ($on_event_names as $event_name) {
                $event = $this->modx->newObject('modPluginEvent');
                $event->set('event', $event_name);

                $events[] = $event;
            }

            $plugin->addMany($events, 'PluginEvents');

            if ($plugin->save()) {
                $this->climate->out($name.' plugin has been installed');

            } else {
                $this->climate->error($name.' plugin did not install');
            }
        }

        // Snippets
        $name = 'getStockpile';
        $desc = 'Return/process for a single resource'.PHP_EOL.
            'Will take chunk and process AND/OR Send to placeholders'.PHP_EOL.
            'Debug will output the available properties';

        $snippet = $this->modx->getObject('modSnippet', ['name' => $name]);
        if (is_object($snippet)) {
            $this->climate->error($name.' snippet has already been installed');
        } else {
            /** @var \modSnippet $snippet */
            $snippet = $this->modx->newObject('modSnippet');
            $snippet->set('name', $name);
            $snippet->set('description', $desc);
            $snippet->set('source', 1);
            $snippet->set('static', 1);
            $snippet->set('static_file', $corePath.'elements/snippets/getStockpile.php');

            if ($snippet->save()) {
                $this->climate->out($name.' snippet has been installed');

            } else {
                $this->climate->error($name.' snippet did not install');
            }
        }

        // Add new system events
        // OnStockpileSave
        $name = 'OnStockpileSave';
        $event = $this->modx->getObject('modEvent', ['name' => $name]);
        if (is_object($event)) {
            $this->climate->error($name.' event has already been installed');
        } else {
            /** @var \modSnippet $event */
            $event = $this->modx->newObject('modEvent');
            $event->set('name', $name);
            $event->set('service', 1);// ??
            $event->set('groupname', 'Resources');// ??

            if ($event->save()) {
                $this->climate->out($name.' event has been installed');

            } else {
                $this->climate->error($name.' event did not install');
            }
        }
    }
    /**
     *
     */
    protected function buildAllowableArgs()
    {
        // Help menu:
        $this->climate->arguments->add([
            'all' => [
                'prefix'      => 'a',
                'longPrefix'  => 'all',
                'description' => '(re)Cache all resources',
                'noValue'     => true,
            ],
            'cache' => [
                'prefix'      => 'c',
                'longPrefix'  => 'cache',
                'description' => '(re)Cache a single resource, pass ID ex: -c 123',
            ],
            'remove' => [
                'prefix'      => 'r',
                'longPrefix'  => 'remove',
                'description' => 'Clear/Remove cache, if resource ID passed then just that one otherwise all, pass ID ex: -r 123',
            ],
            'install' => [
                'prefix'      => 'i',
                'longPrefix'  => 'install',
                'description' => 'Install Stockpile in MODX',
                'noValue'     => true,
            ],
            'help' => [
                'prefix'      => 'h',
                'longPrefix'  => 'help',
                'description' => 'Prints a usage statement',
                'noValue'     => true,
            ]
        ]);

        $this->climate->arguments->parse();
        if ( $this->climate->arguments->get('promote')) {
            $this->run = true;
        }
    }

    /**
     *
     */
    protected function getUsage()
    {
        $this->climate->usage();
    }

}

$awS3Cli = new StockpileCli();
$awS3Cli->run();