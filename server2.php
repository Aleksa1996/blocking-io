<?php
set_time_limit(0);

//create socket, bind address and port and listen for incoming connections
$server = stream_socket_server('tcp://10.10.10.11:8000');
stream_set_blocking($server, 0);

$allConnections = [];

//accept connection
while ($connection = stream_socket_accept($server)) {

    $allConnections[stream_socket_get_name($connection, true)] = $connection;

    echo "New Connection \n";

    $welcomeMessage = "Weclome to our chat! \n";
    fwrite($connection, $welcomeMessage, strlen($welcomeMessage));

    while ($msg = fread($connection, 2048)) {
        fwrite($connection, $msg, strlen($msg));
        foreach ($allConnections as $key => $conn) {
            if ($key != stream_socket_get_name($connection, true)) {
                fwrite($conn, $msg, strlen($msg));
            }
        }

    }


    stream_socket_shutdown($connection, STREAM_SHUT_RDWR);
    fclose($connection);
}
