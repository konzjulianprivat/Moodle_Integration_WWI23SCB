<?php
// Datei: mod/vmrequest/deny.php

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/../lib.php');


$cmid      = required_param('id', PARAM_INT);
$requestid = required_param('requestid', PARAM_INT);

// Course + CM laden
list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'vmrequest');
require_login($course, false, $cm);
$context = context_module::instance($cm->id);

// Capability prüfen
require_capability('mod/vmrequest:approverequest', $context);

global $DB;

// Formular für Ablehnungsbegründung
class vmrequest_deny_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('textarea', 'denialreason',
            get_string('denialreason', 'vmrequest'),
            ['wrap'=>'virtual','rows'=>6,'cols'=>50]
        );
        $mform->setType('denialreason', PARAM_TEXT);
        $mform->addRule('denialreason', null, 'required', null, 'client');

        // Versteckte Felder
        $mform->addElement('hidden', 'id',       null);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'requestid', null);
        $mform->setType('requestid', PARAM_INT);

        $this->add_action_buttons(true,
            get_string('denyrequest', 'vmrequest')
        );
    }
}

// Form initialisieren
$mform = new vmrequest_deny_form();
$mform->set_data([
    'id'        => $cmid,
    'requestid' => $requestid
]);

// Abbruch
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/vmrequest/view.php', [
        'id'     => $cmid,
        'action' => 'manage'
    ]));
}

// Verarbeiten
if ($data = $mform->get_data()) {

    // Lokale Moodle-DB (deaktiviert)
    //$record = $DB->get_record('vmrequest_request', ['id' => $data->requestid], '*', MUST_EXIST);

    // Neue externe API-Logik
    $record = vm_external_api_get_record_by_id('mi_vmrequest_request', (int)$data->requestid);

    if (!$record) {
        throw new moodle_exception('invalidrecord', 'error', '', 'vmrequest_request');
    }

    // Status & Begründung setzen
    $record->status       = 'abgelehnt';
    $record->denialreason = $data->denialreason;

    // Lokale Moodle-DB (deaktiviert)
    // $DB->update_record('vmrequest_request', $record);

    // Neue externe API-Update-Logik
    if (isset($record->id) && is_numeric($record->id)) {
        $id = is_object($record->id) ? (int)$record->id->id : (int)$record->id;
        vm_external_api_update_record('mi_vmrequest_request', $id, [
            'status'       => $record->status,
            'denialreason' => $record->denialreason,
        ]);
    } else {
        debugging('Fehler: Kein gültiger Datensatz (id fehlt oder ist ungültig) beim Update via API.', DEBUG_DEVELOPER);
    }


    // Redirect in Manage-Ansicht, Archiv-Tab
    redirect(
        new moodle_url('/mod/vmrequest/view.php', [
            'id'         => $cmid,
            'action'     => 'manage',
            'archiviert' => 1
        ]),
        get_string('requestdenied', 'vmrequest'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Anzeige des Formulars
$PAGE->set_context($context);
$PAGE->set_url('/mod/vmrequest/deny.php', [
    'id'        => $cmid,
    'requestid' => $requestid
]);
$PAGE->set_title(get_string('denyrequest', 'vmrequest'));
$PAGE->set_heading(get_string('denyrequest', 'vmrequest'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('denyrequest', 'vmrequest'));
$mform->display();
echo $OUTPUT->footer();
