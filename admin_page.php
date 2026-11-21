<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
require_admin();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid request token.');
    }
    if (isset($_POST['delete_id'])) {
        $id = filter_var($_POST['delete_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id) {
            $stmt = mysqli_prepare($conn, 'DELETE FROM books WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header('Location: admin_page.php');
        exit();
    }
    if (isset($_POST['add_book'])) {
        $name = trim($_POST['book_name'] ?? '');
        $price = filter_var($_POST['book_price'] ?? null, FILTER_VALIDATE_FLOAT);
        $author = trim($_POST['author'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image = $_FILES['book_image'] ?? [];
        $pdf = $_FILES['book_pdf'] ?? [];
        if ($name === '' || $author === '' || $description === '' || $price === false || $price <= 0
            || ($image['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || ($pdf['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $message = 'Enter book details and choose an image and PDF.';
        } else {
            $imageMime = (new finfo(FILEINFO_MIME_TYPE))->file($image['tmp_name']);
            $pdfMime = (new finfo(FILEINFO_MIME_TYPE))->file($pdf['tmp_name']);
            $imageExt = ['image/jpeg' => 'jpg', 'image/png' => 'png'][$imageMime] ?? null;
            if (!$imageExt || $pdfMime !== 'application/pdf' || $pdf['size'] > 20000000 || $image['size'] >= 5000000) {
                $message = 'Upload a JPG or PNG image (max 5 MB) and a PDF (max 20 MB).';
            } else {
                $imageName = bin2hex(random_bytes(16)) . '.' . $imageExt;
                $pdfName = bin2hex(random_bytes(16)) . '.pdf';
                $imagePath = __DIR__ . '/uploaded_img/' . $imageName;
                $pdfPath = __DIR__ . '/pdf/' . $pdfName;
                if (!move_uploaded_file($image['tmp_name'], $imagePath)) {
                    $message = 'Could not save the image.';
                } elseif (!move_uploaded_file($pdf['tmp_name'], $pdfPath)) {
                    @unlink($imagePath);
                    $message = 'Could not save the PDF.';
                } else {
                    $stmt = mysqli_prepare($conn, 'INSERT INTO books (name, price, author, description, image, pdf) VALUES (?, ?, ?, ?, ?, ?)');
                    mysqli_stmt_bind_param($stmt, 'sdssss', $name, $price, $author, $description, $imageName, $pdfName);
                    if (mysqli_stmt_execute($stmt)) {
                        header('Location: admin_page.php');
                        exit();
                    }
                    @unlink($imagePath);
                    @unlink($pdfPath);
                    $message = 'Could not add the book.';
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}
$books = mysqli_query($conn, 'SELECT id, name, price, author, description, image, pdf FROM books ORDER BY id DESC');
$token = csrf_token();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Manage books</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css"></head>
<body><main class="container py-5"><h1>Manage books</h1><p><a href="logout.php">Log out</a></p>
<?php if ($message !== ''): ?><p class="text-danger" role="alert"><?php echo h($message); ?></p><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="mb-5"><input type="hidden" name="csrf_token" value="<?php echo h($token); ?>"><h2>Add a book</h2>
<div class="form-group"><label>Name</label><input class="form-control" name="book_name" maxlength="255" required></div>
<div class="form-group"><label>Price</label><input class="form-control" type="number" name="book_price" min="0" step="0.01" required></div>
<div class="form-group"><label>Author</label><input class="form-control" name="author" maxlength="255" required></div>
<div class="form-group"><label>Description</label><textarea class="form-control" name="description" maxlength="255" required></textarea></div>
<div class="form-group"><label>Image</label><input type="file" name="book_image" accept="image/jpeg,image/png" required></div>
<div class="form-group"><label>PDF</label><input type="file" name="book_pdf" accept="application/pdf,.pdf" required></div>
<button class="btn btn-primary" name="add_book" value="1">Add book</button></form>
<h2>Catalog</h2><div class="table-responsive"><table class="table"><thead><tr><th>Book</th><th>Price</th><th>Author</th><th>File</th><th>Actions</th></tr></thead><tbody>
<?php while ($book = mysqli_fetch_assoc($books)): ?><tr><td><?php echo h($book['name']); ?></td><td><?php echo h($book['price']); ?></td><td><?php echo h($book['author']); ?></td><td><?php echo h($book['pdf']); ?></td><td><a href="admin_update.php?edit=<?php echo (int) $book['id']; ?>">Edit</a>
<form method="post" class="d-inline" onsubmit="return confirm('Delete this book?')"><input type="hidden" name="csrf_token" value="<?php echo h($token); ?>"><input type="hidden" name="delete_id" value="<?php echo (int) $book['id']; ?>"><button class="btn btn-link text-danger">Delete</button></form></td></tr><?php endwhile; ?></tbody></table></div></main></body></html>
