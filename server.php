<?php

$publicPath = __DIR__.'/public';
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

// File statis di public/ (termasuk lewat symlink public/storage → storage/app/public) dilayani langsung.
// Jangan memakai realpath(): symlink storage menunjuk ke luar public/, sehingga sampul artikel dan
// foto unggahan admin tidak pernah tersaji dan jatuh ke Laravel (halaman HTML) di server dev.
if ($uri !== '/' && ! str_contains($uri, '..') && is_file($publicPath.$uri)) {
    return false;
}

require $publicPath.'/index.php';
