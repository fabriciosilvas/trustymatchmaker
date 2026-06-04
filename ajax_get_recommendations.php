<?php
require_once('../../config.php');
require_login();
global $DB, $USER, $PAGE;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['status' => 'error', 'message' => 'Método não permitido']));
}

// BUSCA FAKE: Pega até 5 usuários ativos no banco (exceto o próprio usuário logado)
$sql = "SELECT id, firstname, lastname, picture, imagealt, email 
        FROM {user} 
        WHERE id != ? AND deleted = 0 AND suspended = 0 
        LIMIT 5";
        
$users = $DB->get_records_sql($sql, [$USER->id]);

$results = [];
foreach ($users as $u) {
    $userpicture = new user_picture($u);
    $userpicture->size = 1; // f1 (tamanho normal)
    $picurl = $userpicture->get_url($PAGE)->out(false);

    $results[] = [
        'id' => $u->id,
        'fullname' => fullname($u),
        'profileimageurl' => $picurl
    ];
}

echo json_encode(['status' => 'success', 'data' => $results]);