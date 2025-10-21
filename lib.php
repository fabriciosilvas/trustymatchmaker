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
        $url = core_user::get_initials($user);
        if ($size == 50) {
            return $OUTPUT->render_from_template('local_trustymatchmaker/inicial_pfl', ['user-initials' => $url, 's50' => true]);
        }

        return $OUTPUT->render_from_template('local_trustymatchmaker/inicial_pfl', ['user-initials' => $url, 's100' => true]);


    } else {
        return $OUTPUT->render_from_template('local_trustymatchmaker/imagem_pfl', ['link' => $url, 'size' => $size]);
    }
}

function local_trustymatchmaker_load_navbar_pfl($pagina) {
    global $OUTPUT;
    echo $OUTPUT->render_from_template('local_trustymatchmaker/pfl_nav', $pagina);
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

    $interests = $db->get_fieldset('tag','name', ['userid' => $user_id]);
    
    $nada = "";

    if (count($interests) > 0) {
        foreach ($interests as $interest) {
            $nada .= $output->render_from_template('local_trustymatchmaker/pfl_interest', ['interesse' => $interest]);
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

function local_trustymatchmaker_load_trust_score($output, $db, $user_id) {
    $paragrafo = $output->render_from_template('local_trustymatchmaker/nada', ['texto' => "Usuário ainda não avaliado."]);

    $templatedata = ['section_name' => "Índice de confiança",
    'conteudohtml' => $paragrafo];
    echo $output->render_from_template('local_trustymatchmaker/section', $templatedata);
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

