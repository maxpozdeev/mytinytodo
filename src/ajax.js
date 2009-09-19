theme = {
	newTaskFlashColor: '#ffffaa',
	editTaskFlashColor: '#bbffaa',
	errorFlashColor: '#ffffff'
};

//Global vars
var taskList, taskOrder;
var filter = { compl:0, search:'', tag:'', due:'' };
var sortOrder; //save task order before dragging
var searchTimer;
var objPrio = {};
var selTask = 0;
var sortBy = 0;
var flag = { needAuth:false, isLogged:false, canAllRead:true, tagsChanged:true, windowTaskEditMoved:false };
var tz = 0;
var img = {
	'note': ['images/page_white_text_add_bw.png','images/page_white_text_add.png'],
	'edit': ['images/page_white_edit_bw.png','images/page_white_edit.png'],
	'del': ['images/page_cross_bw.png','images/page_cross.png']
};
var taskCnt = { total:0, past: 0, today:0, soon:0 };
var tmp = {};

function loadTasks()
{
	tz = -1 * (new Date()).getTimezoneOffset();
	setAjaxErrorTrigger();
	if(filter.search) search = '&s='+encodeURIComponent(filter.search); else search = '';
	if(filter.tag) tag = '&t='+encodeURIComponent(filter.tag); else tag = '';
	nocache = '&rnd='+Math.random();
	$.getJSON('ajax.php?loadTasks&compl='+filter.compl+'&sort='+sortBy+search+tag+'&tz='+tz+nocache, function(json){
		resetAjaxErrorTrigger();
		$('#total').html(json.total);
		taskList = new Array();
		taskOrder = new Array();
		taskCnt.past = taskCnt.today = taskCnt.soon = 0;
		taskCnt.total = json.total;
		var tasks = '';
		$.each(json.list, function(i,item){
			tasks += prepareTaskStr(item);
			taskList[item.id] = item;
			taskOrder.push(parseInt(item.id));
			if(!item.compl) changeTaskCnt(item.dueClass);
		});
		refreshTaskCnt();
		$('#tasklist').html(tasks);
		if(filter.compl) showhide($('#compl_hide'),$('#compl_show'));
		else showhide($('#compl_show'),$('#compl_hide'));
		if(json.denied) errorDenied();
	});
}

function prepareTaskStr(item)
{
	id = parseInt(item.id);
	prio = parseInt(item.prio);
	readOnly = (flag.needAuth && flag.canAllRead && !flag.isLogged) ? true : false;
	return '<li id="taskrow_'+id+'" class="'+(item.compl?'task-completed ':'')+item.dueClass+'" onDblClick="editTask('+id+')"><div class="task-actions">'+
		'<a href="#" onClick="return toggleTaskNote('+id+')"><img src="'+img.note[0]+'" onMouseOver="this.src=img.note[1]" onMouseOut="this.src=img.note[0]" title="'+lang.actionNote+'"></a>'+
		'<a href="#" onClick="return editTask('+id+')"><img src="'+img.edit[0]+'" onMouseOver="this.src=img.edit[1]" onMouseOut="this.src=img.edit[0]" title="'+lang.actionEdit+'"></a>'+
		'<a href="#" onClick="return deleteTask('+id+')"><img src="'+img.del[0]+'" onMouseOver="this.src=img.del[1]" onMouseOut="this.src=img.del[0]" title="'+lang.actionDelete+'"></a></div>'+
		'<div class="task-left"><input type="checkbox" '+(readOnly?'disabled':'')+' onClick="completeTask('+id+',this)" '+(item.compl?'checked':'')+'></div>'+
		'<div class="task-middle">'+prepareDuedate(item.duedate, item.dueClass, item.dueStr)+
		'<span class="nobr"><span class="task-through">'+preparePrio(prio,id)+'<span class="task-title">'+prepareHtml(item.title)+'</span>'+
		prepareTagsStr(item.tags)+'<span class="task-date">'+lang.taskDate(item.date)+'</span></span></span>'+
		'<div class="task-note-block'+(item.note==''?' hidden':'')+'">'+
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
	s = s.replace(/(^|\s|>)(www\.([\w\#$%&~\/.\-\+;:=,\?\[\]@]+?))(,|\.|:|)?(?=\s|&quot;|&lt;|&gt;|\"|<|>|$)/gi, '$1<a href="http://$2" target="_blank">$2</a>$4');
	return s.replace(/(^|\s|>)((?:http|https|ftp):\/\/([\w\#$%&~\/.\-\+;:=,\?\[\]@]+?))(,|\.|:|)?(?=\s|&quot;|&lt;|&gt;|\"|<|>|$)/ig, '$1<a href="$2" target="_blank">$2</a>$4');
}

function preparePrio(prio,id)
{
	cl = v = '';
	if(prio < 0) { cl = 'prio-neg'; v = '&minus;'+Math.abs(prio); }
	else if(prio > 0) { cl = 'prio-pos'; v = '+'+prio; }
	else { cl = 'prio-o'; v = '&plusmn;0'; }
	return '<span class="task-prio '+cl+'" onMouseOver="prioPopup(1,this,'+id+')" onMouseOut="prioPopup(0,this)">'+v+'</span>';
}

function prepareTagsStr(tags)
{
	if(!tags || tags == '') return '';
	a = tags.split(',');
	if(!a.length) return '';
	for(i in a) {
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
	var tz = -1 * (new Date()).getTimezoneOffset();
	setAjaxErrorTrigger()
	nocache = '&rnd='+Math.random();
	$.post('ajax.php?newTask'+nocache, { title: form.task.value, tz:tz, tag:filter.tag }, function(json){
		resetAjaxErrorTrigger();
		if(!parseInt(json.total)) return;
		$('#total').text( parseInt($('#total').text()) + parseInt(json.total) );
		form.task.value = '';
		var item = json.list[0];
		taskList[item.id] = item;
		taskOrder.push(parseInt(item.id));
		$('#tasklist').append(prepareTaskStr(item));
		changeTaskOrder(item.id);
		$('#taskrow_'+item.id).effect("highlight", {color:theme.newTaskFlashColor}, 2000);
	}, 'json');
	flag.tagsChanged = true;
	return false;
}

function setAjaxErrorTrigger()
{
	resetAjaxErrorTrigger();
	$("#msg").ajaxError(function(event, request, settings){
		var errtxt;
		if(request.status == 0) errtxt = 'Bad connection';
		else if(request.status != 200) errtxt = 'HTTP (ajax.php): '+request.status+'/'+request.statusText;
		else errtxt = request.responseText;
		flashError(lang.error, errtxt);
	});
}

function flashError(str, details)
{
	$("#msg").text(str).css('display','block');
	$("#msgdetails").text(details);
	$("#loading").hide();
	$("#msg").effect("highlight", {color:theme.errorFlashColor}, 700);
}

function toggleMsgDetails()
{
	el = $("#msgdetails");
	if(!el) return;
	if(el.css('display') == 'none') el.show();
	else el.hide()
}

function resetAjaxErrorTrigger()
{
	$("#msg").hide().unbind('ajaxError');
	$("#msgdetails").text('').hide();
}

function deleteTask(id)
{
	if(!confirm(lang.confirmDelete)) {
		return false;
	}
	setAjaxErrorTrigger()
	nocache = '&rnd='+Math.random();
	$.getJSON('ajax.php?deleteTask='+id+nocache, function(json){
		resetAjaxErrorTrigger();
		if(!parseInt(json.total)) return;
		$('#total').text( parseInt($('#total').text()) - 1 );
		var item = json.list[0];
		taskOrder.splice($.inArray(id,taskOrder), 1);
		$('#taskrow_'+item.id).fadeOut('normal', function(){ $(this).remove() });
		if(!taskList[id].compl && changeTaskCnt(taskList[id].dueClass, -1)) refreshTaskCnt();
		delete taskList[id];
	});
	flag.tagsChanged = true;
	return false;
}

function completeTask(id,ch)
{
	compl = 0;
	if(ch.checked) compl = 1;
	setAjaxErrorTrigger();
	$.getJSON('ajax.php?completeTask='+id+'&compl='+compl+nocache, function(json){
		resetAjaxErrorTrigger();
		if(!parseInt(json.total)) return;
		var item = json.list[0];
		if(item.compl) $('#taskrow_'+id).addClass('task-completed');
		else $('#taskrow_'+id).removeClass('task-completed');
		if(changeTaskCnt(taskList[id].dueClass, item.compl?-1:1)) refreshTaskCnt();
		if(item.compl && !filter.compl) {
			delete taskList[id];
			taskOrder.splice($.inArray(id,taskOrder), 1);
			$('#taskrow_'+item.id).fadeOut('normal', function(){ $(this).remove() });
			$('#total').html( parseInt($('#total').text())-1 );
		}
		else if(filter.compl) {
			taskList[id].ow = item.ow;
			taskList[id].compl = item.compl;
			changeTaskOrder(id);
			$('#taskrow_'+id).effect("highlight", {color:theme.editTaskFlashColor}, 'normal');
		}
	});
	return false;
}

function toggleTaskNote(id)
{
	aArea = '#tasknotearea'+id;
	if($(aArea).css('display') == 'none')
	{
		$('#notetext'+id).val(taskList[id].noteText);
		$('#taskrow_'+id+'>div>div.task-note-block').removeClass('hidden');
		$(aArea).css('display', 'block');
		$('#tasknote'+id).css('display', 'none');
		$('#notetext'+id).focus();
	} else {
		cancelTaskNote(id)
	}
	return false;
}

function cancelTaskNote(id)
{
	$('#tasknotearea'+id).css('display', 'none');
	$('#tasknote'+id).css('display', 'block');
	if($('#tasknote'+id).text() == '') {
		$('#taskrow_'+id+'>div>div.task-note-block').addClass('hidden');
	}
	return false;
}

function saveTaskNote(id)
{
	setAjaxErrorTrigger()
	nocache = '&rnd='+Math.random();
	$.post('ajax.php?editNote='+id+nocache, {note: $('#notetext'+id).val()}, function(json){
		resetAjaxErrorTrigger();
		if(!parseInt(json.total)) return;
		var item = json.list[0];
		taskList[id].note = item.note;
		taskList[id].noteText = item.noteText;
		$('#tasknote'+item.id+'>span').html(prepareHtml(item.note));
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
	sel = document.edittask.prio;
	for(i=0; i<sel.length; i++) {
		if(sel.options[i].value == item.prio) sel.options[i].selected = true;
	}
	$('<div id="overlay"></div>').appendTo('body').css('opacity', 0.5).show();
	w = $('#page_taskedit');
	if(!flag.windowTaskEditMoved)
	{
		var x,y;
		if(document.getElementById('viewport')) {
			x = Math.floor(Math.min($(window).width(),screen.width)/2 - w.outerWidth()/2);
			y = Math.floor(Math.min($(window).height(),screen.height)/2 - w.outerHeight()/2);
		}
		else {
			x = Math.floor($(window).width()/2 - w.outerWidth()/2);
			y = Math.floor($(window).height()/2 - w.outerHeight()/2);
		}
		if(x < 0) x = 0;
		if(y < 0) y = 0;
		w.css('left',x).css('top',y);
		tmp.editformpos = [x, y];
	}
	w.fadeIn('fast');	//.show();
	$(document).bind('keydown', cancelEdit);
	return false;
}

function cancelEdit(e)
{
	if(e && e.keyCode != 27) return;
	$(document).unbind('keydown', cancelEdit);
	$('#page_taskedit').hide();
	$('#overlay').remove();
	document.edittask.task.value = '';
	document.edittask.note.value = '';
	document.edittask.tags.value = '';
	document.edittask.duedate.value = '';
	return false;
}

function saveTask(form)
{
	if(flag.needAuth && !flag.isLogged && flag.canAllRead) return false;
	setAjaxErrorTrigger();
	nocache = '&rnd='+Math.random();
	$.post('ajax.php?editTask='+form.id.value+nocache, { title: form.task.value, note:form.note.value, prio:form.prio.value, tags:form.tags.value, duedate:form.duedate.value }, function(json){
		resetAjaxErrorTrigger();
		if(!parseInt(json.total)) return;
		var item = json.list[0];
		if(!taskList[item.id].compl) changeTaskCnt(taskList[item.id].dueClass, -1);
		taskList[item.id] = item;
		$('#taskrow_'+item.id).replaceWith(prepareTaskStr(item));
		if(item.note == '') $('#taskrow_'+item.id+'>div.task-note-block').addClass('hidden');
		else $('#taskrow_'+item.id+'>div.task-note-block').removeClass('hidden');
		if(sortBy != 0) changeTaskOrder(item.id);
		cancelEdit();
		if(!taskList[item.id].compl) {
			changeTaskCnt(item.dueClass, 1);
			refreshTaskCnt();
		}
		$('#taskrow_'+item.id).effect("highlight", {color:theme.editTaskFlashColor}, 'normal');
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
	n = $(this).sortable('toArray');
	// remove possible empty id's
	for(i=0; i<sortOrder.length; i++) {
		if(sortOrder[i] == '') { sortOrder.splice(i,1); i--; }
	}
	if(n.toString() == sortOrder.toString()) return;
	// make assoc from array for easy index
	var h0 = new Array();
	for(j=0; j<sortOrder.length; j++) {
		h0[sortOrder[j]] = j;
	}
	var h1 = new Array();
	for(j=0; j<n.length; j++) {
		h1[n[j]] = j;
		taskOrder[j] = n[j].split('_')[1];
	}
	// prepare param string 
	var s = '';
	var diff;
	var replaceOW = taskList[sortOrder[h1[itemId]].split('_')[1]].ow;
	for(j in h0)
	{
		diff = h1[j] - h0[j];
		if(diff != 0) {
			a = j.split('_');
			if(j == itemId) diff = replaceOW - taskList[a[1]].ow;
			s += a[1] +'='+ diff+ '&';
			taskList[a[1]].ow += diff;
		}
	}
	setAjaxErrorTrigger();
	nocache = '&rnd='+Math.random();
	$.post('ajax.php?changeOrder'+nocache, { order: s }, function(json){
		resetAjaxErrorTrigger();
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

function tabSelected(event, ui)
{
	// reload tasks when we return to task tab (from search tab)
	if(ui.index == 0 && filter.search != '') {
		filter.search = '';
		$('#searchbarkeyword').text('');
		$('#searchbar').hide();
		loadTasks();
	}
}

function dehtml(str)
{
	return str.replace(/&quot;/g,'"').replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&amp;/g,'&');
}

function errorDenied()
{
	flashError(lang.denied);
}

function updateAccessStatus(onInit)
{
	if(flag.needAuth && !flag.isLogged) $("#tasklist").sortable('disable').addClass('readonly');
	else if(sortBy == 0) $("#tasklist").sortable('enable').removeClass('readonly');
	else $("#tasklist").removeClass('readonly');

	if(!flag.canAllRead && !flag.isLogged) {
		$('#page_tasks > h3,#taskcontainer').hide();
		$('#tabs').hide();
	}
	else {
		$('#page_tasks > h3,#taskcontainer').show();
		$('#tabs').show();
	}
	if(flag.needAuth) {
		$('#bar_auth').show();
		showhide($("#bar_login"),$("#bar_logout"));
	}
	if(!flag.needAuth) {
		$("#authstr").text('').hide();
		$('#bar_auth').hide();
	}
	else if(flag.canAllRead && !flag.isLogged) $("#authstr").text(lang.readonly).addClass('attention').show();
	else if(flag.isLogged) showhide($("#bar_logout"),$("#bar_login"));
	else if(!flag.canAllRead) $("#authstr").text('').hide();

	if(onInit == null || !onInit)
	{
		if(flag.isLogged) $("#tabs").tabs('enable',0).tabs('enable',1).tabs('select',0);
		else if(flag.canAllRead) $("#tabs").tabs('enable',1).tabs('select', 1).tabs('disable',0);
		else $("#tabs").tabs('disable',0).tabs('disable',1);
	}
}

function doAuth(form)
{
	setAjaxErrorTrigger();
	nocache = '&rnd='+Math.random();
	$.post('ajax.php?login'+nocache, { password: form.password.value }, function(json){
		resetAjaxErrorTrigger();
		form.password.value = '';
		if(json.logged)
		{
			flag.isLogged = true;
			if(filter.search != '') {
				filter.search = '';
				$('#searchbarkeyword').text('');
				$('#searchbar').hide();
			}
			updateAccessStatus();
			loadTasks();
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
	setAjaxErrorTrigger();
	nocache = '&rnd='+Math.random();
	$.getJSON('ajax.php?logout'+nocache, function(json){
		resetAjaxErrorTrigger();
	});
	flag.isLogged = false;
	updateAccessStatus();
	if(flag.canAllRead) {
		loadTasks();
	}
	else {
		$('#total').html('0');
		$('#tasklist').html('');
	}
	return false;
}

function tasklistClick(e)
{
	node = e.target.nodeName;
	if(node=='SPAN' || node=='LI' || node=='DIV') {
		li = getRecursParent(e.target, 'LI', 10);
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

function cancelTagFilter()
{
	$('#tagcloudbtn>.btnstr').text($('#tagcloudbtn').attr('title'));
	filter.tag = '';
	loadTasks();
}

function addFilterTag(tag)
{
	filter.tag = tag;
	loadTasks();
	$('#tagcloudbtn>.btnstr').html(lang.tagfilter + ' <span class="tag">'+tag+'</span>');
}

function showAuth(el)
{
	w = $('#authform');
	if(w.css('display') == 'none')
	{
		offset = $(el).offset();
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
	offset = $(el).offset();
	$('#priopopup').css({ position: 'absolute', top: offset.top + 1, left: offset.left + 1 });
	objPrio.taskId = id;
	objPrio.el = el;
	objPrio.timer = setTimeout("$('#priopopup').show()", 300);
}

function prioClick(prio, el)
{
	el.blur();
	prio = parseInt(prio);
	setAjaxErrorTrigger();
	nocache = '&rnd='+Math.random();
	$.getJSON('ajax.php?setPrio='+objPrio.taskId+'&prio='+prio+nocache, function(json){
		resetAjaxErrorTrigger();
	});
	taskList[objPrio.taskId].prio = prio;
	$(objPrio.el).replaceWith(preparePrio(prio, objPrio.taskId));
	$('#priopopup').fadeOut('fast'); //.hide();
	if(sortBy != 0) changeTaskOrder(objPrio.taskId);
	$('#taskrow_'+objPrio.taskId).effect("highlight", {color:theme.editTaskFlashColor}, 'normal');
}

function showSort(el)
{
	w = $('#sortform');
	if(w.css('display') == 'none')
	{
		offset = $(el).offset();
		w.css({ position: 'absolute', top: offset.top+el.offsetHeight-1, left: offset.left , 'min-width': $(el).width() }).show();
		$(document).bind("click", sortClose);
	}
	else {
		el.blur();
		sortClose();
	}
}

function setSort(v, init)
{
	if(v == 0) $('#sort>.btnstr').text($('#sortByHand').text());
	else if(v == 1) $('#sort>.btnstr').text($('#sortByPrio').text());
	else if(v == 2) $('#sort>.btnstr').text($('#sortByDueDate').text());
	else return;
	if(sortBy != v) {
		sortBy = v;
		if(v==0) $("#tasklist").sortable('enable');
		else $("#tasklist").sortable('disable');
		if(!init) {
			changeTaskOrder();
			exp = new Date();
			exp.setTime(exp.getTime() + 3650*86400*1000);	//+10 years
			document.cookie = "sort="+sortBy+'; expires='+exp.toUTCString();
		}
	}
}

function sortClose(e)
{
	if(e) {
		if(isParentId(e.target, ['sortform','sort'])) return;
	}
	$(document).unbind("click", sortClose);
	$('#sortform').hide();
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
	oldOrder = taskOrder.slice();
	if(sortBy == 0) taskOrder.sort( function(a,b){ 
			if(taskList[a].compl != taskList[b].compl) return taskList[a].compl-taskList[b].compl;
			return taskList[a].ow-taskList[b].ow
		});
	else if(sortBy == 1) taskOrder.sort( function(a,b){
			if(taskList[a].compl != taskList[b].compl) return taskList[a].compl-taskList[b].compl;
			if(taskList[a].prio != taskList[b].prio) return taskList[b].prio-taskList[a].prio;
			if(taskList[a].dueInt != taskList[b].dueInt) return taskList[a].dueInt-taskList[b].dueInt;
			return taskList[a].ow-taskList[b].ow; 
		});
	else if(sortBy == 2) taskOrder.sort( function(a,b){
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
		indx = $.inArray(id,taskOrder);
		if(indx ==0) {
			$('#tasklist').prepend($('#taskrow_'+id))
		} else {
			after = taskOrder[indx-1];
			$('#taskrow_'+after).after($('#taskrow_'+id));
		}
	}
	else {
		o = $('#tasklist');
		for(i in taskOrder) {
			o.append($('#taskrow_'+taskOrder[i]));
		}
	}
}

function showTagCloud(el)
{
	w = $('#tagcloud');
	if(w.css('display') == 'none')
	{
		if(flag.tagsChanged)
		{
			$('#tagcloudcontent').html('');
			$('#tagcloudload').show();
			offset = $(el).offset();
			l = Math.ceil(offset.left - w.outerWidth()/2 + $(el).outerWidth()/2);
			if(l<0) l=0;
			w.css({ position: 'absolute', top: offset.top+el.offsetHeight-1, left: l }).show();

			setAjaxErrorTrigger();
			nocache = '&rnd='+Math.random();
			$.getJSON('ajax.php?tagCloud'+nocache, function(json){
				resetAjaxErrorTrigger();
				$('#tagcloudload').hide();
				if(!parseInt(json.total)) return;
				var cloud = '';
				$.each(json.cloud, function(i,item){
					cloud += '<a href="#" onClick=\'addFilterTag("'+item.tag+'");tagCloudClose();return false;\' class="tag w'+item.w+'" >'+item.tag+'</a>';
				});
				$('#tagcloudcontent').html(cloud)
				flag.tagsChanged = false;
			});
		}
		else {
			offset = $(el).offset();
			l = Math.ceil(offset.left - w.outerWidth()/2 + $(el).outerWidth()/2);
			if(l<0) l=0;
			w.css({ position: 'absolute', top: offset.top+el.offsetHeight-1, left: l }).show();
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

function preloadImg()
{
	for(i in img) {
		o = new Image();
		o.src = img[i][0];
		if(img[i][1] != img[i][0]) {
			o = new Image();
			o.src = img[i][1];
		}
	}
}

function changeTaskCnt(cl, dir)
{
	if(!dir) dir = 1;
	else if(dir > 0) dir = 1;
	else if(dir < 0) die = -1;
	if(cl == 'soon') { taskCnt.soon += dir; return true; }
	else if(cl == 'today') { taskCnt.today += dir; return true; }
	else if(cl == 'past') { taskCnt.past+= dir; return true; }
}

function refreshTaskCnt()
{
	$('#cnt_past').text(taskCnt.past);
	$('#cnt_today').text(taskCnt.today);
	$('#cnt_soon').text(taskCnt.soon);
}

function showTaskview(el)
{
	w = $('#taskview');
	if(w.css('display') == 'none')
	{
		offset = $(el).offset();
		w.css({ position: 'absolute', top: offset.top+el.offsetHeight-1, left: offset.left , 'min-width': $(el).width() }).show();
		$(document).bind("click", taskviewClose);
	}
	else {
		el.blur();
		taskviewClose();
	}
}

function taskviewClose(e)
{
	if(e) {
		if(isParentId(e.target, ['taskviewcontainer','taskview'])) return;
	}
	$(document).unbind("click", taskviewClose);
	$('#taskview').hide();
}

function setTaskview(v)
{
	if(v == 0)
	{
		if(filter.due == '' && filter.compl == 0) return;
		$('#taskviewcontainer .btnstr').text($('#view_tasks').text());
		if(filter.due != '') {
			$('#tasklist').removeClass('filter-'+filter.due);
			filter.due = '';
			if(filter.compl == 0) $('#total').text(taskCnt.total);
		}
		if(filter.compl != 0) {
			filter.compl = 0;
			$('#total').text('...');
			loadTasks();
		}
	}
	else if(v == 1)
	{
		if(filter.due == '' && filter.compl == 1) return;
		$('#taskviewcontainer .btnstr').text($('#view_compl').text());
		if(filter.due != '') {
			$('#tasklist').removeClass('filter-'+filter.due);
			filter.due = '';
			if(filter.compl == 1) $('#total').text(taskCnt.total);
		}
		if(filter.compl != 1) {
			filter.compl = 1;
			$('#total').text('...');
			loadTasks();
		}
	}
	else if(v=='past' || v=='today' || v=='soon')
	{
		if(filter.due == v) return;
		else if(filter.due != '') {
			$('#tasklist').removeClass('filter-'+filter.due);
		}
		$('#tasklist').addClass('filter-'+v);
		$('#taskviewcontainer .btnstr').text($('#view_'+v).text());
		$('#total').text(taskCnt[v]);
		filter.due = v;
	}
}

function editFormResize(startstop, event)
{
	f = $('#page_taskedit');
	if(startstop == 1) {
		tmp.editformdiff = f.height() - $('#page_taskedit textarea').height();
	}
	else if(startstop == 2) {
		//to avoid bug http://dev.jqueryui.com/ticket/3628
		if(f.is('.ui-draggable')) {
			f.css('left',tmp.editformpos[0]).css('top',tmp.editformpos[1]).css('position', 'fixed');
		}
	}
	else  $('#page_taskedit textarea').height(f.height() - tmp.editformdiff);
}
