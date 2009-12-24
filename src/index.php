<?php
/*
	This file is part of myTinyTodo.
	(C) Copyright 2009 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v3 license. See file COPYRIGHT for details.
*/


require_once('init.php');
require_once('./lang/class.default.php');
require_once('./lang/'.Config::get('lang').'.php');

$lang = new Lang();

if(Config::get('duedateformat') == 2) $duedateformat = 'm/d/yy';
elseif(Config::get('duedateformat') == 3) $duedateformat = 'dd.mm.yy';
elseif(Config::get('duedateformat') == 4) $duedateformat = 'dd/mm/yy';
else $duedateformat = 'yy-mm-dd';

if(!is_int(Config::get('firstdayofweek')) || Config::get('firstdayofweek')<0 || Config::get('firstdayofweek')>6) Config::set('firstdayofweek', 1);

if(Config::get('title') != '') $title = htmlarray(Config::get('title'));
else $title = $lang->get('My Tiny Todolist');


function _e($s)
{
	global $lang;
	echo $lang->get($s);
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<HEAD>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title><?php echo $title; ?></title>
<link rel="stylesheet" type="text/css" href="style.css?v=@VERSION" media="all">
<?php if(isset($_GET['pda'])): ?>
<meta name="viewport" id="viewport" content="width=device-width">
<link rel="stylesheet" type="text/css" href="pda.css?v=@VERSION" media="all">
<?php else: ?>
<link rel="stylesheet" type="text/css" href="print.css?v=@VERSION" media="print">
<?php endif; ?>
</HEAD>

<body>

<script type="text/javascript" src="jquery/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="jquery/jquery-ui-1.7.2.custom.min.js"></script>
<script type="text/javascript" src="ajax.lang.php"></script>
<script type="text/javascript" src="ajax.js"></script>
<script type="text/javascript" src="jquery/jquery.autocomplete.min.js"></script>

<script type="text/javascript">
$().ready(function(){
	$("#tasklist").sortable({cancel:'span,input,a,textarea', delay: 150, update:orderChanged, start:sortStart, items:'> :not(.task-completed)'});
	$("#tasklist").bind("click", tasklistClick);
	$("#edittags").autocomplete('ajax.php?suggestTags', {scroll: false, multiple: true, selectFirst:false, max:8, extraParams:{list:function(){return curList.id}}});
	$("#priopopup").mouseleave(function(){$(this).hide()});
<?php
	if($needAuth)
	{
		echo "\tflag.needAuth = true;\n";
		if(is_logged()) echo "\tflag.isLogged = true;\n";
	}
	echo "\tloadLists(1, 1);\n";
?>
	$("#duedate").datepicker({dateFormat: '<?php echo $duedateformat; ?>', firstDay: <?php echo Config::get('firstdayofweek'); ?>,
		showOn: 'button', buttonImage: 'images/calendar.png', buttonImageOnly: true, changeMonth:true,
		changeYear:true, constrainInput: false, duration:'', nextText:'&gt;', prevText:'&lt;', dayNamesMin:lang.daysMin, 
		dayNames:lang.daysLong, monthNamesShort:lang.monthsLong });
<?php if(!isset($_GET['pda'])): ?>
	$("#page_taskedit").draggable({ handle:'h3', stop: function(e,ui){ flag.windowTaskEditMoved=true; tmp.editformpos=[$(this).css('left'),$(this).css('top')]; } }); 
	$("#page_taskedit").resizable({ minWidth:$("#page_taskedit").width(), minHeight:$("#page_taskedit").height(), start:function(ui,e){editFormResize(1)}, resize:function(ui,e){editFormResize(0,e)}, stop:function(ui,e){editFormResize(2,e)} });
<?php endif; ?>

});
$().ajaxSend( function(r,s) {$("#loading").show();} );
$().ajaxStop( function(r,s) {$("#loading").fadeOut();} );
</script>

<div id="wrapper">
<div id="container">
<div id="body">

<h2><?php echo $title; ?></h2>

<div id="loading"><img src="images/loading1.gif"></div>

<div id="bar">
 <div id="msg" style="float:left"><span class="msg-text" onClick="toggleMsgDetails()"></span><div class="msg-details"></div></div>
 <div align="right">
 <span class="menu-owner">
   <a href="#settings" onClick="showSettings();return false;"><?php _e('a_settings');?></a>
 </span>
 <span class="bar-delim" style="display:none"> | </span>
 <span id="bar_auth">
  <span id="bar_public" style="display:none"><?php _e('public_tasks');?> |</span>
  <span id="bar_login"><a href="#" class="nodecor" onClick="showAuth(this);return false;"><u><?php _e('a_login');?></u> <img src="images/arrdown.gif" border=0></a></span>
  <a href="#" id="bar_logout" onClick="logout();return false"><?php _e('a_logout');?></a>
 </span>
 </div>
</div>

<br clear="all">

<div id="page_tasks" style="display:none">

<div id="lists">
 <ul class="mtt-tabs"></ul>
 <div class="mtt-htabs">
   <span id="rss_icon" style="display:none;"><a href="#" title="<?php _e('rss_feed');?>"><img src="images/feed_bw.png" style="border:none;" onMouseOver="this.src='images/feed.png'" onMouseOut="this.src='images/feed_bw.png'"></a></span>
   <span id="htab_newtask"><?php _e('htab_newtask');?> 
	<form onSubmit="return submitNewTask(this)"><input type="text" name="task" value="" maxlength="250" id="task"> <input type="submit" value="<?php _e('btn_add');?>"></form>
	<a href="#" onClick="showEditForm(1);return false;" title="<?php _e('advanced_add');?>"><img src="images/page_white_edit_bw.png" style="border:none;vertical-align:text-top;" onMouseOver="this.src='images/page_white_edit.png'" onMouseOut="this.src='images/page_white_edit_bw.png'"></a>
	&nbsp;&nbsp;| <a href="#" class="htab-toggle" onClick="addsearchToggle(1);this.blur();return false;"><?php _e('htab_search');?></a>
   </span>
   <span id="htab_search" style="display:none"><?php _e('htab_search');?>
	<form onSubmit="return searchTasks()"><input type="text" name="search" value="" maxlength="250" id="search" onKeyUp="timerSearch()" autocomplete="off"> <input type="submit" value="<?php _e('btn_search');?>"></form>
	&nbsp;&nbsp;| <a href="#" class="htab-toggle" onClick="addsearchToggle(0);this.blur();return false;"><?php _e('htab_newtask');?></a> 
	<div id="searchbar"><?php _e('searching');?> <span id="searchbarkeyword"></span></div> 
   </span>
 </div>
</div>

<h3>
<span id="sort" onClick="btnMenu(this);return false;" style="float:right" class="mtt-btnmenu"><span class="btnstr"></span> <img src="images/arrdown.gif"></span>
<span id="taskview" onClick="btnMenu(this);return false;" class="mtt-btnmenu"><span class="btnstr"><?php _e('tasks');?></span> (<span id="total">0</span>) &nbsp;<img src="images/arrdown.gif"></span>
<span id="tagcloudbtn" onClick="showTagCloud(this);"><span class="btnstr"><?php _e('tags');?></span> <img src="images/arrdown.gif"></span>
<span class="mtt-notes-showhide"><?php _e('notes');?> <a href="#" onClick="toggleAllNotes(1);this.blur();return false;"><?php _e('notes_show');?></a> / <a href="#" onClick="toggleAllNotes(0);this.blur();return false;"><?php _e('notes_hide');?></a></span>
</h3>

<div id="taskcontainer">
 <ol id="tasklist" class="sortable"></ol>
</div>

</div> <!-- end of page_tasks -->


<div id="page_taskedit" style="display:none">

<h3 class="mtt-inadd"><?php _e('add_task');?></h3>
<h3 class="mtt-inedit"><?php _e('edit_task');?>
 <div id="taskedit-date" class="mtt-inedit">
  <span class="date-created" title="<?php _e('taskdate_created');?>"><span></span></span>
  <span class="date-completed" title="<?php _e('taskdate_completed');?>"> / <span></span></span>
 </div>
</h3>

<form onSubmit="return saveTask(this)" name="edittask">
<input type="hidden" name="isadd" value="0">
<input type="hidden" name="id" value="">
<div class="form-row"><span class="h"><?php _e('priority');?></span> <SELECT name="prio"><option value="2">+2</option><option value="1">+1</option><option value="0" selected>&plusmn;0</option><option value="-1">&minus;1</option></SELECT> 
 &nbsp; <span class="h"><?php _e('due');?> </span> <input name="duedate" id="duedate" value="" class="in100" title="Y-M-D, M/D/Y, D.M.Y, M/D, D.M" autocomplete="off"></div>
<div class="form-row"><div class="h"><?php _e('task');?></div> <input type="text" name="task" value="" class="in500" maxlength="250"></div>
<div class="form-row"><div class="h"><?php _e('note');?></div> <textarea name="note" class="in500"></textarea></div>
<div class="form-row"><div class="h"><?php _e('tags');?></div>
 <table cellspacing="0" cellpadding="0" width="100%"><tr>
  <td><input type="text" name="tags" id="edittags" value="" class="in500" maxlength="250"></td>
  <td width="1%" style="white-space:nowrap; padding-left:5px; text-align:right;">
   <a href="#" id="alltags_show" onClick="toggleEditAllTags(1);return false;"><?php _e('alltags_show');?></a>
   <a href="#" id="alltags_hide" onClick="toggleEditAllTags(0);return false;" style="display:none"><?php _e('alltags_hide');?></a></td>
 </tr></table>
</div>
<div class="form-row" id="alltags" style="display:none;"><?php _e('alltags');?> <span class="tags-list"></span></div>
<div class="form-row"><input type="submit" value="<?php _e('save');?>" onClick="this.blur()"> <input type="button" value="<?php _e('cancel');?>" onClick="cancelEdit();this.blur();return false"></div>
</form>

</div>  <!-- end of page_taskedit -->


<div id="authform" style="display:none">
<form onSubmit="doAuth(this);return false;">
 <div class="h"><?php _e('password');?></div><div><input type="password" name="password" id="password"></div><div><input type="submit" value="<?php _e('btn_login');?>"></div>
</form>
</div>

<div id="priopopup" style="display:none">
<span class="prio-neg" onClick="prioClick(-1,this)">&minus;1</span> <span class="prio-o" onClick="prioClick(0,this)">&plusmn;0</span>
<span class="prio-pos" onClick="prioClick(1,this)">+1</span> <span class="prio-pos" onClick="prioClick(2,this)">+2</span>
</div>

<div id="taskviewcontainer" class="mtt-btnmenu-container" style="display:none">
<ul>
 <li onClick="setTaskview(0)"><span id="view_tasks"><?php _e('tasks');?></span></li>
 <li onClick="setTaskview(1)"><span id="view_compl"><?php _e('tasks_and_compl');?></span></li>
 <li onClick="setTaskview('past')"><span id="view_past"><?php _e('f_past');?></span> (<span id="cnt_past">0</span>)</li>
 <li onClick="setTaskview('today')"><span id="view_today"><?php _e('f_today');?></span> (<span id="cnt_today">0</span>)</li>
 <li onClick="setTaskview('soon')"><span id="view_soon"><?php _e('f_soon');?></span> (<span id="cnt_soon">0</span>)</li>
</ul>
</div>

<div id="sortcontainer" class="mtt-btnmenu-container" style="display:none">
<ul>
 <li id="sortByHand" onClick="setSort(0)"><?php _e('sortByHand');?></li>
 <li id="sortByPrio" onClick="setSort(1)"><?php _e('sortByPriority');?></li>
 <li id="sortByDueDate" onClick="setSort(2)"><?php _e('sortByDueDate');?></li>
</ul>
</div>

<div id="tagcloud" style="display:none">
 <div id="tagcloudcancel" onClick="cancelTagFilter();tagCloudClose();"><?php _e('tagfilter_cancel');?></div>
 <div id="tagcloudload"><img src="images/loading1_24.gif"></div>
 <div id="tagcloudcontent"></div>
</div>

<div id="mylistscontainer" class="mtt-btnmenu-container mtt-btnmenu-hasimages" style="display:none">
<ul>
 <li onClick="addList()"><?php _e('list_new');?></li>
 <li class="mtt-need-list" onClick="renameCurList()"><?php _e('list_rename');?></li>
 <li class="mtt-need-list" onClick="deleteCurList()"><?php _e('list_delete');?></li>
 <li class="mtt-need-list" id="btnPublish" onClick="publishCurList()"><?php _e('list_publish');?></li>
</ul>
</div>

<div id="taskcontextcontainer" class="mtt-btnmenu-container mtt-btnmenu-hasimages" style="display:none">
<ul>
 <li id="cmenu_edit"><b><?php _e('action_edit');?></b></li>
 <li id="cmenu_note"><?php _e('action_note');?></li>
<!--
 <li id="cmenu_prio" class="mtt-menu-has-submenu" submenu="priocontainer">Set Priority...</li>
 <li id="cmenu_moveto" class="mtt-menu-has-submenu" submenu="priocontainer">Move to...</li>
-->
 <li id="cmenu_delete"><?php _e('action_delete');?></li>
</ul>
</div>

<div id="priocontainer" class="mtt-btnmenu-container mtt-btnmenu-hasimages" style="display:none">
<ul>
 <li id="cmenu_prio_-1">-1</li>
 <li id="cmenu_prio_0">0</li>
 <li id="cmenu_prio_1">+1</li>
 <li id="cmenu_prio_2">+2</li>
</ul>
</div>

<div id="listsmenucontainer" class="mtt-btnmenu-container mtt-btnmenu-hasimages" style="display:none">
<ul>
</ul>
</div>

<div id="page_ajax" style="display:none"></div>

</div>
<div id="space"></div>
</div>

<div id="footer"><div id="footer_content">Powered by <strong><a href="http://www.mytinytodo.net/">myTinyTodo</a></strong> v@VERSION </div></div>

</div>
</body>
</html>