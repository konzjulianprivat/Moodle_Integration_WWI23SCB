<?php
   echo $OUTPUT->heading('Anträge verwalten');

   // Tabs: Offen / Archiviert
   $tabs = [
       new moodle_url('/mod/vmrequest/view.php', ['id'=>$id,'action'=>'manage','showarchived'=>0]),
       new moodle_url('/mod/vmrequest/view.php', ['id'=>$id,'action'=>'manage','showarchived'=>1]),
   ];
   $tablabels = ['Offene Anträge', 'Archivierte Anträge'];
   echo html_writer::start_div('nav nav-tabs mb-3');
   foreach ($tabs as $index => $url) {
       $active = ($showarchived == $index) ? 'font-weight-bold text-dark' : 'text-muted';
       echo html_writer::link($url, $tablabels[$index], ['class'=>'mr-3 '.$active]);
   }
   echo html_writer::end_div();

   // Anträge laden
   $requests = vmrequest_get_requests_for_manage($cm->id, $showarchived, $general_supervision_enabled);

   // Tabelle erzeugen
   echo html_writer::start_div('table-responsive');
   echo html_writer::start_tag('table', ['class'=>'generaltable table-striped','width'=>'100%']);
   echo html_writer::tag('tr',
       html_writer::tag('th','Student') .
       html_writer::tag('th','E-Mail') .
       html_writer::tag('th','VM-Name') .
       html_writer::tag('th','Größe') .
       html_writer::tag('th','OS') .
       html_writer::tag('th','Status') .
       html_writer::tag('th','Aktion') .
       html_writer::tag('th','Details')
   );

   foreach ($requests as $r) {
       $user = core_user::get_user($r->userid);
       $approveurl = new moodle_url('/mod/vmrequest/actions/approve.php', [
           'id'        => $cm->id,
           'requestid' => $r->id
       ]);
       $denyurl = new moodle_url('/mod/vmrequest/actions/deny.php', [
           'id'        => $cm->id,
           'requestid' => $r->id
       ]);
       $detaillink = new moodle_url('/mod/vmrequest/viewrequest.php', [
            'id'        => $cm->id,
            'requestid' => $r->id
       ]);

       echo html_writer::tag('tr',
           html_writer::tag('td', fullname($user, true)) . // Änderung hier
           html_writer::tag('td', $user->email) .
           html_writer::tag('td', format_string($r->vmname)) .
           html_writer::tag('td', format_string($r->vmsize)) .
           html_writer::tag('td', format_string($r->vmos)) .
           html_writer::tag('td', format_string($r->status)) .
           html_writer::tag('td',
               html_writer::link($approveurl, 'Genehmigen') . ' | ' .
               html_writer::link($denyurl,    'Ablehnen')
           ) .
           html_writer::tag('td',
               html_writer::link($detaillink,'Details anzeigen')
           )
       );
   }

   echo html_writer::end_tag('table');
   echo html_writer::end_div(); // .table-responsive

   $backurl = new moodle_url('/mod/vmrequest/view.php', ['id' => $id]);
   echo html_writer::link($backurl, 'Zurück', ['class' => 'btn btn-secondary mb-3']);
   echo $OUTPUT->footer();
   exit;
   ?>