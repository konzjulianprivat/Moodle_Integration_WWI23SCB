<?php
$moduleinstance = $DB->get_record('vmrequest', ['id' => $cm->instance], '*', MUST_EXIST);
$general_supervision_enabled = $moduleinstance->is_general_supervision ?? 1;

echo $OUTPUT->heading('Was möchten Sie tun?');
echo '<div class="row">';

// Card 1: Anträge verwalten
$linkmanage = new moodle_url('/mod/vmrequest/view.php', [
    'id'     => $id,
    'action' => 'manage'
]);
$iconmanage = $OUTPUT->pix_icon('i/settings', 'Anträge verwalten');
echo '<div class="col-md-3">';
echo '<div class="card text-center">';
echo '<div class="card-body">';
echo $iconmanage;
echo '<h5 class="card-title mt-2">Anträge verwalten</h5>';
echo '<p class="card-text">Verwalten Sie bestehende Einzelanträge.</p>';
echo html_writer::link($linkmanage, 'Öffnen', ['class' => 'btn btn-primary']);
echo '</div>';
echo '</div>';
echo '</div>';

// Card 2: Antrag stellen
$linkapply = new moodle_url('/mod/vmrequest/view.php', [
    'id'     => $id,
    'action' => 'apply'
]);
$iconapply = $OUTPUT->pix_icon('t/add', 'Antrag stellen');
echo '<div class="col-md-3">';
echo '<div class="card text-center">';
echo '<div class="card-body">';
echo $iconapply;
echo '<h5 class="card-title mt-2">Antrag stellen</h5>';
echo '<p class="card-text">Stellen Sie einen neuen Einzelantrag.</p>';
echo html_writer::link($linkapply, 'Öffnen', ['class' => 'btn btn-secondary']);
echo '</div>';
echo '</div>';
echo '</div>';

// Card 3: Kurs-VMs beantragen (falls Berechtigung)
if (has_capability('mod/vmrequest:manageallrequests', $context) AND !$general_supervision_enabled) {
    $linkall = new moodle_url('/mod/vmrequest/view.php', [
        'id'     => $id,
        'action' => 'manageall'
    ]);
    $iconall = $OUTPUT->pix_icon('i/group', 'Kurs-VMs beantragen');
    echo '<div class="col-md-3">';
    echo '<div class="card text-center">';
    echo '<div class="card-body">';
    echo $iconall;
    echo '<h5 class="card-title mt-2">Kurs-VMs beantragen</h5>';
    echo '<p class="card-text">Beantragen Sie VMs für den gesamten Kurs.</p>';
    echo html_writer::link($linkall, 'Öffnen', ['class' => 'btn btn-info']);
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

// Card 4: Kurs-VM Anträge verwalten (falls Berechtigung)
if (has_capability('mod/vmrequest:approvecourserequest', $context)) {
    $approvelink = new moodle_url('/mod/vmrequest/actions/approve_course_request.php', ['id' => $id]);
    $iconapprove = $OUTPUT->pix_icon('i/settings', '[ADMIN] Anträge verwalten');
    echo '<div class="col-md-3">';
    echo '<div class="card text-center">';
    echo '<div class="card-body">';
    echo $iconapprove;
    echo '<h5 class="card-title mt-2">[ADMIN] Anträge verwalten</h5>';
    echo '<p class="card-text">Genehmigen oder verwalten Sie kursweite Anträge.</p>';
    echo html_writer::link($approvelink, 'Öffnen', ['class' => 'btn btn-info']);
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

echo '</div>'; // row
echo $OUTPUT->footer();
exit;
?>