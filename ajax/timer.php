<?php

include ("../../../inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST["action"])) {

   $task_id=$_POST["task_id"];
   switch ($_POST["action"]) {
      case 'start':
         if (PluginActualtimeTask::checkTimerActive($task_id)) {

            // action=start, timer=on
            $result=[
               'mensage' => __("A user is already performing the task", 'actualtime'),
               'title'   => __('Warning'),
               'class'   => 'warn_msg',
            ];

         } else {

            // action=start, timer=off
            if (! PluginActualtimeTask::checkUserFree(Session::getLoginUserID())) {

               // action=start, timer=off, current user is alerady using timer
               $opcional=PluginActualtimeTask::getTicket(Session::getLoginUserID());
               $result=[
                  'mensage' => __("You are already doing a task", 'actualtime')." <a onclick='showtaskform(event)' href='/front/ticket.form.php?id=".$opcional."'>".__("Ticket")."</a>",
                  'title'   => __('Warning'),
                  'class'   => 'warn_msg',
               ];

            } else {

               // action=start, timer=off, current user is free
               $DB->insert(
                  'glpi_plugin_actualtime_tasks', [
                     'tasks_id'     => $task_id,
                     'actual_begin' => date("Y-m-d H:i:s"),
                     'users_id'     => Session::getLoginUserID(),
                  ]
               );
               $result=[
                  'mensage' => __("Timer started", 'actualtime'),
                  'title'   => __('Information'),
                  'class'   => 'info_msg',
               ];

            }
         }
         echo json_encode($result);
         break;

      case 'end':
      case 'pause':
         if (PluginActualtimeTask::checkTimerActive($task_id)) {

            // action=end or pause, timer=on
            if (PluginActualtimeTask::checkUser($task_id, Session::getLoginUserID())) {

               // action=end or pause, timer=on, timer started by current user
               $actual_begin=PluginActualtimeTask::getActualBegin($task_id);
               $seconds=(strtotime(date("Y-m-d H:i:s"))-strtotime($actual_begin));
               $DB->update(
                  'glpi_plugin_actualtime_tasks', [
                     'actual_end'        => date("Y-m-d H:i:s"),
                     'actual_actiontime' => $seconds,
                  ], [
                     'tasks_id' => $task_id,
                     [
                        'NOT' => ['actual_begin' => null],
                     ],
                     'actual_end' => null,
                  ]
               );
               if ($_POST['action']=='end') {
                  $DB->update(
                     'glpi_tickettasks', [
                        'state' => 2,
                     ], [
                        'id' => $task_id,
                     ]
                  );
               }
               $result=[
                  'mensage' => __("Timer completed", 'actualtime'),
                  'title'   => __('Information'),
                  'class'   => 'info_msg',
                  'html' => PluginActualtimeTask::getSegment($task_id),
                  'time' => abs(PluginActualtimeTask::totalEndTime($task_id)),
               ];

            } else {

               // action=end or pause, timer=on, timer started by other user
               $result=[
                  'mensage' => __("Only the user who initiated the task can close it", 'actualtime'),
                  'title'   => __('Warning'),
                  'class'   => 'warn_msg',
               ];

            }

         } else {

            // action=end or pause, timer=off
            if ($_POST['action']=='pause') {

               // action=pause, timer=off
               $result=[
                  'mensage' => __("The task had not been initialized", 'actualtime'),
                  'title'   => __('Warning'),
                  'class'   => 'warn_msg',
               ];
            } else {

               // action=end, timer=off
               $DB->update(
                  'glpi_tickettasks', [
                     'state' => 2,
                  ], [
                     'id' => $task_id,
                  ]
               );
               $result=[
                  'mensage'=>__("Timer completed", 'actualtime'),
                  'title' => __('Information'),
                  'class' => 'info_msg',
                  'html' => PluginActualtimeTask::getSegment($task_id),
                  'time' => abs(PluginActualtimeTask::totalEndTime($task_id)),
               ];

            }
         }
         echo json_encode($result);
         break;

      case 'count':
         echo abs(PluginActualtimeTask::totalEndTime($task_id));
         break;
   }

} else if (isset($_GET["footer"])) {

   // For timer popup windows (called by atualtime.js)
   global $CFG_GLPI;
   // Base function for all general stuff in javascript
   // Translations
   $result = [];
   $result['rand'] = mt_rand();
   //TRANS: d is a symbol for days in a time (displays: 3d)
   $result['symb_d'] = __("%dd", "actualtime");
   $result['symb_day'] = _n("%d day", "%d days", 1);
   $result['symb_days'] = _n("%d day", "%d days", 2);
   //TRANS: h is a symbol for hours in a time (displays: 3h)
   $result['symb_h'] = __("%dh", "actualtime");
   $result['symb_hour'] = _n("%d hour", "%d hours", 1);
   $result['symb_hours'] = _n("%d hour", "%d hours", 2);
   //TRANS: min is a symbol for minutes in a time (displays: 3min)
   $result['symb_min'] = __("%dmin", "actualtime");
   $result['symb_minute'] = _n("%d minute", "%d minutes", 1);
   $result['symb_minutes'] = _n("%d minute", "%d minutes", 2);
   //TRANS: s is a symbol for seconds in a time (displays: 3s)
   $result['symb_s'] = __("%ds", "actualtime");
   $result['symb_second'] = _n("%d second", "%d seconds", 1);
   $result['symb_seconds'] = _n("%d second", "%d seconds", 2);
   // Current user active task. Data to timer popup
   $config = new PluginActualtimeConfig;
   if ($config->showTimerPopup()) {
      $task_id = PluginActualtimeTask::getTask(Session::getLoginUserID());
      if ($task_id) {
         $result['warning'] = __('Warning');
         $result['task_id'] = $task_id;
         $result['ticket_id'] = PluginActualtimetask::getTicket(Session::getLoginUserID());
         $result['time'] = abs(PluginActualtimeTask::totalEndTime($task_id));
         $result['div'] = "<div id='actualtime_timer{$result['rand']}'>" . __("Timer started on", 'actualtime') . " <a onclick='showtaskform(event)' href='{$CFG_GLPI['root_doc']}/front/ticket.form.php?id={$result['ticket_id']}'>" . __("Ticket") . " {$result['ticket_id']}</a> -> <span></span></div>";
      }
   }
   echo json_encode($result);

} else {

   // For modal windows
   $parts = parse_url($_SERVER['REQUEST_URI']);
   parse_str($parts['query'], $query);
   if (isset($query['showform'])) {
      $task_id=PluginActualtimeTask::getTask(Session::getLoginUserID());
      $rand = mt_rand();
      $options = [
         'from_planning_edit_ajax' => true,
         'formoptions'             => "id='edit_event_form$rand'"
      ];
      $options['parent'] = getItemForItemtype("Ticket");
      $options['parent']->getFromDB(PluginActualtimeTask::getTicket(Session::getLoginUserID()));
      $item = getItemForItemtype("TicketTask");
      $item->showForm($task_id, $options);
   }

}
