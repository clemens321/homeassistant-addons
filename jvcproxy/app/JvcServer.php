<?php

namespace clemens321\JvcServer;

use clemens321\JvcProjector\Exception\ConnectionClosedException;
use clemens321\JvcProjector\Exception\TimeOutException;
use clemens321\JvcProjector\JvcClientInterface;
use Navarr\Socket\Server as SocketServer;
use Navarr\Socket\Socket;

enum ClientState {
    case UNINITIALIZED;
    case LOGON_HELO_SENT;
    case LOGON_PJREQ_RECEIVED;
    case IDLE;
    case PROCESSING;
    case IN_REQUEST;
    case IN_OPERATION;
}

class IncompleteMessageException extends \UnexpectedValueException {}

class JvcServer
{
    private ClientState $state = ClientState::UNINITIALIZED;

    private JvcClientInterface $jvcClient;
    private Socket $socket;
    private $message;

    public function __construct(Socket $socket)
    {
        $this->socket = $socket;
        $this->state = ClientState::UNINITIALIZED;
        $this->socket->write('PJ_OK');
        $this->state = ClientState::LOGON_HELO_SENT;
    }

    public function setJvcClient(JvcClientInterface $jvcClient)
    {
        $this->jvcClient = $jvcClient;
    }

    public function onInput(Socket $socket, $message)
    {
        $this->dumpMessage($message);
        try {
            $this->message .= $message;
            while ($this->message) {
                $result = $this->handleMessage();

                if (null !== $result) {
                    return $result;
                }
            }
        } catch (IncompleteMessageException $e) {
            // Ignore
        } catch (\Exception $e) {
            echo $e;
            $this->jvcClient->disconnect();

            return SocketServer::RETURN_HALT_SERVER;
        }
    }

    public function onDisconnect(Socket $socket, $message)
    {
    }

    public function handleMessage()
    {
        if (!$this->message) {
            throw new IncompleteMessageException();
        }

        $this->dumpMessage($this->message, 'Called handleMessage() with ');

        if (ClientState::LOGON_HELO_SENT === $this->state) {
            $this->expect('PJREQ');
            $this->state = ClientState::LOGON_PJREQ_RECEIVED;
            echo "Answering PJREQ with PJACK\n";
            $this->socket->write('PJACK');
            $this->state = ClientState::IDLE;

            return;
        }

        if (ClientState::IDLE === $this->state) {
            $type = substr($this->message, 0, 1);
            if ('?' === $type || '!' === $type) {
                if (6 > strlen($this->message)) {
                    throw new IncompleteMessageException('At least 6 bytes required'); // ?/!, 89, 01, 2xcmd, [..,] 0a
                }
                $this->expect($type.chr(0x89).chr(0x01), false);
                $command = substr($this->message, 3, 2);

                for ($i = 5; $i < strlen($this->message); ++$i) {
                    if (chr(0x0a) === substr($this->message, $i, 1)) {
                        $data = substr($this->message, 5, $i - 5);
                    } else {
                        $this->dumpMessage(substr($this->message, $i, 1), 'Byte'.$i);
                    }
                }
                if ($data) {
                    $this->dumpMessage($data, 'Data');
                }
                if (null === $data) {
                    echo "incomplete\n";

                    throw new IncompleteMessageException();
                }

                $this->state = ClientState::PROCESSING;
                $this->message = substr($this->message, 6 + strlen($data));
                if ('?' === $type) {
                    return $this->handleRequest($command, $data);
                } elseif ('!' === $type) {
                    return $this->handleOperation($command, $data);
                }
            }
        }

        throw new \UnexpectedValueException('Unknown byte '.ord(substr($this->message, 0, 1)));
    }

    public function handleRequest($command, $data)
    {
        $this->state = ClientState::IN_REQUEST;

        try {
            $result = $this->jvcClient->request($command.$data);
        } catch (ConnectionClosedException | TimeOutException $e) {
            $this->dumpMessage($command.$data, 'Retry sending ');
            $result = $this->jvcClient->request($command.$data);
        }

        $this->dumpMessage($result, 'Got from projector ');
        // ACK
        $this->socket->write(chr(0x06).chr(0x89).chr(0x01).$command.chr(0x0a));
        // Answer
        $this->socket->write(chr(0x40).chr(0x89).chr(0x01).$command.$result.chr(0x0a));
        $this->state = ClientState::IDLE;
    }

    public function handleOperation($command, $data)
    {
        $this->state = ClientState::IN_OPERATION;

        try {
            $this->jvcClient->operation($command.$data);
        } catch (ConnectionClosedException | TimeOutException $e) {
            $this->dumpMessage($command.$data, 'Retry sending ');
            $this->jvcClient->operation($command.$data);
        }

        // ACK
        $this->socket->write(chr(0x06).chr(0x89).chr(0x01).$command.chr(0x0a));

        $this->state = ClientState::IDLE;
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
    public function expect($expect, $remove = true)
    {
        if (strlen($this->message) < strlen($expect)) { 
            throw new IncompleteMessageException('Expected '.strlen($expect).' bytes, received '.strlen($this->message));
        }
        if (substr($this->message, 0, strlen($expect)) === $expect) {
            if ($remove) {
                $this->message = substr($this->message, strlen($expect));
            }

            return true;
        }

        for ($i = 0; $i < strlen($expect); ++$i) {
            $expectedByte = substr($expect, $i, 1);
            $receivedByte = substr($this->message, $i, 1);
            if ($expectedByte !== $receivedByte) {
                throw new \Exception(sprintf(
                    'Expected 0x%02x (%s); received 0x%02x (%s)',
                    ord($expectedByte),
                    $expectedByte,
                    ord($receivedByte),
                    $receivedByte
                ));
            }
        }

        throw new \RuntimeException('Reached impossible code block');
    }

    protected function dumpMessage($message, $title = 'Received')
    {
        echo $title." ".strlen($message)." byte(s)";
        if (strlen($message)) {
            echo ": ";
            for ($i = 0; $i < strlen($message); ++$i) {
                $ord = ord($message[$i]);
                if ($ord > 32 && $ord < 128) {
                    echo chr($ord);
                } else {
                    printf('<%02x>', $ord);
                }
            }
        }
        echo "\n";
    }
}
