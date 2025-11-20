<?php
// Datei: mod/vmrequest/db/upgrade.php

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade script for mod_vmrequest.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_vmrequest_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // === Neue Tabelle vmrequest_instance hinzufügen ===
    if ($oldversion < 2025073100) {
        // Tabelle definieren.
        $table = new xmldb_table('vmrequest_course_request');

        // Feld definieren (mit NOTNULL = false).
        $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Falls das Feld existiert, ändere NOT NULL → NULL erlaubt.
        if ($DB->get_manager()->field_exists($table, $field)) {
            $DB->get_manager()->change_field_notnull($table, $field);
        }

        // Savepoint für das Upgrade setzen.
        upgrade_mod_savepoint(true, 2025073100, 'vmrequest');
    }


    return true;
}

