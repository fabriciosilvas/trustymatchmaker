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
 * Main file to view greetings
 *
 * @package     local_trustymatchmaker
 * @copyright   2023 Your name <your@email>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 // 1. adicionar os elementos esseciais do layout da página do Moodle.

/*
A função require_once carrega e executa o conteúdo de outro arquivo PHP — neste caso, config.php, que está localizado dois diretórios acima do arquivo atual

*/
require_once('../../config.php');

require_once($CFG->dirroot. '/local/trustymatchmaker/lib.php');


 if (isloggedin()) {
    $usergreeting = local_trustymatchmaker_get_greeting($USER);
}
else {
    redirect(new moodle_url('/login/index.php'));
}

$userid = optional_param('id', $USER->id, PARAM_INT); // User id.

if (!($DB->record_exists('user', ['id' => $userid]))) {
    redirect(new moodle_url('/local/trustymatchmaker/index.php'));
}

$user = $DB->get_record('user', ['id' => $userid]);

$context = context_system::instance();
$PAGE->set_context($context);


$PAGE->set_url(new moodle_url('/local/trustymatchmaker/collaborators.php'));

$PAGE->set_pagelayout('standard');

$PAGE->set_title(get_string('pluginname', 'local_trustymatchmaker'));

$PAGE->set_heading(get_string('pluginname', 'local_trustymatchmaker'));

echo $OUTPUT->header();



$paginaAtual = [
    'perfil' => true
];

$templateContext = [
    'linkInfo' => '/local/trustymatchmaker/user.php?id='.$user->id,
    'linkMedals' => '/local/trustymatchmaker/medals.php?id='.$user->id,
    'linkFriends' => '/local/trustymatchmaker/friends.php?id='.$user->id,
    'linkColaboratores' => '/local/trustymatchmaker/collaborators.php?id='.$user->id,
    'colaborators' => true
];

$imagem = local_trustymatchmaker_load_profile_picture($user, $context, $PAGE);

echo $OUTPUT->render_from_template('local_trustymatchmaker/sec_nav', $paginaAtual);

$visiblevalor = local_trustymatchmaker_get_visibility($USER->id);

if ($userid == $USER->id) {
    echo $OUTPUT->render_from_template('local_trustymatchmaker/header_pfl_col', [
    'imagem_perfil' => $imagem,
    'username' => fullname($user),
    'visible' => $visiblevalor
]);
} else {
    echo $OUTPUT->render_from_template('local_trustymatchmaker/header_pfl', [
    'imagem_perfil' => $imagem,
    'username' => fullname($user),
]);
}

local_trustymatchmaker_load_navbar_pfl($templateContext, true);

local_trustymatchmaker_load_sections_collaborators($user);

$PAGE->requires->js_call_amd('local_trustymatchmaker/visibility', 'init');

echo $OUTPUT->footer();