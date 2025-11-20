<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/vmrequest/lib.php');
require_once(__DIR__ . '/../lib.php');



global $DB, $USER, $PAGE, $OUTPUT;

// Optionaler Parameter: Course ID zur Rückkehr oder Kontextprüfung
$id = optional_param('id', 0, PARAM_INT); // falls du später zu einem Kurs zurück willst

$context = context_system::instance(); // Kontext auf Systemebene für Super-Admins
require_login();
require_capability('mod/vmrequest:approvecourserequest', $context);

// Moodle-Seite initialisieren
$PAGE->set_context($context);
$PAGE->set_url('/mod/vmrequest/actions/approve_course_request.php'); // Ohne id, da systemweit
$PAGE->set_title('Kursweite VM-Anträge genehmigen');
$PAGE->set_heading('Kursweite VM-Anträge - Genehmigung');

echo $OUTPUT->header();

$approveid = optional_param('approveid', 0, PARAM_INT);

if ($approveid) {
    // Lokale DB – deaktiviert
    //$DB->set_field('vmrequest_course_request', 'status', 'genehmigt', ['id' => $approveid]);
    //$individuals = $DB->get_records('vmrequest_request', ['course_request_id' => $approveid]);

    // Externe API: Kursantrag aktualisieren
    vm_external_api_update_record('mi_vmrequest_course_request', $approveid, ['status' => 'genehmigt']);

    // Externe API: Einzelanträge abrufen
    $allrecords = vm_external_api_get_records('mi_vmrequest_request');

    $individuals = array_filter($allrecords, function($record) use ($approveid) {
        return isset($record->course_request_id) && $record->course_request_id == $approveid;
    });

    if (empty($individuals)) {
        debugging("Keine Einzelanträge gefunden für course_request_id = $approveid", DEBUG_DEVELOPER);
    } else {
        debugging(count($individuals) . " Einzelanträge gefunden für course_request_id = $approveid", DEBUG_DEVELOPER);
    }

    
    foreach ($individuals as $indiv) {
        $indiv->status = 'genehmigt';
        // Lokale Moodle-DB (deaktiviert)
        //$DB->update_record('vmrequest_request', $record);

        // Neue externe API-Update-Logik 
        vm_external_api_update_record('mi_vmrequest_request', $indiv->id, ['status' => 'genehmigt']);

        // Camunda-Prozess starten
        vmrequest_trigger_camunda_creation($indiv);
    }

    redirect(
        new moodle_url('/mod/vmrequest/actions/approve_course_request.php', ['id' => $id]),
        'Antrag wurde genehmigt.',
        2
    );
}



// Anträge laden
// Lokale DB-Logik 
// $requests = $DB->get_records('vmrequest_course_request');

// Externe DB 
$requests = vm_external_api_get_records('mi_vmrequest_course_request');


$todo = [];
$approved = [];

foreach ($requests as $r) {
    if ($r->status === 'beantragt') {
        $todo[] = $r;
    } else {
        $approved[] = $r;
    }
}

function render_course_requests_table($requests) {
    global $DB;

    if (empty($requests)) {
        return html_writer::div('Keine Anträge.', 'alert alert-info');
    }

    $table = new html_table();
    $table->head = ['ID', 'Name', 'Größe', 'OS', 'Begründung', 'Anzahl Studierende', 'Beantragt von', 'Status', 'Erstellt am', 'Aktion'];

    foreach ($requests as $r) {
        $user = $DB->get_record('user', ['id' => $r->userid], 'firstname, lastname, email');
        $submitter = fullname($user) . ' (' . $user->email . ')';

        $approvebutton = '';
        if ($r->status === 'beantragt') {
            $approveurl = new moodle_url('/mod/vmrequest/actions/approve_course_request.php', [
                'id' => required_param('id', PARAM_INT),
                'approveid' => $r->id
            ]);
            $approvebutton = html_writer::link($approveurl, 'Genehmigen', ['class' => 'btn btn-success']);
        } else {
            $approvebutton = 'Bereits genehmigt';
        }

        $table->data[] = [
            $r->id,
            format_string($r->vmname),
            format_string($r->vmsize),
            format_string($r->vmos),
            shorten_text(format_string($r->justification), 50),
            $r->selectedcount,
            $submitter,
            format_string($r->status),
            userdate($r->timecreated),
            $approvebutton
        ];
    }    
    return html_writer::table($table);
}

function render_individual_requests_table($requests) {
    global $DB;

    if (empty($requests)) {
        return html_writer::div('Keine Einzelanträge zur Genehmigung.', 'alert alert-info');
    }

    $table = new html_table();
    $table->head = ['ID', 'Name', 'Größe', 'OS', 'Begründung', 'Beantragt von', 'Status', 'Aktion'];

    foreach ($requests as $r) {
        $user = $DB->get_record('user', ['id' => $r->userid], 'firstname, lastname, email');
        $submitter = fullname($user) . ' (' . $user->email . ')';

        $approveurl = new moodle_url('/mod/vmrequest/actions/approve_individual_request.php', [
            'id' => $r->id,
            'action' => 'approve'
        ]);

        $denyurl = new moodle_url('/mod/vmrequest/actions/approve_individual_request.php', [
            'id' => $r->id,
            'action' => 'deny'
        ]);

        $buttons = html_writer::link($approveurl, 'Genehmigen', ['class' => 'btn btn-success']) . ' ' .
                   html_writer::link($denyurl, 'Ablehnen', ['class' => 'btn btn-danger']);

        $table->data[] = [
            $r->id,
            format_string($r->vmname),
            format_string($r->vmsize),
            format_string($r->vmos),
            shorten_text(format_string($r->justification), 50),
            $submitter,
            format_string($r->status),
            $buttons
        ];
    }

    return html_writer::table($table);
}


$tab = optional_param('tab', 'course', PARAM_ALPHA);
require(__DIR__ . '/../tabs.php');

// Lade Einzelanträge für den Tab 'individual'
//$individuals_to_approve = $DB->get_records('vmrequest_request', ['status' => 'waitingadminapproval']);
$individuals_to_approve = vm_external_api_get_records('mi_vmrequest_request', ['status' => 'waitingadminapproval']);


if ($tab === 'course') {
    // bisheriger Code für Kursanträge
    echo $OUTPUT->heading('Kursanträge');
    echo render_course_requests_table($todo);
    echo $OUTPUT->heading('Bereits genehmigt');
    echo render_course_requests_table($approved);

} elseif ($tab === 'individual') {
    // Code für Einzelanträge, z.B.:
    echo $OUTPUT->heading('Einzelanträge zur Admin-Genehmigung');
    echo render_individual_requests_table($individuals_to_approve);
}


$backurl = new moodle_url('/mod/vmrequest/view.php', ['id' => $id]);

if ($id) {
    $backurl = new moodle_url('/mod/vmrequest/view.php', ['id' => $id]);
} else {
    $backurl = new moodle_url('/my/');
}

echo html_writer::link($backurl, 'Zurück zum Kurs', ['class' => 'btn btn-secondary mb-3']);


echo $OUTPUT->footer();
