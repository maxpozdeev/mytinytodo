<?php header("Content-type: text/html; charset=utf-8"); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php mttinfo('title'); ?></title>
<link rel="stylesheet" type="text/css" href="<?php mttinfo('template_url'); ?>style.css?v=<?php mttinfo('version'); ?>" media="all" />
<link rel="stylesheet" type="text/css" href="<?php mttinfo('template_url'); ?>print.css?v=<?php mttinfo('version'); ?>" media="print" />
<?php if(Config::get('rtl')): ?>
<link rel="stylesheet" type="text/css" href="<?php mttinfo('template_url'); ?>style_rtl.css?v=<?php mttinfo('version'); ?>" media="all" />
<?php endif; ?>
<?php if(Config::get('mobile')): ?>
<meta name="viewport" id="viewport" content="width=device-width" />
<link rel="stylesheet" type="text/css" href="<?php mttinfo('template_url'); ?>mobile.css?v=<?php mttinfo('version'); ?>" media="all" />
<?php endif; ?>
</head>

<body <?php if (Lang::instance()->rtl()) echo 'dir="rtl"'; ?>>

<script type="text/javascript" src="<?php mttinfo('includes_url'); ?>jquery/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="<?php mttinfo('includes_url'); ?>jquery/jquery-ui-1.12.1.min.js"></script>
<script type="text/javascript" src="<?php mttinfo('includes_url'); ?>mytinytodo.js?v=<?php mttinfo('version'); ?>"></script>
<script type="text/javascript" src="<?php mttinfo('includes_url'); ?>mytinytodo_ajax_storage.js?v=<?php mttinfo('version'); ?>"></script>
<?php if(Config::get('mobile')): ?>
<script type="text/javascript" src="<?php mttinfo('includes_url'); ?>jquery/jquery.ui.touch-punch.js"></script>
<?php endif; ?>

<script type="text/javascript">
$().ready(function(){
	mytinytodo.init({
		token: "<?php echo htmlspecialchars(access_token()); ?>" ,
		title: <?php echo mtt_quoted_title() ?> ,
		lang: <?php echo Lang::instance()->makeJS() ?>,
		mttUrl: "<?php mttinfo('mtt_url'); ?>",
		homeUrl: "<?php mttinfo('url'); ?>",
		db: mytinytodoStorageAjax,
		needAuth: <?php echo need_auth() ? "true" : "false"; ?>,
		isLogged: <?php echo is_logged() ? "true" : "false"; ?>,
		showdate: <?php echo (Config::get('showdate') && !Config::get('mobile')) ? "true" : "false"; ?>,
		singletab: <?php echo (isset($_GET['singletab']) || Config::get('mobile')) ? "true" : "false"; ?>,
		duedatepickerformat: "<?php echo htmlspecialchars(Config::get('dateformat2')); ?>",
		firstdayofweek: <?php echo (int) Config::get('firstdayofweek'); ?>,
		calendarIcon: '<?php mttinfo('template_url'); ?>images/calendar.svg',
		autotag: <?php echo Config::get('autotag') ? "true" : "false"; ?>
		<?php if(Config::get('mobile')) echo ", touchDevice: true"; ?>
	}).run();
});
</script>

<div id="wrapper">
<div id="container">
<div id="mtt_body">

<!-- Top block -->
<div class="topblock">
 <div class="topblock-title">
   <h2><?php mttinfo('title'); ?></h2>
 </div>

<div class="topblock-bar">
 <div id="loading"></div>
 <div id="msg"><span class="msg-text"></span><div class="msg-details"></div></div>
 <div class="bar-menu">
   <span class="need-owner">
     <a href="#settings" id="settings"><?php _e('a_settings');?></a>
   </span>
   <span id="bar_auth">
     <span id="bar_public" style="display:none"><?php _e('public_tasks');?> |</span>
     <a href="#login" id="bar_login" class="nodecor"><u><?php _e('a_login');?></u> <span class="arrdown"></span></a>
     <a href="#logout" id="bar_logout"><?php _e('a_logout');?></a>
   </span>
 </div>
</div>

</div>
<!-- End of Top block -->


<div id="page_tasks" style="display:none">

<div id="lists">
 <div class="tabs-n-button">
   <ul class="mtt-tabs"></ul>
   <div class="mtt-tabs-add-button" title="<?php _e('list_new'); ?>"><div class="tab-height-wrapper"><span></span></div></div>
 </div>
 <div id="list_all" class="mtt-tab mtt-tabs-alltasks mtt-tabs-hidden">
	 <a href="#alltasks" title="<?php _e('alltasks'); ?>"><span><?php _e('alltasks'); ?></span><div class="list-action"></div></a>
 </div>
 <div id="tabs_buttons">
   <div class="tab-height-wrapper">
     <div class="mtt-tabs-select-button mtt-img-button" title="<?php _e('list_select'); ?>"><span></span></div>
   </div>
 </div>
</div>



<div id="toolbar">

<div class="newtask-n-search-container">
<div class="taskbox-c">
  <div class="mtt-taskbox">
   <form id="newtask_form" method="post">
     <input type="text" name="task" value="" maxlength="250" id="task" autocomplete="off" placeholder="<?php _e('htab_newtask');?>"/>
     <div id="newtask_submit" class="mtt-taskbox-icon" title="<?php _e('btn_add');?>"></div>
   </form>
  </div>
  <a href="#" id="newtask_adv" class="mtt-img-button" title="<?php _e('advanced_add');?>"><span></span></a>
</div>
<div class="searchbox-c">
  <div class="mtt-searchbox">
    <input type="text" name="search" value="" maxlength="250" id="search" autocomplete="off" />
    <div class="mtt-searchbox-icon mtt-icon-search"></div>
    <div id="search_close" class="mtt-searchbox-icon mtt-icon-cancelsearch"></div>
  </div>
</div>
</div>

<div id="searchbar" style="display:none"><?php _e('searching');?> <span id="searchbarkeyword"></span></div>

<div id="mtt-tag-toolbar" style="display:none">
  <div class="tag-toolbar-content">
	<span id="mtt-tag-filters"></span>
  </div>
  <div class="tag-toolbar-close"><div id="mtt-tag-toolbar-close" class="mtt-img-button"><span></span></div></div>
</div>

</div>



<h3>
<span id="taskview" class="mtt-menu-button"><span class="btnstr"><?php _e('tasks');?></span> (<span id="total">0</span>) <span class="arrdown"></span></span>
<span class="mtt-notes-showhide"><?php _e('notes');?> <a href="#" id="mtt-notes-show"><?php _e('notes_show');?></a> / <a href="#" id="mtt-notes-hide"><?php _e('notes_hide');?></a></span>
<span id="tagcloudbtn" class="mtt-menu-button"><?php _e('tagcloud');?> <span class="arrdown2"></span></span>
</h3>

<div id="taskcontainer">
 <ol id="tasklist" class="sortable"></ol>
</div>

</div>
<!-- End of page_tasks -->


<div id="page_taskedit" style="display:none">

<div><a href="#" class="mtt-back-button"><?php _e('go_back');?></a></div>

<h3 class="mtt-inadd"><?php _e('add_task');?></h3>
<h3 class="mtt-inedit"><?php _e('edit_task');?>
 <div id="taskedit-date" class="mtt-inedit">
  (<span class="date-created" title="<?php _e('taskdate_created');?>"><span></span></span><span class="date-completed" title="<?php _e('taskdate_completed');?>"> &mdash; <span></span></span>)
 </div>
</h3>

<form id="taskedit_form" name="edittask" method="post">
<input type="hidden" name="isadd" value="0" />
<input type="hidden" name="id" value="" />
<div class="form-row form-row-short">
 <span class="h"><?php _e('priority');?></span>
 <select name="prio" class="form-input">
  <option value="2">+2</option><option value="1">+1</option><option value="0" selected="selected">&plusmn;0</option><option value="-1">&minus;1</option>
 </select>
</div>
<div class="form-row form-row-short">
 <span class="h"><?php _e('due');?> </span>
 <input name="duedate" id="duedate" value="" class="in100 form-input" title="Y-M-D, M/D/Y, D.M.Y, M/D, D.M" autocomplete="off" type="text" />
</div>
<div class="form-row-short-end"></div>
<div class="form-row"><div class="h"><?php _e('task');?></div> <input type="text" name="task" value="" class="in500 form-input" maxlength="250" autocomplete="off" /></div>
<div class="form-row"><div class="h"><?php _e('note');?></div> <textarea name="note" class="in500 form-input"></textarea></div>
<div class="form-row"><div class="h"><?php _e('tags');?></div>
 <table cellspacing="0" cellpadding="0" width="100%"><tr>
  <td><input type="text" name="tags" id="edittags" value="" class="in500 form-input" maxlength="250" autocomplete="off" /></td>
  <td class="alltags-cell">
   <a href="#" id="alltags_show"><?php _e('alltags_show');?></a>
   <a href="#" id="alltags_hide" style="display:none"><?php _e('alltags_hide');?></a></td>
 </tr></table>
</div>
<div class="form-row" id="alltags" style="display:none;"><?php _e('alltags');?> <span class="tags-list"></span></div>
<div class="form-row form-bottom-buttons">
 <input type="submit" value="<?php _e('save');?>" class="form-input-button" />
 <input type="button" id="mtt_edit_cancel" class="mtt-back-button form-input-button" value="<?php _e('cancel');?>" />
</div>
</form>

</div>  <!-- end of page_taskedit -->


<div id="authform" style="display:none">
<form id="login_form">
 <div class="h"><?php _e('password');?></div>
 <div><input type="password" name="password" id="password" /></div>
 <div><input type="submit" value="<?php _e('btn_login');?>" /></div>
</form>
</div>

<div id="priopopup" style="display:none">
 <span class="prio-neg prio-neg-1">&minus;1</span>
 <span class="prio-zero">&plusmn;0</span>
 <span class="prio-pos prio-pos-1">+1</span>
 <span class="prio-pos prio-pos-2">+2</span>
</div>

<div id="taskviewcontainer" class="mtt-menu-container" style="display:none">
<ul>
 <li id="view_tasks"><?php _e('tasks');?> (<span id="cnt_total">0</span>)</li>
 <li id="view_past"><?php _e('f_past');?> (<span id="cnt_past">0</span>)</li>
 <li id="view_today"><?php _e('f_today');?> (<span id="cnt_today">0</span>)</li>
 <li id="view_soon"><?php _e('f_soon');?> (<span id="cnt_soon">0</span>)</li>
</ul>
</div>

<div id="tagcloud" style="display:none">
 <a id="tagcloudcancel" class="mtt-img-button"><span></span></a>
 <div id="tagcloudload"></div>
 <div id="tagcloudcontent"></div>
</div>


<div id="listmenucontainer" class="mtt-menu-container" style="display:none">
<ul>
 <li class="mtt-need-list mtt-need-real-list" id="btnRenameList"><?php _e('list_rename');?></li>
 <li class="mtt-need-list mtt-need-real-list" id="btnDeleteList"><?php _e('list_delete');?></li>
 <li class="mtt-need-list mtt-need-real-list" id="btnClearCompleted"><?php _e('list_clearcompleted');?></li>
 <li class="mtt-need-list mtt-need-real-list mtt-menu-indicator" submenu="listexportmenucontainer"><div class="submenu-icon"></div><?php _e('list_export'); ?></li>
 <li class="mtt-need-list mtt-need-real-list" id="btnHideList"><?php _e('list_hide');?></li>
 <li class="mtt-menu-delimiter mtt-need-real-list"></li>
 <li class="mtt-need-list mtt-need-real-list" id="btnPublish"><div class="menu-icon"></div><?php _e('list_publish');?></li>
 <li class="mtt-need-list mtt-need-real-list" id="btnRssFeed"><div class="menu-icon"></div><a href="#"><?php _e('list_rssfeed');?></a></li>
 <li class="mtt-menu-delimiter mtt-need-real-list"></li>
 <li class="mtt-need-list mtt-need-real-list sort-item" id="sortByHand"><div class="menu-icon"></div><?php _e('sortByHand');?> <span class="mtt-sort-direction"></span></li>
 <li class="mtt-need-list sort-item" id="sortByDateCreated"><div class="menu-icon"></div><?php _e('sortByDateCreated');?> <span class="mtt-sort-direction"></span></li>
 <li class="mtt-need-list sort-item" id="sortByPrio"><div class="menu-icon"></div><?php _e('sortByPriority');?> <span class="mtt-sort-direction"></span></li>
 <li class="mtt-need-list sort-item" id="sortByDueDate"><div class="menu-icon"></div><?php _e('sortByDueDate');?> <span class="mtt-sort-direction"></span></li>
 <li class="mtt-need-list sort-item" id="sortByDateModified"><div class="menu-icon"></div><?php _e('sortByDateModified');?> <span class="mtt-sort-direction"></span></li>
 <li class="mtt-menu-delimiter"></li>
 <li class="mtt-need-list" id="btnShowCompleted"><div class="menu-icon"></div><?php _e('list_showcompleted');?></li>
</ul>
</div>

<div id="listexportmenucontainer" class="mtt-menu-container" style="display:none">
<ul>
  <li class="mtt-need-list mtt-need-real-list" id="btnExportCSV"><a href="#"><?php _e('list_export_csv');?></a></li>
  <li class="mtt-need-list mtt-need-real-list" id="btnExportICAL"><a href="#"><?php _e('list_export_ical');?></a></li>
</ul>
</div>

<div id="taskcontextcontainer" class="mtt-menu-container" style="display:none">
<ul>
 <li id="cmenu_edit"><b><?php _e('action_edit');?></b></li>
 <li id="cmenu_note"><?php _e('action_note');?></li>
 <li id="cmenu_prio" class="mtt-menu-indicator" submenu="cmenupriocontainer"><div class="submenu-icon"></div><?php _e('action_priority');?></li>
 <li id="cmenu_move" class="mtt-menu-indicator" submenu="cmenulistscontainer"><div class="submenu-icon"></div><?php _e('action_move');?></li>
 <li id="cmenu_delete"><?php _e('action_delete');?></li>
</ul>
</div>

<div id="cmenupriocontainer" class="mtt-menu-container" style="display:none">
<ul>
 <li id="cmenu_prio:2"><div class="menu-icon"></div>+2</li>
 <li id="cmenu_prio:1"><div class="menu-icon"></div>+1</li>
 <li id="cmenu_prio:0"><div class="menu-icon"></div>&plusmn;0</li>
 <li id="cmenu_prio:-1"><div class="menu-icon"></div>&minus;1</li>
</ul>
</div>

<div id="cmenulistscontainer" class="mtt-menu-container" style="display:none">
<ul>
</ul>
</div>

<div id="slmenucontainer" class="mtt-menu-container" style="display:none">
<ul>
 <li id="slmenu_list:-1" class="list-id--1 mtt-need-list"><div class="menu-icon"></div><a href="#alltasks"><?php _e('alltasks'); ?></a></li>
 <li class="mtt-menu-delimiter slmenu-lists-begin mtt-need-list"></li>
</ul>
</div>

<div id="page_ajax" style="display:none"></div>

</div><!-- end of #mtt_body -->
</div><!-- end of #container -->

<div id="footer">
	<div id="footer_content">
		<span><?php _e('powered_by');?> <a href="http://www.mytinytodo.net/" class="powered-by-link">myTinyTodo</a>&nbsp;<?php mttinfo('version'); ?></span>
		<span id="mobileordesktop">
			<?php if(Config::get('mobile')): ?><a href="<?php echo getDesktopUrl(); ?>"><?php _e('desktop_version');?></a>
			<?php else: ?><a href="<?php mttinfo('mobile_url'); ?>"><?php _e('mobile_version');?></a>
			<?php endif; ?>
		</span>
	</div>
</div>

</div>
</body>
</html>