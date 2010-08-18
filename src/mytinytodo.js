/*
	This file is part of myTinyTodo.
	(C) Copyright 2009-2010 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v3 license. See file COPYRIGHT for details.
*/

(function(){

var taskList = new Array(), taskOrder = new Array();
var filter = { compl:0, search:'', tag:'', due:'' };
var sortOrder; //save task order before dragging
var searchTimer;
var objPrio = {};
var selTask = 0;
var flag = { needAuth:false, isLogged:false, tagsChanged:true, windowTaskEditMoved:false, readOnly:false };
var taskCnt = { total:0, past: 0, today:0, soon:0 };
var cmenu;
var tabLists = {
	_lists: {},
	_length: 0,
	_order: [],
	clear: function(){ this._lists = {}; this._length = 0; this._order = []; },
	length: function(){ return this._length; },
	exists: function(id){ if(this._lists[id]) return true; else return false; },
	add: function(list){ this._lists[list.id] = list; this._length++; this._order.push(list.id); },
	replace: function(list){ this._lists[list.id] = list; },
	get: function(id){ return this._lists[id]; },
	getAll: function(){ var r = []; for(var i in this._order) { r.push(this._lists[this._order[i]]); }; return r; },
	reorder: function(order){ this._order = order; }
};
var curList = 0;
var tagsList = [];

var mytinytodo = window.mytinytodo = _mtt = {

	theme: {
		newTaskFlashColor: '#ffffaa',
		editTaskFlashColor: '#bbffaa',
		msgFlashColor: '#ffffff'
	},

	actions: {},
	menus: {},
	mttUrl: '',
	templateUrl: '',
	options: {
		openList: 0,
		singletab: false,
		autotag: false
	},

	timers: {
		previewtag: 0
	},
		
	tagFilter: [],

	lang: {
		__lang: null,

		daysMin: [],
		daysLong: [],
		monthsShort: [],
		monthsLong: [],

		get: function(v) {
			if(this.__lang[v]) return this.__lang[v];
			else return v;
		},
		
		init: function(lang)
		{
			this.__lang = lang;
			this.daysMin = this.__lang.daysMin;
			this.daysLong = this.__lang.daysLong;
			this.monthsShort = this.__lang.monthsMin;
			this.monthsLong = this.__lang.monthsLong;
		}
	},

	pages: { 
		current: { page:'tasks', class:'' },
		prev: []
	},

	// procs
	init: function(options, lang)
	{
		jQuery.extend(this.options, options);

		flag.needAuth = options.needAuth ? true : false;
		flag.isLogged = options.isLogged ? true : false;

		if(this.options.showdate) $('#page_tasks').addClass('show-inline-date');
		if(this.options.singletab) $('#lists .mtt-tabs').addClass('mtt-tabs-only-one');


		// handlers
		$('.mtt-tabs-add-button').click(function(){
			addList();
		});

		$('.mtt-tabs-select-button').click(function(event){
			if(event.metaKey || event.ctrlKey) {
				// toggle singetab interface
				_mtt.applySingletab(!_mtt.options.singletab);
				return false;
			}
			if(!_mtt.menus.selectlist) _mtt.menus.selectlist = new mttMenu('slmenucontainer', {onclick:slmenuSelect});
			_mtt.menus.selectlist.show(this);
		});

		$('.mtt-tabs-search-button').click(function(){
			addsearchToggle(1);
		});

		$('#newtask_form').submit(function(){
			submitNewTask(this);
			return false;
		});

		$('#newtask_adv').click(function(){
			showEditForm(1);
			return false;
		});

		$('#search_form').submit(function(){
			searchTasks();
			return false;
		});

		$('#search_close').click(function(){
			addsearchToggle(0);
			return false;
		});

		$('#search').keyup(timerSearch);


		$('#taskview').click(function(){
			if(!_mtt.menus.taskview) _mtt.menus.taskview = new mttMenu('taskviewcontainer');
			_mtt.menus.taskview.show(this);
		});

		$('#tag_filters .tag-filter-close').live('click', function(){
			cancelTagFilter($(this).attr('tagid'));
		});

		$('#tagcloudbtn').click(function(){
			if(!_mtt.menus.tagcloud) _mtt.menus.tagcloud = new mttMenu('tagcloud', {
				beforeShow: function(){
					if(flag.tagsChanged) {
						$('#tagcloudcontent').html('');
						$('#tagcloudload').show();
						loadTags(function(){$('#tagcloudload').hide();});
					}
				}, adjustWidth:true
			});
			_mtt.menus.tagcloud.show(this);
		});

		$('#tagcloudcancel').click(function(){
			if(_mtt.menus.tagcloud) _mtt.menus.tagcloud.close();
		});

		$('#tagcloudcontent .tag').live('click', function(){
			addFilterTag($(this).attr('tag'), $(this).attr('tagid'));
			if(_mtt.menus.tagcloud) _mtt.menus.tagcloud.close();
			return false;
		});	

		$('#mtt-notes-show').click(function(){
			toggleAllNotes(1);
			this.blur();
			return false;
		});

		$('#mtt-notes-hide').click(function(){
			toggleAllNotes(0);
			this.blur();
			return false;
		});

		$('#taskviewcontainer li').click(function(){
			if(this.id == 'view_tasks') setTaskview(0);
			else if(this.id == 'view_past') setTaskview('past');
			else if(this.id == 'view_today') setTaskview('today');
			else if(this.id == 'view_soon') setTaskview('soon');
		});

		$("#tasklist").bind("click", tasklistClick);

		
		// Tabs
		$('#lists li.mtt-tab').live('click', function(event){
			tabSelected(this);
			if(event.metaKey || event.ctrlKey) {
				// toggle singetab interface
				_mtt.applySingletab(!_mtt.options.singletab);
			}
			return false;
		});

		$('#lists li.mtt-tab .list-action').live('click', function(){
			listMenu(this);
			return false;	//stop bubble to tab click
		});

		//Priority popup
		$('#priopopup .prio-neg-1').click(function(){
			prioClick(-1,this);
		});

		$('#priopopup .prio-zero').click(function(){
			prioClick(0,this);
		});

		$('#priopopup .prio-pos-1').click(function(){
			prioClick(1,this);
		});

		$('#priopopup .prio-pos-2').click(function(){
			prioClick(2,this);
		});

		$('#priopopup').mouseleave(function(){
			$(this).hide()}
		);


		// edit form handlers
		$('#alltags_show').click(function(){
			toggleEditAllTags(1);
			return false;
		});

		$('#alltags_hide').click(function(){
			toggleEditAllTags(0);
			return false;
		});

		$('#taskedit_form').submit(function(){
			return saveTask(this);
		});

		$('#alltags .tag').live('click', function(){
			addEditTag($(this).attr('tag'));
			return false;
		});	


		// tasklist handlers
		$('#tasklist li').live('dblclick', function(){
			var li = findParentNode(this, 'LI');
			if(li && li.id) {
				var id = li.id.split('_',2)[1];
				if(id) editTask(parseInt(id));
			}
		});

		$('#tasklist .taskactionbtn').live('click', function(){
			var id = parseInt(getLiTaskId(this));
			if(id) taskContextMenu(this, id);
			return false;
		});

		$('#tasklist input[type=checkbox]').live('click', function(){
			var id = parseInt(getLiTaskId(this));
			if(id) completeTask(id, this);
			//return false;
		});

		$('#tasklist .task-toggle').live('click', function(){
			var id = getLiTaskId(this);
			if(id) $('#taskrow_'+id).toggleClass('task-expanded');
			return false;
		});

		$('#tasklist .tag').live('click', function(){
			addFilterTag($(this).attr('tag'), $(this).attr('tagid'));
			return false;
		});

		$('#tasklist .task-prio').live('mouseover mouseout', function(event){
			var id = parseInt(getLiTaskId(this));
			if(!id) return;
			if(event.type == 'mouseover') prioPopup(1, this, id);
			else prioPopup(0, this);
		});

		$('#tasklist .mtt-action-note-cancel').live('click', function(){
			var id = parseInt(getLiTaskId(this));
			if(id) cancelTaskNote(id);
			return false;
		});

		$('#tasklist .mtt-action-note-save').live('click', function(){
			var id = parseInt(getLiTaskId(this));
			if(id) saveTaskNote(id);
			return false;
		});

		$('#tasklist .tag').live('mouseover mouseout', function(event){
			var cl = 'tag-id-' + $(this).attr('tagid');
			var sel = (event.metaKey || event.ctrlKey) ? 'li.'+cl : 'li:not(.'+cl+')';
			if(event.type == 'mouseover') {
				_mtt.timers.previewtag = setTimeout( function(){$('#tasklist '+sel).addClass('not-in-tagpreview');}, 700);
			}
			else {
				clearTimeout(_mtt.timers.previewtag);
				$('#tasklist li').removeClass('not-in-tagpreview');
			}
		});

		$("#tasklist").sortable({cancel:'span,input,a,textarea', delay: 150, update:orderChanged, start:sortStart, items:'> :not(.task-completed)'});

		$("#lists ul").sortable({delay:150, update:listOrderChanged}); 
		this.applySingletab();

		$("#duedate").datepicker({
			dateFormat: _mtt.datepickerformat(),
			firstDay: _mtt.options.firstdayofweek,
			showOn: 'button',
			buttonImage: _mtt.templateUrl + 'images/calendar.png', buttonImageOnly: true,
			changeMonth:true, changeYear:true, 
			constrainInput: false,
			duration:'',
			nextText:'&gt;', prevText:'&lt;',
			dayNamesMin:_mtt.lang.daysMin, dayNames:_mtt.lang.daysLong, monthNamesShort:_mtt.lang.monthsLong
		});

		$("#edittags").autocomplete('ajax.php?suggestTags', {scroll: false, multiple: true, selectFirst:false, max:8, extraParams:{list:function(){return curList.id}}});


		// AJAX Errors
		$('#msg').ajaxSend(function(r,s){
			$("#msg").hide().removeClass('mtt-error mtt-info').find('.msg-details').hide();
			$("#loading").show();
		});

		$('#msg').ajaxStop(function(r,s){
			$("#loading").fadeOut();
		});

		$('#msg').ajaxError(function(event, request, settings){
			var errtxt;
			if(request.status == 0) errtxt = 'Bad connection';
			else if(request.status != 200) errtxt = 'HTTP: '+request.status+'/'+request.statusText;
			else errtxt = request.responseText;
			flashError(_mtt.lang.get('error'), errtxt); 
		}); 


		// Error Message details
		$("#msg>.msg-text").click(function(){
			$("#msg>.msg-details").toggle();
		});


		// Authorization
		$('#bar_login').click(function(){
			showAuth(this);
			return false;
		});

		$('#bar_logout').click(function(){
			logout();
			return false;
		});

		$('#login_form').submit(function(){
			doAuth(this);
			return false;
		});


		// Settings
		$("#settings").click(showSettings);
		$("#settings_form").live('submit', function() {
			saveSettings(this);
			return false;
		});
		
		$(".mtt-back-button").live('click', function(){ _mtt.pageBack(); this.blur(); return false; } );


		// tab menu
		this.addAction('listSelected', tabmenuOnListSelected);

		// task context menu
		this.addAction('listsLoaded', cmenuOnListsLoaded);
		this.addAction('listRenamed', cmenuOnListRenamed);
		this.addAction('listAdded', cmenuOnListAdded);
		this.addAction('listSelected', cmenuOnListSelected);
		this.addAction('listOrderChanged', cmenuOnListOrderChanged);

		// select list menu
		this.addAction('listsLoaded', slmenuOnListsLoaded);
		this.addAction('listRenamed', slmenuOnListRenamed);
		this.addAction('listAdded', slmenuOnListAdded);
		this.addAction('listSelected', slmenuOnListSelected);
		this.addAction('listOrderChanged', slmenuOnListsLoaded);

		return this;
	},

	log: function(v)
	{
		console.log.apply(this, arguments);
	},

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
	},

	setOptions: function(opts) {
		jQuery.extend(this.options, opts);
	},

	loadLists: function(onInit)
	{
		if(filter.search != '') {
			filter.search = '';
			$('#searchbarkeyword').text('');
			$('#searchbar').hide();
		}
		$('#page_tasks').hide();
		
		tabLists.clear();
		
		this.db.loadLists(null, function(res)
		{
			var ti = '';
			if(res && res.total)
			{
				// determine if need to open specific tab
				var openId = res.list[0].id;
				if(_mtt.options.openList) {
					for(var i in res.list) {
						if(_mtt.options.openList == res.list[i].id) {
							openId = res.list[i].id;
							break;
						}
					}
				}
				
				$.each(res.list, function(i,item){
					tabLists.add(item);
					ti += '<li id="list_'+item.id+'" class="mtt-tab '+(item.id==openId?'mtt-tabs-selected':'')+'">'+
						'<a href="#" title="'+item.name+'"><span>'+item.name+'</span>'+
						'<div class="list-action"></div></a></li>';
				});

				//TODO: replace 'chilren', with 'addClass'!
				if(!curList) {
					$('#toolbar').children().removeClass('invisible');
					$('#page_tasks h3').children().removeClass('invisible');
					$('#mylistscontainer .mtt-need-list').removeClass('mtt-disabled');
				}

				curList = tabLists.get(openId);
				loadTasks();
			}
			else
			{
				curList = 0;
				$('#toolbar').children().addClass('invisible');
				$('#page_tasks h3').children().addClass('invisible');
				$('#mylistscontainer .mtt-need-list').addClass('mtt-disabled');
				$('#tasklist').html('');
			}

			_mtt.options.openList = 0;
			$('#lists ul').html(ti);
			$('#lists').show();
			_mtt.doAction('listsLoaded');
			if(curList) _mtt.doAction('listSelected', curList);
			$('#page_tasks').show();

		});

		if(onInit) updateAccessStatus();
	},

	datepickerformat: function()
	{
		var fmt = 'yy-mm-dd';
		if(this.options.duedateformat == 2) fmt = 'm/d/yy';
		else if(this.options.duedateformat == 3) fmt = 'dd.mm.yy';
		else if(this.options.duedateformat == 4) fmt = 'dd/mm/yy';
		return fmt;
	},

	errorDenied: function()
	{
		flashError(this.lang.get('denied'));
	},
	
	pageSet: function(page, pageClass)
	{
		var prev = this.pages.current;
		this.pages.prev.push(this.pages.current);
		this.pages.current = {page:page, class:pageClass};
		showhide($('#page_'+ this.pages.current.page).addClass('mtt-page-'+ this.pages.current.class), $('#page_'+ prev.page));
	},
	
	pageBack: function()
	{
		if(this.pages.current.page == 'tasks') return false;
		var prev = this.pages.current;
		this.pages.current = this.pages.prev.pop();
		showhide($('#page_'+ this.pages.current.page), $('#page_'+ prev.page).removeClass('mtt-page-'+prev.page.class));
	},
	
	applySingletab: function(yesno)
	{
		if(yesno == null) yesno = this.options.singletab;
		else this.options.singletab = yesno;
		
		if(yesno) {
			$('#lists .mtt-tabs').addClass('mtt-tabs-only-one');
			$("#lists ul").sortable('disable');
		}
		else {
			$('#lists .mtt-tabs').removeClass('mtt-tabs-only-one');
			$("#lists ul").sortable('enable');
		}
	}

};

function addList()
{
	var r = prompt(_mtt.lang.get('addList'), _mtt.lang.get('addListDefault'));
	if(r == null) return;

	_mtt.db.request('addList', {name:r}, function(json){
		if(!parseInt(json.total)) return;
		var item = json.list[0];
		var i = tabLists.length();
		tabLists.add(item);
		if(i > 0) {
			$('#lists ul').append('<li id="list_'+item.id+'" class="mtt-tab">'+
					'<a href="#" title="'+item.name+'"><span>'+item.name+'</span>'+
					'<div class="list-action"></div></a></li>');
			mytinytodo.doAction('listAdded', item);
		}
		else _mtt.loadLists();
	});
};

function renameCurList()
{
	if(!curList) return;
	var r = prompt(_mtt.lang.get('renameList'), dehtml(curList.name));
	if(r == null || r == '') return;

	_mtt.db.request('renameList', {list:curList.id, name:r}, function(json){
		if(!parseInt(json.total)) return;
		var item = json.list[0];
		curList = item;
		tabLists.replace(item); 
		$('#lists ul>.mtt-tabs-selected>a').attr('title', item.name).find('span').html(item.name);
		mytinytodo.doAction('listRenamed', item);
	});
};

function deleteCurList()
{
	if(!curList) return false;
	var r = confirm(_mtt.lang.get('deleteList'));
	if(!r) return;

	_mtt.db.request('deleteList', {list:curList.id}, function(json){
		if(!parseInt(json.total)) return;
		_mtt.loadLists();
	})
};

function publishCurList()
{
	if(!curList) return false;
	_mtt.db.request('publishList', { list:curList.id, publish:curList.published?0:1 }, function(json){
		if(!parseInt(json.total)) return;
		curList.published = curList.published?0:1;
		if(curList.published) {
			$('#btnPublish').addClass('mtt-item-checked');
			$('#btnRssFeed').removeClass('mtt-disabled');
		}
		else {
			$('#btnPublish').removeClass('mtt-item-checked');
			$('#btnRssFeed').addClass('mtt-disabled');
		}
	});
};


function loadTasks(opts)
{
	if(!curList) return false;
	setSort(curList.sort, 1);
	opts = opts || {};
	$('#tasklist').html('');
	$('#total').html('...');

	_mtt.db.request('loadTasks', {
		list: curList.id,
		compl: curList.showCompl,
		sort: curList.sort,
		search: filter.search,
		tag: filter.tag,
		tz: tz(),
		setCompl: opts.setCompl
	}, function(json){
		taskList.length = 0;
		taskOrder.length = 0;
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
	});
};


function prepareTaskStr(item, noteExp)
{
	var id = item.id;
	var prio = item.prio;
	return '<li id="taskrow_'+id+'" class="'+(item.compl?'task-completed ':'')+item.dueClass+(item.note!=''?' task-has-note':'')+(noteExp?' task-expanded':'')+
		prepareTagsClass(item.tags_ids) + '">'+
		'<div class="task-actions"><a href="#" class="taskactionbtn"></a></div>'+"\n"+
		'<div class="task-left"><div class="task-toggle"></div>'+
		'<input type="checkbox" '+(flag.readOnly?'disabled="disabled"':'')+(item.compl?'checked="checked"':'')+'/></div>'+"\n"+
		'<div class="task-middle"><div class="task-middle-right">'+prepareDuedate(item)+
		'<span class="task-date-completed" title="'+item.dateCompletedInlineTitle+'">'+item.dateCompletedInline+'</span></div>'+"\n"+
		'<div class="task-through"><span class="task-date" title="'+item.dateInlineTitle+'">'+item.dateInline+'</span>'+preparePrio(prio,id)+
		'<span class="task-title">'+prepareHtml(item.title)+'</span> '+"\n"+
		prepareTagsStr(item)+'</div>'+
		'<div class="task-note-block">'+
			'<div id="tasknote'+id+'" class="task-note"><span>'+prepareHtml(item.note)+'</span></div>'+
			'<div id="tasknotearea'+id+'" class="task-note-area"><textarea id="notetext'+id+'"></textarea>'+
				'<span class="task-note-actions"><a href="#" class="mtt-action-note-save">'+_mtt.lang.get('actionNoteSave')+
				'</a> | <a href="#" class="mtt-action-note-cancel">'+_mtt.lang.get('actionNoteCancel')+'</a></span></div>'+
		'</div>'+
		"</div></li>\n";
};


function prepareHtml(s)
{
	// make URLs clickable
	s = s.replace(/(^|\s|>)(www\.([\w\#$%&~\/.\-\+;:=,\?\[\]@]+?))(,|\.|:|)?(?=\s|&quot;|&lt;|&gt;|\"|<|>|$)/gi, '$1<a href="http://$2" target="_blank">$2</a>$4');
	return s.replace(/(^|\s|>)((?:http|https|ftp):\/\/([\w\#$%&~\/.\-\+;:=,\?\[\]@]+?))(,|\.|:|)?(?=\s|&quot;|&lt;|&gt;|\"|<|>|$)/ig, '$1<a href="$2" target="_blank">$2</a>$4');
};

function preparePrio(prio,id)
{
	var cl =''; var v = '';
	if(prio < 0) { cl = 'prio-neg prio-neg-'+Math.abs(prio); v = '&#8722;'+Math.abs(prio); }	// &#8722; = &minus; = −
	else if(prio > 0) { cl = 'prio-pos prio-pos-'+prio; v = '+'+prio; }
	else { cl = 'prio-zero'; v = '&#177;0'; }													// &#177; = &plusmn; = ±
	return '<span class="task-prio '+cl+'">'+v+'</span>';
};

function prepareTagsStr(item)
{
	if(!item.tags || item.tags == '') return '';
	var a = item.tags.split(',');
	if(!a.length) return '';
	var b = item.tags_ids.split(',')
	for(var i in a) {
		a[i] = '<a href="#" class="tag" tag="'+a[i]+'" tagid="'+b[i]+'">'+a[i]+'</a>';
	}
	return '<span class="task-tags">'+a.join(', ')+'</span>';
};

function prepareTagsClass(ids)
{
	if(!ids || ids == '') return '';
	var a = ids.split(',');
	if(!a.length) return '';
	for(var i in a) {
		a[i] = 'tag-id-'+a[i];
	}
	return ' '+a.join(' ');
};

function prepareDuedate(item)
{
	if(!item.duedate) return '';
	return '<span class="duedate" title="'+item.dueTitle+'"><span class="duedate-arrow">→</span> '+item.dueStr+'</span>';
};


function submitNewTask(form)
{
	if(form.task.value == '') return false;
	_mtt.db.request('newTask', { list:curList.id, title: form.task.value, tz:tz(), tag:filter.tag}, function(json){
		if(!json.total) return;
		$('#total').text( parseInt($('#total').text()) + 1 );
		taskCnt.total++;
		form.task.value = '';
		var item = json.list[0];
		taskList[item.id] = item;
		taskOrder.push(parseInt(item.id));
		$('#tasklist').append(prepareTaskStr(item));
		changeTaskOrder(item.id);
		$('#taskrow_'+item.id).effect("highlight", {color:_mtt.theme.newTaskFlashColor}, 2000);
		refreshTaskCnt();
	}); 
	flag.tagsChanged = true;
	return false;
};


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
};


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
};

function prioClick(prio, el)
{
	el.blur();
	prio = parseInt(prio);
	$('#priopopup').fadeOut('fast'); //.hide();
	setTaskPrio(objPrio.taskId, prio);
};

function setTaskPrio(id, prio)
{
	_mtt.db.request('setPrio', {id:id, prio:prio});
	taskList[id].prio = prio;
	var $t = $('#taskrow_'+id);
	$t.find('.task-prio').replaceWith(preparePrio(prio, id));
	if(curList.sort != 0) changeTaskOrder(id);
	$t.effect("highlight", {color:_mtt.theme.editTaskFlashColor}, 'normal');
};

function setSort(v, init)
{
	$('#mylistscontainer .sort-item').removeClass('mtt-item-checked');
	if(v == 0) $('#sortByHand').addClass('mtt-item-checked');
	else if(v == 1) $('#sortByPrio').addClass('mtt-item-checked');
	else if(v == 2) $('#sortByDueDate').addClass('mtt-item-checked');
	else return;

/* //port:
	if(flag.needAuth && !flag.isLogged) {
		$("#tasklist").sortable('disable');
		return;
	}
*/
	curList.sort = v;
	if(v == 0) $("#tasklist").sortable('enable');
	else $("#tasklist").sortable('disable');
	
	if(!init)
	{
		changeTaskOrder();
		_mtt.db.request('setSort', {list:curList.id, sort:curList.sort});
	}
};


function tz()
{
	return -1 * (new Date()).getTimezoneOffset();
};

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
};

function refreshTaskCnt()
{
	$('#cnt_total').text(taskCnt.total);
	$('#cnt_past').text(taskCnt.past);
	$('#cnt_today').text(taskCnt.today);
	$('#cnt_soon').text(taskCnt.soon);
	if(filter.due == '') $('#total').text(taskCnt.total);
	else if(taskCnt[filter.due] != null) $('#total').text(taskCnt[filter.due]);
};


function setTaskview(v)
{
	if(v == 0)
	{
		if(filter.due == '') return;
		$('#taskview .btnstr').text(_mtt.lang.get('tasks'));
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
		$('#taskview .btnstr').text(_mtt.lang.get('f_'+v));
		$('#total').text(taskCnt[v]);
		filter.due = v;
	}
};


function toggleAllNotes(show)
{
	for(var id in taskList)
	{
		if(taskList[id].note == '') continue;
		if(show) $('#taskrow_'+id).addClass('task-expanded');
		else $('#taskrow_'+id).removeClass('task-expanded');
	}
};


function tabSelected(elementOrId)
{
	var id;
	if(typeof elementOrId == 'number') id = elementOrId;
	else {
		id = $(elementOrId).attr('id');
		if(!id) return;
		id = parseInt(id.split('_', 2)[1]);
	}
	if(!tabLists.exists(id)) return;
	$('#lists .mtt-tabs-selected').removeClass('mtt-tabs-selected');
	$('#list_'+id).addClass('mtt-tabs-selected');
	if(curList.id != id)
	{
		$('#tasklist').html('');
		if(filter.search != '') {
			filter.search = '';
			$('#searchbarkeyword').text('');
			$('#searchbar').hide();
		}
		mytinytodo.doAction('listSelected', tabLists.get(id));
	}
	curList = tabLists.get(id);
	flag.tagsChanged = true;
	cancelTagFilter(0, 1);
	setTaskview(0);
	loadTasks();
};



function listMenu(el)
{
	if(!mytinytodo.menus.listMenu) mytinytodo.menus.listMenu = new mttMenu('mylistscontainer', {onclick:listMenuClick});
	mytinytodo.menus.listMenu.show(el);
};

function listMenuClick(el, menu)
{
	if(!el.id) return;
	switch(el.id) {
		case 'btnAddList': addList(); break;
		case 'btnRenameList': renameCurList(); break;
		case 'btnDeleteList': deleteCurList(); break;
		case 'btnPublish': publishCurList(); break;
		case 'btnExportCSV': exportCurListToCSV(); break;
		case 'btnRssFeed': feedCurList(); break;
		case 'btnShowCompleted': showCompletedToggle(); break;
		case 'btnClearCompleted': clearCompleted(); break;
		case 'sortByHand': setSort(0); break;
		case 'sortByPrio': setSort(1); break;
		case 'sortByDueDate': setSort(2); break;
	}
};

function deleteTask(id)
{
	if(!confirm(_mtt.lang.get('confirmDelete'))) {
		return false;
	}
	_mtt.db.request('deleteTask', {id:id}, function(json){
		if(!parseInt(json.total)) return;
		var item = json.list[0];
		taskOrder.splice($.inArray(id,taskOrder), 1);
		$('#taskrow_'+id).fadeOut('normal', function(){ $(this).remove() });
		changeTaskCnt(taskList[id], -1);
		refreshTaskCnt();
		delete taskList[id];
	});
	flag.tagsChanged = true;
	return false;
};

function completeTask(id, ch)
{
	if(!taskList[id]) return; //click on already removed from the list while anim. effect
	var compl = 0;
	if(ch.checked) compl = 1;
	_mtt.db.request('completeTask', {id:id, compl:compl, list:curList.id, tz:tz()}, function(json){
		if(!parseInt(json.total)) return;
		var item = json.list[0];
		if(item.compl) $('#taskrow_'+id).addClass('task-completed');
		else $('#taskrow_'+id).removeClass('task-completed');
		taskList[id].ow = item.ow;
		taskList[id].compl = item.compl;
		taskList[id].dateCompleted = item.dateCompleted;
		changeTaskCnt(taskList[id], 0);
		if(item.compl && !curList.showCompl) {
			delete taskList[id];
			taskOrder.splice($.inArray(id,taskOrder), 1);
			$('#taskrow_'+id).fadeOut('normal', function(){ $(this).remove() });
		}
		else if(curList.showCompl) {
			$('#taskrow_'+id).fadeOut('fast', function(){
				changeTaskOrder(id);
				$(this).effect("highlight", {color:_mtt.theme.editTaskFlashColor}, 'normal', function(){$(this).css('display','')});
			});
		}
		refreshTaskCnt();
	});
	return false;
};

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
};

function cancelTaskNote(id)
{
	if(taskList[id].note == '') $('#taskrow_'+id).removeClass('task-expanded');
	$('#tasknotearea'+id).hide();
	$('#tasknote'+id).show();
	return false;
};

function saveTaskNote(id)
{
	_mtt.db.request('editNote', {id:id, note:$('#notetext'+id).val()}, function(json){
		if(!parseInt(json.total)) return;
		var item = json.list[0];
		taskList[id].note = item.note;
		taskList[id].noteText = item.noteText;
		$('#tasknote'+id+'>span').html(prepareHtml(item.note));
		if(item.note == '') $('#taskrow_'+id).removeClass('task-has-note task-expanded');
		else $('#taskrow_'+id).addClass('task-has-note task-expanded');
		cancelTaskNote(id);
	});
	return false;
};

function editTask(id)
{
	var item = taskList[id];
	if(!item) return false;
	// no need to clear form
	var form = document.getElementById('taskedit_form');
	form.task.value = dehtml(item.title);
	form.note.value = item.noteText;
	form.id.value = item.id;
	form.tags.value = item.tags.split(',').join(', ');
	form.duedate.value = item.duedate;
	form.prio.value = item.prio;
	$('#taskedit-date .date-created>span').text(item.date);
	if(item.compl) $('#taskedit-date .date-completed').show().find('span').text(item.dateCompleted);
	else $('#taskedit-date .date-completed').hide();
	toggleEditAllTags(0);
	showEditForm();
	return false;
};

function clearEditForm()
{
	var form = document.getElementById('taskedit_form');
	form.task.value = '';
	form.note.value = '';
	form.tags.value = '';
	form.duedate.value = '';
	form.prio.value = '0';
	toggleEditAllTags(0);
};

function showEditForm(isAdd)
{
	var form = document.getElementById('taskedit_form');
	if(isAdd)
	{
		clearEditForm();
		$('#page_taskedit').removeClass('mtt-inedit').addClass('mtt-inadd');
		form.isadd.value = 1;
		if(_mtt.options.autotag) form.tags.value = filter.tag;
		if($('#task').val() != '')
		{
			_mtt.db.request('parseTaskStr', { list:curList.id, title:$('#task').val(), tz:tz(), tag:filter.tag }, function(json){
				if(!json) return;
				form.task.value = json.title
				form.tags.value = (form.tags.value != '') ? form.tags.value +', '+ json.tags : json.tags;
				form.prio.value = json.prio;
				$('#task').val('');

			});
		}
	}
	else {
		$('#page_taskedit').removeClass('mtt-inadd').addClass('mtt-inedit');
		form.isadd.value = 0;
	}

	_mtt.pageSet('taskedit');
};

function saveTask(form)
{
//port:	if(flag.needAuth && !flag.isLogged) return false;
	if(form.isadd.value != 0)
		return submitFullTask(form);

	_mtt.db.request('editTask', {id:form.id.value, list:curList.id, tz:tz(), title: form.task.value, note:form.note.value,
		prio:form.prio.value, tags:form.tags.value, duedate:form.duedate.value},
		function(json){
			if(!parseInt(json.total)) return;
			var item = json.list[0];
			changeTaskCnt(item, 0, taskList[item.id]);
			taskList[item.id] = item;
			var noteExpanded = (item.note != '' && $('#taskrow_'+item.id).is('.task-expanded')) ? 1 : 0;
			$('#taskrow_'+item.id).replaceWith(prepareTaskStr(item, noteExpanded));
			if(curList.sort != 0) changeTaskOrder(item.id);
			_mtt.pageBack(); //back to list
			refreshTaskCnt();
			$('#taskrow_'+item.id).effect("highlight", {color:_mtt.theme.editTaskFlashColor}, 'normal', function(){$(this).css('display','')});
	});
	$("#edittags").flushCache();
	flag.tagsChanged = true;
	return false;
};

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
};

function fillEditAllTags()
{
	var a = [];
	for(var i=tagsList.length-1; i>=0; i--) { 
		a.push('<a href="#" class="tag" tag="'+tagsList[i].tag+'">'+tagsList[i].tag+'</a>');
	}
	$('#alltags .tags-list').html(a.join(', '));
	$('#alltags').show();
};

function addEditTag(tag)
{
	var v = $('#edittags').val();
	if(v == '') { 
		$('#edittags').val(tag);
		return;
	}
	var r = v.search(new RegExp('(^|,)\\s*'+tag+'\\s*(,|$)'));
	if(r < 0) $('#edittags').val(v+', '+tag);
};

function loadTags(callback)
{
	_mtt.db.request('tagCloud', {list:curList.id}, function(json){
		if(!parseInt(json.total)) tagsList = [];
		else tagsList = json.cloud;
		var cloud = '';
		$.each(tagsList, function(i,item){
			cloud += ' <a href="#" tag="'+item.tag+'" tagid="'+item.id+'" class="tag w'+item.w+'" >'+item.tag+'</a>';
		});
		$('#tagcloudcontent').html(cloud)
		flag.tagsChanged = false;
		callback();
	});
};

function cancelTagFilter(tagId, dontLoadTasks)
{
	if(tagId && _mtt.tagFilter[tagId])
	{
		delete _mtt.tagFilter[tagId];
		var a = [];
		for(var i in _mtt.tagFilter) {
			a.push(_mtt.tagFilter[i]);
		}
		filter.tag = a.join(',');
		$('#tag_filters .tag-filter.tag-id-'+tagId).remove();
	}
	else {
		_mtt.tagFilter.length = 0;
		filter.tag = '';
		$('#tag_filters').html('');
	}
	if(dontLoadTasks==null || !dontLoadTasks) loadTasks();
};

function addFilterTag(tag, tagId)
{
	// no action if already filtered this tag
	if(_mtt.tagFilter[tagId]) return false;

	_mtt.tagFilter[tagId] = tag;
	var a = [];
	for(var i in _mtt.tagFilter) {
		a.push(_mtt.tagFilter[i]);
	}
	filter.tag = a.join(', ');

	loadTasks();
	$('#tag_filters').append('<span class="tag-filter tag-id-'+tagId+'"><b>'+
		_mtt.lang.get('tagfilter')+'</b> '+tag+'<span class="tag-filter-close" tagid="'+tagId+'"></span></span>');
};

function addsearchToggle(toSearch)
{
	if(toSearch)
	{
		showhide($('#htab_search'), $('#htab_newtask'));
		$('#search').focus();
	}
	else
	{
		if(flag.readOnly) $('#htab_search').hide();
		else showhide($('#htab_newtask'), $('#htab_search'));

		// reload tasks when we return to task tab (from search tab)
		if(filter.search != '') {
			filter.search = '';
			$('#searchbarkeyword').text('');
			$('#searchbar').hide();
			loadTasks();
		}
		$('#task').focus();
	}
};

function timerSearch(event)
{
	if(event.keyCode == 13) return;  // do not process Enter key
	clearTimeout(searchTimer);
	searchTimer = setTimeout(function(){searchTasks()}, 500);
};

function searchTasks()
{
	filter.search = $('#search').val();
	$('#searchbarkeyword').text(filter.search);
	if(filter.search != '') $('#searchbar').fadeIn('fast');
	else $('#searchbar').fadeOut('fast');
	loadTasks();
	return false;
};


function submitFullTask(form)
{
//port:	if(flag.needAuth && !flag.isLogged) return false;

	_mtt.db.request('fullNewTask', { list:curList.id, tag:filter.tag, title: form.task.value, note:form.note.value,
			prio:form.prio.value, tags:form.tags.value, duedate:form.duedate.value }, function(json){
		if(!parseInt(json.total)) return;
		form.task.value = '';
		var item = json.list[0];
		taskList[item.id] = item;
		taskOrder.push(parseInt(item.id));
		$('#tasklist').append(prepareTaskStr(item));
		changeTaskOrder(item.id);
		_mtt.pageBack();
		$('#taskrow_'+item.id).effect("highlight", {color:_mtt.theme.newTaskFlashColor}, 2000);
		changeTaskCnt(item, 1);
		refreshTaskCnt();
	});

	$("#edittags").flushCache();
	flag.tagsChanged = true;
	return false;
};


function sortStart(event,ui)
{
	// remember initial order before sorting
	sortOrder = $(this).sortable('toArray');
};

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

	// prepare param
	var o = [];
	var diff;
	var replaceOW = taskList[sortOrder[h1[itemId]].split('_')[1]].ow;
	for(var j in h0)
	{
		diff = h1[j] - h0[j];
		if(diff != 0) {
			var a = j.split('_');
			if(j == itemId) diff = replaceOW - taskList[a[1]].ow;
			o.push({id:a[1], diff:diff});
			taskList[a[1]].ow += diff;
		}
	}

	_mtt.db.request('changeOrder', {order:o});
};


function mttMenu(container, options)
{
	var menu = this;
	this.container = document.getElementById(container);
	this.$container = $(this.container);
	this.menuOpen = false;
	this.options = options || {};
	this.submenu = [];
	this.curSubmenu = null;
	this.showTimer = null;
	this.ts = (new Date).getTime();
	this.container.mttmenu = this.ts;

	this.$container.find('li').click(function(){
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
			submenu.ts = submenu.container.mttmenu = submenu.root.ts;

			submenu.$container.find('li').click(function(){
				submenu.root.onclick(this, submenu);
				return false;
			});
		}

		$(this).hover(
			function(){
				if(!$(this).is('.mtt-menu-item-active')) menu.$container.find('li').removeClass('mtt-menu-item-active');
				clearTimeout(menu.showTimer);
				if(menu.hideTimer && menu.parent) {
					clearTimeout(menu.hideTimer);
					menu.hideTimer = null;
					menu.$caller.addClass('mtt-menu-item-active');
					clearTimeout(menu.parent.showTimer);
				}

				if(menu.curSubmenu && menu.curSubmenu.menuOpen && menu.curSubmenu != submenu && !menu.curSubmenu.hideTimer)
				{
					menu.$container.find('li').removeClass('mtt-menu-item-active');
					var curSubmenu = menu.curSubmenu;
					curSubmenu.hideTimer = setTimeout(function(){
						curSubmenu.hide();
						curSubmenu.hideTimer = null;
					}, 300);
				}

				if(!submenu || menu.curSubmenu == submenu && menu.curSubmenu.menuOpen)
					return;
			
				menu.showTimer = setTimeout(function(){
					menu.curSubmenu = submenu;
					submenu.showSub();
				}, 400);
			},
			function(){}
		);

	});

	this.onclick = function(item, fromMenu)
	{
		if($(item).is('.mtt-disabled,.mtt-menu-indicator')) return;
		menu.close();
		if(this.options.onclick) this.options.onclick(item, fromMenu);
	};

	this.hide = function()
	{
		for(var i in this.submenu) this.submenu[i].hide();
		clearTimeout(this.showTimer);
		this.$container.hide();
		this.$container.find('li').removeClass('mtt-menu-item-active');
		this.menuOpen = false;
	};

	this.close = function(event)
	{
		if(!this.menuOpen) return;
		if(event)
		{
			// ignore if event (click) was on caller or container
			var t = event.target;
			if(t == this.caller || (t.mttmenu && t.mttmenu == this.ts)) return;
			while(t.parentNode) {
				if(t.parentNode == this.caller || (t.mttmenu && t.mttmenu == this.ts)) return;
				t = t.parentNode;
			}
		}
		this.hide();
		$(this.caller).removeClass('mtt-menu-button-active');
		$(document).unbind('mousedown.mttmenuclose');
	};

	this.show = function(caller)
	{
		if(this.menuOpen)
		{
			this.close();
			if(this.caller && this.caller == caller) return;
		}
		$(document).triggerHandler('mousedown.mttmenuclose'); //close any other open menu
		this.caller = caller;
		var $caller = $(caller);
		
		// beforeShow trigger
		if(this.options.beforeShow && this.options.beforeShow.call)
			this.options.beforeShow();

		// adjust width
		if(this.options.adjustWidth && this.$container.outerWidth(true) > $(window).width())
			this.$container.width($(window).width() - (this.$container.outerWidth(true) - this.$container.width()));

		$caller.addClass('mtt-menu-button-active');
		var offset = $caller.offset();
		var x2 = $(window).width() + $(document).scrollLeft() - this.$container.outerWidth(true) - 1;
		var x = offset.left < x2 ? offset.left : x2;
		if(x<0) x=0;
		var y = offset.top+caller.offsetHeight-1;
		if(y + this.$container.outerHeight(true) > $(window).height() + $(document).scrollTop()) y = offset.top - this.$container.outerHeight();
		if(y<0) y=0;
		this.$container.css({ position: 'absolute', top: y, left: x, width:this.$container.width() /*, 'min-width': $caller.width()*/ }).show();
		var menu = this;
		$(document).bind('mousedown.mttmenuclose', function(e){ menu.close(e) });
		this.menuOpen = true;
	};

	this.showSub = function()
	{
		this.$caller.addClass('mtt-menu-item-active');
		var offset = this.$caller.offset();
		var x = offset.left+this.$caller.outerWidth();
		if(x + this.$container.outerWidth(true) > $(window).width() + $(document).scrollLeft()) x = offset.left - this.$container.outerWidth() - 1;
		if(x<0) x=0;
		var y = offset.top + this.parent.$container.offset().top-this.parent.$container.find('li:first').offset().top;
		if(y +  this.$container.outerHeight(true) > $(window).height() + $(document).scrollTop()) y = $(window).height() + $(document).scrollTop()- this.$container.outerHeight(true) - 1;
		if(y<0) y=0;
		this.$container.css({ position: 'absolute', top: y, left: x, width:this.$container.width() /*, 'min-width': this.$caller.outerWidth()*/ }).show();
		this.menuOpen = true;
	};

	this.destroy = function()
	{
		for(var i in this.submenu) {
			this.submenu[i].destroy();
			delete this.submenu[i];
		}
		this.$container.find('li').unbind(); //'click mouseenter mouseleave'
	};
};


function taskContextMenu(el, id)
{
	if(!cmenu) cmenu = new mttMenu('taskcontextcontainer', {onclick:taskContextClick});
	cmenu.tag = id;
	cmenu.show(el);
};

function taskContextClick(el, menu)
{
	if(!el.id) return;
	var taskId = parseInt(cmenu.tag);
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
};


function moveTaskToList(taskId, listId)
{
	if(curList.id == listId) return;
	_mtt.db.request('moveTask', {id:taskId, from:curList.id, to:listId}, function(json){
		if(!parseInt(json.total)) return;
		changeTaskCnt(taskList[taskId], -1)
		delete taskList[taskId];
		taskOrder.splice($.inArray(taskId,taskOrder), 1);
		$('#taskrow_'+taskId).fadeOut('normal', function(){ $(this).remove() });
		refreshTaskCnt();
	});

	$("#edittags").flushCache();
	flag.tagsChanged = true;
};


function cmenuOnListsLoaded()
{
	if(cmenu) cmenu.destroy();
	cmenu = null;
	var s = '';
	var all = tabLists.getAll();
	for(var i in all) {
		s += '<li id="cmenu_list:'+all[i].id+'">'+all[i].name+'</li>';
	}
	$('#listsmenucontainer ul').html(s);
};

function cmenuOnListAdded(list)
{
	if(cmenu) cmenu.destroy();
	cmenu = null;
	$('#listsmenucontainer ul').append('<li id="cmenu_list:'+list.id+'">'+list.name+'</li>');
};

function cmenuOnListRenamed(list)
{
	$('#cmenu_list\\:'+list.id).text(list.name);
};

function cmenuOnListSelected(list)
{
	$('#listsmenucontainer li').removeClass('mtt-disabled');
	$('#cmenu_list\\:'+list.id).addClass('mtt-disabled');
};

function cmenuOnListOrderChanged()
{
	cmenuOnListsLoaded();
	$('#cmenu_list\\:'+curList.id).addClass('mtt-disabled');
};


function tabmenuOnListSelected(list)
{
	if(list.published) {
		$('#btnPublish').addClass('mtt-item-checked');
		$('#btnRssFeed').removeClass('mtt-disabled');
	}
	else {
		$('#btnPublish').removeClass('mtt-item-checked');
		$('#btnRssFeed').addClass('mtt-disabled');
	}
	if(list.showCompl) $('#btnShowCompleted').addClass('mtt-item-checked');
	else $('#btnShowCompleted').removeClass('mtt-item-checked');
};


function listOrderChanged(event, ui)
{
	var a = $(this).sortable("toArray");
	var order = [];
	for(var i in a) {
		order.push(a[i].split('_')[1]);
	}
	tabLists.reorder(order);
	_mtt.db.request('changeListOrder', {order:order});
	_mtt.doAction('listOrderChanged', {order:order});
};

function showCompletedToggle()
{
	var act = curList.showCompl ? 0 : 1;
	curList.showCompl = tabLists.get(curList.id).showCompl = act;
	if(act) $('#btnShowCompleted').addClass('mtt-item-checked');
	else $('#btnShowCompleted').removeClass('mtt-item-checked');
	loadTasks({setCompl:1});
};

function clearCompleted()
{
	if(!curList) return false;
	var r = confirm(_mtt.lang.get('clearCompleted'));
	if(!r) return;
	_mtt.db.request('clearCompletedInList', {list:curList.id}, function(json){
		if(!parseInt(json.total)) return;
		flag.tagsChanged = true;
		if(curList.showCompl) loadTasks();
	});
};

function tasklistClick(e)
{
	var node = e.target.nodeName.toUpperCase();
	if(node=='SPAN' || node=='LI' || node=='DIV')
	{
		var li =  findParentNode(e.target, 'LI');
		if(li) {
			if(selTask && li.id != selTask) $('#'+selTask).removeClass('clicked doubleclicked');
			selTask = li.id;
			if($(li).is('.clicked')) $(li).toggleClass('doubleclicked');
			else $(li).addClass('clicked');
		}
	}
};


function showhide(a,b)
{
	a.show();
	b.hide();
};

function findParentNode(el, node)
{
	// in html nodename is in uppercase, in xhtml nodename in in lowercase
	if(el.nodeName.toUpperCase() == node) return el;
	if(!el.parentNode) return null;
	while(el.parentNode) {
		el = el.parentNode;
		if(el.nodeName.toUpperCase() == node) return el;
	}
};

function getLiTaskId(el)
{
	var li = findParentNode(el, 'LI');
	if(!li || !li.id) return 0;
	return li.id.split('_',2)[1];
};

function isParentId(el, id)
{
	if(el.id && $.inArray(el.id, id) != -1) return true;
	if(!el.parentNode) return null;
	return isParentId(el.parentNode, id);
};

function dehtml(str)
{
	return str.replace(/&quot;/g,'"').replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&amp;/g,'&');
};


function slmenuOnListsLoaded()
{
	if(_mtt.menus.selectlist) {
		_mtt.menus.selectlist.destroy();
		_mtt.menus.selectlist = null;
	}

	var s = '';
	var all = tabLists.getAll();
	for(var i in all) {
		s += '<li id="slmenu_list:'+all[i].id+'" class="'+(all[i].id==curList.id?'mtt-item-checked':'')+' list-id-'+all[i].id+'"><div class="menu-icon"></div><a href="#list/'+all[i].id+'">'+all[i].name+'</a></li>';
	}
	$('#slmenucontainer ul').html(s);
};

function slmenuOnListRenamed(list)
{
	$('#slmenucontainer li.list-id-'+list.id).find('a').html(list.name);
};

function slmenuOnListAdded(list)
{
	if(_mtt.menus.selectlist) {
		_mtt.menus.selectlist.destroy();
		_mtt.menus.selectlist = null;
	}
	$('#slmenucontainer ul').append('<li id="slmenu_list:'+list.id+'" class="list-id-'+list.id+'"><div class="menu-icon"></div><a href="#list/'+list.id+'">'+list.name+'</a></li>');
};

function slmenuOnListSelected(list)
{
	$('#slmenucontainer li').removeClass('mtt-item-checked');
	$('#slmenucontainer li.list-id-'+list.id).addClass('mtt-item-checked');

};

function slmenuSelect(el, menu)
{
	if(!el.id) return;
	var id = el.id, value;
	var a = id.split(':');
	if(a.length == 2) {
		id = a[0];
		value = a[1];
	}
	if(id == 'slmenu_list') {
		tabSelected(parseInt(value));
	}
	return false;
};


function exportCurListToCSV()
{
	if(!curList) return;
	window.location.href = _mtt.mttUrl + 'export.php?list='+curList.id +'&format=csv';
};

function feedCurList()
{
	if(!curList) return;
	window.location.href = _mtt.mttUrl + 'feed.php?list='+curList.id;
}


/*
	Errors and Info messages
*/

function flashError(str, details)
{
	$("#msg>.msg-text").text(str)
	$("#msg>.msg-details").text(details);
	$("#loading").hide();
	$("#msg").addClass('mtt-error').effect("highlight", {color:_mtt.theme.msgFlashColor}, 700);
}

function flashInfo(str, details)
{
	$("#msg>.msg-text").text(str)
	$("#msg>.msg-details").text(details);
	$("#loading").hide();
	$("#msg").addClass('mtt-info').effect("highlight", {color:_mtt.theme.msgFlashColor}, 700);
}

function toggleMsgDetails()
{
	var el = $("#msg>.msg-details");
	if(!el) return;
	if(el.css('display') == 'none') el.show();
	else el.hide()
}


/*
	Authorization
*/
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
		flag.readOnly = true;
		$("#bar_public").show();
		$('#page_tasks').addClass('readonly')
		addsearchToggle(1);
	}
	else {
		flag.readOnly = false;
		$('#page_tasks').removeClass('readonly')
		$("#bar_public").hide();
		addsearchToggle(0);
	}
	$('#page_ajax').hide();
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

function doAuth(form)
{
	$.post(mytinytodo.mttUrl+'ajax.php?login', { login:1, password: form.password.value }, function(json){
		form.password.value = '';
		if(json.logged)
		{
			flag.isLogged = true;
			_mtt.loadLists(1);
		}
		else {
			flashError(_mtt.lang.get('invalidpass'));
			$('#password').focus();
		}
	}, 'json');
	$('#authform').hide();
}

function logout()
{
	$.post(mytinytodo.mttUrl+'ajax.php?logout', { logout:1 }, function(json){
		flag.isLogged = false;
		_mtt.loadLists(1);
	}, 'json');
	return false;
} 


/*
	Settings
*/

function showSettings()
{
	if(_mtt.pages.current.page == 'ajax' && _mtt.pages.current.class == 'settings') return false;
	$('#page_ajax').load(_mtt.mttUrl+'settings.php?ajax=yes',null,function(){ 
		//showhide($('#page_ajax').addClass('mtt-page-settings'), $('#page_tasks'));
		_mtt.pageSet('ajax','settings');
	})
	return false;
}

function saveSettings(frm)
{
	if(!frm) return false;
	var params = { save:'ajax' };
	$(frm).find("input:text,input:checked,select,:password").filter(":enabled").each(function() { params[this.name || '__'] = this.value; }); 
	$(frm).find(":submit").attr('disabled','disabled').blur();
	$.post(_mtt.mttUrl+'settings.php', params, function(json){
		if(json.saved) {
			flashInfo(_mtt.lang.get('settingsSaved'));
			setTimeout('window.location.reload();', 1000);
		}
	}, 'json');
} 

})();