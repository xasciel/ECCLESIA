<?php
// Rellena estos datos con los de tu panel InfinityFree. No compartas este archivo.
$host = 'sql102.infinityfree.com';
$dbname = 'if0_42901477_Ecclesia';
$username = 'if0_42901477';
$password = 'zgrAGwukafTGeY2';
try {
    return new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4",$username,$password,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
} catch(PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar a la base de datos.');
}
