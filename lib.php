<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Legt das Plugin innerhalb eines Kurses an und speichert den Eintrag in der Moodle DB ab
 */

function vmrequest_add_instance(stdClass $data, mod_vmrequest_mod_form $mform = null) {
    global $DB;
    $data->timecreated = time();
    // speichert in mdl_vmrequest
    return $DB->insert_record('vmrequest', $data);
}

/**
 * Update-Instanz (Plugin) (wenn jemand Bearbeiten speichert).
 */
function vmrequest_update_instance(stdClass $data, mod_vmrequest_mod_form $mform = null) {
    global $DB;
    $data->timemodified = time();
    $data->id = $data->instance;
    return $DB->update_record('vmrequest', $data);
}

/**
 * Löschen einer Instanz (Plugin).
 */
function vmrequest_delete_instance($id) {
    global $DB;
    return $DB->delete_records('vmrequest', ['id'=>$id]);
}

/** ------------------------------------------------------------------------------------------------------------------------------ 
 * AB HIER BEGINNT DIE EIGENTLICHE VM-LOGIK 
 * Aktuell werden die Daten zweigleisig gespeichert. 
 * Das heißt so wohl die interne Moodle DB (Schema siehe vmrequest/db/install.xml) als auch die externe DB werden befüllt.
 * Für das abrufen von Records wird aktuell auschließlich die externe DB per API verwendet um inkonsistenzen zu vermeiden. 
 * Sollte die API oder externe DB nicht laufen muss dies entsprechend im Code umgestellt werden.
*/
/**
 * Zentrale Funktion für "WRITE"-Operationen auf der externen DB
 */

function vm_insert_into_external_db($table_name, $data) {
    $api_url = "http://swim-api.swimdhbw.de/api/data/$table_name";

    try {
        $json_data = json_encode($data);

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code >= 200 && $http_code < 300) {
            $decoded = json_decode($response);

            // Falls API eine ID zurückliefert (z. B. {"data":{"id":123,...}})
            if (isset($decoded->data->id)) {
                error_log("Insert erfolgreich: Externe ID = {$decoded->data->id}");
                return (object)['id' => $decoded->data->id];  // ID zurückgeben
            } else {
                error_log("Insert erfolgreich, aber keine ID gefunden: " . $response);
                return null;
            }
        } else {
            $error = "API-Fehler: HTTP $http_code - $response";
            error_log("Externe DB-Insert fehlgeschlagen: $error");
            debugging("Externe DB-Insert fehlgeschlagen: $error", DEBUG_DEVELOPER);
            return null;
        }
    } catch (Exception $e) {
        error_log("Externe DB-Insert fehlgeschlagen: " . $e->getMessage());
        debugging("Externe DB-Insert fehlgeschlagen: " . $e->getMessage(), DEBUG_DEVELOPER);
        return null;
    }
}



/**
 * Fügt einen VM-Antrag in die lokale und externe Datenbank ein.
 */
function vmrequest_insert_request(stdClass $data) {
    
    // Lokale Speicherung
    global $DB, $USER;

    $record = new stdClass();
    $record->coursemodule = $data->coursemodule;
    $record->userid = $USER->id;
    $record->vmname = $data->vmname;
    $record->vmsize = $data->vmsize;
    $record->vmos = $data->vmos;
    $record->justification = $data->justification;
    $record->status = 'beantragt';
    $record->timecreated = time();
    $record->submitter = $USER->id;
    $record->supervisorid = $data->supervisorid ?? '0';

    $insert_id = $DB->insert_record('vmrequest_request', $record);

    // Externe Speicherung via API
    $api_data = [
        'coursemodule' => $data->coursemodule,
        'userid' => $USER->id,
        'vmname' => $data->vmname,
        'vmsize' => $data->vmsize,
        'vmos' => $data->vmos,
        'justification' => $data->justification,
        'status' => 'beantragt',
        'timecreated' => $record->timecreated,
        'submitter' => $USER->id,
        'supervisorid' => $data->supervisorid ?? '0'
    ];
    vm_insert_into_external_db('mi_vmrequest_request', $api_data);

    return $insert_id;
}
/**
 * Fügt einen kursweiten VM-Antrag in die Tabelle vmrequest_course_request ein.
 *
 * @param stdClass $data
 * @param cm_info $cm
 * @param int $userid
 * @return int Insert-ID
 */
function vmrequest_insert_course_request(stdClass $data) {
    global $DB, $USER;

    $record = new stdClass();
    $record->coursemodule = $data->coursemodule;
    $record->userid = $USER->id;
    $record->vmname = $data->vmname;
    $record->vmsize = $data->vmsize;
    $record->vmos = $data->vmos;
    $record->justification = $data->justification;
    $record->selectedcount = $data->selectedcount;
    $record->status = $data->status ?? 'genehmigt';
    $record->timecreated = time();

    // Lokale Speicherung
    $insert_id = $DB->insert_record('vmrequest_course_request', $record);

    // Externe Speicherung via API
    $api_data = [
        'coursemodule' => $data->coursemodule,
        'userid' => $USER->id,
        'vmname' => $data->vmname,
        'vmsize' => $data->vmsize,
        'vmos' => $data->vmos,
        'justification' => $data->justification,
        'selectedcount' => $data->selectedcount,
        'status' => $data->status ?? 'genehmigt',
        'timecreated' => $record->timecreated
    ];

    $external = vm_insert_into_external_db('mi_vmrequest_course_request', $api_data);
    $externalid = $external?->id ?? null;  // Null-safe access (PHP 8+)

    return $externalid;
}


/**
 * Fügt einen Einzel-VM-Antrag für einen spezifischen Nutzer in die Tabelle vmrequest_request ein. 
 * Diese Methode ist notwendig und wird verwendet, wenn aus einenm Kurs Antrag einzel Anträge erstellt werden müssen und der Antrag dem jeweiligen Studenten zugeordnet werden soll. 
 *
 * @param stdClass $data
 * @param cm_info $cm
 * @param int $userid
 * @return int Insert-ID
 */
/**
 * Fügt einen Einzel-VM-Antrag für einen spezifischen Nutzer in die lokale und externe Datenbank ein.
 */
function vmrequest_insert_individual_request(stdClass $data, int $studentid, int $submitterid, int $course_request_id) {
    global $DB;

    $record = new stdClass();
    $record->coursemodule = $data->coursemodule;
    $record->userid = $studentid;
    $record->vmname = $data->vmname;
    $record->vmsize = $data->vmsize;
    $record->vmos = $data->vmos;
    $record->justification = $data->justification;
    $record->status = !empty($data->status) ? $data->status : 'beantragt';
    $record->timecreated = time();
    $record->submitter = $submitterid;
    $record->course_request_id = $course_request_id;

    // Lokale Speicherung
    $insert_id = $DB->insert_record('vmrequest_request', $record);

    // Externe Speicherung via API
    $api_data = [
        'coursemodule' => $data->coursemodule,
        'userid' => $studentid,
        'vmname' => $data->vmname,
        'vmsize' => $data->vmsize,
        'vmos' => $data->vmos,
        'justification' => $data->justification,
        'status' => !empty($data->status) ? $data->status : 'beantragt',
        'timecreated' => $record->timecreated,
        'submitter' => $submitterid,
        'course_request_id' => $course_request_id
    ];
    vm_insert_into_external_db('mi_vmrequest_request', $api_data);

    return $insert_id;
}
/**
 * Gibt alle Kursteilnehmer außer dem aktuellen User zurück.
 *
 * Diese Methode greift ausschließlich auf die interne Moodle db zurück, da hier die Moodle-Nutzer abgefragt werden.
 *
 * @param int $excludeuserid Die User-ID, die ausgeschlossen werden soll (z. B. der aktuelle User)
 * @return array Array von userids => "Vollständiger Name (E-Mail)"
 */
function vmrequest_get_possible_supervisors(int $courseid, int $excludeuserid): array {
    global $DB;

    $context = context_course::instance($courseid);
    $users = get_enrolled_users($context);

    $supervisors = [];
    foreach ($users as $user) {
        if ($user->id != $excludeuserid) { // mich selbst ausschließen
            $supervisors[(string)$user->id] = fullname($user) . ' (' . $user->email . ')';
        }
    }

    return $supervisors;
}


/**
 * Gibt alle Anträge eines Nutzers in einem Course-Modul zurück. (Alte Methode greift auf Moodle DB zu)
 */
function vmrequest_get_requests_by_user($cmid, $userid) {
    //     global $DB;
    //     return $DB->get_records('vmrequest_request', [
    //         //'coursemodule' => $cmid, auskommatiert da alle requests einer person angezeigt werden sollen also plugin übergreifend.
    //         'userid'       => $userid
    //     ], 'timecreated DESC');

    //Neue API-DB Abfrage mit clientseitiger Filterung, da die API das FIltern über die API noch nicht erlaubt.
    $all = vm_external_api_get_records('mi_vmrequest_request');

    // Defensive Filterung: userid als string vergleichen
    $filtered = array_filter($all, function($r) use ($userid) {
        return isset($r->userid) && (string)$r->userid === (string)$userid;
    });

    // Sortierung nach Zeit absteigend
    usort($filtered, fn($a, $b) => $b->timecreated <=> $a->timecreated);
    error_log("Records geladen: " . count($all));

    return $filtered;
}

function vmrequest_get_course_requests_by_user($cmid, $userid) {
    // Alte interne Abfrage (auskommentiert)
    /*
    global $DB;
    return $DB->get_records('vmrequest_course_request', [
        'coursemodule' => $cmid,
        'userid'       => $userid
    ], 'timecreated DESC');
    */

    // Neue externe API-Abfrage
    $all = vm_external_api_get_records('mi_vmrequest_course_request');

    // Defensive Filterung: userid und coursemodule vergleichen (beides als string)
    $filtered = array_filter($all, function($r) use ($userid, $cmid) {
        return isset($r->userid, $r->coursemodule)
            && (string)$r->userid === (string)$userid
            && (string)$r->coursemodule === (string)$cmid;
    });

    // Sortierung nach Zeit absteigend
    usort($filtered, fn($a, $b) => $b->timecreated <=> $a->timecreated);

    error_log("Externe Kursanträge geladen: " . count($filtered));

    return $filtered;
}



/**
 * Gibt alle Anträge, offen oder archiviert, für ein Course-Modul zurück.
 */
function vmrequest_get_requests_for_manage($cmid, $showarchived = false, $general_supervision_enabled = false) {
    global $USER;

    // --- Externe API: Alle Datensätze abrufen ---
    $all = vm_external_api_get_records('mi_vmrequest_request');

    // --- Filtern gemäß ursprünglicher Bedingungen ---
    $filtered = array_filter($all, function($r) use ($cmid, $showarchived, $general_supervision_enabled, $USER) {
        // Nur Anträge dieses Kursmoduls
        if (!isset($r->coursemodule) || (int)$r->coursemodule !== (int)$cmid) {
            return false;
        }

        // Archivfilter
        if ($showarchived) {
            if (!isset($r->status) || $r->status === 'beantragt') {
                return false;
            }
        } else {
            if (!isset($r->status) || $r->status !== 'beantragt') {
                return false;
            }
        }

        // Supervisorfilter
        if ($general_supervision_enabled) {
            if (!isset($r->supervisorid) || (int)$r->supervisorid !== (int)$USER->id) {
                return false;
            }
        }

        return true;
    });

    // Sortieren nach Zeit (neueste zuerst)
    usort($filtered, fn($a, $b) => $b->timecreated <=> $a->timecreated);

    return $filtered;

    /*
    // --- Alte lokale DB-Logik ---
    global $DB;

    $conditions = ['coursemodule = ?'];
    $params = [$cmid];

    if ($showarchived) {
        $conditions[] = "status != 'beantragt'";
    } else {
        $conditions[] = "status = 'beantragt'";
    }

    if ($general_supervision_enabled) {
        $conditions[] = "supervisorid = ?";
        $params[] = $USER->id;
    }

    $wheresql = implode(' AND ', $conditions);
    return $DB->get_records_select('vmrequest_request', $wheresql, $params, 'timecreated DESC');
    */
}


/**
 * Zentrale Methode zum Start des Camunda-Prozesses und zur VM-Erstellung.
 *
 * Diese Funktion wird für jeden einzelnen Antrag aufgerufen und führt folgende Schritte durch:
 * - Validiert eingehende Daten
 * - Bereitet VM-spezifische Parameter (Größe, Betriebssystem, Name) auf
 * - Sendet eine Anfrage an den Camunda-Webhook zur Prozessauslösung
 * - Speichert eine neue Instanz lokal und extern (API)
 * - Verknüpft die erzeugte Instanz mit dem ursprünglichen Antrag
 *
 * @param stdClass $data Antrag mit den Feldern: id, userid, vmsize, vmos, vmname
 * @return bool Erfolgsstatus
 */
function vmrequest_trigger_camunda_creation($data) {
    global $DB;

    // Webhook-Konfiguration
    $webhook_url = 'https://camunda-dev.swimdhbw.de/connectors/inbound/create_instance';
    $apiKey = '6diFER6EgibI8qd';

    // Mappings: vmsize => Flavor-ID, vmos => Image-ID
    $flavorIds = [
        'tiny'   => '5848e066-0a8f-4f93-863b-8c4128c87ab0',
        // neue id für tiny muss noch gepflegt werden
        'medium' => 'ce6b63c6-0ccf-453f-a994-368f6004e5d5',
        'large'  => '0ffe6506-ba05-4df1-9ce8-8197b57ce17b',
    ];

    $imageIds = [
        'ubuntu'  => '7f5f9fb3-d478-4e3e-8edc-03c04ddcb151',
        'windows' => 'dabfb132-4417-4f48-8790-93d93cc05725',
    ];

    // Pflichtfelder prüfen
    if (empty($data->vmsize) || empty($data->vmos) || empty($data->vmname)) {
        debugging('Ungültige VM-Daten: vmsize, vmos oder vmname fehlt.', DEBUG_DEVELOPER);
        error_log("VMRequest Fehler: Ungültige Eingabedaten für Antrag {$data->id}");
        return false;
    }

    // Benutzer-E-Mail holen
    $user = $DB->get_record('user', ['id' => $data->userid], 'id, email');
    $useremail = $user ? $user->email : null;

    // VM-Namen bereinigen und generieren
    $emailprefix = $useremail ? explode('@', $useremail)[0] : 'unknownuser';
    $clean_vmname = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $data->vmname));
    $final_vmname = $emailprefix . '_' . $clean_vmname;

    // IDs zuweisen (Fallbacks bei ungültigen Eingaben)
    $flavorId = $flavorIds[$data->vmsize] ?? $flavorIds['medium'];
    $imageId = $imageIds[$data->vmos] ?? $imageIds['ubuntu'];

    // UUID generieren
    $uuid = core_text::strtolower(\core\uuid::generate());

    //Anfrage an Camunda vorbereiten
    $postData = [
        'status'         => 'calling',
        'instanceName'   => $final_vmname,
        'instanceFlavor' => $flavorId,
        'instanceImage'  => $imageId,
        'userEmail'      => $useremail,
        'id'             => $data->id
    ];

    // HTTP-Kontext vorbereiten
    $options = [
        'http' => [
            'header'  => "Authorization: $apiKey\r\nContent-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($postData),
            'timeout' => 10,
        ],
    ];
    $context = stream_context_create($options);

    // Camunda-Aufruf
    $response = @file_get_contents($webhook_url, false, $context);

    if ($response === false) {
        error_log("VMRequest Fehler: Camunda-Webhook fehlgeschlagen für Anfrage-ID {$data->id}");
        return false;
    } 
    
    else {
        error_log("VMRequest Info: Camunda-Webhook erfolgreich gesendet für Anfrage-ID {$data->id}");

        // Gemeinsame VM-Daten
        $instance = new stdClass();
        $instance->requestid       = $data->id;
        $instance->uuid            = $uuid;
        $instance->timecreated     = time();
        $instance->vmname          = $final_vmname;
        $instance->vmos            = $imageId;
        $instance->vmsize          = $flavorId;
        $instance->status          = 'pending';
        $instance->guac_client_id  = null;
        $instance->useremail       = $useremail;

        // ========= ALT: Lokale Speicherung =========
        $instanceid = $DB->insert_record('vmrequest_instance', $instance);
        $DB->set_field('vmrequest_request', 'instanceid', $instanceid, ['id' => $data->id]);

        // ========= NEU: Externe Speicherung =========
        $api_data = [
            'requestid'       => $data->id,
            'uuid'            => $uuid,
            'timecreated'     => $instance->timecreated,
            'vmname'          => $final_vmname,
            'vmos'            => $imageId,
            'vmsize'          => $flavorId,
            'status'          => 'pending',
            'guac_client_id'  => null,
            'useremail'       => $useremail
        ];

        // Eintrag in externer Tabelle erstellen
        $created_instance = vm_insert_into_external_db('mi_vmrequest_instance', $api_data);

        // Prüfen ob erfolgreich und ID zurückgegeben wurde
        if ($created_instance && !empty($created_instance->id)) {
            // instanceid im zugehörigen Antrag setzen
            vm_external_api_update_record('mi_vmrequest_request', (int)$data->id, [
                'instanceid' => (int)$created_instance->id
            ]);
        } else {
            debugging("Externe Instanz konnte nicht erstellt oder ID nicht ermittelt werden.", DEBUG_DEVELOPER);
        }
    }


    return true;
}


/**
 * Zenrale Methode um Datensätze aus der externen SWIM-API abzurufen.
 *
 * @param string $table Name der Tabelle, z.B. 'mi_vmrequest...'
 * @param array $filters Optionales Array von Spaltennamen => Werte (als Query-Parameter)
 * @return array|null Array mit Objekten bei Erfolg, null bei Fehler
 */
function vm_external_api_get_records(string $table, array $filters = []): array {
    $url = "http://swim-api.swimdhbw.de/api/data/{$table}";

    $options = [
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\n"
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        debugging("Fehler beim Abrufen der Daten aus externer DB ({$table})", DEBUG_DEVELOPER);
        return [];
    }

    $decoded = json_decode($response);

    if (!isset($decoded->data) || !is_array($decoded->data)) {
        debugging("Unerwartetes Format der API-Antwort für {$table}", DEBUG_DEVELOPER);
        return [];
    }

    // Fallback: lokale Filterung, falls API keine unterstützt
    $filtered = array_filter($decoded->data, function($record) use ($filters) {
        foreach ($filters as $key => $value) {
            if (!property_exists($record, $key) || $record->$key != $value) {
                return false;
            }
        }
        return true;
    });

    return array_values($filtered);
}



/**
 * Holt genau einen Datensatz aus der externen SWIM-API.
 *
 * @param string $table Tabellenname (z. B. 'mi_vmrequest_instance')
 * @param array $filters Spaltenname => Wert (z. B. ['id' => 123])
 * @return stdClass|null Der Datensatz als Objekt oder null
 */
function vm_external_api_get_record(string $table, array $filters): ?stdClass {
    $records = vm_external_api_get_records($table, $filters);
    if (empty($records)) {
        return null;
    }
    // Es wird der erste Treffer zurückgegeben (wie bei get_record())
    return reset($records);
}

function vm_external_api_update_record(string $table, int $id, array $fields): bool {
    $url = "http://swim-api.swimdhbw.de/api/data/{$table}/{$id}";

    $options = [
        'http' => [
            'method'  => 'PUT', // Geändert von PATCH zu PUT
            'header'  => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'content' => json_encode($fields),
            'ignore_errors' => true
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        $error = error_get_last();
        debugging("Fehler beim Aktualisieren von {$table} ID {$id}: " . ($error ? $error['message'] : 'Unbekannter Fehler'), DEBUG_DEVELOPER);
        return false;
    }

    $status_line = $http_response_header[0] ?? '';
    preg_match('/HTTP\/\d+\.\d+\s+(\d+)/', $status_line, $matches);
    $status_code = isset($matches[1]) ? (int)$matches[1] : 0;


    if ($status_code >= 200 && $status_code < 300) {
        return true;
    } else {
        debugging("Fehlerhafter Statuscode beim Aktualisieren von {$table} ID {$id}: $http_response - Response: " . ($response ?: 'Keine Daten'), DEBUG_DEVELOPER);
        return false;
    }
}

/**
 * Ruft einen Datensatz aus der externen Datenbank über die API ab, mit clientseitiger Filterung.
 * @param string $table Tabellenname (z. B. 'mi_vmrequest_request')
 * @param int $id ID des Datensatzes
 * @return ?object Der Datensatz oder null bei Fehlschlag
 * @throws moodle_exception Bei ungültiger API-Antwort
 */
function vm_external_api_get_record_by_id(string $table, int $id): ?object {
    $url = "http://swim-api.swimdhbw.de/api/data/{$table}";

    $options = [
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\n"
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        debugging("Fehler beim Abrufen von {$table} mit ID {$id}", DEBUG_DEVELOPER);
        return null;
    }

    $decoded = json_decode($response);

    if (!isset($decoded->data) || !is_array($decoded->data)) {
        debugging("Ungültige API-Antwort für {$table} ID {$id}", DEBUG_DEVELOPER);
        return null;
    }

    // Clientseitige Filterung nach ID
    $filtered = array_filter($decoded->data, function($record) use ($id) {
        return property_exists($record, 'id') && (int)$record->id === $id;
    });

    $filtered = array_values($filtered);
    return !empty($filtered) ? (object) $filtered[0] : null;
}


function vm_external_api_delete_record(string $table, int $id): bool {
    $url = "http://swim-api.swimdhbw.de/api/data/{$table}/{$id}";

    $options = [
        'http' => [
            'method' => 'DELETE',
            'header' => "Accept: application/json\r\n"
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        debugging("Fehler beim Löschen von {$table} mit ID {$id}", DEBUG_DEVELOPER);
        return false;
    }

    return true;
}





