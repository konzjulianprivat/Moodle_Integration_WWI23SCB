<?php
// Datei: vm_form.php
// Eigenständiges Formular für VM-Anträge (Studenten / Dozenten im „Beantragen“-Modus).

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class vmrequest_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];

        // Überschrift (optional)
        $mform->addElement('header', 'general', get_string('general', 'form'));

        /// VM-Name (Textfeld)
        $mform->addElement('text', 'vmname', get_string('vmname', 'vmrequest'), 'maxlength="255" size="64"');
        $mform->setType('vmname', PARAM_TEXT);
        $mform->addRule('vmname', null, 'required', null, 'client');
        $mform->addHelpButton('vmname', 'vmname', 'vmrequest'); // ← Tooltip mit Erklärung
        $mform->addElement('static', 'hinttext', '',
                    '<small><em>Beispiel: "DB_Projekt_2025"</em></small>');

        // VM-Größe (Dropdown)
        $sizeoptions = [
            'tiny'   => get_string('vmsize_tiny', 'vmrequest'),
            'medium' => get_string('vmsize_medium', 'vmrequest'),
            'large'  => get_string('vmsize_large', 'vmrequest'),
        ];
        // VM-Größe
        $mform->addElement('select', 'vmsize', get_string('vmsize', 'vmrequest'), $sizeoptions);
        $mform->setDefault('vmsize', 'medium');
        $mform->setType('vmsize', PARAM_TEXT);
         // Hinweis-Element (zunächst versteckt)
        $mform->addElement('static', 'confignote', '',
            html_writer::tag('div',
                'Hinweis: Wenn Sie die größte VM-Größe auswählen, muss der Antrag zunächst vom Admin genehmigt werden.',
                ['id' => 'specialConfigNote', 'style' => 'display:none; color: red; font-weight: bold;']
            )
        );

        // Betriebssystem (Dropdown)
        $osos = [
            'ubuntu'  => get_string('vmos_ubuntu', 'vmrequest'),
            'windows' => get_string('vmos_windows', 'vmrequest'),
        ];
        // OS-Auswahl
        $mform->addElement('select', 'vmos', get_string('vmos', 'vmrequest'), $osos);
        $mform->setDefault('vmos', 'ubuntu');
        $mform->setType('vmos', PARAM_TEXT);
        $mform->addHelpButton('vmos', 'vmos', 'vmrequest');


        // Begründung (Textarea)
        $mform->addElement('textarea', 'justification', get_string('justification', 'vmrequest'),
            'wrap="virtual" rows="5" cols="60"');
        $mform->setType('justification', PARAM_TEXT);
        $mform->addRule('justification', null, 'required', null, 'client');
        $mform->addHelpButton('justification', 'justification', 'vmrequest');
        $mform->addElement('static', 'hinttext', '',
            '<small><em>Beispiel: "Ich benötige die VM zur Durchführung von SQL-Tests in einem Gruppenprojekt."</em></small>');



        $supervision = $this->_customdata['supervision'] ?? 0;

        if ($supervision == 1) { // Nur wenn in der Modulkonfiguration aktiv
            $courseid = $this->_customdata['courseid'];
            global $USER;

            $supervisors = vmrequest_get_possible_supervisors($courseid, $USER->id);

            if (!empty($supervisors)) {
                $mform->addElement('select', 'supervisorid', 'Betreuer auswählen', $supervisors);
                $mform->addRule('supervisorid', 'Pflichtfeld', 'required');
                $mform->setType('supervisorid', PARAM_INT);
            } else {
                $mform->addElement('static', 'nosupervisors', '', 'Keine Betreuer gefunden.');
            }
        }

        $mform->addElement('html', '
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const vmsizeField = document.querySelector("select[name=\'vmsize\']");
                    const note = document.getElementById("specialConfigNote");
                    function toggleNote() {
                        if (vmsizeField.value === "large") {
                            note.style.display = "block";
                        } else {
                            note.style.display = "none";
                        }
                    }
                    vmsizeField.addEventListener("change", toggleNote);
                    toggleNote(); // Initial prüfen
                });
            </script>
        ');

        // Verstecktes Feld: coursemodule (ID des Moduls), um später in der Datenbank gespeichert zu werden
        // Der Konstruktor von view.php übergibt in customdata ['cmid' => $cm->id]
        if (!empty($this->_customdata['cmid'])) {
            $mform->addElement('hidden', 'coursemodule', $this->_customdata['cmid']);
            $mform->setType('coursemodule', PARAM_INT);
        }

        // Buttons Speichern / Abbrechen
        $editing = $this->_customdata['editing'] ?? false;
        $this->add_action_buttons(true, $editing ? 'Änderungen speichern' : get_string('submitrequest', 'vmrequest'));

    }
}
