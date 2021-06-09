<?php

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManagers\ArrayChannelManager;
use Illuminate\Support\Facades\Route;

use Ratchet\ConnectionInterface;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/a', function () {
    return view('a');
});
Route::get('/b',[\App\WebSocketHandler::class,'b']);

Route::get('/t', function (ChannelManager $channelManager) {
    \Ratchet\Client\connect('ws://localhost:6001/reader_stream')->then(function($connection) {
//        dd($connection);
        $connection->send('hash updated!');
        $connection->close();
    }, function ($e) {});
//    dd($channel);
    event(new App\Events\RealTimeMessage("&something=12-12-13"));
    dd('Event Run Successfully.');
});

use Symfony\Component\Console\Output\NullOutput;
use BeyondCode\LaravelWebSockets\Server\Logger\WebsocketsLogger;

app()->singleton(WebsocketsLogger::class, function () {
    return (new WebsocketsLogger(new NullOutput()))->enable(false);
});

use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;

WebSocketsRouter::webSocket('/reader_stream', \App\WebSocketHandler::class);
