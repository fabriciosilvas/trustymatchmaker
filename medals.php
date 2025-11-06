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
 * Página para exibir as medalhas do usuário
 *
 * @package     local_trustymatchmaker
 * @copyright   2023 Your name <your@email>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot. '/local/trustymatchmaker/lib.php');

// 1. Verificações de login e usuário
if (!isloggedin()) {
    redirect(new moodle_url('/login/index.php'));
}

$userid = optional_param('id', $USER->id, PARAM_INT); 

if (!($DB->record_exists('user', ['id' => $userid]))) {
    redirect(new moodle_url('/local/trustymatchmaker/index.php'));
}

$user = $DB->get_record('user', ['id' => $userid]);
$context = context_system::instance();

// 2. Preparar dados para os templates de navegação
$paginaAtual = ['perfil' => true];

$infoLink = new moodle_url('/local/trustymatchmaker/profile.php');
if ($userid != $USER->id) {
    $infoLink = new moodle_url('/local/trustymatchmaker/user.php', ['id' => $userid]);
}

$templateContext = [
    'linkInfo' => $infoLink,
    'linkMedals' => new moodle_url('/local/trustymatchmaker/medals.php', ['id' => $userid]),
    'linkFriends' => new moodle_url('/local/trustymatchmaker/friends.php', ['id' => $userid]),
    'linkColaboratores' => '#',
    'medals' => true 
];
$url = new moodle_url('/local/trustymatchmaker/medals.php', ['id' => $userid]);
$PAGE->set_url($url);

// 3. Renderizar o cabeçalho da página
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_trustymatchmaker'));
$PAGE->set_heading(get_string('pluginname', 'local_trustymatchmaker'));

echo $OUTPUT->header();

$imagem = local_trustymatchmaker_load_profile_picture($user, $context, $PAGE);

echo $OUTPUT->render_from_template('local_trustymatchmaker/sec_nav', $paginaAtual);

echo $OUTPUT->render_from_template('local_trustymatchmaker/header_pfl', [
    'imagem_perfil' => $imagem,
    'username' => fullname($user)
]);

local_trustymatchmaker_load_navbar_pfl($templateContext);

// 4. LÓGICA ESPECÍFICA DESTA PÁGINA
local_trustymatchmaker_load_static_medals_grid($user); // Nova função mais simples

$PAGE->requires->js_call_amd('local_trustymatchmaker/medals', 'init');

// 5. Rodapé
echo $OUTPUT->footer();