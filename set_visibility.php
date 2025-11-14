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

$visible = optional_param('visible', 0, PARAM_INT);
$userid = $USER->id;

require_login();

$context = context_system::instance();
$PAGE->set_context($context);


local_trustymatchmaker_set_visibility($userid, $visible);

redirect(new moodle_url('/local/trustymatchmaker/collaborators.php'));