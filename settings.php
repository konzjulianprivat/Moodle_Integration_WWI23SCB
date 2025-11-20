<?php

if (has_capability('mod/vmrequest:approvecourserequest', context_system::instance())) {
    $ADMIN->add('modsettings', new admin_externalpage(
        'mod_vmrequest_approvecourserequests',
        get_string('approvecourserequests', 'vmrequest'),
        new moodle_url('/mod/vmrequest/approve_course_requests.php')
    ));
}
