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
class JvcProjector
{
    /**
     * @var JvcClientInterface
     */
    protected $client;

    /**
     * @var mixed[]
     */
    protected $cache;

    protected $commands = [
        'powerState' => [
            'command' => 'PW',
            'statusLength' => 1,
            'data' => [
                [ 'key' => '0', 'name' => 'off' ],
                [ 'key' => '1', 'name' => 'on' ],
                [ 'key' => '2', 'name' => 'cooling', 'writeable' => false ],
                [ 'key' => '3', 'name' => 'reserved', 'writeable' => false ],
                [ 'key' => '4', 'name' => 'error', 'writeable' => false ],
            ],
        ],
        'pictureMode' => [
            'command' => 'PMPM',
            'statusLength' => 2,
            'data' => [
                [ 'key' => '00', 'name' => 'film' ], // not on X5000
                [ 'key' => '01', 'name' => 'cinema' ],
                [ 'key' => '02', 'name' => 'animation' ],
                [ 'key' => '03', 'name' => 'natural' ],
                [ 'key' => '06', 'name' => 'thx'   ], // not on X5000
                [ 'key' => '0C', 'name' => 'user1' ],
                [ 'key' => '0D', 'name' => 'user2' ],
                [ 'key' => '0E', 'name' => 'user3' ],
                [ 'key' => '0F', 'name' => 'user4' ],
                [ 'key' => '10', 'name' => 'user5' ],
                [ 'key' => '11', 'name' => 'user6' ],
            ],
        ],
        /* 'clearBlack' => [
            'command' => '',
            'statusLength' => 1,
            'data' => [
                [ 'key' => '0', 'name' => 'off' ],
                [ 'key' => '1', 'name' => 'low' ],
                [ 'key' => '2', 'name' => 'high' ],
            ],
        ], // */
        /* 'intelligentLensAperture' => [
            'command' => '',
            'statusLength' => 1,
            'data' => [
                [ 'key' => '0', 'name' => 'off' ],
                [ 'key' => '1', 'name' => 'auto1' ],
                [ 'key' => '2', 'name' => 'auto2' ],
            ],
        ], // */
        //'clearMotionDrive' => [], // off = 0, low = 3, high = 4, inverseTelecine = 5
        //'motionEnhance' => [], // off = 0, low = 1, high = 2
        //'lampPower' => [], // normal = 0, high = 1
        //'MpcAnalyze' => [], // off = 0, analyze = 1, enhance = 2, dynamicContrast = 3, smoothing = 4, histogram = 5
        //'EShift4k' => [], // off = 0, on = 1
        'hdmi3dMode' => [
            'command' => 'IS3D',
            'statusLength' => 1,
            'data' => [
                [ 'key' => '0', 'name' => '2d' ],
                [ 'key' => '1', 'name' => 'auto' ],
                [ 'key' => '3', 'name' => 'sbs' ],
                [ 'key' => '4', 'name' => 'tab' ],
            ],
        ],
        'hdmi3dPhase' => [
            'command' => 'IS3P',
            'statusLength' => 1,
            'data' => [
                [ 'key' => '0', 'name' => 'default' ],
                [ 'key' => '1', 'name' => 'flipped' ],
            ],
        ],
        'sourceDisplay' => [
            'command' => 'IFIS',
            'statusLength' => 2,
            'data' => [
                [ 'key' => '02', 'name' => '480p', 'writeable' => false ],
                [ 'key' => '03', 'name' => '576p', 'writeable' => false ],
                [ 'key' => '04', 'name' => '720p50', 'writeable' => false ],
                [ 'key' => '05', 'name' => '720p60', 'writeable' => false ],
                [ 'key' => '06', 'name' => '1080i50', 'writeable' => false ],
                [ 'key' => '07', 'name' => '1080i60', 'writeable' => false ],
                [ 'key' => '08', 'name' => '1080p24', 'writeable' => false ],
                [ 'key' => '09', 'name' => '1080p50', 'writeable' => false ],
                [ 'key' => '0A', 'name' => '1080p60', 'writeable' => false ],
                [ 'key' => '0B', 'name' => 'no sig.', 'writeable' => false ],
                [ 'key' => '0C', 'name' => '720p 3D', 'writeable' => false ],
                [ 'key' => '0D', 'name' => '1080i 3D', 'writeable' => false ],
                [ 'key' => '0E', 'name' => '1080p 3D', 'writeable' => false ],
                [ 'key' => '10', 'name' => '4K(4096)60', 'writeable' => false ],
                [ 'key' => '11', 'name' => '4K(4096)50', 'writeable' => false ],
                [ 'key' => '12', 'name' => '4K(4096)30', 'writeable' => false ],
                [ 'key' => '13', 'name' => '4K(4096)25', 'writeable' => false ],
                [ 'key' => '14', 'name' => '4K(4096)24', 'writeable' => false ],
                [ 'key' => '15', 'name' => '4K(3840)60', 'writeable' => false ],
                [ 'key' => '16', 'name' => '4K(3840)50', 'writeable' => false ],
                [ 'key' => '17', 'name' => '4K(3840)30', 'writeable' => false ],
                [ 'key' => '18', 'name' => '4K(3840)25', 'writeable' => false ],
                [ 'key' => '19', 'name' => '4K(3840)24', 'writeable' => false ],
            ],
        ],
        'deepColor' => [
            'command' => 'IFDC',
            'statusLength' => 1,
            'data' => [
                [ 'key' => '0', 'name' => '8 bit' ],
                [ 'key' => '1', 'name' => '10 bit' ],
                [ 'key' => '2', 'name' => '12 bit' ],
            ],
        ],
        'colorSpace' => [
            'command' => 'IFXV',
            'statusLength' => 1,
            'data' => [
                [ 'key' => '0', 'name' => 'RGB' ],
                [ 'key' => '1', 'name' => 'YUV' ],
                [ 'key' => '2', 'name' => 'x.v.Color' ],
            ],
        ],
    ];


    /**
     * Constructor
     *
     * @param   JvcClientInterface|string $client Either a client instance, hostname/ip address or hostname/ip:port
     */
    public function __construct($client)
    {
        if ($client instanceof JvcClientInterface) {
            $this->client = $client;
        } elseif ($client && is_string($client)) {
            $parts = explode(':', $client);
            if (1 === count($parts)) {
                $this->client = new JvcClient($parts[0]);
            } elseif (2 === count($parts)) {
                $this->client = new JvcClient($parts[0], $parts[1]);
            }
        }
        if (!$this->client) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown argument $client, expected JvcClientInterface-aware object or string; received "%s"',
                (is_object($client) ? get_class($client) : gettype($client))
            ));
        }
    }

    public function __call($methodName, $arguments = [])
    {
        dump($methodName);
        $pattern = [];
        if (preg_match('/^(get|set)(.*?)(Name)?$/', $methodName, $pattern)) {
            if ($pattern[1] === 'set' && !empty($pattern[3])) {
                throw new \BadMethodCallException(sprintf(
                    'Method "SET" not allowed for a ...Name()-method; received "%s"',
                    $methodName
                ));
            }
            $apiName = lcfirst($pattern[2]);
            if (!isset($this->commands[$apiName])) {
                throw new \BadMethodCallException(sprintf(
                    'Unknown api %s; received "%s"',
                    $apiName,
                    $methodName
                ));
            }
            $apiDefinition =& $this->commands[$apiName];

            // Power-related methods are defined natively.
            if (isset($this->cache['powerState']) && $this->cache['powerState'] !== '1') {
                throw new PowerOffException();
            }

            if ($pattern[1] === 'get') {
                if (!isset($apiDefinition['statusLength'])) {
                    throw new \BadMethodCallException(sprintf(
                        'Method "GET" not allowed for this api; received "%s"',
                        $methodName
                    ));
                }
                $forceReload = false;
                if (isset($arguments[0]) && $arguments[0] === true) {
                    $forceReload = true;
                }
                if ($forceReload || !isset($this->cache[$apiName])) {
                    $this->cache[$apiName] = $this->request($apiDefinition['command'], $apiDefinition['statusLength']);
                }

                if (empty($pattern[3])) {
                    return $this->cache[$apiName];
                }

                if (isset($this->commands[$apiName]['cmdStatus'])) {
                    $cmdStatus =& $apiDefinition['cmdStatus'];
                } else {
                    $cmdStatus = array_flip($apiDefinition['cmdData']);
                }

                if (isset($cmdStatus[$this->cache[$apiName]])) {
                    return $cmdStatus[$this->cache[$apiName]];
                }

                return 'unknown '.$this->cache[$apiName];
            } else {
                // pattern[1] === 'set'
                $newValue = $arguments[0];
                $cmdData = $apiDefinition['cmdData'];
                if (isset($cmdData[$newValue])) {
                    $newValue = $cmdData[$newValue];
                } else {
                    $found = false;
                    foreach ($cmdData as $key => $value) {
                        if ($newValue === $value) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        throw new \Exception('Invalid value for '.$apiName);
                    }
                }

                $this->operation($apiDefinition['command'].$newValue);
                $this->cache[$apiName] = $newValue;

                return $this;
            }
        } else {
            throw new \BadMethodCallException('Method not found: '.$methodName);
        }
    }

    public function __isset($propertyName)
    {
        $apiName = $propertyName;
        if (substr($apiName, -4) === 'Name') {
            $apiName = substr($apiName, 0, -4);
        }

        return isset($this->commands[$apiName]);
    }

    public function __get($propertyName)
    {
        return $this->__call('get'.ucfirst($propertyName));
    }

    // - - 4.1 NULL command
    /**
     * Send null operation command for testing purpose.
     *
     * @return  self
     */
    public function testOperation()
    {
        return $this->operation("\0\0");
    }

    // - - 4.2 Power [PoWer]
    /**
     * Send power off operation command.
     *
     * @return  self
     */
    public function setPowerOff()
    {
        $newValue = $this->commands['powerState']['cmdData']['off'];
        $this->operation($this->commands['powerState']['command'].$newValue);
        $this->cache['powerState'] = $newValue;

        return $this;
    }

    /**
     * Send power on operation command.
     *
     * @return  self
     */
    public function setPowerOn()
    {
        $newValue = $this->commands['powerState']['cmdData']['on'];
        $this->operation($this->commands['powerState']['command'].$newValue);
        $this->cache['powerState'] = $newValue;

        return $this;
    }

    /**
     * Request power state.
     *
     * See isPower*()-methods for specific values.
     *
     * @param   bool $forceReload Prevents to use a local cached value.
     * @return  string  Power state, values '0' to '4' according to manual.
     */
    public function getPower($forceReload = false)
    {
        if (!isset($this->cache['powerState']) || $forceReload) {
            $this->cache['powerState'] = $this->request($this->commands['powerState']['command'], 1);
        }

        return $this->cache['powerState'];
    }

    /**
     * Retrieve a keyword for the current power state.
     *
     * Do not use this for further testing, use isPower*()-methods (preferred)
     * instead or use getPower() directly.
     *
     * @param   bool $forceReload Prevents to use a local cached value.
     * @return  string  Human readable status text
     */
    public function getPowerName($forceReload = false)
    {
        $value = $this->getPower($forceReload);
        if (isset($this->commands['powerState']['cmdStatus'][$value])) {
            return $this->commands['powerState']['cmdStatus'][$value];
        }

        return 'unknown '.$value;
    }

    /**
     * Check if power state is 'off' ('0') now.
     *
     * @param   bool $forceReload Prevents to use a local cached value.
     * @return  bool True if power state is '0'
     */
    public function isPowerOff($forceReload = false)
    {
        return ($this->getPower($forceReload) === '0');
    }

    /**
     * Check if power state is 'on' ('1') now.
     *
     * @param   bool $forceReload Prevents to use a local cached value.
     * @return  bool True if power state is '1'
     */
    public function isPowerOn($forceReload = false)
    {
        return ($this->getPower($forceReload) === '1');
    }

    /**
     * Check if power state is 'cooling' ('2') now.
     *
     * @param   bool $forceReload Prevents to use a local cached value.
     * @return  bool True if power state is '2'
     */
    public function isPowerCooling($forceReload = false)
    {
        return ($this->getPower($forceReload) === '2');
    }

    /**
     * Check if power state is 'reserved' ('3') now.
     *
     * @param   bool $forceReload Prevents to use a local cached value.
     * @return  bool True if power state is '3'
     */
    public function isPowerReserved($forceReload = false)
    {
        return ($this->getPower($forceReload) === '3');
    }

    /**
     * Check if power state is 'error' ('4') now.
     *
     * @param   bool $forceReload Prevents to use a local cached value.
     * @return  bool True if power state is '4'
     */
    public function isPowerError($forceReload = false)
    {
        return ($this->getPower($forceReload) === '4');
    }

    // - - 4.3 Input [InPut]
    /**
     * Switch to input HDMI-1
     *
     * @return  self
     */
    public function setInputHdmi1()
    {
        return $this->operation("IP6");
    }

    /**
     * Switch to input HDMI-2.
     *
     * @return  self
     */
    public function setInputHdmi2()
    {
        return $this->operation("IP7");
    }

    /**
     * Request input state.
     *
     * See isInput*()-methods for specific values.
     *
     * @param   bool $forceReload Prevents to use a local cached value.
     * @return  string  Input state, values '6' or '7' according to manual.
     */
    public function getInput($forceReload = false)
    {
        if (!isset($this->cache['inputState']) || $forceReload) {
            $this->cache['inputState'] = $this->request('IP', 1);
        }

        return $this->cache['inputState'];
    }

    /**
     * Retrieve a keyword for the current input.
     *
     * Do not use this for further testing, use isInput*()-methods (preferred)
     * instead or use getInput() directly.
     *
     * @param   bool $forceReload Prevents to use a local cached value.
     * @return  string  Human readable status text
     */
    public function getInputName($forceReload)
    {
        switch ($this->getInput($forceReload)) {
            case '6':
                return 'HDMI-1';
            case '7':
                return 'HDMI-2';
            default:
                return 'unknown';
        }
    }

    /**
     * Check if input is 'HDMI-1' ('6') now.
     *
     * @param   bool $forceReload Prevents to use a local cached value.
     * @return  bool True if input state is '6'
     */
    public function isInputHdmi1($forceReload = false)
    {
        return ($this->getInput($forceReload) === '6');
    }

    /**
     * Check if input is 'HDMI-2' ('7') now.
     *
     * @param   bool $forceReload Prevents to use a local cached value.
     * @return  bool True if input state is '7'
     */
    public function isInputHdmi2($forceReload = false)
    {
        return ($this->getInput($forceReload) === '7');
    }

    // - - 4.4 Remote control pass-through [RemoteCode]
    /**
     * Send passed argument as remote control code.
     *
     * @param   string $code RemoteCode, 4 chars, '0'-'9'&'A'-'F'
     * @return  self
     */
    public function sendRemoteCode($code)
    {
        if (strlen($code) !== 4) {
            throw new \Exception(sprintf(
                'Code must be a string with 4 chars; received "%s"',
                $code
            ));
        }

        return $this->operation('RC'.$code);
    }

    // - - 4.5 Setup [SetUp]
    // - - 4.6 Gamma data of Gamma table "Custom 1/2/3" [GammaRed, Green, Blue]
    // - - 4.7 Panel Alignment (zone) Data [Panel alignment(zone) Red, Blue]
    // - - 4.8 Source Asking [SourCe]
    /**
     * Check if a valid signal is available.
     *
     * @param   bool $forceReload Prevents to use a local cached value.
     * @return  bool  True means Available signal is input to the projector.
     */
    public function hasSource($forceReload = false)
    {
        $value = $this->request('SC', 1);

        return ($value === '1');
    }

    // - - 4.9 Model status asking [MoDel]
    // - - 4.10 Adjustment [AdjustmentCommand]
    // - - - Picture Adjust [PM], Input Signal, INstallation, Display Setup, FUnction, InFormation

/*
    public function getPictureMode($forceReload = false)
    {
        if (isset($this->cache['powerState']) && $this->cache['powerState'] !== '1') {
            throw new PowerOffException();
        }

        if ($forceReload || !isset($this->cache['pictureMode'])) {
            $this->cache['pictureMode'] = $this->request('PMPM', 2);
        }

        return $this->cache['pictureMode'];
    }

    public function getPictureModeName($forceReload = false)
    {
        $value = $this->getPictureMode($forceReload);
        $apiName = 'pictureMode';
        if (isset($this->commands[$apiName]['cmdStatus'])) {
            $cmdStatus =& $this->commands[$apiName]['cmdStatus'];
        } else {
            $cmdStatus = array_flip($this->commands[$apiName]['cmdData']);
        }

        if (isset($cmdStatus[$value])) {
            return $cmdStatus[$value];
        }

        return 'unknown '.$value;
    }

    public function setPictureMode($pictureMode)
    {
        $newValue = $pictureMode;
        $apiName = 'pictureMode';

        $cmdData = $this->commands[$apiName]['cmdData'];
        if (isset($cmdData[$newValue])) {
            $newValue = $cmdData[$newValue];
        } else {
            $found = false;
            foreach ($cmdData as $key => $value) {
                if ($newValue === $value) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new \Exception('Invalid value for '.$apiName);
            }
        }

        $this->operation($this->commands[$apiName]['command'].$newValue);
        $this->cache[$apiName] = $newValue;

        return $this;
    }

    public function getHdmi3dMode($forceReload = false)
    {
        if (isset($this->cache['powerState']) && $this->cache['powerState'] !== '1') {
            throw new PowerOffException();
        }

        if ($forceReload || !isset($this->cache['hdmi3dMode'])) {
            $this->cache['hdmi3dMode'] = $this->request('IS3D', 1);
        }

        return $this->cache['hdmi3dMode'];
    }

    public function getHdmi3dModeName($forceReload = false)
    {
        $value = $this->getHdmi3dMode($forceReload);
        if (isset($this->commands['hdmi3dMode']['cmdStatus'])) {
            $cmdStatus =& $this->commands['hdmi3dMode']['cmdStatus'];
        } else {
            $cmdStatus = array_flip($this->commands['hdmi3dMode']['cmdData']);
        }

        if (isset($cmdStatus[$value])) {
            return $cmdStatus[$value];
        }

        return 'unknown '.$value;
    }

    public function setHdmi3dMode($hdmi3dMode)
    {
        $hdmi3dModes = [
            '0' => '2d',
            '1' => 'auto',
            '3' => 'sbs',
            '4' => 'tab',
        ];
        $found = false;
        foreach ($hdmi3dModes as $key => $value) {
            if ($hdmi3dMode === $key) {
                $found = true;
                break;
            } elseif (strtolower($hdmi3dMode) === strtolower($value)) {
                $found = true;
                $hdmi3dMode = $key;
                break;
            }
        }
        if (!$found) {
            throw new \Exception('Invalid 3d Mode');
        }
        $this->operation('IS3D'.$hdmi3dMode);
        $this->cache['hdmi3dMode'] = $hdmi3dMode;

        return $this;
    }
 */

    public function loadLensMemory($lensMemory)
    {
        $lensMemory = (int) $lensMemory;
        if ($lensMemory === 0) {
            throw new \OutOfRangeException('lensMemory is invalid');
        }
        // for X5000, 10 for X7000/X9000
        $max = 5;
        if ($lensMemory < 1 or $lensMemory > $max) {
            throw new \OutOfRangeException('lensMemory is out of range');
        }
        $lensMemory--;
        $lensMemory = (string) $lensMemory;

        $this->operation('INML'.$lensMemory);

        return $this;
    }

    // - - Helper methods

    /**
     * Mid-Level method for operation commands.
     */
    protected function operation($command)
    {
        // 0x21 = Operation
        // 0x89 = Unit code (fixed)
        // 0x01 = Individual code (fixed)
        // 0x0a = line feed (fixed)
        $this->sendBytes(chr(0x21).chr(0x89).chr(0x01).$command.chr(0x0a));

        $this->expect(chr(0x06).chr(0x89).chr(0x01).substr($command, 0, 2).chr(0x0a));

        return $this;
    }

    /**
     * Mid-Level method for request commands.
     */
    protected function request($command, $statusLength)
    {
        // 0x3f = Request
        // 0x89 = Unit code (fixed)
        // 0x01 = Individual code (fixed)
        // 0x0a = line feed (fixed)
        $this->sendBytes(chr(0x3f).chr(0x89).chr(0x01).$command.chr(0x0a));

        // Expect usual ACK first
        $this->expect(chr(0x06).chr(0x89).chr(0x01).substr($command, 0, 2).chr(0x0a));
        $this->expect(chr(0x40).chr(0x89).chr(0x01).substr($command, 0, 2));
        $data = $this->receiveBytes($statusLength);
        $this->expect(chr(0x0a));

        return $data;
    }

    /**
     * Read strlen($expect) bytes from socket and compare with $expect.
     *
     * Low-Level helper method for receiving bytes.
     *
     * Read and compare byte by byte.
     *
     * @throws  \Exception
     * @return  self
     */
    protected function expect($expect)
    {
        try {
            while ($receivedByte = $this->receiveBytes(1)) {
                $expectedByte = substr($expect, 0, 1);
                if ($receivedByte === $expectedByte) {
                    $expect = substr($expect, 1);
                } else {
                    throw new \Exception(sprintf(
                        'Expected 0x%02x (%s); received 0x%02x (%s)',
                        ord($expectedByte),
                        $expectedByte,
                        ord($receivedByte),
                        $receivedByte
                    ));
                }
                if (!$expect) {
                    break;
                }
            }
        } catch (TimeOutException $e) {
            throw new TimeOutException(sprintf(
                'Expected %02x (%s)',
                ord(substr($expect, 0, 1)),
                substr($expect, 0, 1)
            ));
        }

        return $this;
    }

    /**
     * Low-Level method for connecting.
     */
    protected function connect()
    {
        $this->socket = @fsockopen($this->remoteAddress, $this->remotePort, $errno, $errstr, 1);
        if (!$this->socket) {
            throw new DeviceNotFoundException('Socket could not be created');
        }
        stream_set_timeout($this->socket, 3);
        $this->expect('PJ_OK');

        fwrite($this->socket, 'PJREQ');
        $this->expect('PJACK');
    }

    /**
     * Low-Level method for sending bytes.
     */
    protected function sendBytes($bytes)
    {
        if (!$this->socket) {
            $this->connect();
        }

        $res = @fwrite($this->socket, $bytes);
        if (!$res) {
            throw new \Exception('Could not write to socket');
        }

        return true;
    }

    /**
     * Low-Level method for receiving bytes.
     */
    protected function receiveBytes($length = 1)
    {
        if (feof($this->socket)) {
            throw new \Exception('Socket reached eof');
        }

        $buffer = fread($this->socket, $length);
        // access method result as array since PHP 5.4
        if (stream_get_meta_data($this->socket)['timed_out']) {
            throw new TimeOutException();
        }

        return $buffer;
    }
}
