<?php
   require_once($CFG->dirroot . '/mod/vmrequest/forms/vm_form.php');

   $requests = vmrequest_get_requests_by_user($cm->id, $USER->id);
   echo $OUTPUT->heading('Ihre bisherigen Anträge');

   if (!empty($requests)) {
       echo '<div class="row">';
       foreach ($requests as $r) {
           $submitter = $DB->get_record('user', ['id' => $r->submitter], 'id, firstname, lastname, email');
           $supervisor = $DB->get_record('user', ['id' => $r->supervisorid], 'id, firstname, lastname, email');
           $submitterinfo = $submitter ? fullname($submitter, true) . ' (' . $submitter->email . ')' : 'Unbekannt';
           $supervisorinfo = $supervisor ? fullname($supervisor, true) . ' (' . $supervisor->email . ')' : 'Kurs Verantwortliche';

           echo '<div class="col-md-6 mb-4">';
           echo '<div class="card h-100">';
           echo '<div class="card-header">';
           echo format_string($r->vmname) . ' <span class="badge badge-secondary ml-2">' . format_string($r->status) . '</span>';
           echo '</div>';
           echo '<div class="card-body">';
           echo html_writer::tag('p', '<strong>'.get_string('vmsize','vmrequest').':</strong> '. format_string($r->vmsize));
           echo html_writer::tag('p', '<strong>'.get_string('vmos','vmrequest').':</strong> '. format_string($r->vmos));
           echo html_writer::tag('p', '<strong>'.get_string('justification','vmrequest').':</strong> '. format_text($r->justification, FORMAT_HTML));
           echo html_writer::tag('p', '<strong>Beantragt von:</strong> ' . format_string($submitterinfo));
           echo html_writer::tag('p', '<strong>Genehmigende Person:</strong> ' . format_string($supervisorinfo));
           if (!empty($r->denialreason)) {
               echo html_writer::tag('p', '<strong>'.get_string('denialreason','vmrequest').':</strong> '. format_text($r->denialreason, FORMAT_HTML));
           }
           if ($r->status !== 'genehmigt') {
               $editurl = new moodle_url('/mod/vmrequest/edit_request.php', [
                   'id' => $cm->id,
                   'requestid' => $r->id
               ]);
               echo html_writer::link($editurl, 'Bearbeiten', ['class' => 'btn btn-primary mt-3']);
           }
           if (!empty($r->instanceid)) {
               //$instance = $DB->get_record('vmrequest_instance', ['id' => $r->instanceid]);
                $instance = vm_external_api_get_record_by_id('mi_vmrequest_instance', $r->instanceid);
               if ($instance) {
                   $guacurl = "https://guacamole.swimdhbw.de";
                   echo html_writer::start_div('text-center mt-4');
                   echo html_writer::tag('a', 'Virtuelle Maschine öffnen', [
                       'href' => $guacurl,
                       'target' => '_blank',
                       'class' => 'btn btn-success'
                   ]);
                   echo html_writer::end_div();
               } elseif ($instance) {
                   echo html_writer::div("Die VM ist erzeugt, aber noch nicht mit Guacamole verknüpft.", 'alert alert-warning mt-3');
               }
           } else {
               echo html_writer::div("Für diesen Antrag wurde noch keine VM erzeugt.", 'alert alert-info mt-3');
           }
           echo '</div>';
           echo '</div>';
           echo '</div>';
       }
       echo '</div>';
   }

   $backurl = new moodle_url('/mod/vmrequest/view.php', ['id' => $id]);
   echo html_writer::link($backurl, 'Zurück', ['class' => 'btn btn-secondary mb-3']);

   if ($studentrequests_enabled || $isdozent || $general_supervision_enabled) {
       $url = new moodle_url('/mod/vmrequest/view.php', [
           'id'     => $cm->id,
           'action' => 'apply'
       ]);
       $form = new vmrequest_form($url, ['courseid' => $courseid, 'cmid' => $cm->id, 'supervision' => $general_supervision_enabled]);

       if ($form->is_cancelled()) {
           redirect($url);
       } elseif ($data = $form->get_data()) {
           $supervisorid = $data->supervisorid ?? 0;
           $status = ($data->vmsize === 'large') ? 'waiting_admin_approval' : 'beantragt';
           vmrequest_insert_request($data);
           redirect($url, get_string('requestsubmitted','vmrequest'));
       }
       echo $OUTPUT->heading('Neuen VM-Antrag stellen');
       echo '<div class="card mb-4" style="max-width:600px;">';
       echo '<div class="card-body">';
       $form->display();
       echo '</div>';
       echo '</div>';
       exit;
   }
   ?>