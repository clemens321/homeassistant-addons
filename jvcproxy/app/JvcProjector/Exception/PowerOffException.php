<?php
/**
 * This file is part of my homesrv control system.
 *
 * @author  Clemens Brauers <cb@admin-cb.de>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3
 */

namespace clemens321\JvcProjector\Exception;

/**
 * Projector not on exception.
 *
 * @author  Clemens Brauers <cb@admin-cb.de>
 */
class PowerOffException extends \RuntimeException implements ExceptionInterface
{
    /**
     * Retrieve a localized end-user error message.
     *
     * @return  string
     */
    public function getDisplayMessage()
    {
        return 'Der Projektor ist ausgeschaltet.';
    }
}
