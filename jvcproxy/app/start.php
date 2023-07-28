#!/usr/bin/php
<?php

require_once 'Sockets/Server.php';
require_once 'Sockets/Socket.php';
require_once 'Sockets/Exception/SocketException.php';

require_once 'JvcProjector/Exception/ExceptionInterface.php';
require_once 'JvcProjector/Exception/ConnectionClosedException.php';
require_once 'JvcProjector/Exception/DeviceNotFoundException.php';
require_once 'JvcProjector/Exception/PowerOffException.php';
require_once 'JvcProjector/Exception/TimeOutException.php';
require_once 'JvcProjector/JvcClientInterface.php';
require_once 'JvcProjector/JvcClient.php';
require_once 'JvcProjector/JvcConfigInterface.php';
require_once 'JvcProjector/AbstractJvcConfig.php';
require_once 'JvcProjector/JvcConfigDilaX5000.php';
require_once 'JvcProjector/JvcProjector.php';

require_once 'JvcServer.php';

require_once 'JvcProxy.php';

use clemens321\JvcProjector\JvcClient;

$options = [];
if (file_exists('/data/options.json')) {
    $options = json_decode(file_get_contents('/data/options.json'), true);
}

if (!isset($options['jvc_addr']) || !$options['jvc_addr']) {
    echo "No jvc address defined in options\n";
    exit(1);
}
if (isset($options['jvc_port'])) {
    if (!is_int($options['jvc_port']) || 1 > $options['jvc_port'] || 65535 < $options['jvc_port']) {
        echo "Given jvc port is invalid\n";
        exit(1);
    }
} else {
    $options['jvc_port'] = null;
}

printf("Using %s:%s as upstream projector\n", $options['jvc_addr'], $options['jvc_port'] ?? '<default>');
$jvcClient = new JvcClient($options['jvc_addr'], $options['jvc_port']);

$proxy = new JvcProxy();

if (isset($options['sleep_us']) && is_int($options['sleep_us']) && 0 <= $options['sleep_us'] && 1000000 >= $options['sleep_us']) {
    printf("Set main loop sleep to %dus\n", $options['sleep_us']);
    $proxy->setUSleep($options['sleep_us']);
}
$proxy->setJvcClient($jvcClient);

pcntl_async_signals(true);

$signalTerminate = function ($signalNumber) use ($jvcClient, $proxy) {
    printf("Signal %d received, exiting\n", $signalNumber);
    $proxy->stop();
    $jvcClient->disconnect();

    exit(0);
};
pcntl_signal(SIGINT, $signalTerminate);
pcntl_signal(SIGTERM, $signalTerminate);

$proxy->run();
