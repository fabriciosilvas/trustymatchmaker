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

// 2. Definir o contexto da página, que é o local onde a página será exibida.
/*
A função context_system::instance() cria uma instância do contexto do sistema, que é o contexto global do Moodle. Isso é necessário para definir onde a página será exibida e quais permissões serão verificadas.

No Moodle, um contexto representa um nível hierárquico de acesso e permissões. Os principais tipos de contexto incluem:

context_system: o nível mais alto (o sistema como um todo)

context_course: um curso específico

context_module: uma atividade dentro de um curso

context_user: um usuário específico

Esses contextos são usados para verificar capacidades e permissões (como "pode editar cursos", "pode ver notas", etc.).
 */

 if (isloggedin()) {
    $usergreeting = local_trustymatchmaker_get_greeting($USER);
}
else {
    redirect(new moodle_url('/login/index.php'));
}

$context = context_system::instance();
$PAGE->set_context($context);

// 3. Setar o url

$PAGE->set_url(new moodle_url('/local/trustymatchmaker/profile.php'));

//  4. setar o layout da página
$PAGE->set_pagelayout('standard');

// 4.1 Definir o título da página
$PAGE->set_title(get_string('pluginname', 'local_trustymatchmaker'));

// 4.2 Definir o título da página
$PAGE->set_heading(get_string('pluginname', 'local_trustymatchmaker'));

// 5. definir o cabeçalho da página
echo $OUTPUT->header();


//$templatedata = ['usergreeting' => $usergreeting];
//echo $OUTPUT->render_from_template('local_trustymatchmaker/greeting_message', $templatedata);

$paginaAtual = [
    'perfil' => true
];

$userpic = core_user::get_profile_picture($USER, $context, ['size' => 100]);
$url = $userpic->get_url($PAGE);
$hasPicture = $DB->get_field('user', 'picture', ['id' => $USER->id]);

if (!$hasPicture) {
    $url = core_user::get_initials($USER);
    $imagem = $OUTPUT->render_from_template('local_trustymatchmaker/inicial_pfl', ['user-initials' => $url]);
} else {
    $imagem = $OUTPUT->render_from_template('local_trustymatchmaker/imagem_pfl', ['link' => $url]);
}

echo $OUTPUT->render_from_template('local_trustymatchmaker/sec_nav', $paginaAtual);



echo $OUTPUT->render_from_template('local_trustymatchmaker/header_pfl', [
    'imagem_perfil' => $imagem,
    'username' => fullname($USER)
]);

//echo $OUTPUT->render_from_template('local_trustymatchmaker/pfl_nav', []);

local_trustymatchmaker_load_navbar_pfl();
local_trustymatchmaker_load_sections_pfl();

// 6. definir o rodapé da página
echo $OUTPUT->footer();