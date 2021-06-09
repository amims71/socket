<?php

namespace App;

use BeyondCode\LaravelWebSockets\Apps\App;
use BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger;
use BeyondCode\LaravelWebSockets\Facades\StatisticsLogger;
use BeyondCode\LaravelWebSockets\QueryParameters;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\ConnectionsOverCapacity;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\UnknownAppKey;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\WebSocketException;
use BeyondCode\LaravelWebSockets\WebSockets\Messages\PusherMessageFactory;
use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use DB;
use function Ratchet\Client\connect;

class WebSocketHandler implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = array();

    }
    public function onOpen(ConnectionInterface $connection)
    {
        $this
            ->verifyAppKey($connection)
            ->limitConcurrentConnections($connection)
            ->generateSocketId($connection)
            ->establishConnection($connection);
        $this->clients[$connection->socketId]=$connection;
        $connection->send('test');
        \Log::debug('ON OPEN');
    }

    public function onClose(ConnectionInterface $connection)
    {
        \Log::debug(count($this->clients));

        unset($this->clients[$connection->socketId]);
        $connection->close();
        DashboardLogger::disconnection($connection);

        StatisticsLogger::disconnection($connection);
        \Log::debug('ON CLOSE');
        \Log::debug(count($this->clients));
    }

    public function onError(ConnectionInterface $connection, Exception $exception)
    {
        if ($exception instanceof WebSocketException) {
            $connection->send(json_encode(
                $exception->getPayload()
            ));
        }
        \Log::debug('ON CLOSE');
    }

    public function onMessage(ConnectionInterface $connection, MessageInterface $message)
    {
        $requestContent='xyz';
//        dump(count($this->clients));
        foreach ($this->clients as $key=>$client) {
//dd($client->socketId);
            if ($connection !== $client) {
                $client->send('&versions_hash='.$requestContent);
            }
        }

        StatisticsLogger::webSocketMessage($connection);
        \Log::debug('ON MESSAGE');
    }

   /* public function b(){
        \Ratchet\Client\connect('ws://localhost:6001/reader_stream')->then(function($conn) {
            $conn->send('hash updated!');
            $conn->close();
        }, function ($e) {});
       return true;
    }*/



    protected function verifyAppKey(ConnectionInterface $connection)
    {
        $appKey = '12345';

        if (! $app = App::findByKey($appKey)) {
            throw new UnknownAppKey($appKey);
        }

        $connection->app = $app;

        return $this;
    }

    protected function limitConcurrentConnections(ConnectionInterface $connection)
    {
        if (! is_null($capacity = $connection->app->capacity)) {
            $connectionsCount = $this->channelManager->getConnectionCount($connection->app->id);
            if ($connectionsCount >= $capacity) {
                throw new ConnectionsOverCapacity();
            }
        }

        return $this;
    }

    protected function generateSocketId(ConnectionInterface $connection)
    {
//        $socketId = sprintf('%d.%d', random_int(1, 1000000000), random_int(1, 1000000000));
        $queryString = $this->queryToArray($connection->httpRequest->getUri()->getQuery());
        $guid = isset($queryString['guid']) ? $queryString['guid']:'';
        $connection->socketId = $guid;

        return $this;
    }

    protected function establishConnection(ConnectionInterface $connection)
    {
        $connection->send(json_encode([
            'event' => 'pusher:connection_established',
            'data' => json_encode([
                'socket_id' => $connection->socketId,
                'activity_timeout' => 30,
            ]),
        ]));

        DashboardLogger::connection($connection);

        StatisticsLogger::connection($connection);

        return $this;
    }
    public function queryToArray($qry)
    {
        $result = array();
        //string must contain at least one = and cannot be in first position
        if(strpos($qry,'=')) {

            if(strpos($qry,'?')!==false) {
                $q = parse_url($qry);
                $qry = $q['query'];
            }
        }else {
            return false;
        }

        foreach (explode('&', $qry) as $couple) {
            list ($key, $val) = explode('=', $couple);
            $result[$key] = $val;
        }

        return empty($result) ? false : $result;
    }
}
