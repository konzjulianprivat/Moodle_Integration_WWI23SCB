<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    // Fähigkeit: VM-Request hinzufügen (z. B. über Kurseditor)
    'mod/vmrequest:addinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/course:manageactivities'
    ),

    'mod/vmrequest:submitrequest' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'student' => CAP_ALLOW,
        ],
    ],
    'mod/vmrequest:approverequest' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
    'mod/vmrequest:manageallrequests' => [
        'captype'     => 'write',
        'contextlevel'=> CONTEXT_MODULE,
        'archetypes'  => [
            'editingteacher' => CAP_ALLOW, 
            'manager' => CAP_ALLOW],
    ],

    'mod/vmrequest:approvecourserequest' => [ 
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' =>['manager' => CAP_ALLOW],
    ],
);
