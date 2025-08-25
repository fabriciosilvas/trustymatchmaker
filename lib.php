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

function local_trustymatchmaker_load_navbar_pfl() {
    global $OUTPUT;
    echo $OUTPUT->render_from_template('local_trustymatchmaker/pfl_nav', []);
}

function local_trustymatchmaker_load_sections_pfl() {
    global $DB, $USER, $OUTPUT;
    local_trustymatchmaker_load_description($OUTPUT, $DB, $USER->id);
    local_trustymatchmaker_load_user_info($OUTPUT, $DB, $USER->id);
    local_trustymatchmaker_load_interests($OUTPUT, $DB, $USER->id);
    local_trustymatchmaker_load_trust_score($OUTPUT, $DB, $USER->id);
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

