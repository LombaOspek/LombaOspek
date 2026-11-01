<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Tolak permintaan MCP yang nyasar ke dev server
|--------------------------------------------------------------------------
|
| Klien MCP di mesin pengembang (mis. `laravel/boost` lewat .mcp.json, atau
| editor yang masih menunjuk ke http://127.0.0.1:8000/mcp) mencoba menyambung
| ulang terus-menerus saat server MCP-nya tidak hidup. Setiap percobaan tetap
| membangun seluruh framework hanya untuk berakhir 404: ~320 ms per request,
| puluhan kali per menit, dan dev server PHP yang hanya satu proses jadi
| kehabisan napas sehingga halaman biasa ikut 500 ms - 2 detik.
|
| Permintaan itu tidak pernah dilayani aplikasi ini, jadi ditolak lebih awal
| dengan 404 murah sebelum framework dibootstrap. Hapus blok ini bila suatu
| saat rute /mcp memang mau dilayani aplikasi ini.
|
*/
if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/mcp')) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    echo 'No MCP endpoint on this server.';
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
