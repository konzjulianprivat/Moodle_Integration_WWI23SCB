<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_vmrequest_mod_form extends moodleform_mod {
    public function definition() {
        $mform = $this->_form;

        // Konfiguration: z.B. Hinweistext
        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // In mod/vmrequest/mod_form.php, innerhalb definition()
        $mform->addElement('advcheckbox',
            'config_studentrequests',
            get_string('config_studentrequests', 'vmrequest'),
            get_string('config_studentrequests_desc', 'vmrequest')
        );
        $mform->setDefault('config_studentrequests', 1); // Standard: erlaubt

        $mform->addElement('advcheckbox', 'is_general_supervision', 
            get_string('is_general_supervision', 'vmrequest'),
            get_string('is_general_supervision_desc', 'vmrequest'));
        
        $mform->setDefault('is_general_supervision', 0);
        $mform->addHelpButton('is_general_supervision', 'is_general_supervision', 'vmrequest');



        $this->standard_intro_elements();
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
