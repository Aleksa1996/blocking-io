<?php
set_time_limit(0);

//create socket, bind address and port and listen for incoming connections
$server = stream_socket_server('tcp://10.10.10.11:8000');
stream_set_blocking($server, 0);

$allConnections = [];

//accept connection
while ($connection = stream_socket_accept($server)) {

    echo "New Connection \n";

    $welcomeMessage = "Weclome to our chat! \n";
    fwrite($connection, $welcomeMessage, strlen($welcomeMessage));

    while ($msg = fread($connection, 2048)) {
        echo "$msg \n";
    }

    stream_socket_shutdown($connection, STREAM_SHUT_RDWR);
    fclose($connection);
}
