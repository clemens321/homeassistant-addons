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
     * @var JvcConfigInterface
     */
    protected $config;

    /**
     * @var mixed[]
     */
    protected $cache;


    /**
     * Constructor
     *
     * @param   JvcClientInterface|string $client Either a client instance, hostname/ip address or hostname/ip:port
     * @param   JvcConfigInterface        $config Defaults to bundled X5000 config
     */
    public function __construct($client, $config = null)
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
        if (!isset($config)) {
            $config = new JvcConfigDilaX5000();
        }
        $this->config = $config;
    }

    /**
     * Magic call method.
     *
     * @param   string  $methodName
     * @param   mixed[] $arguments
     * @return  mixed
     */
    public function __call($methodName, $arguments = [])
    {
        $method      = null;
        $command     = null; // this is most relevant
        $commandName = null;
        $dataName    = null;

        $pattern = [];
        if (preg_match('/^(get|set|is|call)([A-Z][a-zA-Z0-9]*)$/', $methodName, $pattern)) {
            $method = $pattern[1];
            $commandName = lcfirst($pattern[2]);
            if ($this->config->hasCommand($commandName)) {
                // found a command
                // this is ok, keep $commandName as is
            } elseif ($this->config->hasCommandSplit($commandName)) {
                // found a command-data-pair
                $split = $this->config->getCommandSplit($commandName);
                $commandName = $split[0];
                $dataName = $split[1];
            } else {
                // found nothing, reset commandName
                $commandName = null;
            }

            if ($commandName) {
                $command = $this->config->getCommand($commandName);
                $command['name'] = $commandName;
            }
        }

        if ($command) {
            // For GET and IS methods check the first argument being bool or null (=> forceReload)
            $forceReload = false;
            if ('get' === $method || 'is' === $method) {
                if (isset($arguments[0])) {
                    if (!is_bool($arguments[0])) {
                        throw new \InvalidArgumentException(sprintf(
                            'For "is" and "get" methods the first argument must be bool if present; received "%s"',
                            (is_object($arguments[0]) ? get_class($arguments[0]) : gettype($arguments[0]))
                        ));
                    }
                    $forceReload = $arguments[0];
                }
            }

            throw new \Exception('TODO');


            // get or is
            $forceReload = false;
            if (isset($arguments[0]) && true === $arguments[0]) {
                $forceReload = true;
            }

            if ($forceReload || !isset($this->cache[$command['name']])) {
            }

            if ('is' === $method) {
                // TODO: Wenn !==, prüfen ob $command['isValue'] element of $command['data'][*]['name']
                return $command['isValue'] === $this->cache[$command['name']];
            }

            return $this->cache[$command['name']];
        }

        throw new \BadMethodCallException('Method not found: '.$methodName);
    }

    /**
     * Use API as property / send operations.
     *
     * This method only gives a return value in case it is used as ordinary method.
     * The magic __set() can't give a return value.
     *
     * @param   string $propertyName
     * @param   mixed  $value
     * @return  self
     */
    public function __set($propertyName, $value)
    {
        $this->__call('set'.ucfirst($propertyName), [ $value ]);

        return $this;
    }

    /**
     * Use API as property / send requests.
     *
     * @param   string $propertyName
     * @return  mixed
     */
    public function __get($propertyName)
    {
        return $this->get($propertyName);
    }

    /**
     * Check whether a property exists as API command.
     *
     * @param   string $propertyName
     * @return  bool
     */
    public function __isset($propertyName)
    {
        return $this->config->hasCommand($propertyName);
    }

    public function get($commandName, $forceReload = false)
    {
        // ignore dataName
        $command = $this->findCommandByArgument($commandName);

        // Check for methods which need a powered-on projector
        if ((!isset($command['onlyPowerOn']) || $command['onlyPowerOn']) && $commandName !== 'power') {
            if ('on' !== $this->get('power', $forceReload)) {
                throw new PowerOffException();
            }
        }

        if ($forceReload || !isset($this->cache[$commandName])) {
            $this->cache[$commandName] = $this->client->getCommand($command);
        }

        return $this->cache[$commandName];
    }

    public function is($commandName, $forceReload = false)
    {
        $dataName = null;
        $command = $this->findCommandByArgument($commandName, $dataName);

        // IS with dataName provided is a shorthand for GET with that specific data
        if (null !== $dataName) {
            if ($forceReload || !isset($this->cache[$commandName])) {
                // The get()-method saves its result to $this->cache[] itself.
                $this->get($commandName, $forceReload);
            }

            return $dataName === $this->cache[$commandName];
        }

        throw new \Exception('this case is still todo');

        $command = $this->config->getCommand($commandName);
        $command['name'] = $commandName;
    }

    public function set($commandName, $value = null)
    {
        $dataName = null;
        $command = $this->findCommandByArgument($commandName, $dataName);

        if (null !== $dataName && null !== $value) {
            throw new \InvalidArgumentException(sprintf(
                'Either provide a $value or a command-data name; received "%s" and "%s"',
                $commandName,
                $value
            ));
        } elseif (null !== $dataName) {
            $value = $dataName;
        }

        $this->client->setCommand($command, $value);
        $this->cache[$command['name']] = $value;

        return $this;
    }

    protected function findCommandByArgument($argument, &$dataName = null)
    {
        $commandName = null;
        if ($this->config->hasCommand($argument)) {
            // found a command
            $commandName = $argument;
        } elseif ($this->config->hasCommandSplit($argument)) {
            // found a command-data-pair
            $split = $this->config->getCommandSplit($argument);
            $commandName = $split[0];
            $dataName = $split[1];
        } else {
            throw new \InvalidArgumentException(sprintf(
                'Unknown argument "%s"',
                $argument
            ));
        }

        $command = $this->config->getCommand($commandName);
        $command['name'] = $commandName;

        return $command;
    }
}
