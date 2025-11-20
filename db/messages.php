<?php
/**
 * Definition of message providers for mod_vmrequest.
 *
 * @package   mod_vmrequest
 */

defined('MOODLE_INTERNAL') || die();

$messageproviders = [
    // Einfache textbasierte Notification, wenn ein Request gelöscht wurde.
    'request_deleted' => [
        'capability' => 'mod/vmrequest:approverequest', // Wer darf Notifications senden
        'defaults'   => [
            'popup' => true,   // keine Popup-Benachrichtigung
            'email' => false     // sende E-Mail, falls aktiviert
        ]
    ],
];