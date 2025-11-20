<?php
require_once('../../config.php');
require_once(__DIR__ . '/lib.php');


$requestid = required_param('requestid', PARAM_INT);

// Holen des Antrags aus der separaten Tabelle über externe API
$record = vm_external_api_get_record_by_id('mi_vmrequest_request', $requestid);

/*
// Alte lokale DB-Logik
$record = $DB->get_record('vmrequest_request',
    ['id' => $requestid], '*', MUST_EXIST);
*/

// Holen des Course-Module-Objekts (für Links und Kontext)
$cm = get_coursemodule_from_id('vmrequest', $record->coursemodule, 0, false, MUST_EXIST);
$course = get_course($cm->course);

// Login und Context setzen
require_login($course, false, $cm);
$context = context_module::instance($cm->id);

// PAGE-Initialisierung
$PAGE->set_url(new moodle_url('/mod/vmrequest/viewrequest.php', ['requestid' => $requestid]));
$PAGE->set_title(get_string('pluginname','vmrequest'));
$PAGE->set_heading(get_string('pluginname','vmrequest'));
$PAGE->set_context($context);

echo $OUTPUT->header();



// Card mit Details
echo html_writer::start_div('card mb-4', ['style' => 'max-width:800px; margin:auto;']);
  // Header
  echo html_writer::start_div('card-header');
    echo html_writer::tag('h4', 'VM-Antrag – Details', ['class'=>'card-title m-0']);
  echo html_writer::end_div();

  // Body
  echo html_writer::start_div('card-body');
    // Funktion für Detail-Zeilen
    $detail = function($label, $value) {
        return html_writer::tag('dt', $label, ['class'=>'col-sm-4 font-weight-bold']) .
               html_writer::tag('dd', $value ?? '-', ['class'=>'col-sm-8']);
    };

    echo html_writer::start_tag('dl', ['class'=>'row mb-0']);
      echo $detail('VM-Name', format_string($record->vmname));
      echo $detail('Größe',   format_string($record->vmsize));
      echo $detail('OS',      format_string($record->vmos));
      echo $detail('Status',  format_string($record->status));

      // Wer hat beantragt? Dynamisch aus user-Tabelle
      $user = core_user::get_user($record->userid);
      echo $detail('Beantragt von', fullname($user).' ('.$user->email.')');

      $supervisor = core_user::get_user($record->supervisorid);
      echo $detail('Genehmigende Person', fullname($supervisor).' ('.$user->email.')');


      if (!empty($record->justification)) {
        echo $detail('Begründung', format_text($record->justification, FORMAT_HTML));
      }
      if (!empty($record->denialreason)) {
        echo $detail('Ablehnungsgrund', format_text($record->denialreason, FORMAT_HTML));
      }
    echo html_writer::end_tag('dl');

  echo html_writer::end_div(); // .card-body
echo html_writer::end_div();   // .card

// Buttons: Wenn Dozent und Status = beantragt
if (has_capability('mod/vmrequest:approverequest', $context) && $record->status === 'beantragt') {
    $approveurl = new moodle_url('/mod/vmrequest/actions/approve.php', [
        'id'        => $cm->id,
        'requestid' => $record->id
    ]);
    $denyurl    = new moodle_url('/mod/vmrequest/actions/deny.php',    [
        'id'        => $cm->id,
        'requestid' => $record->id
    ]);
    $deleteurl  = new moodle_url('/mod/vmrequest/actions/delete.php',  [
        'id'        => $cm->id,
        'requestid' => $record->id
    ]);

    echo html_writer::start_div('d-flex mb-4');
      echo html_writer::link($approveurl, 'Genehmigen', ['class'=>'btn btn-success mr-2']);
      echo html_writer::link($denyurl,    'Ablehnen',   ['class'=>'btn btn-danger mr-2']);
      echo html_writer::link($deleteurl,  'Löschen',    [
          'class'=>'btn btn-outline-danger',
          'onclick'=>"return confirm('Antrag wirklich endgültig löschen?');"
      ]);
    echo html_writer::end_div();
}

// Zurück-Link (führt in Manage-Ansicht zurück, Archiv-Tab wenn genehmigt/abgelehnt)
$returnurl = new moodle_url('/mod/vmrequest/view.php', [
    'id'         => $cm->id,
    'action'     => 'manage',
    'archiviert' => ($record->status !== 'beantragt') ? 1 : 0
]);
echo html_writer::link($returnurl, get_string('continue','moodle'), ['class'=>'btn btn-secondary']);

echo $OUTPUT->footer();
