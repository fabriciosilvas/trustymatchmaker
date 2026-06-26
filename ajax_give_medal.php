<?php
// ajax_give_medal.php

require_once('../../config.php');
require_once($CFG->dirroot. '/local/trustymatchmaker/lib.php');

// Verifica se o usuário está logado
if (!isloggedin() || isguestuser()) {
    echo json_encode(['status' => 'error', 'message' => 'Usuário não autenticado.']);
    die();
}

$receiverid = required_param('receiverid', PARAM_INT);
$medalid = required_param('medalid', PARAM_INT); // Agora é singular
$issuerid = $USER->id;

$qtd_ja_dada = $DB->count_records('local_trustymatchmaker_issued', ['issuerid' => $issuerid, 'userid' => $receiverid]);

if ($qtd_ja_dada >= 1) {
    echo json_encode(['status' => 'error', 'message' => "Limite excedido. Você já concedeu uma medalha a este colaborador."]);
    die();
}

try {
    $giver = $DB->get_record('user', ['id' => $issuerid]);
    $receiver = $DB->get_record('user', ['id' => $receiverid]);
    $medal = $DB->get_record('local_trustymatchmaker_medals', ['id' => $medalid]);

    if (!$medal) {
        echo json_encode(['status' => 'error', 'message' => 'Medalha não encontrada.']);
        die();
    }

    // 1. Salvamento no banco de dados
    $new_medal = new stdClass();
    $new_medal->medalid = $medalid;
    $new_medal->userid = $receiverid;
    $new_medal->issuerid = $issuerid;
    $new_medal->timecreated = time();
    $DB->insert_record('local_trustymatchmaker_issued', $new_medal);

    // 2. Notificação (Agora sempre singular)
    $assunto = 'Você recebeu uma nova medalha!';
    $texto_html = "<p>Olá, <strong>" . fullname($receiver) . "</strong>!</p>";
    $texto_html .= "<p><strong>" . fullname($giver) . "</strong> reconheceu sua colaboração e lhe presenteou com a medalha: <strong>{$medal->name}</strong>.</p>";
    $texto_html .= "<p>Acesse o seu perfil do Trusty MatchMaker para ver a medalha recebida!</p>";

    $eventdata = new \core\message\message();
    $eventdata->courseid          = SITEID;
    $eventdata->component         = 'moodle'; 
    $eventdata->name              = 'badgerecipientnotice'; 
    $eventdata->userfrom          = $giver;
    $eventdata->userto            = $receiver;
    $eventdata->subject           = $assunto;
    $eventdata->fullmessage       = strip_tags($texto_html); 
    $eventdata->fullmessageformat = FORMAT_HTML;
    $eventdata->fullmessagehtml   = $texto_html; 
    $eventdata->smallmessage      = $assunto; 
    $eventdata->notification      = 1; 

    message_send($eventdata);

    echo json_encode(['status' => 'success', 'message' => 'Medalha concedida e notificação enviada com sucesso!']);
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar no banco de dados.']);
}