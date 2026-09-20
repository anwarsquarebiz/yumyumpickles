<?php

$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

/*
 | PHP's built-in server returns 403 for Windows directory junctions.
 | `php artisan storage:link` creates public/storage as a junction, so
 | treating those URIs as static files hides product images and banners.
 | Hand them to Laravel's public-disk file server instead.
 */
$isPublicStorage = $uri === '/storage' || str_starts_with($uri, '/storage/');

if ($uri !== '/' && ! $isPublicStorage && file_exists($publicPath.$uri)) {
    return false;
}

/*
 | The built-in server sets SCRIPT_NAME to the request URI when the path
 | looks like a file (e.g. /storage/products/foo.webp). Laravel then treats
 | that URI as the app mount point and serves the homepage as HTML.
 */
$_SERVER['SCRIPT_FILENAME'] = $publicPath.'/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

$formattedDateTime = date('D M j H:i:s Y');

$requestMethod = $_SERVER['REQUEST_METHOD'];
$remoteAddress = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];

file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

require_once $publicPath.'/index.php';
