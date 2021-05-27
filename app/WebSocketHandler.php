<?php

namespace App;

use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;
use DB;
use SplObjectStorage;
use function Ratchet\Client\connect;

class WebSocketHandler implements MessageComponentInterface
{
    protected $clients;
    public function __construct() {
        $this->clients = new SplObjectStorage;
    }

    /**
     * When a new connection is opened it will be passed to this method
     * @param ConnectionInterface $conn The socket/connection that just connected to your application
     * @throws \Exception
     */
    function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection in $this->clients
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     * @param ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    function onClose(ConnectionInterface $conn)
    {
        // TODO: Implement onClose() method.
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param ConnectionInterface $conn
     * @param \Exception $e
     * @throws \Exception
     */
    function onError(ConnectionInterface $conn, \Exception $e)
    {
        // TODO: Implement onError() method.
    }

    public function onMessage(ConnectionInterface $conn, MessageInterface $msg)
    {
        foreach ( $this->clients as $client ) {
            if ( $conn->resourceId == $client->resourceId ) {
                continue;
            }
            $client->send( "Client $conn->resourceId said $msg" );
        }
    }
    public function b(){

        connect('ws://localhost:6001/reader_stream')->then(

            function($conn) {
            $conn->send('hash updated!');
            $conn->close();
        }, function ($e) {
//            dd($e);
        });
    }
}
