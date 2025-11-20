<?php
// Datei: mod/vmrequest/approve.php

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/vmrequest/lib.php');

// CM- und Request-Parameter
$cmid      = required_param('id', PARAM_INT);
$requestid = required_param('requestid', PARAM_INT);

// Kurs- und Kontextdaten laden
list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'vmrequest');
require_login($course, false, $cm);
$context = context_module::instance($cm->id);

// Zugriffsprüfung
require_capability('mod/vmrequest:approverequest', $context);

// Externen Antrag laden
$record = vm_external_api_get_record_by_id('mi_vmrequest_request', $requestid);

if (!$record) {
    throw new moodle_exception('invalidrecord', 'error', '', 'vmrequest_request');
}

// Logik zur Genehmigung je nach VM-Größe
if ($record->vmsize === 'large') {
    // Große VM → Admin-Genehmigung notwendig
    $record->status = 'waitingadminapproval';

    // Update via externe API
    vm_external_api_update_record('mi_vmrequest_request', (int)$record->id, [
        'status' => $record->status
    ]);
} else {
    // Normale VM → direkte Genehmigung
    $record->status = 'genehmigt';

    // Update via externe API
    vm_external_api_update_record('mi_vmrequest_request', (int)$record->id, [
        'status' => $record->status
    ]);

    // Camunda-Prozess anstoßen
    vmrequest_trigger_camunda_creation($record);
}

// Weiterleitung zur Verwaltungsansicht (Archiv-Tab)
redirect(
    new moodle_url('/mod/vmrequest/view.php', [
        'id'         => $cmid,
        'action'     => 'manage',
        'archiviert' => 1
    ]),
    get_string('requestapproved', 'vmrequest'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
