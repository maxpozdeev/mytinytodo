<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2011,2020-2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

if (!defined('MTT_PAGE')) {
    die("Unexpected usage");
}

?>

<div id="page_settings">

<h3 class="page-title"><?php _e('a_controlpanel');?></h3>

<div id="settings_msg" style="display:none"></div>

<div id="settings_container">

<div id="settings_menu">
  <p><a href="<?php mtturl('controlpanel/general'); ?>"><?php _e('set_general');?></a></p>
  <p><a href="<?php mtturl('controlpanel/css'); ?>"><?php _e('set_customcss');?></a></p>
  <p><a href="<?php mtturl('controlpanel/backup'); ?>"><?php _e('set_backup');?></a></p>
  <p><a href="<?php mtturl('controlpanel/extensions'); ?>"><?php _e('set_extensions');?></a></p>
</div>

  <div id="settings_content">
<?php
    if (isset(MTTVars::$settingsPageFile)) {
        require_once(MTTVars::$settingsPageFile);
    }
    else {
        echo "Content not found";
    }
?>
  </div>

</div>

</div>
