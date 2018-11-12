<?php

$connection = stream_socket_client('tcp://10.10.10.11:8000');
set_time_limit(0);
stream_set_blocking($connection, 0);

if ($connection) {
    echo "Connected to server! \n";
}

echo "Send message to chat (0 for exit) \n";



$stdin = fopen('php://stdin', 'r');
stream_set_blocking($stdin, 0);

$run = true;
do {

    $typed = fgets($stdin);

    if (empty($typed)) {
        while ($msg = fread($connection, 2048)) {
            echo "$msg \n";
        }
    } else {
        $run = $typed != '0';
        fwrite($connection, trim($typed), strlen($typed));
    }

} while ($run);


// while (true) {

//     do {
//         $typed = readline("You: ");
//         $typed = trim($typed);
//         fwrite($connection, $typed, strlen($typed));

//         while ($msg = fread($connection, 2048)) {
//             echo "$msg \n";
//         }

//         $run = $typed != '0';
//     } while ($run);

//     echo "stopped sending data! \n";
//     // break;
// }
