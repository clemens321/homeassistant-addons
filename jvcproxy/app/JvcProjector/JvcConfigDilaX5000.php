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
class JvcConfigDilaX5000 extends AbstractJvcConfig implements JvcConfigInterface
{
    /**
     * Retrieve command array.
     *
     * @return  array[]
     */
    public function getCommands()
    {
        if (!isset($this->commands)) {
            $this->commands = [
                // 4.1 NULL-command
                'test' => [
                    'command'       => "\0\0",
                    'onlyPowerOn'   => false,
                    'readLength'    => 0,
                    'readable'      => false,
                ],
                // 4.2 Power [PoWer]
                'power' => [
                    'command'       => 'PW',
                    'onlyPowerOn'   => false,
                    'readLength'    => 1,
                    'data' => [
                        [ 'key' => '0', 'name' => 'off' ],
                        [ 'key' => '1', 'name' => 'on' ],
                        [ 'key' => '2', 'name' => 'cooling',  'writeable' => false ],
                        [ 'key' => '3', 'name' => 'reserved', 'writeable' => false ],
                        [ 'key' => '4', 'name' => 'error',    'writeable' => false ],
                    ],
                ],
                // 4.3 Input
                'input' => [
                    'command'       => 'IP',
                    'readLength'    => 1,
                    'data' => [
                        [ 'key' => '6', 'name' => 'hdmi1' ],
                        [ 'key' => '7', 'name' => 'hdmi2' ],
                    ],
                ],
                // 4.4 Remote control pass-through
                'remoteControl' => [
                    'command'       => 'RC',
                    'readable'      => false,
                    'writeRegex'    => '/^[0-9A-F]{4}$/',
                ],
                // 4.5 Setup [ignored]
                // 4.6 Gamma data of Gamma table "custom 1/2/3 [ignored]
                // 4.7 Panel Alignment (zone) Data [ignored]
                // 4.8 Source Asking
                'source' => [
                    'command'       => 'SC',
                    'readLength'    => 1,
                    'writeable'     => false,
                    'data' => [
                        [ 'key' => '0', 'name' => 'no' ],
                        [ 'key' => '1', 'name' => 'yes' ],
                    ],
                ],
                // 4.9 Model status asking [ignored]
                // 4.10 Adjustment / part 1, Picture Adjust
                'pictureMode' => [
                    'command'       => 'PMPM',
                    'readLength'    => 2,
                    'data' => [
                        //[ 'key' => '00', 'name' => 'film' ], // not on X5000
                        [ 'key' => '01', 'name' => 'cinema' ],
                        [ 'key' => '02', 'name' => 'animation' ],
                        [ 'key' => '03', 'name' => 'natural' ],
                        //[ 'key' => '06', 'name' => 'thx'   ], // not on X5000
                        [ 'key' => '0C', 'name' => 'user1' ],
                        [ 'key' => '0D', 'name' => 'user2' ],
                        [ 'key' => '0E', 'name' => 'user3' ],
                        [ 'key' => '0F', 'name' => 'user4' ],
                        [ 'key' => '10', 'name' => 'user5' ],
                        [ 'key' => '11', 'name' => 'user6' ],
                    ],
                ],
                'clearBlack' => [
                    'command'       => 'PMAN',
                    'readLength'    => 1,
                    'data' => [
                        [ 'key' => '0', 'name' => 'off' ],
                        [ 'key' => '1', 'name' => 'low' ],
                        [ 'key' => '2', 'name' => 'high' ],
                    ],
                ],
                'intelligentLensAperture' => [
                    'command'       => 'PMDI',
                    'readLength'    => 1,
                    'data' => [
                        [ 'key' => '0', 'name' => 'off' ],
                        [ 'key' => '1', 'name' => 'auto1' ],
                        [ 'key' => '2', 'name' => 'auto2' ],
                    ],
                ],
                'clearMotionDrive' => [
                    'command'       => 'PMCM',
                    'readLength'    => 1,
                    'data' => [
                        [ 'key' => '0', 'name' => 'off' ],
                        [ 'key' => '3', 'name' => 'low' ],
                        [ 'key' => '4', 'name' => 'high' ],
                        [ 'key' => '5', 'name' => 'inverseTelecine' ],
                    ],
                ],
                'motionEnhance' => [
                    'command'       => 'PMME',
                    'readLength'    => 1,
                    'data' => [
                        [ 'key' => '0', 'name' => 'off' ],
                        [ 'key' => '1', 'name' => 'low' ],
                        [ 'key' => '2', 'name' => 'high' ],
                    ],
                ],
                /*
                'lensAperture' => [
                    'command' => 'PMLA',
                    'readLength' => 4, // numeric
                ],
                 */
                'lampPower' => [
                    'command'       => 'PMLP',
                    'readLength'    => 1,
                    'data' => [
                        [ 'key' => '0', 'name' => 'normal' ],
                        [ 'key' => '1', 'name' => 'high' ],
                    ],
                ],
                'mpcAnalyze' => [
                    'command'       => 'PMMA',
                    'readLength'    => 1,
                    'data' => [
                        [ 'key' => '0', 'name' => 'off' ],
                        [ 'key' => '1', 'name' => 'analyze' ],
                        [ 'key' => '2', 'name' => 'enhance' ],
                        [ 'key' => '3', 'name' => 'dynamicContrast' ],
                        [ 'key' => '4', 'name' => 'smoothing' ],
                        [ 'key' => '5', 'name' => 'histogram' ],
                    ],
                ],
                'eShift4k' => [
                    'command'       => 'PMUS',
                    'readLength'    => 1,
                    'data' => [
                        [ 'key' => '0', 'name' => 'off' ],
                        [ 'key' => '1', 'name' => 'on' ],
                    ],
                ],
                'originalResolution' => [
                    'command'       => 'PMRP',
                    'readLength'    => 1,
                    'data' => [
                        [ 'key' => '0', 'name' => 'off' ],
                        [ 'key' => '3', 'name' => '1080p' ],
                        [ 'key' => '4', 'name' => '4k' ],
                    ],
                ],
                /*
                'enhance' => [
                    'command'       => 'PMEN',
                    'readLength'    => 4, // numeric
                ],
                'dynamicContrast' => [
                    'command'       => 'PMDY',
                    'readLength'    => 4, // numeric
                ],
                'smoothing' => [
                    'command'       => 'PMST',
                    'readLength'    => 4, // numeric
                ],
                 */

                // 4.10 Adjustment / part 2, Input Signal
                // HDMI Input Level Switch ISIL
                // HDMI Color Space Swtich ISHS
                'hdmi3dMode' => [
                    'command'       => 'IS3D',
                    'readLength'    => 1,
                    'data' => [
                        [ 'key' => '0', 'name' => '2d' ],
                        [ 'key' => '1', 'name' => 'auto' ],
                        [ 'key' => '3', 'name' => 'sbs' ],
                        [ 'key' => '4', 'name' => 'tab' ],
                    ],
                ],
                'hdmi3dPhase' => [
                    'command'       => 'IS3P',
                    'readLength'    => 1,
                    'data' => [
                        [ 'key' => '0', 'name' => 'default' ],
                        [ 'key' => '1', 'name' => 'flipped' ],
                    ],
                ],

                // 4.10 Adjustment / part 3, Installation
                'loadLensMemory' => [
                    'command'       => 'INML',
                    'readable'      => false,
                    'writeRegex'    => '/^[1-5]$/',
                    'writeCallable' => function ($input) {
                        return (string) --$input;
                    },
                ],
                // 4.10 Adjustment / part 4, Display Setup
                // 4.10 Adjustment / part 5, Function
                // 4.10 Adjustment / part 6, Information
                'sourceDisplay' => [
                    'command'       => 'IFIS',
                    'readLength'    => 2,
                    'writeable'     => false,
                    'data' => [
                        [ 'key' => '02', 'name' => '480p' ],
                        [ 'key' => '03', 'name' => '576p' ],
                        [ 'key' => '04', 'name' => '720p50' ],
                        [ 'key' => '05', 'name' => '720p60' ],
                        [ 'key' => '06', 'name' => '1080i50' ],
                        [ 'key' => '07', 'name' => '1080i60' ],
                        [ 'key' => '08', 'name' => '1080p24' ],
                        [ 'key' => '09', 'name' => '1080p50' ],
                        [ 'key' => '0A', 'name' => '1080p60' ],
                        [ 'key' => '0B', 'name' => 'no signal' ],
                        [ 'key' => '0C', 'name' => '720p 3D' ],
                        [ 'key' => '0D', 'name' => '1080i 3D' ],
                        [ 'key' => '0E', 'name' => '1080p 3D' ],
                        [ 'key' => '10', 'name' => '4K(4096)60' ],
                        [ 'key' => '11', 'name' => '4K(4096)50' ],
                        [ 'key' => '12', 'name' => '4K(4096)30' ],
                        [ 'key' => '13', 'name' => '4K(4096)25' ],
                        [ 'key' => '14', 'name' => '4K(4096)24' ],
                        [ 'key' => '15', 'name' => '4K(3840)60' ],
                        [ 'key' => '16', 'name' => '4K(3840)50' ],
                        [ 'key' => '17', 'name' => '4K(3840)30' ],
                        [ 'key' => '18', 'name' => '4K(3840)25' ],
                        [ 'key' => '19', 'name' => '4K(3840)24' ],
                    ],
                ],
                'deepColor' => [
                    'command'       => 'IFDC',
                    'readLength'    => 1,
                    'writeable'     => false,
                    'data' => [
                        [ 'key' => '0', 'name' => '8 bit' ],
                        [ 'key' => '1', 'name' => '10 bit' ],
                        [ 'key' => '2', 'name' => '12 bit' ],
                    ],
                ],
                'colorSpace' => [
                    'command'       => 'IFXV',
                    'readLength'    => 1,
                    'writeable'     => false,
                    'data' => [
                        [ 'key' => '0', 'name' => 'RGB' ],
                        [ 'key' => '1', 'name' => 'YUV' ],
                        [ 'key' => '2', 'name' => 'x.v.Color' ],
                    ],
                ],
            ];
        }

        return $this->commands;
    }
}
