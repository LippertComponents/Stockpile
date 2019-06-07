<?php

namespace LCI\MODX\Stockpile\Console;

use LCI\MODX\Console\Application;
use LCI\MODX\Console\Command\PackageCommands;
use LCI\MODX\Console\Console;

class ActivePackageCommands implements PackageCommands
{
    /** @var Console  */
    protected $console;

    /** @var array  */
    protected $commands = [
        'modx_installed' => [
            'LCI\MODX\Stockpile\Console\Command\BuildCache',
            'LCI\MODX\Stockpile\Console\Command\ProcessCacheQue',
            'LCI\MODX\Stockpile\Console\Command\RemoveCache'
        ],
        'modx_not_installed' => []
    ];

    public function __construct(Console $console)
    {
        $this->console = $console;
    }

    /**
     * @return array ~ of Fully qualified names of all command class
     */
    public function getAllCommands()
    {
        $all_commands = [];
        foreach ($this->commands as $group => $commands) {
            foreach ($commands as $command) {
                $all_commands[] = $command;
            }
        }

        return $all_commands;
    }

    /**
     * @return array ~ of Fully qualified names of active command classes. This could differ from all if package creator
     *      has different commands based on the state like the DB. Example has Install and Uninstall, only one would
     *      be active/available depending on the state
     */
    public function getActiveCommands()
    {
        $active_commands = [];

        if ($this->console->isModxInstalled()) {

            $commands = $this->commands['modx_installed'];
            foreach ($commands as $command) {
                $active_commands[] = $command;
            }

        } else {
            $commands = $this->commands['modx_not_installed'];
            foreach ($commands as $command) {
                $active_commands[] = $command;
            }
        }

        return $active_commands;
    }

    /**
     * @param \LCI\MODX\Console\Application $application
     * @return \LCI\MODX\Console\Application
     */
    public function loadActiveCommands(Application $application)
    {
        $commands = $this->getActiveCommands();

        foreach ($commands as $command) {
            $class = new $command();

            if (is_object($class) ) {
                if (method_exists($class, 'setConsole')) {
                    $class->setConsole($this->console);
                }

                $application->add($class);
            }
        }

        return $application;
    }
}
