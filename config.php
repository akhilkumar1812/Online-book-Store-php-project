<?php

$conn = mysqli_connect('localhost','root','','onlinebookstore');
if (!$conn) {
    http_response_code(500);
    exit('Database connection failed.');
}
mysqli_set_charset($conn, 'utf8mb4');

?>
