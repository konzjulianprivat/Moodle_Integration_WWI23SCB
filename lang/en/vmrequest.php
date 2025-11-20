<?php

$string['pluginname'] = 'VM Request';
$string['pluginadministration'] = 'VM Request Administration';
$string['vmrequest:manage'] = 'Manage VM Request';
$string['vmrequest:addinstance'] = 'Neues Plugin erstellen';
$string['vmrequest:approverequest'] = 'VM Anträge genehmigen (Dozent)';
$string['vmrequest:manageallrequests'] = 'VM Kursanträge erstellen (Dozent)';
$string['vmrequest:submitrequest'] = 'VM Anträge erstellen (Student)';
$string['vmrequest:approvecourserequest'] = 'Admin-Genehmigung (Admin';
$string['name'] = 'Name der Anfrage';
$string['description'] = 'Beschreibung';
$string['modulename'] = 'VM Request';  // Ersetze dies durch den tatsächlichen Namen des Moduls
$string['modulenameplural'] = 'VM Requests';

$string['vmname']           = 'VM Name';
$string['vmsize_tiny']      = 'Tiny (1 CPU, 1 GB RAM)';
$string['vmsize_medium']    = 'Medium (2 CPU, 4 GB RAM)';
$string['vmsize_large']     = 'Large (4 CPU, 8 GB RAM)';
$string['vmos_ubuntu']      = 'Ubuntu';
$string['vmos_windows']     = 'Windows';
$string['justification']    = 'Justification';
$string['submitrequest']    = 'Neuen Antrag erstellen';
$string['requestsubmitted'] = 'Antrag erfolgreich übermittelt';
$string['backtocourse'] = 'Zurück zur Kursseite';
$string['editingnotallowed'] = 'Dieser Antrag kann nicht mehr bearbeitet werden.';
$string['vmname_help'] = 'Geben Sie einen eindeutigen Namen für die VM an. Dieser erscheint später im Dashboard.';
$string['vmsize'] = 'VM-Größe';
$string['vmsize_help'] = 'Wählen Sie die gewünschte Leistung der VM. Große VMs erfordern eine zusätzliche Genehmigung.';
$string['vmos'] = 'Betriebssystem';
$string['vmos_help'] = 'Wählen Sie das gewünschte Betriebssystem für die VM aus.';
$string['justification'] = 'Begründung des Antrags';
$string['justification_help'] = 'Beschreiben Sie hier den Zweck der VM und warum diese Konfiguration erforderlich ist.';


$string['denialreason'] = 'Begründung für die Ablehnung';
$string['denyrequest'] = 'Antrag ablehnen';
$string['requestdenied'] = 'Der Antrag wurde abgelehnt.';

$string['approverequest'] = 'Antrag genehmigen';
$string['approvalreason'] = 'Begründung für Genehmigung'; // Falls du auch eine Genehmigungsbegründung brauchst
$string['requestapproved'] = 'Der Antrag wurde genehmigt.';

$string['deletedsubject']    = 'Ihr VM-Antrag wurde gelöscht';
$string['deletedmessage']    = 'Ihr Antrag für die VM „{$a->vmname}“ (Status: {$a->status}) wurde von {$a->by} gelöscht.';
$string['deletedsuccess']    = 'Der Antrag wurde erfolgreich gelöscht.';

$string['config_studentrequests']      = 'Allow student requests';
$string['config_studentrequests_desc'] = 'If unchecked, students cannot submit VM requests; they will only see assigned VMs.';

$string['is_general_supervision']      = 'Plugin flavor';
$string['is_general_supervision_desc'] = 'Only check if the plugin will be used in broader context outside normal courses.?';
$string['is_general_supervision_help'] = 'Only check if the plugin will be used in broader context outside normal courses.?';


$string['approvecourserequests'] = 'Kursweite Anträge genehmigen';
$string['mod/vmrequest:approvecourserequest'] = 'Kann kursweite VM-Anträge genehmigen';

$string['selectsupervisor'] = 'Betreuer auswählen';
$string['nosupervisors'] = 'Keine Betreuer gefunden.';
$string['required'] = 'Pflichtfeld';

