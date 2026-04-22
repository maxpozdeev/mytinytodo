<?php
  if (!defined('MTTPATH')) die("Unexpected usage.");
  header("Content-type: text/html; charset=utf-8");
?>
<!doctype html>
<html data-appearance="<?php mttinfo('appearance'); ?>">
<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <title><?php mttinfo('title'); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/gif" href="<?php mttinfo('theme_url'); ?>images/logo.gif">
  <link rel="apple-touch-icon" href="<?php mttinfo('theme_url'); ?>images/apple-touch-icon.png" sizes="180x180">
  <link rel="stylesheet" type="text/css" href="<?php mttinfo('theme_url'); ?>style.css?v=<?php filever('theme', 'style.css'); ?>" media="all">
  <link rel="stylesheet" type="text/css" href="<?php mttinfo('theme_url'); ?>markdown.css?v=<?php filever('theme', 'markdown.css'); ?>" media="all">
  <?php if (get_mttinfo('appearance') == 'system'): ?>
  <link id="link_css_dark" rel="stylesheet" type="text/css" href="<?php mttinfo('theme_url'); ?>dark.css?v=<?php filever('theme', 'dark.css'); ?>" media="screen and (prefers-color-scheme:dark)">
  <?php elseif (get_mttinfo('appearance') == 'dark'): ?>
  <link id="link_css_dark" rel="stylesheet" type="text/css" href="<?php mttinfo('theme_url'); ?>dark.css?v=<?php filever('theme', 'dark.css'); ?>" media="screen">
  <?php endif; ?>
  <link rel="stylesheet" type="text/css" href="<?php mttinfo('theme_url'); ?>print.css?v=<?php filever('theme', 'print.css'); ?>" media="print">
  <?php if(Config::get('rtl')): ?>
  <link rel="stylesheet" type="text/css" href="<?php mttinfo('theme_url'); ?>style_rtl.css?v=<?php filever('theme', 'style_rtl.css'); ?>" media="all">
  <?php endif; ?>
  <?php do_action('theme_head_end'); ?>
</head>

<body <?php if (Lang::instance()->rtl()) echo 'dir="rtl"'; ?>>

<script type="text/javascript" src="<?php mttinfo('content_url'); ?>js/jquery.min.js?v=3.7.1"></script>
<script type="text/javascript" src="<?php mttinfo('content_url'); ?>js/jquery-ui.min.js?v=1.14.1"></script>
<script type="text/javascript" src="<?php mttinfo('content_url'); ?>js/jquery.ui.touch-punch.js?v=1.1.5-2"></script>
<script type="text/javascript" src="<?php mttinfo('content_url'); ?>mytinytodo.js?v=<?php filever('content', 'mytinytodo.js'); ?>"></script>
<script type="text/javascript" src="<?php mttinfo('content_url'); ?>mytinytodo_api.js?v=<?php filever('content', 'mytinytodo_api.js'); ?>"></script>

<?php do_action('theme_scripts'); ?>


<script type="text/javascript">
$().ready(function(){
  mytinytodo.init(<?php js_options(); ?>).setApiDriver(MytinytodoAjaxApi);
});
</script>


<div id="mtt">

<!-- Top block -->
<div id="topblock">

  <a class="logo" href="<?php mttinfo('url'); ?>"></a>

  <h2><?php mttinfo('title'); ?></h2>

  <div class="topblock-bar">
    <div id="msg"><span class="msg-text"></span><div class="msg-details"></div></div>
    <div class="bar-menu">
      <span id="bar_public" style="display:none" class="mtt-need-auth-enabled"><?php _e('public_tasks');?></span>
    <?php if (is_logged()): ?>
      <span id="usermenu" class="mtt-menu-button"><?php mttinfo('username') ?> <span class="arrdown2"></span></span>
    <?php else: ?>
      <a href="<?php mtturl('login'); ?>"  id="login_btn"  class="mtt-need-auth-enabled"><?php _e('a_login');?></a>
    <?php endif; ?>
    </div>
  </div>

</div>
<!-- End of Top block -->

<div id="usermenucontainer" class="mtt-menu-container" style="display:none">
<ul>
  <li><a href="<?php mttinfo('tasks_uri'); ?>"><?php _e('tasks');?></a></li>
  <li><a href="<?php mtturl('settings/general'); ?>"><?php _e('a_settings');?></a></li>
  <?php if (is_admin()) { ?>
    <li class="mtt-menu-delimiter"></li>
    <li><a href="<?php mtturl('controlpanel/general'); ?>"><?php _e('a_controlpanel');?></a></li>
  <?php } ?>
  <li class="mtt-menu-delimiter"></li>
  <li id="usermenu--logout"><a href="#logout"><?php _e('a_logout');?></a></li>
</ul>
</div>
