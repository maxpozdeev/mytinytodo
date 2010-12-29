/*
	This file is a part of myTinyTodo.
	(C) Copyright 2010 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v3 license. See file COPYRIGHT for details.
*/

// AJAX myTinyTodo Storage

(function(){

var mtt;

function mytinytodoStorageAjax(amtt) 
{
	this.mtt = mtt = amtt;
}

window.mytinytodoStorageAjax = mytinytodoStorageAjax;

mytinytodoStorageAjax.prototype = 
{
	/* required method */
	request:function(action, params, callback)
	{
		if(!this[action]) throw "Unknown storage action: "+action;

		this[action](params, function(json){
			if(json.denied) mtt.errorDenied();
			if(callback) callback.call(mtt, json)
		});
	},


	loadLists: function(params, callback)
	{
		$.getJSON(this.mtt.mttUrl+'ajax.php?loadLists'+'&rnd='+Math.random(), callback);
	},


	loadTasks: function(params, callback)
	{
		var q = '';
		if(params.search && params.search != '') q += '&s='+encodeURIComponent(params.search);
		if(params.tag && params.tag != '') q += '&t='+encodeURIComponent(params.tag);
		if(params.setCompl && params.setCompl != 0) q += '&setCompl=1';
		q += '&rnd='+Math.random();

/*		$.getJSON(mtt.mttUrl+'ajax.php?loadTasks&list='+params.list+'&compl='+params.compl+'&sort='+params.sort+'&tz='+params.tz+q, function(json){
			callback.call(mtt, json);
		})
*/

		$.getJSON(this.mtt.mttUrl+'ajax.php?loadTasks&list='+params.list+'&compl='+params.compl+'&sort='+params.sort+q, callback);
	},


	newTask: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?newTask',
			{ list:params.list, title: params.title, tag:params.tag }, callback, 'json');
	},
	

	fullNewTask: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?fullNewTask',
			{ list:params.list, title:params.title, note:params.note, prio:params.prio, tags:params.tags, duedate:params.duedate },
			callback, 'json');
	},


	editTask: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?editTask='+params.id,
			{ id:params.id, title:params.title, note:params.note, prio:params.prio, tags:params.tags, duedate:params.duedate },
			callback, 'json');
	},


	editNote: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?editNote='+params.id, {id:params.id, note: params.note}, callback, 'json');
	},


	completeTask: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?completeTask='+params.id, { id:params.id, compl:params.compl }, callback, 'json');
	},


	deleteTask: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?deleteTask='+params.id, { id:params.id }, callback, 'json');
	},


	setPrio: function(params, callback)
	{
		$.getJSON(this.mtt.mttUrl+'ajax.php?setPrio='+params.id+'&prio='+params.prio+'&rnd='+Math.random(), callback);
	},

	
	setSort: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?setSort', { list:params.list, sort:params.sort }, callback, 'json');
	},

	changeOrder: function(params, callback)
	{
		var order = '';
		for(var i in params.order) {
			order += params.order[i].id +'='+ params.order[i].diff + '&';
		}
		$.post(this.mtt.mttUrl+'ajax.php?changeOrder', { order:order }, callback, 'json');
	},

	tagCloud: function(params, callback)
	{
		$.getJSON(this.mtt.mttUrl+'ajax.php?tagCloud&list='+params.list+'&rnd='+Math.random(), callback);
	},

	moveTask: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?moveTask', { id:params.id, from:params.from, to:params.to }, callback, 'json');
	},

	parseTaskStr: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?parseTaskStr', { list:params.list, title:params.title, tag:params.tag }, callback, 'json');
	},
	

	// Lists
	addList: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?addList', { name:params.name }, callback, 'json'); 

	},

	renameList:  function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?renameList', { list:params.list, name:params.name }, callback, 'json');
	},

	deleteList: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?deleteList', { list:params.list }, callback, 'json');
	},

	publishList: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?publishList', { list:params.list, publish:params.publish },  callback, 'json');
	},
	
	setShowNotesInList: function(params, callback)
	{
	    $.post(this.mtt.mttUrl+'ajax.php?setShowNotesInList', { list:params.list, shownotes:params.shownotes },  callback, 'json');
	},
	
	setHideList: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?setHideList', { list:params.list, hide:params.hide }, callback, 'json');
	},

	changeListOrder: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?changeListOrder', { order:params.order }, callback, 'json');
	},

	clearCompletedInList: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?clearCompletedInList', { list:params.list }, callback, 'json');
	}

};

})();