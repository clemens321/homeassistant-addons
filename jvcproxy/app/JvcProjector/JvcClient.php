<?php
/**
 * This file is part of my homesrv control system.
 *
 * @author  Clemens Brauers <cb@admin-cb.de>
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3
 */

namespace clemens321\JvcProjector;

use clemens321\JvcProjector\Exception\ConnectionClosedException;
use clemens321\JvcProjector\Exception\DeviceNotFoundException;
use clemens321\JvcProjector\Exception\PowerOffException;
use clemens321\JvcProjector\Exception\TimeOutException;

/**
 * Connector to the JVC projector.
 *
 * @author  Clemens Brauers <cb@admin-cb.de>
 */
class JvcClient implements JvcClientInterface
{
    const DEFAULT_PORT = 20554;

    /**
     * @var string
     */
    protected $remoteAddress;

    /**
     * @var int
     */
    protected $remotePort;

    /**
     * @var resource
     */
    private $socket;

    /**
     * Constructor.
     *
     * @param   string $remoteAddress
     * @param   int    $remotePort
     */
    public function __construct(string $remoteAddress, ?int $remotePort = null)
    {
        if (!$remotePort) {
            $remotePort = static::DEFAULT_PORT;
        }

        $this->remoteAddress = $remoteAddress;
        $this->remotePort = $remotePort;
    }


    /**
     * Execute a request command.
     *
     * @param   array $command
     * @return  mixed
     */
    public function getCommand($command)
    {
        if (isset($command['readable']) && false === $command['readable']) {
            throw new \BadMethodCallException(sprintf(
                'The command "%s" is not readable',
                $command['name']
            ));
        }
        if (!isset($command['readLength'])) {
            throw new \BadMethodCallException(sprintf(
                'The command "%s" is not readable / readLength not defined',
                $command['name']
            ));
        }

        $rawValue = $this->request($command['command'], $command['readLength']);
        $value = null;
        if (isset($command['data'])) {
            foreach ($command['data'] as $row) {
                if ($row['key'] === $rawValue) {
                    return $row['name'];
                }
            }
        }

        return 'unknown ('.$rawValue.')';
    }

    /**
     * Execute an operate command.
     *
     * @param   array $command
     * @param   mixed $value
     * @return  self
     */
    public function setCommand($command, $value = null)
    {
        if (isset($command['writeable']) && false === $command['writeable']) {
            throw new \BadMethodCallException(sprintf(
                'The command "%s" is not writeable',
                $command['name']
            ));
        }
        $rawValue = null;

        if (isset($command['data'])) {
            if (!isset($value)) {
                throw new \InvalidArgumentException('Missing new value.');
            }

            foreach ($command['data'] as $row) {
                if ($row['name'] === $value) {
                    $rawValue = $row['key'];
                }
            }
            if (null === $rawValue) {
                throw new \InvalidArgumentException(sprintf(
                    'Unknown value "%s" for command "%s"',
                    $value,
                    $command['name']
                ));
            }
        } elseif (isset($command['writeRegex'])) {
            if (!isset($value)) {
                throw new \InvalidArgumentException('Missing new value.');
            }

            if (!preg_match($command['writeRegex'], $value)) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid value "%s" for command "%s"',
                    $value,
                    $command['name']
                ));
            }
        }

        if (isset($command['writeCallable'])) {
            $rawValue = call_user_func($command['writeCallable'], $value);
        }

        $this->operation($command['command'].$rawValue);
    }

    /**
     * Connect the socket.
     *
     * @return  self
     */
    public function connect()
    {
        if ($this->socket) {
            throw new \UnexpectedValueException('Socket already exists');
        }

        $this->socket = @fsockopen($this->remoteAddress, $this->remotePort, $errno, $errstr, 1);
        if (!$this->socket) {
            throw new DeviceNotFoundException('Socket could not be created');
        }
        try {
            stream_set_timeout($this->socket, 3);
            $this->expect('PJ_OK');

            fwrite($this->socket, 'PJREQ');
            $this->expect('PJACK');
        } catch (TimeOutException $e) {
            fclose($this->socket);
            $this->socket = null;

            throw $e;
        }

        return $this;
    }

    /**
     * Disconnect the socket.
     *
     * @return  self
     */
    public function disconnect()
    {
        if ($this->socket) {
            @fclose($this->socket);
            $this->socket = null;
        }

        return $this;
    }

    /**
     * Check if the socket is connected.
     *
     * @todo    Check if the socket is really still connected
     * @return  bool
     */
    public function isConnected()
    {
        return (bool) $this->socket;
    }

    /**
     * Mid-Level method for operation commands.
     *
     * @param   string $command
     * @return  self
     */
    public function operation($command)
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
     *
     * @param   string $command
     * @param   int    $readLength
     * @return  string
     */
    public function request($command, $readLength = null)
    {
        // 0x3f = Request
        // 0x89 = Unit code (fixed)
        // 0x01 = Individual code (fixed)
        // 0x0a = line feed (fixed)
        $this->sendBytes(chr(0x3f).chr(0x89).chr(0x01).$command.chr(0x0a));

        // Expect usual ACK first
        $this->expect(chr(0x06).chr(0x89).chr(0x01).substr($command, 0, 2).chr(0x0a));
        $this->expect(chr(0x40).chr(0x89).chr(0x01).substr($command, 0, 2));
        if (null === $readLength) {
            $data = '';
            while (chr(0x0a) !== ($byte = $this->receiveBytes(1))) {
                $data .= $byte;
            };

            return $data;
        }

        $data = $this->receiveBytes($readLength);
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
     * @param   string $expect
     * @return  self
     */
    public function expect($expect)
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
     * Low-Level method for sending bytes.
     *
     * @param   string $bytes
     * @return  self
     */
    protected function sendBytes($bytes)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $res = @fwrite($this->socket, $bytes);
        if (!$res) {
            throw new \Exception('Could not write to socket');
        }

        return $this;
    }

    /**
     * Low-Level method for receiving bytes.
     *
     * @param   int $length
     * @return  string
     */
    protected function receiveBytes($length = 1)
    {
        if (feof($this->socket)) {
            $this->socket = null;
            
            throw new ConnectionClosedException();
        }

        $buffer = fread($this->socket, $length);
        // access method result as array (since PHP 5.4)
        if (stream_get_meta_data($this->socket)['timed_out']) {
            throw new TimeOutException();
        }

        return $buffer;
    }
}
