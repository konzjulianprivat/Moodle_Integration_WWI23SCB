<?php
require_once(__DIR__ . '/../../config.php'); // Moodle-Konfiguration laden
require_once(__DIR__ . '/lib.php'); // Plugin-Bibliothek mit der Funktion
$pdo = vm_get_external_pg_connection();
if ($pdo) {
    echo "Verbindung erfolgreich!";
} else {
    echo "Verbindung fehlgeschlagen.";
}
