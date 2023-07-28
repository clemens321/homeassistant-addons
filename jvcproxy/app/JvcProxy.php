<?php

use clemens321\JvcServer\JvcServer;
use Navarr\Socket\Server;
use Navarr\Socket\Socket;
use Navarr\Socket\Exception\SocketException;

class JvcProxy
{
    const DEFAULT_PORT = 20554;

    private $jvcClient;
    private $jvcServers = [];
    private $server;

    public function __construct(string $address = '::', ?int $port = null)
    {
        if (!$port) {
            $port = static::DEFAULT_PORT;
        }

        $this->server = new Server($address, $port);
        $this->server->addHook(Server::HOOK_CONNECT, array($this, 'onConnect'));
        $this->server->addHook(Server::HOOK_INPUT, array($this, 'onInput'));
        $this->server->addHook(Server::HOOK_DISCONNECT, array($this, 'onDisconnect'));
    }

    public function setJvcClient($jvcClient)
    {
        $this->jvcClient = $jvcClient;
    }

    public function setUSleep(int $usleep)
    {
        $this->server->setUSleep($usleep);
    }

    public function run(): void
    {
        while (true) {
            try {
                $this->server->run();

                return;
            } catch (SocketException $e) {
                echo "Received SocketException:\n";
                echo $e;
            }
        }
    }

    public function stop()
    {
        // This will call the destruct method
        unset($this->server);
    }

    public function onConnect(Server $server, Socket $client, $message)
    {
        list($remoteAddr, $remotePort) = ['', 0];
        $client->getPeerName($remoteAddr, $remotePort);
        echo (new \DateTime())->format('d.m.Y H:i:s').' Connect from '.$remoteAddr.' as '.spl_object_id($client)."\n";

        $key = spl_object_id($client);
        $this->jvcServers[$key] = new JvcServer($client);
        $this->jvcServers[$key]->setJvcClient($this->jvcClient);
        if ($message) {
            return $this->jvcServers[$key]->onInput($client, $message);
        }
    }

    public function onInput(Server $server, Socket $client, $message)
    {
        echo (new \DateTime())->format('d.m.Y H:i:s').' Received '.strlen($message).' bytes via socket '.spl_object_id($client)."\n";

        if (!isset($this->jvcServers[spl_object_id($client)])) {
            throw new \Exception('Unknown jvc server object id: '.spl_object_id($client));
        }

        return $this->jvcServers[spl_object_id($client)]->onInput($client, $message);
    }

    public function onDisconnect(Server $server, Socket $client, $message)
    {
        echo (new \DateTime())->format('d.m.Y H:i:s').' Disconnect socket '.spl_object_id($client)."\n";

        if (!isset($this->jvcServers[spl_object_id($client)])) {
            throw new \Exception('Unknown jvc server object id: '.spl_object_id($client));
        }

        $this->jvcServers[spl_object_id($client)]->onDisconnect($client, $message);
        unset($this->jvcServers[spl_object_id($client)]);
    }
}
