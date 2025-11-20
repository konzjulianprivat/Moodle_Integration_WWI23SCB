<?php
// Datei: mod/vmrequest/edit_request.php

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/vmrequest/forms/vm_form.php');
require_once($CFG->dirroot . '/mod/vmrequest/lib.php');

$id        = required_param('id', PARAM_INT);        // coursemodule ID
$requestid = required_param('requestid', PARAM_INT); // zu bearbeitender Antrag

list($course, $cm) = get_course_and_cm_from_cmid($id, 'vmrequest');
require_login($course, false, $cm);
$context = context_module::instance($cm->id);

// Rechte prüfen
require_capability('mod/vmrequest:submitrequest', $context);

global $DB, $PAGE, $OUTPUT;

$PAGE->set_url('/mod/vmrequest/edit_request.php', ['id' => $id, 'requestid' => $requestid]);
$PAGE->set_context($context);
$PAGE->set_title('VM-Antrag bearbeiten');

// Datensatz via API laden
$request = vm_external_api_get_record_by_id('mi_vmrequest_request', $requestid);

if (!$request) {
    throw new moodle_exception('Datensatz nicht gefunden', 'mod_vmrequest');
}

// Nur wenn nicht genehmigt oder auf Adminfreigabe wartend
if (in_array($request->status, ['genehmigt', 'waitingadminapproval'])) {
    throw new moodle_exception('editingnotallowed', 'mod_vmrequest');
}

// Modulkonfiguration abrufen
$instance = $DB->get_record('vmrequest', ['id' => $cm->instance], '*', MUST_EXIST);
$supervision = $instance->supervision ?? 0;

// Formular-Setup mit zusätzlichem Flag „editing“
$customdata = [
    'courseid'    => $course->id,
    'cmid'        => $cm->id,
    'supervision' => $supervision,
    'editing'     => true // WICHTIG: Steuert den Button-Text
];

$formurl = new moodle_url('/mod/vmrequest/edit_request.php', ['id' => $id, 'requestid' => $requestid]);
$mform = new vmrequest_form($formurl, $customdata);

// Wenn Abbrechen gedrückt
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/vmrequest/view.php', ['id' => $cm->id]));
}

// Wenn gespeichert
if ($data = $mform->get_data()) {
    // Update-Daten vorbereiten
    $updatedfields = [
        'vmname'        => $data->vmname,
        'vmsize'        => $data->vmsize,
        'vmos'          => $data->vmos,
        'justification' => $data->justification,
        'timemodified'  => time(),
    ];

    if ($supervision == 1 && isset($data->supervisorid)) {
        $updatedfields['supervisorid'] = $data->supervisorid;
    }

    $updatedfields['status'] = 'beantragt'; // oder anpassen

    // Update via externe API (ID als int, Felder als Array)
    $success = vm_external_api_update_record('mi_vmrequest_request', $requestid, $updatedfields);
    if (!$success) {
        throw new moodle_exception('Aktualisierung fehlgeschlagen', 'mod_vmrequest');
    }

    redirect(new moodle_url('/mod/vmrequest/view.php', ['id' => $data->coursemodule, 'action' => 'apply']),
        'Antrag wurde aktualisiert.', 2);
}

// Initialdaten setzen (inkl. coursemodule für das hidden-Feld)
$mform->set_data([
    'vmname'        => $request->vmname,
    'vmsize'        => $request->vmsize,
    'vmos'          => $request->vmos,
    'justification' => $request->justification,
    'supervisorid'  => $request->supervisorid ?? null,
    'coursemodule'  => $cm->id,
]);

echo $OUTPUT->header();
echo $OUTPUT->heading('VM-Antrag bearbeiten');
$mform->display();
echo $OUTPUT->footer();
