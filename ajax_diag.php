<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../../config.php');
require_login();
global $DB, $USER, $PAGE, $CFG;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['status' => 'error', 'message' => 'Metodo nao permitido']));
}

$raw_criteria = optional_param_array('criteria', [], PARAM_ALPHANUMEXT);

$trustor_row = $DB->get_record('user',
    ['id' => $USER->id],
    'id, firstname, lastname, city, country, department',
    MUST_EXIST
);

echo json_encode(['status' => 'ok_diag', 'userid' => $USER->id, 'criteria' => $raw_criteria, 'firstname' => $trustor_row->firstname]);
