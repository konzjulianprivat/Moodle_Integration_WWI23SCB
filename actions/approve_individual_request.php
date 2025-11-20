<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/vmrequest/lib.php');
require_once(__DIR__ . '/../lib.php');

global $USER, $OUTPUT;

$requestid = required_param('id', PARAM_INT);
$action    = required_param('action', PARAM_ALPHA); // 'approve' oder 'deny'

$context = context_system::instance();
require_login();
require_capability('mod/vmrequest:approvecourserequest', $context);

// Antrag laden via API
$record = vm_external_api_get_record_by_id('mi_vmrequest_request', $requestid);

if (!$record) {
    throw new moodle_exception('invalidrecord', 'error', '', 'vmrequest_request');
}

// Status prüfen
if ($record->status !== 'waitingadminapproval') {
    print_error('Dieser Antrag benötigt keine Admin-Genehmigung.');
}

// Aktion verarbeiten
if ($action === 'approve') {
    $updateddata = ['status' => 'genehmigt'];

    $success = vm_external_api_update_record('mi_vmrequest_request', $requestid, $updateddata);
    if (!$success) {
        throw new moodle_exception('Update fehlgeschlagen', 'mod_vmrequest');
    }

    vmrequest_trigger_camunda_creation($record);

    redirect(
        new moodle_url('/mod/vmrequest/actions/approve_course_request.php', ['tab' => 'individual']),
        'Einzelantrag wurde genehmigt. Und die virtuelle Maschine wird erstellt',
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );

} elseif ($action === 'deny') {
    $updateddata = ['status' => 'abgelehnt'];

    $success = vm_external_api_update_record('mi_vmrequest_request', $requestid, $updateddata);
    if (!$success) {
        throw new moodle_exception('Update fehlgeschlagen', 'mod_vmrequest');
    }

    redirect(
        new moodle_url('/mod/vmrequest/actions/approve_course_request.php', ['tab' => 'individual']),
        'Einzelantrag wurde abgelehnt.',
        null,
        \core\output\notification::NOTIFY_INFO
    );

} else {
    print_error('Ungültige Aktion.');
}
