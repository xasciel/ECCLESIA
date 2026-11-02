<?php
require 'bootstrap.php';
require_church();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$q = trim((string)($_GET['q'] ?? ''));
if (strlen($q) < 2) { echo '[]'; exit; }
try {
    $like = '%' . $q . '%';
    $sql = 'SELECT id, first_names, last_names, birth_date, birthplace, father_name, mother_name
            FROM people WHERE church_id = ? AND (
            first_names LIKE ? OR last_names LIKE ? OR
            CONCAT_WS(" ",first_names,last_names) LIKE ? OR
            CONCAT_WS(" ",last_names,first_names) LIKE ? OR
            father_name LIKE ? OR mother_name LIKE ?)
            ORDER BY last_names, first_names, id LIMIT 30';
    $stmt = $db->prepare($sql);
    $stmt->execute([(int)me()['church_id'],$like,$like,$like,$like,$like,$like]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $ex) {
    error_log('Ecclesia buscar personas: '.$ex->getMessage());
    http_response_code(500);
    echo json_encode(['error'=>'Error al consultar personas. Comprueba que search_people.php esté subido y la tabla people tenga los campos esperados.']);
}
