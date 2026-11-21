<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
require_login();

$bookId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$bookId) {
    http_response_code(400);
    exit('Invalid book.');
}

$stmt = mysqli_prepare($conn, 'SELECT pdf FROM books WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $bookId);
mysqli_stmt_execute($stmt);
$book = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$book || !preg_match('/\A[\pL\pN._ -]+\.pdf\z/iu', $book['pdf'])) {
    http_response_code(404);
    exit('File not found.');
}

$pdfDirectory = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'pdf');
$filePath = $pdfDirectory === false ? false : realpath($pdfDirectory . DIRECTORY_SEPARATOR . $book['pdf']);
if ($filePath === false || !is_file($filePath)
    || strncmp($filePath, $pdfDirectory . DIRECTORY_SEPARATOR, strlen($pdfDirectory . DIRECTORY_SEPARATOR)) !== 0) {
    http_response_code(404);
    exit('File not found.');
}

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: attachment; filename="' . rawurlencode(basename($filePath)) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($filePath);
exit();
