<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
require_admin();

$id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    http_response_code(400);
    exit('Invalid book.');
}
$stmt = mysqli_prepare($conn, 'SELECT id, name, price, author, description, image, pdf FROM books WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$book = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$book) {
    http_response_code(404);
    exit('Book not found.');
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid request token.');
    }
    $name = trim($_POST['book_name'] ?? '');
    $price = filter_var($_POST['book_price'] ?? null, FILTER_VALIDATE_FLOAT);
    $author = trim($_POST['author'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $imageName = $book['image'];
    $pdfName = $book['pdf'];
    if ($name === '' || $author === '' || $description === '' || $price === false || $price <= 0) {
        $message = 'Enter valid book details.';
    } else {
        foreach (['book_image' => 'image', 'book_pdf' => 'pdf'] as $field => $kind) {
            $upload = $_FILES[$field] ?? null;
            if (!$upload || $upload['error'] === UPLOAD_ERR_NO_FILE) continue;
            if ($upload['error'] !== UPLOAD_ERR_OK || ($kind === 'image' && $upload['size'] > 5000000)
                || ($kind === 'pdf' && $upload['size'] > 20000000)) {
                $message = 'Upload failed or exceeds the file size limit.';
                break;
            }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
            $extension = $kind === 'image' ? (['image/jpeg' => 'jpg', 'image/png' => 'png'][$mime] ?? null)
                : ($mime === 'application/pdf' ? 'pdf' : null);
            if (!$extension) {
                $message = $kind === 'image' ? 'Image must be JPG or PNG.' : 'File must be a PDF.';
                break;
            }
            $newName = bin2hex(random_bytes(16)) . '.' . $extension;
            $folder = $kind === 'image' ? 'uploaded_img' : 'pdf';
            if (!move_uploaded_file($upload['tmp_name'], __DIR__ . '/' . $folder . '/' . $newName)) {
                $message = 'Could not save the upload.';
                break;
            }
            if ($kind === 'image') $imageName = $newName; else $pdfName = $newName;
        }
        if ($message === '') {
            $stmt = mysqli_prepare($conn, 'UPDATE books SET name = ?, price = ?, author = ?, description = ?, image = ?, pdf = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'sdssssi', $name, $price, $author, $description, $imageName, $pdfName, $id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header('Location: admin_page.php');
                exit();
            }
            mysqli_stmt_close($stmt);
            $message = 'Could not update the book.';
        }
    }
}
$token = csrf_token();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Edit book</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css"></head>
<body><main class="container py-5"><h1>Edit book</h1><?php if ($message !== ''): ?><p class="text-danger" role="alert"><?php echo h($message); ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?php echo h($token); ?>">
<div class="form-group"><label>Name</label><input class="form-control" name="book_name" maxlength="255" value="<?php echo h($book['name']); ?>" required></div>
<div class="form-group"><label>Price</label><input class="form-control" type="number" name="book_price" min="0" step="0.01" value="<?php echo h($book['price']); ?>" required></div>
<div class="form-group"><label>Author</label><input class="form-control" name="author" maxlength="255" value="<?php echo h($book['author']); ?>" required></div>
<div class="form-group"><label>Description</label><textarea class="form-control" name="description" maxlength="255" required><?php echo h($book['description']); ?></textarea></div>
<div class="form-group"><label>Replace image (optional)</label><input type="file" name="book_image" accept="image/jpeg,image/png"></div>
<div class="form-group"><label>Replace PDF (optional)</label><input type="file" name="book_pdf" accept="application/pdf,.pdf"></div>
<button class="btn btn-primary">Save changes</button> <a class="btn btn-secondary" href="admin_page.php">Cancel</a></form></main></body></html>
