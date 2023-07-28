<?php
/**
 * This file is part of my homesrv control system.
 *
 * @author  Clemens Brauers <cb@admin-cb.de>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3
 */

namespace clemens321\JvcProjector;

/**
 */
interface JvcClientInterface
{
    public function __construct(string $remoteAddr, ?int $remotePort);

    public function getCommand($command);
    public function setCommand($command, $value = null);

    public function operation($command);
    public function request($command, $readLength = null);

    public function connect();
    public function disconnect();
    public function isConnected();
}
