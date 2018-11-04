<?php

$connection = stream_socket_client('tcp://10.10.10.11:8000');

if ($connection) {
    echo "Connected to server! \n";
}

echo "Send message to chat (0 for exit) \n";

while ($msg = fread($connection, 2048)) {
    echo "$msg \n";

    do {
        $typed = readline("You: ");
        $typed = trim($typed);
        fwrite($connection, $typed, strlen($typed));

        $run = $typed == '0' ? false : true;
    } while ($run);

    echo "stopped sending data! \n";
    break;
}

fclose($connection);
stream_socket_shutdown($connection, STREAM_SHUT_RDWR);
