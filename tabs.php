<?php
$row = array();

$row[] = new tabobject('course', new moodle_url('/mod/vmrequest/actions/approve_course_request.php', ['tab' => 'course']), 'Kursanträge');
$row[] = new tabobject('individual', new moodle_url('/mod/vmrequest/actions/approve_course_request.php', ['tab' => 'individual']), 'Einzelanträge');

$tabs = $row; // NICHT: array($row)

echo $OUTPUT->tabtree($tabs, $tab);

?>
