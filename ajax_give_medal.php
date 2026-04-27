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
$medalids = required_param_array('medalids', PARAM_INT); 
$issuerid = $USER->id;
$qtd_selecionada = count($medalids);

// Trava 1: Garante que selecionou pelo menos 1 e no máximo 2 nesta tela
if ($qtd_selecionada < 1 || $qtd_selecionada > 2) {
    echo json_encode(['status' => 'error', 'message' => 'Você deve selecionar até duas medalhas.']);
    die();
}

// Trava 2: A Matemática do Banco de Dados
$qtd_ja_dada = $DB->count_records('local_trustymatchmaker_issued', ['issuerid' => $issuerid, 'userid' => $receiverid]);

// Se o que ele escolheu agora + o que ele já deu passar de 2, bloqueia!
if (($qtd_ja_dada + $qtd_selecionada) > 2) {
    $restante = 2 - $qtd_ja_dada;
    echo json_encode(['status' => 'error', 'message' => "Limite excedido. Você já concedeu {$qtd_ja_dada} medalha(s) a este usuário e só pode enviar mais {$restante}."]);
    die();
}

try {
    $giver = $DB->get_record('user', ['id' => $issuerid]);
    $receiver = $DB->get_record('user', ['id' => $receiverid]);

    $nomes_das_medalhas = [];

    // 1. Salvamento no banco de dados
    foreach ($medalids as $medalid) {
        if (!$medal = $DB->get_record('local_trustymatchmaker_medals', ['id' => $medalid])) {
            continue; 
        }

        $new_medal = new stdClass();
        $new_medal->medalid = $medalid;
        $new_medal->userid = $receiverid;
        $new_medal->issuerid = $issuerid;
        $new_medal->timecreated = time();
        $DB->insert_record('local_trustymatchmaker_issued', $new_medal);

        // Guarda o nome desta medalha na nossa lista
        $nomes_das_medalhas[] = $medal->name;
    }

    // 2. Notificação
    $qtd_concedida = count($nomes_das_medalhas);
    
    if ($qtd_concedida > 0) {
        
        $texto_medalhas = implode(' e ', $nomes_das_medalhas);
        
        // Verifica se é 1 ou mais de 1 para adaptar o português
        if ($qtd_concedida === 1) {
            $assunto = 'Você recebeu uma nova medalha!';
            $texto_html = "<p>Olá, <strong>" . fullname($receiver) . "</strong>!</p>";
            $texto_html .= "<p><strong>" . fullname($giver) . "</strong> reconheceu sua colaboração e lhe presenteou com a medalha: <strong>{$texto_medalhas}</strong>.</p>";
            $texto_html .= "<p>Acesse o seu perfil do Trusty MatchMaker para ver a medalha recebida!</p>";;
        }
        else {
            $assunto = 'Você recebeu duas novas medalhas!';
            $texto_html = "<p>Olá, <strong>" . fullname($receiver) . "</strong>!</p>";
            $texto_html .= "<p><strong>" . fullname($giver) . "</strong> reconheceu sua colaboração e lhe presenteou com as medalhas: <strong>{$texto_medalhas}</strong>.</p>";
            $texto_html .= "<p>Acesse o seu perfil do Trusty MatchMaker para ver as medalhas recebidas!</p>";;
        }

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
        $eventdata->smallmessage      = $smallmessage; 
        $eventdata->notification      = 1; 

        message_send($eventdata);
    }

    echo json_encode(['status' => 'success', 'message' => 'Medalha(s) concedida(s) e notificação enviada com sucesso!']);
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar no banco de dados.']);
}