<?php
/**
 * Created by PhpStorm.
 * User: jgulledge
 * Date: 10/2/2017
 * Time: 11:02 AM
 */

namespace Lci;


class GetStockpileSnippet
{
    /** @var bool  */
    protected $debug = false;

    /** @var array  */
    protected $properties = [];

    /** @var null  */
    protected $stockpile_resource = null;

    /**
     * StockpileSnippet constructor.
     *
     * @param \modx $modx
     * @param array $properties
     */
    public function __construct(&$modx, $properties=[])
    {
        $this->modx = $modx;
        $this->loadDefaultProperties($properties);
    }

    /**
     * @return string
     */
    public function runSnippet()
    {
        if ($this->properties['sendToPlaceholders']) {
            $this->toPlaceholders();
        }
        return $this->debugMessage().$this->getOutput();
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param int $id - required a valid resource ID
     *
     * @return $this
     */
    public function setPropResourceID(int $id)
    {
        $this->properties['resourceID'] = $id;
        return $this;
    }

    /**
     * @param string $chunk_name ~ chunk name to be be processed with resource data. If empty snippet will return empty
     *
     * @return $this
     */
    public function setPropItem($chunk_name)
    {
        $this->properties['item'] = $chunk_name;
        return $this;
    }

    /**
     * @param bool $send - default is true
     *
     * @return $this
     */
    public function setPropSendToPlaceholders(bool $send=true)
    {
        $this->properties['sendToPlaceholders'] = $send;
        return $this;
    }

    /**
     * @param string $prefix - for placeholders
     *
     * @return $this
     */
    public function setPropPrefix($prefix)
    {
        $this->properties['prefix'] = $prefix;
        return $this;
    }

    /**
     * @param bool $published - require a resource to be published to complete, default is true, if 0 then will show all
     *
     * @return $this
     */
    public function setPropPublished(bool $published=true)
    {
        $this->properties['published'] = $published;
        return $this;
    }

    /**
     * @param bool $debug
     *
     * @return $this
     */
    public function setDebug(bool $debug=true)
    {
        if ($debug) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        }
        $this->debug = $debug;
        return $this;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        $output = '';
        if (!empty($this->properties['item'])) {
            $data = $this->getResource();
            if (!empty($this->properties['prefix'])) {
                $data = [$this->properties['prefix'] => $this->getResource()];
            }
            $output = $this->modx->getChunk($this->properties['item'], $data);
        }
        return $output;
    }

    /**
     * @return $this
     */
    public function toPlaceholders()
    {
        $this->modx->toPlaceholders($this->getResource(), $this->properties['prefix']);
        return $this;
    }

    /**
     * @return string
     */
    public function debugMessage()
    {
        $debug_message = '';
        if ($this->debug) {
            $debug_message = '<pre>'.PHP_EOL.
                '## ScriptProperties ##';

            $new_line = PHP_EOL.str_pad('- ', 4, ' ', STR_PAD_LEFT);

            $debug_message .= $new_line.'resourceID: '.$this->properties['resourceID']
                . $new_line.'item: '.$this->properties['item']
                . $new_line.'sendToPlaceholders :'.$this->properties['sendToPlaceholders']
                . $new_line.'prefix: '.$this->properties['prefix']
                . $new_line.'published: '.$this->properties['published']
                . $new_line.'debug: '.$this->debug;

            $debug_message .= PHP_EOL.PHP_EOL.
                '## Resource Fields, available placeholders ##';

            foreach ($this->getResource() as $name => $value) {

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
        return $debug_message;
    }

    /**
     * @return array|mixed|null
     */
    protected function getResource()
    {
        if (is_null($this->stockpile_resource)) {
            $stockpile = new Stockpile($this->modx);
            $this->stockpile_resource = $stockpile->getResource($this->properties['resourceID']);
        }
        return $this->stockpile_resource;
    }

    /**
     * @param $properties
     */
    protected function loadDefaultProperties($properties)
    {
        $this->setPropResourceID((int) $this->modx->getOption('resourceID', $properties, 0));
        $this->setPropItem($this->modx->getOption('item', $properties, ''));
        $this->setPropSendToPlaceholders((bool) $this->modx->getOption('sendToPlaceholders', $properties, true));
        $this->setPropPrefix($this->modx->getOption('prefix', $properties, ''));
        $this->setPropPublished((bool) $this->modx->getOption('published', $properties, true));
        $this->setDebug((bool) $this->modx->getOption('debug', $properties, false));
    }
}
