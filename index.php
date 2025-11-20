<?php

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('mod/vmrequest:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/vmrequest/index.php'));
$PAGE->set_title(get_string('pluginname', 'mod_vmrequest'));
$PAGE->set_heading(get_string('pluginname', 'mod_vmrequest'));

echo $OUTPUT->header();
echo $OUTPUT->heading('Willkommen zu VM Request');

// Später kannst du hier eine DB-Ausgabe oder Formulare integrieren

echo $OUTPUT->footer();
