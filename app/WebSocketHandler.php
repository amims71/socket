<?php

namespace App;

use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use DB;
use function Ratchet\Client\connect;

class WebSocketHandler implements MessageComponentInterface
{
    public function onOpen(ConnectionInterface $connection)
    {
        // TODO: Implement onOpen() method.
        \Log::debug('ON OPEN');
        $socketId = sprintf('%d.%d', random_int(1, 1000000000), random_int(1, 1000000000));
        $connection->socketId = $socketId;
        $connection->app = new \stdClass();
        $connection->app->id = 'my_app';
    }

    public function onClose(ConnectionInterface $connection)
    {
        // TODO: Implement onClose() method.
        \Log::debug('ON CLOSE');
    }

    public function onError(ConnectionInterface $connection, \Exception $e)
    {
        // TODO: Implement onError() method.
        \Log::debug('ON ERROR');
        // \Log::debug([$connection]);
        // \Log::debug($e);
    }

    public function onMessage(ConnectionInterface $connection, MessageInterface $msg)
    {
        $connection->send('Hello World!');
        // TODO: Implement onMessage() method.
        \Log::debug(['ON MESSAGE', $msg]);
    }

    public function b(){
        \Ratchet\Client\connect('ws://localhost:6001/reader_stream')->then(function($conn) {
            $conn->send('hash updated!');
            $conn->close();
        }, function ($e) {});
       return true;
    }
}
