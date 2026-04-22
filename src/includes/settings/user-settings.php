<?php

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2026 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

if (!defined('MTT_PAGE')) {
    die("Unexpected usage");
}

?>

<div id="page_settings">

<h3 class="page-title"><?php _e('a_settings');?></h3>

<div id="settings_container">

  <div id="settings_menu">
    <p><a href="<?php mtturl('settings/general'); ?>"><?php _e('set_general');?></a></p>
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
