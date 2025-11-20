<?php
require_once('../../config.php');

require_login();

$id = required_param('id', PARAM_INT);           // Course Module ID
$action = optional_param('action', '', PARAM_ALPHA); // 'manage' oder 'apply'
$showarchived = optional_param('showarchived', 0, PARAM_BOOL);

$cm = get_coursemodule_from_id('vmrequest', $id, 0, false, MUST_EXIST);
$courseid = $cm->course;
$context = context_module::instance($cm->id);
require_login($cm->course, false, $cm); // Corrected to pass $cm object

global $PAGE, $DB, $USER, $OUTPUT;

// Moodle-Seite initialisieren
$PAGE->set_context($context);
$PAGE->set_url('/mod/vmrequest/view.php', ['id' => $id]);
$PAGE->set_title(get_string('pluginname', 'vmrequest'));
$PAGE->set_heading(get_string('pluginname', 'vmrequest'));
$PAGE->requires->css('/mod/vmrequest/styles/style.css');

// Prüfen, ob aktueller Nutzer die Berechtigung hat (Dozent/Manager)
$isdozent = has_capability('mod/vmrequest:approverequest', $context);

// Wenn Nutzer kein Dozent ist, direkt auf apply weiterleiten
if (!$isdozent) {
    // Aktion "apply" erzwingen
    $action = 'apply';
}

echo $OUTPUT->header();

// Moduleinstellungen laden
$moduleinstance = $DB->get_record('vmrequest', ['id' => $cm->instance], '*', MUST_EXIST);
$studentrequests_enabled = $moduleinstance->config_studentrequests ?? 0;
$general_supervision_enabled = $moduleinstance->is_general_supervision ?? 1;

// Include separate logic files based on action
if ($isdozent && empty($action)) {
    require_once(__DIR__ . '/includes/dozent_dashboard.php');
} elseif ($isdozent && $action === 'manage') {
    require_once(__DIR__ . '/includes/manage_requests.php');
} elseif ($isdozent && $action === 'manageall') {
    require_once(__DIR__ . '/includes/manage_course_requests.php');
} elseif ($action === 'apply') {
    require_once(__DIR__ . '/includes/apply_request.php');
}

echo $OUTPUT->footer();
exit;
?>
