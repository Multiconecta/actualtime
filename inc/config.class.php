<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginActualtimeConfig
 */
class PluginActualtimeConfig extends CommonDBTM {

   static $rightname = 'config';
   static private $_config = null;

   /**
    * @param bool $update
    *
    * @return PluginActualtimeConfig
    */
   static function getConfig($update = false) {

      if (!isset(self::$_config)) {
         self::$_config = new self();
      }
      if ($update) {
         self::$_config->getFromDB(1);
      }
      return self::$_config;
   }

   /**
    * PluginActualtimeConfig constructor.
    */
   function __construct() {
      global $DB;

      if ($DB->tableExists($this->getTable())) {
         $this->getFromDB(1);
      }
   }

   static function canCreate() {
      return Session::haveRight('config', UPDATE);
   }


   static function canView() {
      return Session::haveRight('config', READ);
   }

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {
      return __("Task timer configuration", "actualtime");
   }

   function showForm() {

      $rand = mt_rand();

      $this->getFromDB(1);
      $this->showFormHeader();

      echo "<input type='hidden' name='id' value='1'>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __("Enable timer on tasks", "actualtime") . "</td><td>";
      Dropdown::showYesNo('enable', $this->isEnabled(), -1,
                          ['on_change' => 'show_hide_options(this.value);']);
      echo "</td>";
      echo "</tr>";

      echo Html::scriptBlock("
         function show_hide_options(val) {
            var display = (val == 0) ? 'none' : '';
            $('tr[name=\"optional$rand\"').css( 'display', display );
         }");

      $style = ($this->isEnabled()) ? "" : "style='display: none '";

      // Include lines with other settings

      echo "<tr class='tab_bg_1' name='optional$rand' $style>";
      echo "<td>" . __("Display pop-up window with current running timer", "actualtime") . "</td><td>";
      Dropdown::showYesNo('showtimerpopup', $this->showTimerPopup(), -1);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1' name='optional$rand' $style>";
      echo "<td>" . __("Display actual time in closed task box ('Processing ticket' list)", "actualtime") . "</td><td>";
      Dropdown::showYesNo('showtimerinbox', $this->showTimerInBox(), -1);
      echo "</td>";
      echo "</tr>";

      $values = [
         0 => __('For all users', 'actualtime'),
         1 => __('Only for task\'s assigned user', 'actualtime'),
      ];
      echo "<tr class='tab_bg_1' name='optional$rand' $style>";
      echo "<td>" . __("Display actual time information on task forms", "actualtime") . "</td><td>";
      Dropdown::showFromArray(
         'displayinfofor',
         $values,
         [
            'value' => $this->fields['displayinfofor']
         ]
      );
      echo "</td>";
      echo "</tr>";

      //echo "<tr class='tab_bg_1' name='optional$rand' $style>
      //   <td>example settings 2";
      //echo "</td>";
      //echo "</tr>";

      echo "<tr class='tab_bg_1' align='center'>";

      $this->showFormButtons(['candel'=>false]);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType()=='Config') {
            return __("Actual time", "actualtime");
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType()=='Config') {
         $instance = self::getConfig();
         $instance->showForm();
      }
      return true;
   }

   /**
    * Is plugin enabled in plugin settings?
    *
    * @return boolean
    */
   function isEnabled() {
      return ($this->fields['enable'] ? true : false);
   }

   /**
    * Is displaying timer pop-up on every page enabled in plugin settings?
    *
    * @return boolean
    */
   function showTimerPopup() {
      return ($this->fields['showtimerpopup'] ? true : false);
   }

   /**
    * Is timer shown in closed task box at 'Actions historical' page?
    *
    * @return boolean
    */
   function showTimerInBox() {
      return ($this->fields['showtimerinbox'] ? true : false);
   }

   /**
    * Is actual time information (timers) shown to every user?
    *
    * @return boolean
    */
   function displayInfoForEveryone() {
      return ($this->fields['displayinfofor'] == 0);
   }

   static function install(Migration $migration) {
      global $DB;

      $table = self::getTable();
      if (! $DB->tableExists($table)) {
         $migration->displayMessage("Installing $table");

         $query = "CREATE TABLE IF NOT EXISTS $table (
                      `id` int(11) NOT NULL auto_increment,
                      `enable` boolean NOT NULL DEFAULT true,
                      PRIMARY KEY (`id`)
                   )
                   ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->query($query) or die($DB->error());
      }

      if ($DB->tableExists($table)) {

         // Create default record (if it does not exist)
         $reg = $DB->request($table);
         if (! count($reg)) {
            $DB->insert(
               $table, [
                  'enable' => 1
               ]
            );
         }

         $migration->addField(
            $table,
            'showtimerpopup',
            'boolean',
            [
               'update' => 1,
               'value'  => 1,
               'after' => 'enable'
            ]
         );

         $migration->addField(
            $table,
            'showtimerinbox',
            'boolean',
            [
               'update' => 1,
               'value'  => 1,
               'after' => 'showtimerpopup'
            ]
         );

         // For whom the actualtime timers are displayed?
         // 0 - All users
         // 1 - Only user assigned to the task
         $migration->addField(
            $table,
            'displayinfofor',
            'smallint',
            [
               'update' => 1,
               'value'  => 1,
               'after' => 'showtimerinbox'
            ]
         );

      }

   }

   static function uninstall(Migration $migration) {
      global $DB;

      $table = self::getTable();
      if ($DB->TableExists($table)) {
         $migration->displayMessage("Uninstalling $table");
         $migration->dropTable($table);
      }
   }
}
