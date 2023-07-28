<?php
/**
 * This file is part of my homesrv control system.
 *
 * @author  Clemens Brauers <cb@admin-cb.de>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3
 */

namespace clemens321\JvcProjector;

use clemens321\JvcProjector\Exception\DeviceNotFoundException;
use clemens321\JvcProjector\Exception\PowerOffException;
use clemens321\JvcProjector\Exception\TimeOutException;

/**
 * Remote control for JVC projector X5000 series.
 *
 * @author  Clemens Brauers <cb@admin-cb.de>
 */

interface JvcConfigInterface
{
    /**
     * Retrieve command array.
     *
     * @return  array[]
     */
    public function getCommands();

    /**
     * Check if there is a specific command defined.
     *
     * @param   string $command
     * @return  bool
     */
    public function hasCommand($command);

    /**
     * Retrieve a specific command definition.
     *
     * @param   string $command
     * @return  array
     */
    public function getCommand($command);

    /**
     * Retrieve all combined command-data-pairs.
     *
     * @return  array
     */
    public function getCommandSplits();

    /**
     * Check if there is a combined command-value-pair.
     *
     * @param   string $command Combined commandValue-Pair
     * @return  bool
     */
    public function hasCommandSplit($command);

    /**
     * Retrieve a named command-value-pair
     *
     * @param   string $command
     * @return  string[] Array('commandName', 'dataName')
     */
    public function getCommandSplit($command);
}
