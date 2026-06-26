<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>;.

/**
 *
 * @package     local_trustymatchmaker
 * @copyright   2023 Your name <your@email>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_once($CFG->dirroot. '/local/trustymatchmaker/lib.php');

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['status' => 'error', 'message' => 'Método não permitido']));
}

$friendtoadd = required_param('friendtoadd', PARAM_INT);
$userid = $USER->id;

$context = context_system::instance();
$PAGE->set_context($context);

// Garanta que esta linha exista antes do try:
global $DB;

try {
    // 1. Verifica se já são amigos
    if (\core_message\api::is_contact($userid, $friendtoadd)) {
        echo json_encode([
            'status' => 'already_friends', 
            'message' => 'Você e este colaborador já são amigos!'
        ]);
        exit;
    }

    // 2. Verifica se JÁ EXISTE um convite pendente (ida ou volta)
    $convite_enviado = $DB->record_exists('message_contact_requests', ['userid' => $userid, 'requesteduserid' => $friendtoadd]);
    $convite_recebido = $DB->record_exists('message_contact_requests', ['userid' => $friendtoadd, 'requesteduserid' => $userid]);

    if ($convite_enviado || $convite_recebido) {
        echo json_encode([
            'status' => 'pending_request', 
            'message' => 'Já existe um convite de contato pendente entre vocês!'
        ]);
        exit; // Para a execução aqui
    }

    // 3. Se passou pelas duas travas, prossegue com a sua função
    local_trustymatchmaker_add_friend($userid, $friendtoadd);
    
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}