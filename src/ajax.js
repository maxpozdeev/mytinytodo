theme = {
	newTaskFlashColor: '#ffffaa',
	editTaskFlashColor: '#bbffaa',
	msgFlashColor: '#ffffff'
};

//Global vars
var taskList, taskOrder;
var filter = { compl:0, search:'', tag:'', due:'' };
var sortOrder; //save task order before dragging
var searchTimer;
var objPrio = {};
var selTask = 0;
var flag = { needAuth:false, isLogged:false, tagsChanged:true, windowTaskEditMoved:false, autoTag:true };
var taskCnt = { total:0, past: 0, today:0, soon:0 };
var tmp = {};
var oBtnMenu = {};
var cmenu;
var tabLists = [];
var curList = 0;
var tagsList = [];
var page = {cur:'', prev:''};

var mytinytodo = {
	actions: {},
	menus: {},

	addAction: function(action, proc)
	{
		if(!this.actions[action]) this.actions[action] = new Array();
		this.actions[action].push(proc);
	},

	doAction: function(action, opts)
	{
		if(!this.actions[action]) return;
		for(var i in this.actions[action]) {
			this.actions[action][i](opts);
		}
	}
};

$().ajaxSend(function(r,s){
	$("#msg").hide().removeClass('mtt-error mtt-info');
	$("#loading").show();
});

$().ajaxStop(function(r,s){
	$("#loading").fadeOut();
});

$().ajaxError(function(event, request, settings){
	var errtxt;
	if(request.status == 0) errtxt = 'Bad connection';
	else if(request.status != 200) errtxt = 'HTTP: '+request.status+'/'+request.statusText;
	else errtxt = request.responseText;
	flashError(lang.error, errtxt); 
});

mytinytodo.addAction('listRenamed', cmenuListRenamed);
mytinytodo.addAction('listsLoaded', cmenuListsLoaded);
mytinytodo.addAction('listAdded', cmenuListAdded);
mytinytodo.addAction('listSelected', actionListSelected);


function loadTasks(opts)
{
	if(!curList) return false;
	setSort(curList.sort, 1);
	opts = opts || {};
	$('#tasklist').html('');
	$('#total').html('...');
	var search = filter.search ? '&s='+encodeURIComponent(filter.search) : '';
	var tag = filter.tag ? '&t='+encodeURIComponent(filter.tag) : '';
	var nocache = '&rnd='+Math.random();
	var setCompl = opts.setCompl != null ? '&setCompl=1' : '';
	$.getJSON('ajax.php?loadTasks&list='+curList.id+'&compl='+curList.showCompl+'&sort='+curList.sort+search+tag+'&tz='+tz()+setCompl+nocache, function(json){
		taskList = new Array();
		taskOrder = new Array();
		taskCnt.total = taskCnt.past = taskCnt.today = taskCnt.soon = 0;
		var tasks = '';
		$.each(json.list, function(i,item){
			tasks += prepareTaskStr(item);
			taskList[item.id] = item;
			taskOrder.push(parseInt(item.id));
			changeTaskCnt(item, 1);
		});
		refreshTaskCnt();
		$('#tasklist').html(tasks);
		if(json.denied) errorDenied();
	});
}

function prepareTaskStr(item, noteExp)
{
	var id = parseInt(item.id);
	var prio = parseInt(item.prio);
	var readOnly = (flag.needAuth && !flag.isLogged) ? true : false;
	return '<li id="taskrow_'+id+'" class="'+(item.compl?'task-completed ':'')+item.dueClass+(item.note!=''?' task-has-note':'')+
		(noteExp?' task-expanded':'')+'" onDblClick="editTask('+id+')">'+
		'<div class="task-actions"><a href="#" class="taskactionbtn" onClick="return taskContextMenu(this,'+id+')"></a></div>'+
		'<div class="task-left"><div class="task-toggle" onClick="toggleNote('+id+')"></div>'+
		'<input type="checkbox" '+(readOnly?'disabled':'')+' onClick="completeTask('+id+',this)" '+(item.compl?'checked':'')+'></div>'+
		'<div class="task-middle">'+prepareDuedate(item.duedate, item.dueClass, item.dueStr)+
		'<div class="task-through">'+preparePrio(prio,id)+'<span class="task-title">'+prepareHtml(item.title)+'</span> '+
		prepareTagsStr(item.tags)+'<span class="task-date">'+item.dateInline+'</span></div>'+
		'<div class="task-note-block">'+
			'<div id="tasknote'+id+'" class="task-note"><span>'+prepareHtml(item.note)+'</span></div>'+
			'<div id="tasknotearea'+id+'" class="task-note-area"><textarea id="notetext'+id+'"></textarea>'+
				'<span class="task-note-actions"><a href="#" onClick="return saveTaskNote('+id+')">'+lang.actionNoteSave+
				'</a> | <a href="#" onClick="return cancelTaskNote('+id+')">'+lang.actionNoteCancel+'</a></span></div>'+
		'</div>'+
		"</div></li>\n";
}

function prepareHtml(s)
{
	// make URLs clickable
	var s = s.replace(/(^|\s|>)(www\.([\w\#$%&~\/.\-\+;:=,\?\[\]@]+?))(,|\.|:|)?(?=\s|&quot;|&lt;|&gt;|\"|<|>|$)/gi, '$1<a href="http://$2" target="_blank">$2</a>$4');
	return s.replace(/(^|\s|>)((?:http|https|ftp):\/\/([\w\#$%&~\/.\-\+;:=,\?\[\]@]+?))(,|\.|:|)?(?=\s|&quot;|&lt;|&gt;|\"|<|>|$)/ig, '$1<a href="$2" target="_blank">$2</a>$4');
}

function preparePrio(prio,id)
{
	var cl =''; var v = '';
	if(prio < 0) { cl = 'prio-neg'; v = '&minus;'+Math.abs(prio); }
	else if(prio > 0) { cl = 'prio-pos'; v = '+'+prio; }
	else { cl = 'prio-o'; v = '&plusmn;0'; }
	return '<span class="task-prio '+cl+'" onMouseOver="prioPopup(1,this,'+id+')" onMouseOut="prioPopup(0,this)">'+v+'</span>';
}

function prepareTagsStr(tags)
{
	if(!tags || tags == '') return '';
	var a = tags.split(',');
	if(!a.length) return '';
	for(var i in a) {
		a[i] = '<a href="#" class="tag" onClick=\'addFilterTag("'+a[i]+'");return false\'>'+a[i]+'</a>';
	}
	return '<span class="task-tags">'+a.join(', ')+'</span>';
}

function prepareDuedate(duedate, c, s)
{
	if(!duedate) return '';
	return '<span class="duedate" title="'+duedate+'">'+s+'</span>';
}

function submitNewTask(form)
{
	if(form.task.value == '') return false;
	var nocache = '&rnd='+Math.random();
	$.post('ajax.php?newTask'+nocache, { list:curList.id, title: form.task.value, tz:tz(), tag:filter.tag }, function(json){
		if(!parseInt(json.total)) return;
		$('#total').text( parseInt($('#total').text()) + 1 );
		taskCnt.total++;
		form.task.value = '';
		var item = json.list[0];
		taskList[item.id] = item;
		taskOrder.push(parseInt(item.id));
		$('#tasklist').append(prepareTaskStr(item));
		changeTaskOrder(item.id);
		$('#taskrow_'+item.id).effect("highlight", {color:theme.newTaskFlashColor}, 2000);
		refreshTaskCnt();
	}, 'json');
	flag.tagsChanged = true;
	return false;
}

function flashError(str, details)
{
	$("#msg>.msg-text").text(str)
	$("#msg>.msg-details").text(details);
	$("#loading").hide();
	$("#msg").addClass('mtt-error').effect("highlight", {color:theme.msgFlashColor}, 700);
}

function flashInfo(str, details)
{
	$("#msg>.msg-text").text(str)
	$("#msg>.msg-details").text(details);
	$("#loading").hide();
	$("#msg").addClass('mtt-info').effect("highlight", {color:theme.msgFlashColor}, 700);
}

function toggleMsgDetails()
{
	var el = $("#msg>.msg-details");
	if(!el) return;
	if(el.css('display') == 'none') el.show();
	else el.hide()
}

function deleteTask(id)
{
	if(!confirm(lang.confirmDelete)) {
		return false;
	}
	var nocache = '&rnd='+Math.random();
	$.getJSON('ajax.php?deleteTask='+id+nocache, function(json){
		if(!parseInt(json.total)) return;
		var item = json.list[0];
		taskOrder.splice($.inArray(id,taskOrder), 1);
		$('#taskrow_'+item.id).fadeOut('normal', function(){ $(this).remove() });
		changeTaskCnt(taskList[id], -1);
		refreshTaskCnt();
		delete taskList[id];
	});
	flag.tagsChanged = true;
	return false;
}

function completeTask(id,ch)
{
	var compl = 0;
	if(ch.checked) compl = 1;
	var nocache = '&rnd='+Math.random();
	$.getJSON('ajax.php?completeTask='+id+'&compl='+compl+nocache, function(json){
		if(!parseInt(json.total)) return;
		var item = json.list[0];
		if(item.compl) $('#taskrow_'+id).addClass('task-completed');
		else $('#taskrow_'+id).removeClass('task-completed');
		taskList[id].ow = item.ow;
		taskList[id].compl = item.compl;
		changeTaskCnt(taskList[id], 0);
		if(item.compl && !curList.showCompl) {
			delete taskList[id];
			taskOrder.splice($.inArray(id,taskOrder), 1);
			$('#taskrow_'+item.id).fadeOut('normal', function(){ $(this).remove() });
		}
		else if(curList.showCompl) {
			changeTaskOrder(id);
			$('#taskrow_'+id).effect("highlight", {color:theme.editTaskFlashColor}, 'normal', function(){$(this).css('display','')} );
		}
		refreshTaskCnt();
	});
	return false;
}

function toggleTaskNote(id)
{
	var aArea = '#tasknotearea'+id;
	if($(aArea).css('display') == 'none')
	{
		$('#notetext'+id).val(taskList[id].noteText);
		$(aArea).show();
		$('#tasknote'+id).hide();
		$('#taskrow_'+id).addClass('task-expanded');
		$('#notetext'+id).focus();
	} else {
		cancelTaskNote(id)
	}
	return false;
}

function cancelTaskNote(id)
{
	if(taskList[id].note == '') $('#taskrow_'+id).removeClass('task-expanded');
	$('#tasknotearea'+id).hide();
	$('#tasknote'+id).show();
	return false;
}

function saveTaskNote(id)
{
	var nocache = '&rnd='+Math.random();
	$.post('ajax.php?editNote='+id+nocache, {note: $('#notetext'+id).val()}, function(json){
		if(!parseInt(json.total)) return;
		var item = json.list[0];
		taskList[id].note = item.note;
		taskList[id].noteText = item.noteText;
		$('#tasknote'+item.id+'>span').html(prepareHtml(item.note));
		if(item.note == '') $('#taskrow_'+id).removeClass('task-has-note task-expanded');
		else $('#taskrow_'+id).addClass('task-has-note task-expanded');
		cancelTaskNote(item.id);
	}, 'json');
	return false;
}

function editTask(id)
{
	var item = taskList[id];
	if(!item) return false;
	document.edittask.task.value = dehtml(item.title);
	document.edittask.note.value = item.noteText;
	document.edittask.id.value = item.id;
	document.edittask.tags.value = item.tags.split(',').join(', ');
	document.edittask.duedate.value = item.duedate;
	document.edittask.prio.value = item.prio;
	$('#taskedit-date .date-created>span').text(item.date);
	if(item.compl) $('#taskedit-date .date-completed').show().find('span').text(item.dateCompleted);
	else $('#taskedit-date .date-completed').hide();
	showEditForm();
	return false;
}

function showEditForm(isAdd)
{
	if(isAdd)
	{
		$('#page_taskedit').removeClass('mtt-inedit').addClass('mtt-inadd');
		document.edittask.isadd.value = 1;
		if($('#task').val() != '')
		{
			$.post('ajax.php?parseTaskStr'+'&rnd='+Math.random(), { list:curList.id, title: $('#task').val(), tz:tz(), tag:filter.tag }, function(json){
				if(!json) return;
				document.edittask.task.value = json.title
				document.edittask.tags.value = json.tags;
				document.edittask.prio.value = json.prio;
				$('#task').val('');
			}, 'json');
		}
	}
	else {
		$('#page_taskedit').removeClass('mtt-inadd').addClass('mtt-inedit');
		document.edittask.isadd.value = 0;
	}
	
	if(flag.pda) {
		showhide($('#page_taskedit'), $('#page_tasks'));
		return;
	}

	$('<div id="overlay"></div>').appendTo('body').css('opacity', 0.5).show();
	//clear selection
	if(document.selection && document.selection.empty && document.selection.createRange().text) document.selection.empty();
	else if(window.getSelection) window.getSelection().removeAllRanges();

	var w = $('#page_taskedit');
	if(!flag.windowTaskEditMoved)
	{
		var x = Math.floor($(window).width()/2 - w.outerWidth()/2);
		var y = Math.floor($(window).height()/2 - w.outerHeight()/2);
		if(x < 0) x = 0;
		if(y < 0) y = 0;
		w.css({left:x, top:y});
		tmp.editformpos = [x, y];
	}
	w.css('max-width', $(window).width() - (w.outerWidth() - w.width()));
	w.fadeIn('fast');	//.show();
	$(document).bind('keydown', cancelEdit);
}

function cancelEdit(e)
{
	if(e && e.keyCode != 27) return;
	if(flag.pda) {
		showhide($('#page_tasks'), $('#page_taskedit'));
	} else
	{
		$(document).unbind('keydown', cancelEdit);
		$('#page_taskedit').hide();
		$('#overlay').remove();
	}
	document.edittask.task.value = '';
	document.edittask.note.value = '';
	document.edittask.tags.value = '';
	document.edittask.duedate.value = '';
	document.edittask.prio.value = '0';
	toggleEditAllTags(0);
	return false;
}

function saveTask(form)
{
	if(flag.needAuth && !flag.isLogged) return false;
	if(form.isadd.value != 0) return submitFullTask(form);
	var nocache = '&rnd='+Math.random();
	$.post('ajax.php?editTask='+form.id.value+nocache, { list:curList.id, tz:tz(), title: form.task.value, note:form.note.value, prio:form.prio.value, tags:form.tags.value, duedate:form.duedate.value }, function(json){
		if(!parseInt(json.total)) return;
		var item = json.list[0];
		changeTaskCnt(item, 0, taskList[item.id]);
		taskList[item.id] = item;
		var noteExpanded = (item.note != '' && $('#taskrow_'+item.id).is('.task-expanded')) ? 1 : 0;
		$('#taskrow_'+item.id).replaceWith(prepareTaskStr(item, noteExpanded));
		if(curList.sort != 0) changeTaskOrder(item.id);
		cancelEdit();
		refreshTaskCnt();
		$('#taskrow_'+item.id).effect("highlight", {color:theme.editTaskFlashColor}, 'normal', function(){$(this).css('display','')});
	}, 'json');
	$("#edittags").flushCache();
	flag.tagsChanged = true;
	return false;
}

function showhide(a,b)
{
	a.show();
	b.hide();
}

function sortStart(event,ui)
{
	// remember initial order before sorting
	sortOrder = $(this).sortable('toArray');
}

function orderChanged(event,ui)
{
	if(!ui.item[0]) return;
	var itemId = ui.item[0].id;
	var n = $(this).sortable('toArray');
	// remove possible empty id's
	for(var i=0; i<sortOrder.length; i++) {
		if(sortOrder[i] == '') { sortOrder.splice(i,1); i--; }
	}
	if(n.toString() == sortOrder.toString()) return;
	// make assoc from array for easy index
	var h0 = new Array();
	for(var j=0; j<sortOrder.length; j++) {
		h0[sortOrder[j]] = j;
	}
	var h1 = new Array();
	for(var j=0; j<n.length; j++) {
		h1[n[j]] = j;
		taskOrder[j] = n[j].split('_')[1];
	}
	// prepare param string 
	var s = '';
	var diff;
	var replaceOW = taskList[sortOrder[h1[itemId]].split('_')[1]].ow;
	for(var j in h0)
	{
		diff = h1[j] - h0[j];
		if(diff != 0) {
			var a = j.split('_');
			if(j == itemId) diff = replaceOW - taskList[a[1]].ow;
			s += a[1] +'='+ diff+ '&';
			taskList[a[1]].ow += diff;
		}
	}
	var nocache = '&rnd='+Math.random();
	$.post('ajax.php?changeOrder'+nocache, { order: s }, function(json){
	}, 'json');
}

function timerSearch()
{
	clearTimeout(searchTimer);
	searchTimer = setTimeout("searchTasks()", 500);
}

function searchTasks()
{
	filter.search = $('#search').val();
	$('#searchbarkeyword').text(filter.search);
	if(filter.search != '') $('#searchbar').fadeIn('fast');
	else $('#searchbar').fadeOut('fast');
	loadTasks();
	return false;
}

function dehtml(str)
{
	return str.replace(/&quot;/g,'"').replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&amp;/g,'&');
}

function errorDenied()
{
	flashError(lang.denied);
}

function updateAccessStatus()
{
	// flag.needAuth is not changed after pageload
	if(flag.needAuth)
	{
		$('#bar_auth').show();
		if(flag.isLogged) {
			showhide($("#bar_logout"),$("#bar_login"));
			$('#bar .menu-owner').show();
			$('#bar .bar-delim').show();
		}
		else {
			showhide($("#bar_login"),$("#bar_logout"));
			$('#bar .menu-owner').hide();
			$('#bar .bar-delim').hide();
		}
	}
	if(flag.needAuth && !flag.isLogged) {
		$('#page_tasks').addClass('readonly')
		addsearchToggle(1);
	}
	else {
		$('#page_tasks').removeClass('readonly')
		$("#bar_public").hide();
		addsearchToggle(0);
	}
	$('#page_ajax').hide();
	page.cur = '';
}

function doAuth(form)
{
	$.post('ajax.php?rnd='+Math.random(), { login:1, password: form.password.value }, function(json){
		form.password.value = '';
		if(json.logged)
		{
			flag.isLogged = true;
			loadLists(0,1);
		}
		else {
			flashError(lang.invalidpass);
			$('#password').focus();
		}
	}, 'json');
	$('#authform').hide();
}

function logout()
{
	$.post('ajax.php?rnd='+Math.random(), { logout:1 }, function(json){
		flag.isLogged = false;
		loadLists(0,1);
	}, 'json');
	return false;
}

function tasklistClick(e)
{
	var node = e.target.nodeName;
	if(node=='SPAN' || node=='LI' || node=='DIV') {
		var li = getRecursParent(e.target, 'LI', 10);
		if(li) {
			if(selTask && li.id != selTask) $('#'+selTask).removeClass('clicked doubleclicked');
			selTask = li.id;
			if($(li).is('.clicked')) $(li).toggleClass('doubleclicked');
			else $(li).addClass('clicked');
		}
	}
}

function getRecursParent(el, needle, level)
{
	if(el.nodeName == needle) return el;
	if(!el.parentNode) return null;
	level--;
	if(level <= 0) return false;
	return getRecursParent(el.parentNode, needle, level);
}

function cancelTagFilter(dontLoadTasks)
{
	$('#tagcloudbtn>.btnstr').text(lang.tags);
	filter.tag = '';
	if(dontLoadTasks==null || !dontLoadTasks) loadTasks();
}

function addFilterTag(tag)
{
	filter.tag = tag;
	loadTasks();
	$('#tagcloudbtn>.btnstr').html(lang.tagfilter + ' <span class="tag">'+tag+'</span>');
}

function showAuth(el)
{
	var w = $('#authform');
	if(w.css('display') == 'none')
	{
		var offset = $(el).offset();
		w.css({
			position: 'absolute',
			top: offset.top + el.offsetHeight + 3,
			left: offset.left + el.offsetWidth - w.outerWidth()
		}).show();
		$('#password').focus();
	}
	else {
		w.hide();
		el.blur();
	}
}

function prioPopup(act, el, id)
{
	if(act == 0) {
		clearTimeout(objPrio.timer);
		return;
	}
	var offset = $(el).offset();
	$('#priopopup').css({ position: 'absolute', top: offset.top + 1, left: offset.left + 1 });
	objPrio.taskId = id;
	objPrio.el = el;
	objPrio.timer = setTimeout("$('#priopopup').show()", 300);
}

function prioClick(prio, el)
{
	el.blur();
	prio = parseInt(prio);
	$('#priopopup').fadeOut('fast'); //.hide();
	setTaskPrio(objPrio.taskId, prio);
}

function setTaskPrio(id, prio)
{
	$.getJSON('ajax.php?setPrio='+id+'&prio='+prio+'&rnd='+Math.random(), function(json){
	});
	taskList[id].prio = prio;
	var $t = $('#taskrow_'+id);
	$t.find('.task-prio').replaceWith(preparePrio(prio, id));
	if(curList.sort != 0) changeTaskOrder(id);
	$t.effect("highlight", {color:theme.editTaskFlashColor}, 'normal');
}

function setSort(v, init)
{
	$('#mylistscontainer .sort-item').removeClass('mtt-item-checked');
	if(v == 0) $('#sortByHand').addClass('mtt-item-checked');
	else if(v == 1) $('#sortByPrio').addClass('mtt-item-checked');
	else if(v == 2) $('#sortByDueDate').addClass('mtt-item-checked');
	else return;

	if(flag.needAuth && !flag.isLogged) {
		$("#tasklist").sortable('disable');
		return;
	}
	curList.sort = v;
	if(v == 0) $("#tasklist").sortable('enable');
	else $("#tasklist").sortable('disable');
	
	if(!init)
	{
		changeTaskOrder();
		$.post('ajax.php?setSort'+'&rnd='+Math.random(), { list:curList.id, sort:curList.sort }, function(json){
		}, 'json');
	}
}

function isParentId(el, id)
{
	if(el.id && $.inArray(el.id, id) != -1) return true;
	if(!el.parentNode) return null;
	return isParentId(el.parentNode, id);
}

function changeTaskOrder(id)
{
	id = parseInt(id);
	if(taskOrder.length < 2) return;
	var oldOrder = taskOrder.slice();
	if(curList.sort == 0) taskOrder.sort( function(a,b){ 
			if(taskList[a].compl != taskList[b].compl) return taskList[a].compl-taskList[b].compl;
			return taskList[a].ow-taskList[b].ow
		});
	else if(curList.sort == 1) taskOrder.sort( function(a,b){
			if(taskList[a].compl != taskList[b].compl) return taskList[a].compl-taskList[b].compl;
			if(taskList[a].prio != taskList[b].prio) return taskList[b].prio-taskList[a].prio;
			if(taskList[a].dueInt != taskList[b].dueInt) return taskList[a].dueInt-taskList[b].dueInt;
			return taskList[a].ow-taskList[b].ow; 
		});
	else if(curList.sort == 2) taskOrder.sort( function(a,b){
			if(taskList[a].compl != taskList[b].compl) return taskList[a].compl-taskList[b].compl;
			if(taskList[a].dueInt != taskList[b].dueInt) return taskList[a].dueInt-taskList[b].dueInt;
			if(taskList[a].prio != taskList[b].prio) return taskList[b].prio-taskList[a].prio;
			return taskList[a].ow-taskList[b].ow; 
		});
	else return;
	if(oldOrder.toString() == taskOrder.toString()) return;
	if(id && taskList[id])
	{
		// optimization: determine where to insert task: top or after some task
		var indx = $.inArray(id,taskOrder);
		if(indx ==0) {
			$('#tasklist').prepend($('#taskrow_'+id))
		} else {
			var after = taskOrder[indx-1];
			$('#taskrow_'+after).after($('#taskrow_'+id));
		}
	}
	else {
		var o = $('#tasklist');
		for(var i in taskOrder) {
			o.append($('#taskrow_'+taskOrder[i]));
		}
	}
}

function loadTags(callback)
{
	$.getJSON('ajax.php?tagCloud&list='+curList.id+'&rnd='+Math.random(), function(json){
		if(!parseInt(json.total)) tagsList = [];
		else tagsList = json.cloud;
		var cloud = '';
		$.each(tagsList, function(i,item){
			cloud += ' <a href="#" onClick=\'addFilterTag("'+item.tag+'");tagCloudClose();return false;\' class="tag w'+item.w+'" >'+item.tag+'</a>';
		});
		$('#tagcloudcontent').html(cloud)
		flag.tagsChanged = false;
		callback();
	});
}

function showTagCloud(el)
{
	var w = $('#tagcloud');
	if(w.css('display') == 'none')
	{
		if(flag.tagsChanged)
		{
			$('#tagcloudcontent').html('');
			$('#tagcloudload').show();
			var offset = $(el).offset();
			w.css({ position: 'absolute', top: offset.top+el.offsetHeight-1, left: offset.left }).show();
			loadTags(function(){$('#tagcloudload').hide();});
		}
		else {
			var offset = $(el).offset();
			w.css({ position: 'absolute', top: offset.top+el.offsetHeight-1, left: offset.left }).show();
		}
		$(document).bind("click", tagCloudClose);
	}
	else {
		el.blur();
		tagCloudClose();
	}
}

function tagCloudClose(e)
{
	if(e) {
		if(isParentId(e.target, ['tagcloudbtn','tagcloud'])) return;
	}
	$(document).unbind("click", tagCloudClose);
	$('#tagcloud').hide();
}

function changeTaskCnt(task, dir, old)
{
	if(dir > 0) dir = 1;
	else if(dir < 0) dir = -1;
	if(dir == 0 && old != null && task.dueClass != old.dueClass) //on saveTask
	{
		if(old.dueClass != '') taskCnt[old.dueClass]--;
		if(task.dueClass != '') taskCnt[task.dueClass]++;
	}
	else if(dir == 0 && old == null) //on comleteTask
	{
		if(!curList.showCompl && task.compl) taskCnt.total--;
		if(task.dueClass != '') taskCnt[task.dueClass] += task.compl ? -1 : 1;
	}
	if(dir != 0) {
		if(task.dueClass != '' && !task.compl) taskCnt[task.dueClass] += dir;
		taskCnt.total += dir;
	}
}

function refreshTaskCnt()
{
	$('#cnt_total').text(taskCnt.total);
	$('#cnt_past').text(taskCnt.past);
	$('#cnt_today').text(taskCnt.today);
	$('#cnt_soon').text(taskCnt.soon);
	if(filter.due == '') $('#total').text(taskCnt.total);
	else if(taskCnt[filter.due] != null) $('#total').text(taskCnt[filter.due]);
}

function setTaskview(v)
{
	if(v == 0)
	{
		if(filter.due == '') return;
		$('#taskview .btnstr').text($('#view_tasks').text());
		$('#tasklist').removeClass('filter-'+filter.due);
		filter.due = '';
		$('#total').text(taskCnt.total);
	}
	else if(v=='past' || v=='today' || v=='soon')
	{
		if(filter.due == v) return;
		else if(filter.due != '') {
			$('#tasklist').removeClass('filter-'+filter.due);
		}
		$('#tasklist').addClass('filter-'+v);
		$('#taskview .btnstr').text($('#view_'+v).text());
		$('#total').text(taskCnt[v]);
		filter.due = v;
	}
}

function editFormResize(startstop, event)
{
	var f = $('#page_taskedit');
	if(startstop == 1) {
		tmp.editformdiff = f.height() - $('#page_taskedit textarea').height();
	}
	else if(startstop == 2) {
		//to avoid bug http://dev.jqueryui.com/ticket/3628
		if(f.is('.ui-draggable')) {
			f.css( {left:tmp.editformpos[0], top:tmp.editformpos[1], height:''} ).css('position', 'fixed');
		}
	}
	else  $('#page_taskedit textarea').height(f.height() - tmp.editformdiff);
}

function mttTabSelected(id, indx)
{
	$('#lists .mtt-tabs-selected').removeClass('mtt-tabs-selected');
	$('#list_'+tabLists[indx].id).addClass('mtt-tabs-selected');
	if(!tabLists[indx]) return;
	if(indx != curList.i) {
		$('#tasklist').html('');
		if(filter.search != '') {
			filter.search = '';
			$('#searchbarkeyword').text('');
			$('#searchbar').hide();
		}
		//if(tabLists[indx].published)
			$('#rss_icon').find('a').attr('href', 'feed.php?list='+tabLists[indx].id);
		mytinytodo.doAction('listSelected', tabLists[indx]);
	}
	curList = tabLists[indx];
	flag.tagsChanged = true;
	cancelTagFilter(1);
	setTaskview(0);
	loadTasks();
}

function btnMenu(el)
{
	if(!el.id) return;
	oBtnMenu.container = el.id+'container';
	oBtnMenu.targets = [el.id, oBtnMenu.container];
	var w = $('#'+oBtnMenu.container);
	if(w.css('display') == 'none')
	{
		oBtnMenu.h = [];
		$(w).find('li').each( function(i,o){
			if(o.onclick) {
				oBtnMenu.h[i] = o.onclick;
				$(o).bind("click2", o.onclick);
				if(!$(o).is('.mtt-disabled')) o.onclick = function(event) { $('#'+oBtnMenu.container).hide(); $(o).trigger('click2'); btnMenuClose(); }
			} else {
				oBtnMenu.h[i] = null;
			}
		} );
		var offset = $(el).offset();
		w.css({ position: 'absolute', top: offset.top+el.offsetHeight-1, left: offset.left , 'min-width': $(el).width(), 'width':w.width() }).show();
		$(document).bind("click", btnMenuClose);
	}
	else {
		el.blur();
		btnMenuClose();
	}
}

function btnMenuClose(e)
{
	if(e) {
		if(isParentId(e.target, oBtnMenu.targets)) return;
	}
	$(document).unbind("click", btnMenuClose);
	$('#'+oBtnMenu.container).hide().find('li').each( function(i,o){ 
		if(oBtnMenu.h[i]) {
			o.onclick = oBtnMenu.h[i];
			$(o).unbind('click2');
		}
	});
	oBtnMenu = {};
}

function toggleNote(id)
{
	$('#taskrow_'+id).toggleClass('task-expanded');
}

function toggleAllNotes(show)
{
	for(var id in taskList)
	{
		if(taskList[id].note == '') continue;
		if(show) $('#taskrow_'+id).addClass('task-expanded');
		else $('#taskrow_'+id).removeClass('task-expanded');
	}
}

function loadLists(onInit, updAccess)
{
	if(filter.search != '') {
		filter.search = '';
		$('#searchbarkeyword').text('');
		$('#searchbar').hide();
	}
	var nocache = '&rnd='+Math.random();
	$.getJSON('ajax.php?loadLists'+nocache, function(json){
		tabLists = new Array();
		var ti = '';
		if(parseInt(json.total))
		{
			$.each(json.list, function(i,item){
				item.i = i;
				tabLists[i] = item;
				ti += '<li id="list_'+item.id+'" class="'+(i==0?'mtt-tabs-selected':'')+'">'+
					'<a href="#list'+item.id+'" title="'+item.name+'" onClick="mttTabSelected('+item.id+','+i+');return false;"><span>'+item.name+'</span>'+
					'<div class="list-action" onClick="listMenu(this,'+i+');return stopBubble(arguments[0]);"></div></a></li>';
			});
			if(!curList) {
				$('#toolbar').children().removeClass('invisible');
				$('#page_tasks h3').children().removeClass('invisible');
				$('#mylistscontainer .mtt-need-list').removeClass('mtt-disabled');
			}
			curList = tabLists[0];
			loadTasks();
			//if(curList.published)
				$('#rss_icon').find('a').attr('href', 'feed.php?list='+curList.id);
			if(flag.needAuth && !flag.isLogged) $('#bar_public').show();
		}
		else {
			curList = 0;
			$('#toolbar').children().addClass('invisible');
			$('#page_tasks h3').children().addClass('invisible');
			$('#mylistscontainer .mtt-need-list').addClass('mtt-disabled');
			$('#tasklist').html('');
		}
		$('#lists ul').html(ti);
		$('#lists').show();
		mytinytodo.doAction('listsLoaded');
		if(curList) mytinytodo.doAction('listSelected', curList);
		if(!flag.needAuth || flag.isLogged || curList) $('#page_tasks').show();
	});
	$('#page_tasks').hide();
	if(updAccess) updateAccessStatus();
}

function addList()
{
	var r = prompt(lang.addList, lang.addListDefault);
	if(r == null) return;
	var nocache = '&rnd='+Math.random();
	$.post('ajax.php?'+nocache, { addList:1, name:r }, function(json){
		if(!parseInt(json.total)) return;
		var item = json.list[0];
		var i = tabLists.length;
		item.i = i;
		tabLists[i] = item;
		if(i > 0) {
			$('#lists ul').append('<li id="list_'+item.id+'">'+
					'<a href="#list'+item.id+'" title="'+item.name+'" onClick="mttTabSelected('+item.id+','+i+');return false;"><span>'+item.name+'</span>'+
					'<div class="list-action" onClick="listMenu(this,'+i+');return stopBubble(arguments[0]);"></div></a></li>');
			mytinytodo.doAction('listAdded', item);
		}
		else loadLists();
	}, 'json');
}

function renameCurList()
{
	if(!curList) return;
	var r = prompt(lang.renameList, dehtml(curList.name));
	if(r == null || r == '') return;
	var nocache = '&rnd='+Math.random();
	$.post('ajax.php?'+nocache, { renameList:1, id:curList.id, name:r }, function(json){
		if(!parseInt(json.total)) return;
		var item = json.list[0];
		item.i = curList.i;
		tabLists[curList.i] = item;
		curList = item;
		$('#lists ul>.mtt-tabs-selected>a').attr('title', item.name).find('span').html(item.name);
		mytinytodo.doAction('listRenamed', item);
	}, 'json');
}

function deleteCurList()
{
	if(!curList) return false;
	var r = confirm(lang.deleteList);
	if(!r) return;
	$.post('ajax.php?'+'&rnd='+Math.random(), { deleteList:1, id:curList.id }, function(json){
		if(!parseInt(json.total)) return;
		loadLists();
	}, 'json');

}

function addsearchToggle(toSearch)
{
	if(toSearch)
	{
		showhide($('#htab_search'), $('#htab_newtask'));
		$('#search').focus();
	}
	else
	{
		if(flag.needAuth && !flag.isLogged) return false;
		showhide($('#htab_newtask'), $('#htab_search'));
		// reload tasks when we return to task tab (from search tab)
		if(filter.search != '') {
			filter.search = '';
			$('#searchbarkeyword').text('');
			$('#searchbar').hide();
			loadTasks();
		}
		$('#task').focus();
	}
}

function toggleEditAllTags(show)
{
	if(show)
	{
		if(flag.tagsChanged) loadTags(fillEditAllTags);
		else fillEditAllTags();
		showhide($('#alltags_hide'), $('#alltags_show'));
	}
	else {
		$('#alltags').hide();
		showhide($('#alltags_show'), $('#alltags_hide'))
	}
}

function fillEditAllTags()
{
	var a = [];
	for(var i=tagsList.length-1; i>=0; i--) { 
		a.push('<a href="#" class="tag" onClick=\'addEditTag("'+tagsList[i].tag+'");return false\'>'+tagsList[i].tag+'</a>');
	}
	$('#alltags .tags-list').html(a.join(', '));
	$('#alltags').show();
}

function addEditTag(tag)
{
	var v = $('#edittags').val();
	if(v == '') { 
		$('#edittags').val(tag);
		return;
	}
	var r = v.search(new RegExp('(^|,)\\s*'+tag+'\\s*(,|$)'));
	if(r < 0) $('#edittags').val(v+', '+tag);
}

function showSettings()
{
	if(page.cur == 'settings') return false;
	$('#page_ajax').load('settings.php?ajax=yes',null,function(){ 
		showhide($('#page_ajax').addClass('mtt-page-settings'), $('#page_tasks'));
		page.prev = page.cur;
		page.cur = 'settings';
	})
}

function closeSettings()
{
	showhide($('#page_tasks'), $('#page_ajax').removeClass('mtt-page-settings'));
	page.prev = page.cur;
	page.cur = '';
//	$("#msg").hide();
}

function saveSettings(frm)
{
	if(!frm) return false;
	var params = { save:'ajax' };
	$(frm).find("input:text,input:checked,select,:password").filter(":enabled").each(function() { params[this.name || '__'] = this.value; }); 
	$(frm).find(":submit").attr('disabled','disabled').blur();
	$.post('settings.php?'+'&rnd='+Math.random(), params, function(json){
		if(json.saved) {
			flashInfo(lang.settingsSaved);
			setTimeout('window.location.reload();', 1000);
		}
	}, 'json');
}

function submitFullTask(form)
{
	if(flag.needAuth && !flag.isLogged) return false;
	var nocache = '&rnd='+Math.random();
	$.post('ajax.php?fullNewTask'+nocache, { list:curList.id, tag:filter.tag, title: form.task.value, note:form.note.value, prio:form.prio.value, tags:form.tags.value, duedate:form.duedate.value }, function(json){
		if(!parseInt(json.total)) return;
		form.task.value = '';
		var item = json.list[0];
		taskList[item.id] = item;
		taskOrder.push(parseInt(item.id));
		$('#tasklist').append(prepareTaskStr(item));
		changeTaskOrder(item.id);
		cancelEdit();
		$('#taskrow_'+item.id).effect("highlight", {color:theme.newTaskFlashColor}, 2000);
		changeTaskCnt(item, 1);
		refreshTaskCnt();
	}, 'json');
	$("#edittags").flushCache();
	flag.tagsChanged = true;
	return false;
}

function publishCurList()
{
	if(!curList) return false;
	var nocache = '&rnd='+Math.random();
	$.post('ajax.php?publishList'+nocache, { list:curList.id, publish:curList.published?0:1 }, function(json){
		if(!parseInt(json.total)) return;
		curList.published = curList.published?0:1;
		if(curList.published) $('#btnPublish').addClass('mtt-item-checked');
		else $('#btnPublish').removeClass('mtt-item-checked');
	}, 'json');
}

function taskContextMenu(el, id)
{
	if(!cmenu) cmenu = new mttMenu('taskcontextcontainer', {onclick:taskContextClick});
	cmenu.tag = id;
	cmenu.show(el);
	return false;
}

function taskContextClick(el, menu)
{
	if(!el.id) return;
	var taskId = cmenu.tag;
	var id = el.id, value;
	var a = id.split(':');
	if(a.length == 2) {
		id = a[0];
		value = a[1];
	}
	switch(id) {
		case 'cmenu_edit': editTask(taskId); break;
		case 'cmenu_note': toggleTaskNote(taskId); break;
		case 'cmenu_delete': deleteTask(taskId); break;
		case 'cmenu_prio': setTaskPrio(taskId, parseInt(value)); break;
		case 'cmenu_list':
			if(menu.$caller && menu.$caller.attr('id')=='cmenu_move') moveTaskToList(taskId, value);
			break;
	}
}

function mttMenu(container, options)
{
	var menu = this;
	this.container = $('#'+container);
	this.menuOpen = false;
	this.options = options || {};
	this.submenu = [];
	this.curSubmenu = null;
	this.showTimer = null;

	this.container.find('li').click(function(){
		menu.onclick(this, menu);
		return false;
	})
	.each(function(){

		var submenu = 0;
		if($(this).is('.mtt-menu-indicator'))
		{
			submenu = new mttMenu($(this).attr('submenu'));
			submenu.$caller = $(this);
			submenu.parent = menu;
			if(menu.root) submenu.root = menu.root;	//!! be careful with circular references
			else submenu.root = menu;
			menu.submenu.push(submenu);

			submenu.container.find('li').click(function(){
				submenu.root.onclick(this, submenu);
				return false;
			});
		}

		$(this).hover(
			function(){
				if(!$(this).is('.mtt-menu-item-active')) menu.container.find('li').removeClass('mtt-menu-item-active');
				clearTimeout(menu.showTimer);
				if(menu.curSubmenu && menu.curSubmenu.menuOpen && menu.curSubmenu != submenu)
				{
					menu.container.find('li').removeClass('mtt-menu-item-active');
					var curSubmenu = menu.curSubmenu;
					hideTimer = setTimeout(function(){
						curSubmenu.hide();
					}, 400);
				}

				if(!submenu || menu.curSubmenu == submenu && menu.curSubmenu.menuOpen) return;
				menu.curSubmenu = submenu;
				menu.showTimer = setTimeout(function(){
					submenu.showSub();
				}, 300);
				$(this).addClass('mtt-menu-item-active');
			},
			null
		);

	});

	this.onclick = function(item, fromMenu)
	{
		if($(item).is('.mtt-disabled,.mtt-menu-indicator')) return;
		menu.close();
		if(this.options.onclick) this.options.onclick(item, fromMenu);
	}

	this.hide = function()
	{
		for(var i in this.submenu) this.submenu[i].hide();
		this.container.hide();
		this.container.find('li').removeClass('mtt-menu-item-active');
		this.menuOpen = false;
	}

	this.close = function(event)
	{
		if(!this.menuOpen) return;
		if(event)
		{
			var t = event.target;
			if(t == this.caller) return;
			while(t.parentNode) {
				if(t.parentNode == this.caller) return;
				t = t.parentNode;
			}
		}
		this.hide();
		$(this.caller).removeClass('mtt-menu-button-active');
		$(document).unbind('click.mttmenuclose');
	}

	this.show = function(caller)
	{
		if(this.menuOpen)
		{
			this.close();
			if(this.caller && this.caller == caller) return;
		}
		$(document).triggerHandler('click.mttmenuclose'); //close any other open menu
		this.caller = caller;
		$caller = $(caller);
		$caller.addClass('mtt-menu-button-active');
		var offset = $caller.offset();
		var x2 = $(window).width() + $(document).scrollLeft() - this.container.outerWidth(true) - 1;
		var x = offset.left < x2 ? offset.left : x2;
		if(x<0) x=0;
		var y = offset.top+caller.offsetHeight-1;
		if(y + this.container.outerHeight(true) > $(window).height() + $(document).scrollTop()) y = offset.top - this.container.outerHeight();
		if(y<0) y=0;
		this.container.css({ position: 'absolute', top: y, left: x, width:this.container.width() /*, 'min-width': $caller.width()*/ }).show();
		var menu = this;
		$(document).bind('click.mttmenuclose', function(e){ menu.close(e) });
		this.menuOpen = true;
	}

	this.showSub = function()
	{
		var offset = this.$caller.offset();
		var x = offset.left+this.$caller.outerWidth();
		if(x + this.container.outerWidth(true) > $(window).width() + $(document).scrollLeft()) x = offset.left - this.container.outerWidth() - 1;
		if(x<0) x=0;
		var y = offset.top + this.parent.container.offset().top-this.parent.container.find('li:first').offset().top;
		if(y +  this.container.outerHeight(true) > $(window).height() + $(document).scrollTop()) y = $(window).height() + $(document).scrollTop()- this.container.outerHeight(true) - 1;
		if(y<0) y=0;
		this.container.css({ position: 'absolute', top: y, left: x, width:this.container.width() /*, 'min-width': this.$caller.outerWidth()*/ }).show();
		this.menuOpen = true;
	}

	this.destroy = function()
	{
		for(var i in this.submenu) {
			this.submenu[i].destroy();
			delete this.submenu[i];
		}
		this.container.find('li').unbind(); //'click mouseenter mouseleave'
	}

}

function moveTaskToList(taskId, listId)
{
	if(curList.id == listId) return;
	$.post('ajax.php?moveTask&rnd='+Math.random(), { id:taskId, from:curList.id, to:listId }, function(json){
		if(!parseInt(json.total)) return;
		changeTaskCnt(taskList[taskId], -1)
		delete taskList[taskId];
		taskOrder.splice($.inArray(taskId,taskOrder), 1);
		$('#taskrow_'+taskId).fadeOut('normal', function(){ $(this).remove() });
		refreshTaskCnt();
	}, 'json');
	$("#edittags").flushCache();
	flag.tagsChanged = true;
}

function cmenuListsLoaded()
{
	if(cmenu) cmenu.destroy();
	cmenu = null;
	var s = '';
	for(var i in tabLists) {
		s += '<li id="cmenu_list:'+tabLists[i].id+'">'+tabLists[i].name+'</li>';
	}
	$('#listsmenucontainer ul').html(s);
}

function cmenuListAdded(list)
{
	if(cmenu) cmenu.destroy();
	cmenu = null;
	$('#listsmenucontainer ul').append('<li id="cmenu_list:'+list.id+'">'+list.name+'</li>');
}

function cmenuListRenamed(list)
{
	$('#cmenu_list\\:'+list.id).text(list.name);
}

function showCompletedToggle()
{
	var act = curList.showCompl ? 0 : 1;
	curList.showCompl = tabLists[curList.i].showCompl = act;
	if(act) $('#btnShowCompleted').addClass('mtt-item-checked');
	else $('#btnShowCompleted').removeClass('mtt-item-checked');
	loadTasks({setCompl:1});
}

function listOrderChanged(event, ui)
{
	var order = $(this).sortable("serialize");
	$.post('ajax.php?changeListOrder'+'&rnd='+Math.random(), order, function(json){
	}, 'json');
}

function actionListSelected(list)
{
	if(list.published) $('#btnPublish').addClass('mtt-item-checked');
	else $('#btnPublish').removeClass('mtt-item-checked');
	if(list.showCompl) $('#btnShowCompleted').addClass('mtt-item-checked');
	else $('#btnShowCompleted').removeClass('mtt-item-checked');
	$('#listsmenucontainer li').removeClass('mtt-disabled');
	$('#cmenu_list\\:'+list.id).addClass('mtt-disabled');
}

function tz()
{
	return -1 * (new Date()).getTimezoneOffset();
}

function listMenu(el)
{
	if(!mytinytodo.menus.listMenu) mytinytodo.menus.listMenu = new mttMenu('mylistscontainer', {onclick:listMenuClick});
	mytinytodo.menus.listMenu.show(el);
}

function listMenuClick(el, menu)
{
	if(!el.id) return;
	switch(el.id) {
		case 'btnAddList': addList(); break;
		case 'btnRenameList': renameCurList(); break;
		case 'btnDeleteList': deleteCurList(); break;
		case 'btnPublish': publishCurList(); break;
		case 'btnShowCompleted': showCompletedToggle(); break;
		case 'sortByHand': setSort(0); break;
		case 'sortByPrio': setSort(1); break;
		case 'sortByDueDate': setSort(2); break;
	}
}

function stopBubble(e)
{
	if(!e) e = window.event;
	e.cancelBubble = true;
	if(e.stopPropagation) e.stopPropagation();
	return false;
}
