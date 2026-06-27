<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_trustymatchmaker_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025062301) {

        // collaboratorvisibility
        if (!$dbman->table_exists('collaboratorvisibility')) {
            $table = new xmldb_table('collaboratorvisibility');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('visibility', XMLDB_TYPE_CHAR, '1', null, XMLDB_NOTNULL);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('trustymatchmaker_userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $dbman->create_table($table);
        }

        // local_trustymatchmaker_medals
        if (!$dbman->table_exists('local_trustymatchmaker_medals')) {
            $table = new xmldb_table('local_trustymatchmaker_medals');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null);
            $table->add_field('icon', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        // local_trustymatchmaker_issued
        if (!$dbman->table_exists('local_trustymatchmaker_issued')) {
            $table = new xmldb_table('local_trustymatchmaker_issued');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('medalid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('issuerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('medalid', XMLDB_KEY_FOREIGN, ['medalid'], 'local_trustymatchmaker_medals', ['id']);
            $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $table->add_key('issuerid', XMLDB_KEY_FOREIGN, ['issuerid'], 'user', ['id']);
            $dbman->create_table($table);
        }

        // local_trustymatchmaker_score_types
        if (!$dbman->table_exists('local_trustymatchmaker_score_types')) {
            $table = new xmldb_table('local_trustymatchmaker_score_types');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        // local_trustymatchmaker_evals
        if (!$dbman->table_exists('local_trustymatchmaker_evals')) {
            $table = new xmldb_table('local_trustymatchmaker_evals');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('evaluatorid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('target_user', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $table->add_key('evaluator_user', XMLDB_KEY_FOREIGN, ['evaluatorid'], 'user', ['id']);
            $dbman->create_table($table);
        }

        // local_trustymatchmaker_ratings
        if (!$dbman->table_exists('local_trustymatchmaker_ratings')) {
            $table = new xmldb_table('local_trustymatchmaker_ratings');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('evalid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('scoreid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('value', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('evaluation_link', XMLDB_KEY_FOREIGN, ['evalid'], 'local_trustymatchmaker_evals', ['id']);
            $table->add_key('score_type_link', XMLDB_KEY_FOREIGN, ['scoreid'], 'local_trustymatchmaker_score_types', ['id']);
            $dbman->create_table($table);
        }

        // local_trustymatchmaker_trust_score
        if (!$dbman->table_exists('local_trustymatchmaker_trust_score')) {
            $table = new xmldb_table('local_trustymatchmaker_trust_score');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('scoreid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('value', XMLDB_TYPE_NUMBER, '10', null, null, null, null, null, '1');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('unique_score', XMLDB_KEY_UNIQUE, ['userid', 'scoreid']);
            $table->add_key('score_type_link', XMLDB_KEY_FOREIGN, ['scoreid'], 'local_trustymatchmaker_score_types', ['id']);
            $table->add_key('trustymatchmaker_userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025062301, 'local', 'trustymatchmaker');
    }

    return true;
}
