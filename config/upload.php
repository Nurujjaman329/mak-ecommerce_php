<?php
// config/upload.php
$uploadDir = __DIR__ . '/../uploads/';
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

function uploadFiles($files) {
    global $uploadDir;
    $uploaded = [];
    foreach ($files['name'] as $key => $name) {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if(!in_array($ext, ['jpg','jpeg','png'])) continue;

        $filename = time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        move_uploaded_file($files['tmp_name'][$key], $uploadDir . $filename);
        $uploaded[] = $filename;
    }
    return $uploaded;
}
