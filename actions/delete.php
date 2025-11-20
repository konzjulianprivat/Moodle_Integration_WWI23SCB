<?php
// Datei: mod/vmrequest/delete.php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');


$cmid      = required_param('id', PARAM_INT);
$requestid = required_param('requestid', PARAM_INT);

// Korrektes Entpacken: erst $course, dann $cm
list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'vmrequest');
$context = context_module::instance($cm->id);

// require_login im richtigen Aufruf: (Course, autologinguest, CM)
require_login($course, false, $cm);

// Capability prüfen
require_capability('mod/vmrequest:approverequest', $context);

global $DB, $USER;

// Lokale Moodle-DB (deaktiviert)
//$req = $DB->get_record('vmrequest_request', ['id' => $requestid], '*', MUST_EXIST);
//$DB->delete_records('vmrequest_request', ['id' => $requestid]);

// Neue externe API-Logik

$req = vm_external_api_get_record_by_id('mi_vmrequest_request', (int)$requestid);

if (!$req) {
    throw new moodle_exception('invalidrecord', 'error', '', 'vmrequest_request');
}

// Externer API-Löschvorgang
vm_external_api_delete_record('mi_vmrequest_request', (int)$requestid);


// --- Nachricht an den Antragsteller senden ---
$userfrom = core_user::get_noreply_user();
$userto   = core_user::get_user($req->userid);
$subject  = get_string('deletedsubject', 'vmrequest');
$body     = get_string('deletedmessage', 'vmrequest', (object)[
    'vmname' => format_string($req->vmname),
    'status' => format_string($req->status),
    'by'     => fullname($USER)
]);

$message = new \core\message\message();
$message->component         = 'mod_vmrequest';
$message->name              = 'request_deleted';
$message->userfrom          = $userfrom;
$message->userto            = $userto;
$message->subject           = $subject;
$message->fullmessage       = $body;
$message->fullmessageformat = FORMAT_PLAIN;
$message->notification      = 1;
// Kontext-URL zurück zur Detailseite (die jetzt nicht mehr existiert, wir leiten später in manage)
$message->contexturl        = new moodle_url('/mod/vmrequest/view.php', [
    'id'        => $cmid,
    'action'    => 'manage',
    'archiviert'=> 0
]);
$message->contexturlname    = get_string('pluginname', 'vmrequest');

session_write_close();

message_send($message);

// Zurück zur Verwaltungsübersicht mit Erfolgsmeldung
redirect(
    new moodle_url('/mod/vmrequest/view.php', [
        'id'        => $cmid,
        'action'    => 'manage',
        'archiviert'=> 0
    ]),
    get_string('deletedsuccess', 'vmrequest'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
