<?php
require_once($CFG->dirroot . '/mod/vmrequest/forms/course_vm_form.php');

$course_requests = vmrequest_get_course_requests_by_user($cm->id, $USER->id);

echo $OUTPUT->heading('Ihre bisherigen Anträge');

if (!empty($course_requests)) {
    echo '<div class="row">';
    foreach ($course_requests as $r) {
        echo '<div class="col-md-6 mb-4">';
        echo '<div class="card h-100">';
        echo '<div class="card-header">';
        echo format_string($r->vmname) . ' <span class="badge badge-secondary ml-2">' . format_string($r->status) . '</span>';
        echo '</div>';
        echo '<div class="card-body">';
        echo html_writer::tag('p', '<strong>'.get_string('vmsize','vmrequest').':</strong> '. format_string($r->vmsize));
        echo html_writer::tag('p', '<strong>'.get_string('vmos','vmrequest').':</strong> '. format_string($r->vmos));
        echo html_writer::tag('p', '<strong>'.get_string('justification','vmrequest').':</strong> '. format_text($r->justification, FORMAT_HTML));
        echo html_writer::tag('p', '<strong>Anzahl an VMs:</strong> ' . format_string($r->selectedcount));
        if (!empty($r->denialreason)) {
            echo html_writer::tag('p', '<strong>'.get_string('denialreason','vmrequest').':</strong> '. format_text($r->denialreason, FORMAT_HTML));
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
}

echo $OUTPUT->heading('Kursweite VM beantragen');
$students = get_enrolled_users($context);
$submitterid = $USER->id;

$studentoptions = [];
foreach ($students as $student) {
    $studentoptions[$student->id] = [
        'name' => fullname($student),
        'email' => $student->email
    ];
}

$url1 = new moodle_url('/mod/vmrequest/view.php', ['id' => $cm->id, 'action' => 'manageall']);
$url2 = new moodle_url('/mod/vmrequest/view.php', ['id' => $cm->id]);
$form = new vmrequest_course_form($url1, ['cmid' => $cm->id, 'students' => $studentoptions]);

if ($form->is_cancelled()) {
    redirect($url2);
} elseif ($data = $form->get_data()) {
    $is_special_config = ($data->vmsize == 'large');
    $data->status = $is_special_config ? 'beantragt' : 'genehmigt';
    $data->selectedcount = (!empty($data->selectedstudents)) ? count($data->selectedstudents) : 0;

    // Kursweiten Antrag einfügen
    $course_request_id = vmrequest_insert_course_request($data);

    // Einzelanträge einfügen
    if (!empty($data->selectedstudents)) {
        foreach ($data->selectedstudents as $studentid) {
            vmrequest_insert_individual_request($data, $studentid, $submitterid, $course_request_id);
        }
    }

    // Falls KEINE Sonderkonfiguration → sofort genehmigen und Camunda triggern
    if (!$is_special_config) {
        // --- ALT: Moodle-interne DB ---
        // $DB->set_field('vmrequest_course_request', 'status', 'genehmigt', ['id' => $course_request_id]);

        // --- NEU: Externe API ---
        vm_external_api_update_record('mi_vmrequest_course_request', $course_request_id, ['status' => 'genehmigt']);

        // --- ALT: Einzelanträge laden ---
        // $individuals = $DB->get_records('vmrequest_request', ['course_request_id' => $course_request_id]);

        // --- NEU: Einzelanträge per API laden ---
        $individuals = vm_external_api_get_records('mi_vmrequest_request', ['course_request_id' => $course_request_id]);

        foreach ($individuals as $indiv) {
            // --- ALT: Status setzen + update ---
            // $indiv->status = 'genehmigt';
            // $DB->update_record('vmrequest_request', $indiv);

            // --- NEU: Status via API setzen ---
            vm_external_api_update_record('mi_vmrequest_request', $indiv->id, ['status' => 'genehmigt']);

            // Camunda-Prozess starten (bleibt gleich)
            vmrequest_trigger_camunda_creation($indiv);
        }
    }


    redirect($url1, 'Kursweiter VM-Antrag sowie Einzelanträge wurden erfolgreich erstellt.');
}


echo '<div class="card mb-4" style="max-width:600px;">';
echo '<div class="card-body">';
$form->display();
echo '</div>';
echo '</div>';
echo <<<JS
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const confirmed = confirm("Sind Sie sicher, dass Sie diesen Antrag absenden möchten?");
        if (!confirmed) {
            e.preventDefault();
        }
    });
});
</script>
JS;

$backurl = new moodle_url('/mod/vmrequest/view.php', ['id' => $id]);
echo html_writer::link($backurl, 'Zurück', ['class' => 'btn btn-secondary mb-3']);
exit;
?>