<?php
set_time_limit(0);

class Loop
{
    public $readStreams = [];
    public $writeStreams = [];
    public $readListeners = [];
    public $writeListeners = [];

    public function run()
    {

        while (true) {

            if (!$this->readStreams && !$this->writeStreams) {
                break;
            }

            $read = $this->readStreams;
            $write = $this->writeStreams;
            $available = $this->streamSelect($read, $write);

            if (false === $available) {
                return;
            }

            foreach ($read as $stream) {
                $key = (int)$stream;
                if (isset($this->readListeners[$key])) {
                    call_user_func($this->readListeners[$key], $stream);
                }
            }

            foreach ($write as $stream) {
                $key = (int)$stream;
                if (isset($this->writeListeners[$key])) {
                    call_user_func($this->writeListeners[$key], $stream);
                }
            }
        }

    }

    public function streamSelect(&$read, &$write)
    {
        if ($read || $write) {
            $except = null;
            return stream_select($read, $write, $except, null, null);
        }

        return 0;
    }

    public function addReadStream($stream, $callback)
    {
        $key = (int)$stream;
        if (!isset($this->readStreams[$key])) {
            $this->readStreams[$key] = $stream;
            $this->readListeners[$key] = $callback;
        }
    }

    public function removeReadStream($stream)
    {
        $key = (int)$stream;
        unset($this->readStreams[$key], $this->readListeners[$key]);
    }

    public function addWriteStream($stream, $callback)
    {
        $key = (int)$stream;
        if (!isset($this->writeStreams[$key])) {
            $this->writeStreams[$key] = $stream;
            $this->writeListeners[$key] = $callback;
        }
    }

    public function removeWriteStream($stream)
    {
        $key = (int)$stream;
        unset($this->writeStreams[$key], $this->writeListeners[$key]);
    }
}


class Server
{
    public $server;
    /**
     * Undocumented variable
     *
     * @var Loop
     */
    public $loop;
    public $connections = [];

    public function __construct()
    {
        $this->server = stream_socket_server(
            'tcp://10.10.10.11:8000',
            $errno,
            $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
        );
        stream_set_blocking($this->server, 0);

        $this->loop = new Loop();
    }

    public function listen()
    {
        $this->loop->addReadStream($this->server, function ($stream) {
            $connection = stream_socket_accept($stream);
            $this->handleConnection($connection);
        });
    }

    public function handleConnection($connection)
    {
        $this->connections[(int)$connection] = ['connection' => $connection, 'nick' => ''];

        $this->loop->addReadStream($connection, function ($stream) {
            stream_set_blocking($stream, 0);
            $data = stream_get_contents($stream, 65536);
            $this->handleData($stream, $data);
        });
    }

    public function handleData($stream, $data)
    {
        if (empty($data)) {
            unset($this->connections[(int)$stream]);
            $this->loop->removeReadStream($stream);
            stream_socket_shutdown($stream, STREAM_SHUT_RDWR);
            fclose($stream);
        } else {
            //Save nick
            if (empty($this->connections[(int)$stream]['nick'])) {
                $this->connections[(int)$stream]['nick'] = $data;
                $data = 'New user joined with nick: ' . $data;
            } else {
                $data = $this->connections[(int)$stream]['nick'] . ": " . $data;
            }
            $this->broadcastMessage($stream, "$data \n");
        }
    }


    public function broadcastMessage($from, $message)
    {
        foreach ($this->connections as $connection) {
            if ($connection['connection'] != $from)
                fwrite($connection['connection'], trim($message));
        }
    }

    public function run()
    {
        $this->loop->run();
    }

}



$instance = new Server();

$instance->listen();

$instance->run();


// Making response for browser

// echo "$data \n";
// $response = "HTTP/1.1 200 Not Found\r\nAccess-Control-Allow-Origin: *\r\n";
// $response .= "Accept: application/json\r\n";
// $response .= "Content-Type: application/json; charset=UTF-8\r\n\n";
// $response .= '{"json":"true"}';
// $headers = [];
// $headerss = explode("\n", $data);

// foreach ($headerss as $header) {
//     $it = explode(':', $header);
//     if (isset($it[0]) && isset($it[1])) {
//         $headers[trim($it[0])] = trim($it[1]);
//     }
// }


// $ws_magic_string = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
// $accept = base64_encode(sha1($headers['Sec-WebSocket-Key'] . $ws_magic_string, true));

// $response = "HTTP/1.1 101 Switching Protocols\r\n";
// $response .= "Access-Control-Allow-Origin: *\r\n";
// $response .= "Upgrade: websocket\r\n";
// $response .= "Connection: Upgrade\r\n";
// $response .= "WebSocket-Location: ws://10.10.10.11:8000\r\n";
// $response .= "Sec-WebSocket-Accept: $accept\r\n\r\n";
