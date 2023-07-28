<?php
/**
 * This file is part of my homesrv control system.
 *
 * @author  Clemens Brauers <cb@admin-cb.de>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3
 */

namespace clemens321\JvcProjector\Exception;

/**
 * Base ExceptionInterface for JvcProjector component.
 *
 * @author  Clemens Brauers <cb@admin-cb.de>
 */
interface ExceptionInterface
{
    /**
     * Retrieve a localized end-user error message.
     *
     * @return  string
     */
    public function getDisplayMessage();
}
