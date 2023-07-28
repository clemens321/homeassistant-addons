<?php
/**
 * This file is part of my homesrv control system.
 *
 * @author  Clemens Brauers <cb@admin-cb.de>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3
 */

namespace clemens321\JvcProjector;

/**
 * Remote control commands for JVC projector D-ILA X5000.
 *
 * @author  Clemens Brauers <cb@admin-cb.de>
 */
abstract class AbstractJvcConfig implements JvcConfigInterface
{
    protected $commands;
    protected $commandSplits;

    /**
     * Check if there is a specific command defined.
     *
     * @param   string $command
     * @return  bool
     */
    public function hasCommand($command)
    {
        $commands = $this->getCommands();

        return isset($commands[$command]);
    }

    /**
     * Retrieve a specific command definition.
     *
     * @param   string $command
     * @return  array
     */
    public function getCommand($command)
    {
        $commands = $this->getCommands();

        if (!isset($commands[$command])) {
            throw new \InvalidArgumentException(sprintf(
                'Command not found: %s',
                $command
            ));
        }

        return $commands[$command];
    }

    /**
     * Retrieve all combined command-data-pairs.
     *
     * @return  array
     */
    public function getCommandSplits()
    {
        if (!isset($this->commandSplits)) {
            $commands = $this->getCommands();
            $this->commandSplits = [];

            foreach ($commands as $cmdName => $cmdRow) {
                if (!isset($cmdRow['data'])) {
                    continue;
                }
                foreach ($cmdRow['data'] as $dataRow) {
                    $this->commandSplits[$cmdName.ucfirst($dataRow['name'])] = [ $cmdName, $dataRow['name'] ];
                }
            }
        }

        return $this->commandSplits;
    }

    /**
     * Check if there is a combined command-value-pair.
     *
     * @param   string $command Combined commandValue-Pair
     * @return  bool
     */
    public function hasCommandSplit($command)
    {
        $commandSplit = $this->getCommandSplits();

        return isset($commandSplit[$command]);
    }

    /**
     * Retrieve a named command-value-pair
     *
     * @param   string $command
     * @return  string[] Array('commandName', 'dataName')
     */
    public function getCommandSplit($command)
    {
        $commandSplit = $this->getCommandSplits();

        if (!isset($commandSplit[$command])) {
            throw new \InvalidArgumentException(sprintf(
                'Command-Split not found: %s',
                $command
            ));
        }

        return $commandSplit[$command];
    }
}
