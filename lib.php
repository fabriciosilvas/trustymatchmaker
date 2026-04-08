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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     local_trustymatchmaker
 * @category    string
 * @copyright   2025 InfoNaEdu
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_message\api;

function local_trustymatchmaker_get_greeting($user) {
    if ($user == null) {
        return get_string('greetinguser', 'local_trustymatchmaker');
    }

    $country = $user->country;
    switch ($country) {
        case 'ES':
            $langstr = 'greetinguseres';
            break;
        case 'BR':
            $langstr = 'greetinguserbr';
            break;
        default:
            $langstr = 'greetingloggedinuser';
            break;
    }

    return get_string($langstr, 'local_trustymatchmaker', fullname($user));
}

function local_trustymatchmaker_extend_navigation_frontpage(navigation_node $frontpage) {
    $frontpage->add(
        get_string('pluginname','local_trustymatchmaker'), 
        new moodle_url('/local/trustymatchmaker/index.php'),
        navigation_node::TYPE_CUSTOM,
    );  
}

function local_trustymatchmaker_load_profile_picture($user, $context, $page, $size = 100) {
    global $DB, $OUTPUT;

    $userpic = core_user::get_profile_picture($user, $context, ['size' => $size]);
    $url = $userpic->get_url($page);
    $hasPicture = $DB->get_field('user', 'picture', ['id' => $user->id]);

    if (!$hasPicture) {
        // Se o usuário NÃO TEM FOTO, renderiza as iniciais
        $url = core_user::get_initials($user);
        $data = ['user-initials' => $url]; // Prepara os dados

        // Define a flag de tamanho correta
        if ($size == 30) {
            $data['s30'] = true;
        } else if ($size == 50) {
            $data['s50'] = true;
        } else {
            // Padrão para 100 (ou qualquer outro tamanho)
            $data['s100'] = true; 
        }
    return $OUTPUT->render_from_template('local_trustymatchmaker/inicial_pfl', $data);
    } else {
    // Se o usuário TEM FOTO, renderiza a imagem
    return $OUTPUT->render_from_template('local_trustymatchmaker/imagem_pfl', ['link' => $url, 'size' => $size]);
    }
}

function local_trustymatchmaker_load_navbar_pfl($pagina, $show_collaborators) {
    global $OUTPUT;
    if ($show_collaborators) {
        echo $OUTPUT->render_from_template('local_trustymatchmaker/pfl_nav', $pagina);
    } else {
        echo $OUTPUT->render_from_template('local_trustymatchmaker/sec_nav_wo_collaborator', $pagina);
    }
}

function local_trustymatchmaker_load_sections_pfl($user) {
    global $DB, $OUTPUT;
    local_trustymatchmaker_load_description($OUTPUT, $DB, $user->id);
    local_trustymatchmaker_load_user_info($OUTPUT, $DB, $user->id);
    local_trustymatchmaker_load_interests($OUTPUT, $DB, $user->id);
    local_trustymatchmaker_load_trust_score($OUTPUT, $DB, $user->id);
}

function local_trustymatchmaker_load_description($output, $db, $user_id) {
    $descricao = $db->get_field('user', 'description', ['id' => $user_id], MUST_EXIST);

    if (empty($descricao)) {
        $paragrafo = $output->render_from_template('local_trustymatchmaker/nada', ['texto' => "Nada a mostrar."]);
    }
    else {
        $paragrafo = $output->render_from_template('local_trustymatchmaker/paragrafo', ['texto' => 
    strip_tags($descricao)]);
    }

    $templatedata = ['section_name' => "Descrição",
        'conteudohtml' => $paragrafo];
        
    echo $output->render_from_template('local_trustymatchmaker/section', $templatedata);
}

function local_trustymatchmaker_load_interests($output, $db, $user_id) {

    //$interests = $db->get_fieldset('tag','name', ['userid' => $user_id]);

     $sql = 'SELECT DISTINCT t.id, t.name
        FROM {tag} t
        JOIN {tag_instance} ti ON t.id = ti.tagid
        WHERE ti.itemid = :userid';

    $params = ['userid' => $user_id];

    $interests = $db->get_records_sql($sql, $params);
    
    $nada = "";

    if (count($interests) > 0) {
        foreach ($interests as $interest) {
            $nada .= $output->render_from_template('local_trustymatchmaker/pfl_interest', ['interesse' => $interest->name]);
        }
        
    } else {
        $nada = $output->render_from_template('local_trustymatchmaker/nada', ['texto' => "Nada a mostrar."]);
    }

    $interesses = $output->render_from_template('local_trustymatchmaker/pfl_list_intesrests', [
        'conteudo-html' => $nada
    ]);

    $templatedata = ['section_name' => "Interesses",
        'conteudohtml' => $interesses];
    echo $output->render_from_template('local_trustymatchmaker/section', $templatedata);
}

function local_trustymatchmaker_load_user_info($output, $db, $user_id) {
    $cidade = $db->get_field('user', 'city', ['id' => $user_id]);
    $pais = $db->get_field('user', 'country', ['id' => $user_id]);
    $departamento = $db->get_field('user', 'department', ['id' => $user_id]);

    $apresentacao = "";

    if (!empty($cidade)) {
        if (!empty($pais)) {
            $cidade_pais = "$cidade, $pais";
            $apresentacao .= $output->render_from_template('local_trustymatchmaker/apresentacao_pfl', [
            'titulo' => "Cidade",
            'conteudo' => $cidade_pais
        ]);
        } else {
            $apresentacao .= $output->render_from_template('local_trustymatchmaker/apresentacao_pfl', [
                'titulo' => "Cidade",
                'conteudo' => $cidade
            ]);
        
        }
    } else {
        if (!empty($pais)) {
            $apresentacao .= $output->render_from_template('local_trustymatchmaker/apresentacao_pfl', [
                'titulo' => "País",
                'conteudo' => $pais
            ]);
        }
    }
        
    if (!empty($departamento)) {
        $apresentacao .= $output->render_from_template('local_trustymatchmaker/apresentacao_pfl', [
            'titulo' => "Departamento",
            'conteudo' => $departamento
        ]);
    }

    if (empty($apresentacao)) {
        $paragrafo = $output->render_from_template('local_trustymatchmaker/nada', ['texto' => "Nada a mostrar."]);

        $templatedata = ['section_name' => "Apresentação",
        'conteudohtml' => $paragrafo];
        echo $output->render_from_template('local_trustymatchmaker/section', $templatedata);
    } else {
        $templatedata = ['section_name' => "Apresentação",
        'conteudohtml' => $apresentacao];
        echo $output->render_from_template('local_trustymatchmaker/section', $templatedata);
    } 
}

/**
 * Feature Flag: Controla a exibição do Índice de Confiança (Estrelas)
 * @return bool True para mostrar para todos, False para ocultar de todos.
 */
function local_trustymatchmaker_is_trust_score_enabled() {
    // Mude para 'false' para ocultar o sistema inteiro.
    return true; 
}

function local_trustymatchmaker_load_trust_score($output, $db, $user_id) {

    if (!local_trustymatchmaker_is_trust_score_enabled()) {
        return; 
    }

    $scores = $db->get_records_sql(
    'SELECT ts.*, st.*
      FROM {local_trustymatchmaker_trust_score} ts
      JOIN {local_trustymatchmaker_score_types} st
           ON st.id = ts.scoreid
     WHERE ts.userid = :userid',
    [
        'userid' => $user_id, 
    ]
    );
    if ($scores) {
        $stars_html = "";
        foreach ($scores as $score) {
            if ($score->value >= 1) {
            $fill = ($score->value/5) * 100;
            $data = [
                'name' => $score->name,
                'fill' => $fill,
                'score' => $score->value,
                'description' => $score->description
            ];

        $stars_html .= $output->render_from_template('local_trustymatchmaker/score_item', $data);
        }
        }
        $templatedata = ['section_name' => "Índice de confiança",
        'conteudohtml' =>  "<div class='score-list'>".$stars_html."</div>"];
    }
    else {
      $nada = $output->render_from_template('local_trustymatchmaker/nada', ['texto' => "Usuário ainda não avaliado."]);
      $templatedata = ['section_name' => "Índice de confiança",
       'conteudohtml' => $nada];
   }
    echo $output->render_from_template('local_trustymatchmaker/section', $templatedata);
}

function local_trustymatchmaker_load_overall_score($user_id) {

    if (!local_trustymatchmaker_is_trust_score_enabled()) {
        return; 
    }

    $name = 'Avaliação geral';
    global $OUTPUT, $DB;
    $scores = $DB->get_records_sql(
    'SELECT ts.*, st.*
      FROM {local_trustymatchmaker_trust_score} ts
      JOIN {local_trustymatchmaker_score_types} st
           ON st.id = ts.scoreid
     WHERE ts.userid = :userid',
    [
        'userid' => $user_id, 
    ]
    );
    if ($scores) {
        $overall = 0;
        $count = 0;
        foreach ($scores as $score) {
            if ($score->value >= 1) {
                $overall += $score->value;
                $count += 1;
            }
        }
        $overall /= $count;
        $fill = ($overall / 5) * 100;

        $data = [ 'name' => $name,
        'description' => 'Média dos atributos de confiabilidade avaliados.',
        'score' => number_format($overall, 1),
        'fill' => $fill];
    }
    else {

        $data = [ 'name' => $name,
        'description' => 'Usuário ainda não avaliado.',
        'score' => 'N/A',
        'fill' => 0
        ];
    }
    $overallscore = $OUTPUT->render_from_template('local_trustymatchmaker/overall_score',$data);
    return $overallscore;
}

function local_trustymatchmaker_load_sections_friends($user, $otheruser = false) {
    global $DB, $OUTPUT;
    if (!$otheruser) {
        local_trustymatchmaker_load_my_friends_list($OUTPUT, $DB, $user->id);
    }
}

function local_trustymatchmaker_get_user_friends($db, $user_id) {
    $contacts = $db->get_records_sql(
    'SELECT u.*
     FROM {message_contacts} mc
     JOIN {user} u ON (u.id = mc.contactid OR u.id = mc.userid)
     WHERE :userid1 IN (mc.userid, mc.contactid)
       AND u.id <> :userid2
     ORDER BY u.firstname ASC, u.lastname ASC',
    [
        'userid1' => $user_id, 
        'userid2' => $user_id
    ]
    );

    $friendList = [];

    foreach ($contacts as $c) {
        if ($c->userid == $user_id) {
            $friendid = $c->id;
        } else {
            $friendid = $c->id;
        }
        $friendList[] = $friendid;
    }

    return $friendList;
}

function local_trustymatchmaker_get_user_collaborators($db, $user_id) {
   $collaborators = $db->get_records_sql(
    'SELECT u.*
     FROM {collaborators} col
     JOIN {user} u ON (u.id = col.userid OR u.id = col.collaboratorid)
     WHERE :userid1 IN (col.userid, col.collaboratorid)
       AND u.id <> :userid2
     ORDER BY u.firstname ASC, u.lastname ASC',
    [
        'userid1' => $user_id, 
        'userid2' => $user_id
    ]
    );

    $collaborators_id = [];

    foreach ($collaborators as $c) {
        if ($c->userid == $user_id) {
            $collaborator = $c->id;
        } else {
            $collaborator = $c->id;
        }
        $collaborators_id[] = $collaborator;
    }

    return $collaborators_id;
}

function local_trustymatchmaker_load_sections_collaborators($user) {
    global $DB, $OUTPUT, $PAGE, $USER;

    $collaborators = local_trustymatchmaker_get_user_collaborators($DB, $user->id);

    $collaboratorList = "";

    foreach ($collaborators as $collaboratorid) {
        $collaborator = $DB->get_record('user', ['id' => $collaboratorid]);

        $collaboratorProfile = new moodle_url('/local/trustymatchmaker/user.php', ['id' => $collaboratorid]);
        $collaboratorName = fullname($collaborator);
        $collaboratorProfilePicture = local_trustymatchmaker_load_profile_picture($collaborator, context_system::instance(), $PAGE, 50);
        $can_give = local_trustymatchmaker_can_give_medal($USER->id, $collaboratorid);

        $collaboratorList .= $OUTPUT->render_from_template('local_trustymatchmaker/collaborator', [
            'sent' => local_trustymatchmaker_get_request($USER->id, $collaboratorid)['sent'],
            'received' => local_trustymatchmaker_get_request($USER->id, $collaboratorid)['received'],
            'contact' => local_trustymatchmaker_get_request($USER->id, $collaboratorid)['contact'],
            'profile-link' => $collaboratorProfile,
            'collaborator-name' => $collaboratorName,
            'collaborator-id' => $collaboratorid,
            'profile-picture' => $collaboratorProfilePicture,
            'cangivemedal' => $can_give
        ]);
    }

    if (empty($collaboratorList)) {
        $collaboratorList = $OUTPUT->render_from_template('local_trustymatchmaker/nada', ['texto' => "Usuário não possui colaboradores."]);
    }
    $templatedata = ['section_name' => "Colaboradores",
        'conteudohtml' => $collaboratorList];
    echo $OUTPUT->render_from_template('local_trustymatchmaker/section', $templatedata);  
}

function local_trustymatchmaker_load_user_friends($output, $db, $user_id) {
    global $USER, $PAGE;
    $contacts = local_trustymatchmaker_get_user_friends($db, $user_id);
    $my_friends = local_trustymatchmaker_get_user_friends($db, $USER->id);
    
    $mutualFriendList = array_intersect($contacts, $my_friends);

    //$my_friends[] = $USER->id;
    //$friendList = array_diff($contacts, $my_friends);
    $mutualFriends = "";
    //$otherFriends = "";

    foreach ($mutualFriendList as $friendid) {
        $friend = $db->get_record('user', ['id' => $friendid]);

        $firendProfile = new moodle_url('/local/trustymatchmaker/user.php', ['id' => $friend->id]);
        $friendName = fullname($friend);
        $friendProfilePicture = local_trustymatchmaker_load_profile_picture($friend, context_system::instance(), $PAGE, 50);
        $mutualFriends .= $output->render_from_template('local_trustymatchmaker/mutual_friend', [
            'profile-link' => $firendProfile,
            'friend-name' => $friendName,
            'profile-picture' => $friendProfilePicture
        ]);
        
    }

    if (!empty($mutualFriends)) {
        $templatedata = ['section_name' => "Amigos em comum",
        'conteudohtml' => $mutualFriends];
        echo $output->render_from_template('local_trustymatchmaker/section', $templatedata);
    }

    $mutualFriendList[] = $USER->id;

    foreach ($contacts as $friendid) {
        $friend = $db->get_record('user', ['id' => $friendid]);
        $firendProfile = new moodle_url('/local/trustymatchmaker/user.php', ['id' => $friend->id]);
        $friendName = fullname($friend);
        $friendProfilePicture = local_trustymatchmaker_load_profile_picture($friend, context_system::instance(), $PAGE, 50);

        if (in_array($friendid, $mutualFriendList)) {
            $otherFriends .= $output->render_from_template('local_trustymatchmaker/mutual_friend', [
            'profile-link' => $firendProfile,
            'friend-name' => $friendName,
            'profile-picture' => $friendProfilePicture
        ]);
        }
        else {
            $otherFriends .= $output->render_from_template('local_trustymatchmaker/other_friend', [
            'friend-id' => $friend->id,
            'sent' => local_trustymatchmaker_get_request($USER->id, $friend->id)['sent'],
            'received' => local_trustymatchmaker_get_request($USER->id, $friend->id)['received'],
            'profile-link' => $firendProfile,
            'friend-name' => $friendName,
            'profile-picture' => $friendProfilePicture
        ]);
        }
    }

    if (empty($otherFriends)) {
        $otherFriends = $output->render_from_template('local_trustymatchmaker/nada', ['texto' => "Usuário não adicionou nenhum amigo."]);
    }
    $templatedata = ['section_name' => "Lista de amigos",
        'conteudohtml' => $otherFriends];
    echo $output->render_from_template('local_trustymatchmaker/section', $templatedata);
}

function local_trustymatchmaker_load_my_friends_list($output, $db, $user_id) {
    global $PAGE;
    $contacts = local_trustymatchmaker_get_user_friends($db, $user_id);

    $friendList = "";

    foreach ($contacts as $friendid) {
        $friend = $db->get_record('user', ['id' => $friendid]);
        $firendProfile = new moodle_url('/local/trustymatchmaker/user.php', ['id' => $friend->id]);
        $friendName = fullname($friend);
        $friendProfilePicture = local_trustymatchmaker_load_profile_picture($friend, context_system::instance(), $PAGE, 50);
        $friendList .= $output->render_from_template('local_trustymatchmaker/friend', [
            'friend-id' => $friend->id,
            'profile-link' => $firendProfile,
            'friend-name' => $friendName,
            'profile-picture' => $friendProfilePicture
        ]);
        
    }

    if (empty($friendList)) {
        $friendList = $output->render_from_template('local_trustymatchmaker/nada', ['texto' => "Usuário não adicionou nenhum amigo."]);
    }

    $templatedata = ['section_name' => "Lista de amigos",
        'conteudohtml' => $friendList];
    echo $output->render_from_template('local_trustymatchmaker/section', $templatedata);
}

function local_trustymatchmaker_load_static_medals_grid($user) {
    global $OUTPUT, $CFG, $DB, $PAGE; 

    // 1. Buscar as definições de medalhas DO BANCO DE DADOS
    $medals_defs = $DB->get_records('local_trustymatchmaker_medals');

    $grid_html = "";
    $popups_html = ""; 
    $has_any_medal = false;

    foreach ($medals_defs as $medal) {
        
        // 2. Buscar quem deu esta medalha para o usuário atual
        $sql = "SELECT i.id AS uniqueid, u.*
                  FROM {local_trustymatchmaker_issued} i
                  JOIN {user} u ON u.id = i.issuerid
                 WHERE i.userid = :userid 
                   AND i.medalid = :medalid";
        
        $params = ['userid' => $user->id, 'medalid' => $medal->id];
        $givers = $DB->get_records_sql($sql, $params);

        // Se ninguém deu essa medalha, pula para a próxima
        if (empty($givers)) {
            continue; 
        }
        
        $has_any_medal = true;

        $issuers_list_data = [];
        foreach ($givers as $issuer) {
            $profile_url = new moodle_url('/local/trustymatchmaker/user.php', ['id' => $issuer->id]);

            $issuers_list_data[] = [
                'fullname' => fullname($issuer),
                'profile_pic' => local_trustymatchmaker_load_profile_picture($issuer, context_system::instance(), $PAGE, 30),
                'profile_url' => $profile_url->out()
            ];
        }

        $givers_count = count($issuers_list_data);
        
        $icon_url = $CFG->wwwroot . '/local/trustymatchmaker/assets/img/' . $medal->icon;

        $icon_data = [
            'name' => $medal->name,
            'icon_url' => $icon_url,
            'count' => $givers_count,
            'has_count' => $givers_count > 1, 
            'modal_id' => 'modal_medal_' . $medal->id
        ];
        $grid_html .= $OUTPUT->render_from_template('local_trustymatchmaker/medal_icon_static', $icon_data);

        $popup_data = [
            'modal_id' => 'modal_medal_' . $medal->id,
            'name' => $medal->name,
            'icon' => $icon_url,
            'description' => $medal->description,
            'issuers' => $issuers_list_data
        ];
        $popups_html .= $OUTPUT->render_from_template('local_trustymatchmaker/medal_popup', $popup_data);
    }

    // 3. Renderização Final
    $templatedata = [
        'section_name' => "Suas medalhas",
        'section_id' => "medals-section",
        'conteudohtml' => ""
    ];

    if ($has_any_medal) {
        $templatedata['conteudohtml'] = "<div class='medal-grid'>".$grid_html."</div>";
    } else {
        $templatedata['conteudohtml'] = $OUTPUT->render_from_template('local_trustymatchmaker/nada', ['texto' => "Nenhuma medalha recebida ainda."]);
    }

    echo $OUTPUT->render_from_template('local_trustymatchmaker/section', $templatedata);
    echo $popups_html;
}

function local_trustymatchmaker_get_visibility($userid) {
    global $DB;
    $visibility = $DB->get_field('collaboratorvisibility', 'visibility', ['userid' => $userid]);

    if ($visibility != '1') {
        return false;
    }
    else {
        return true;
    };
}

function local_trustymatchmaker_set_visibility($userid, $visible) {
    global $DB;
    $record = $DB->get_record('collaboratorvisibility', ['userid' => $userid]);
    if ($record) {
        $record->visibility = $visible;
        $DB->update_record('collaboratorvisibility', $record);
    }
    else {
        $new_record = new stdClass();
        $new_record->userid = $userid;
        $new_record->visibility = $visible;
        $DB->insert_record('collaboratorvisibility', $new_record);
    }
}

function local_trustymatchmaker_remove_friend($userid, $friendtoremove) {
    api::remove_contact($userid, $friendtoremove);
}

function local_trustymatchmaker_add_friend($userid, $friendtoadd) {
    api::create_contact_request($userid, $friendtoadd);
}

function local_trustymatchmaker_get_request($userid, $friendid) {

    if (api::is_contact($userid, $friendid)) {
        return [
            'contact' => true,
            'sent' => false,
            'received' => false
        ];
    }

    $requests = api::get_contact_requests_between_users($userid, $friendid);

    if (empty($requests)) {
        return [
            'contact' => false,
            'sent' => false,
            'received' => false
        ];
    }
    
    $key = array_key_first($requests);
    $request = $requests[$key];

    if ($request->userid == $userid) {
        return [
            'contact' => false,
            'sent' => true,
            'received' => false
        ];
    }

    return [
        'contact' => false,
        'sent' => false,
        'received' => true
    ];
}

// Verifica se dois usuários colaboraram entre si.
function local_trustymatchmaker_have_collaborated($userid1, $userid2) {
    global $DB;
    
    $sql = "SELECT id FROM {collaborators} 
             WHERE (userid = :userid1 AND collaboratorid = :userid2) 
                OR (userid = :userid3 AND collaboratorid = :userid4)";
                
    $params = [
        'userid1' => $userid1,
        'userid2' => $userid2,
        'userid3' => $userid2,
        'userid4' => $userid1
    ];
    
    return $DB->record_exists_sql($sql, $params);
}

/*
 * Verifica se o usuário pode dar uma medalha para o colaborador.
 * Regra: Devem ter colaborado e o limite é de 2 medalhas por colaborador.
 */
function local_trustymatchmaker_can_give_medal($giverid, $receiverid) {
    global $DB;

    if (!local_trustymatchmaker_have_collaborated($giverid, $receiverid)) {
        return false;
    }

    $medal_count = $DB->count_records('local_trustymatchmaker_issued', [
        'issuerid' => $giverid,
        'userid' => $receiverid
    ]);

    // Retorna true se deu menos de 2 medalhas
    return ($medal_count < 2);
}

// Retorna quantas medalhas o usuário ainda pode dar para este colaborador específico.
function local_trustymatchmaker_get_remaining_medals($giverid, $receiverid) {
    global $DB;
    
    $medal_count = $DB->count_records('local_trustymatchmaker_issued', [
        'issuerid' => $giverid,
        'userid' => $receiverid
    ]);
    
    $remaining = 2 - $medal_count;
    
    return ($remaining > 0) ? $remaining : 0;
}

// Renderiza o modal de seleção de medalhas
function local_trustymatchmaker_load_medal_selection_modal() {
    global $DB, $OUTPUT, $PAGE, $CFG;

    $medals = $DB->get_records('local_trustymatchmaker_medals', null, 'id ASC');
    
    $catalog = [];
    foreach ($medals as $medal) {
        $catalog[] = [
            'id' => $medal->id,
            'name' => $medal->name,
            'description' => strip_tags($medal->description), 
            'icon_url' => $CFG->wwwroot . '/local/trustymatchmaker/assets/img/' . $medal->icon
        ];
    }

    $templateContext = [
        'medals_catalog' => $catalog
    ];

    echo $OUTPUT->render_from_template('local_trustymatchmaker/medal_select_popup', $templateContext);
}