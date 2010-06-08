/*
	(C) Copyright 2010 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v3 license. See license.txt for details.
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

		this[action](params, callback);
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

		$.getJSON(this.mtt.mttUrl+'ajax.php?loadTasks&list='+params.list+'&compl='+params.compl+'&sort='+params.sort+'&tz='+params.tz+q, callback);
	},


	newTask: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?newTask'+'&rnd='+Math.random(),
			{ list:params.list, title: params.title, tz:params.tz, tag:params.tag }, callback, 'json');
	},
	

	fullNewTask: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?fullNewTask'+'&rnd='+Math.random(),
			{ list:params.list, tz:params.tz, title:params.title, note:params.note, prio:params.prio, tags:params.tags, duedate:params.duedate },
			callback, 'json');
	},


	editTask: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?editTask='+params.id+'&rnd='+Math.random(),
			{ list:params.list, tz:params.tz, title:params.title, note:params.note, prio:params.prio, tags:params.tags, duedate:params.duedate },
			callback, 'json');
	},


	editNote: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?editNote='+params.id+'&rnd='+Math.random(), {note: params.note}, callback, 'json');
	},


	completeTask: function(params, callback)
	{
		$.getJSON(this.mtt.mttUrl+'ajax.php?completeTask='+params.id+'&compl='+params.compl+'&tz='+params.tz+'&rnd='+Math.random(), callback);
	},


	deleteTask: function(params, callback)
	{
		$.getJSON(this.mtt.mttUrl+'ajax.php?deleteTask='+params.id+'&rnd='+Math.random(), callback);
	},


	setPrio: function(params, callback)
	{
		$.getJSON(this.mtt.mttUrl+'ajax.php?setPrio='+params.id+'&prio='+params.prio+'&rnd='+Math.random(), callback);
	},

	
	setSort: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?setSort'+'&rnd='+Math.random(), { list:params.list, sort:params.sort }, callback, 'json');
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


	// Lists
	addList: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php', { addList:1, name:params.name }, callback, 'json'); 

	},

	renameList:  function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php', { renameList:1, id:params.list, name:params.name }, callback, 'json');
	},

	deleteList: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php', { deleteList:1, id:params.list }, callback, 'json');
	},

	publishList: function(params, callback)
	{
		$.post(this.mtt.mttUrl+'ajax.php?publishList', { list:params.list, publish:params.publish }, function(json){
			if(json.denied) mtt.errorDenied();
			callback.call(mtt, json)
		}, 'json');
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