/*
    This file is a part of myTinyTodo.
    (C) Copyright 2010,2020,2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

(function(){

"use strict";

var mtt;

function MytinytodoAjaxApi(amtt)
{
    mtt = amtt;
}

window.MytinytodoAjaxApi = MytinytodoAjaxApi;

MytinytodoAjaxApi.prototype =
{
    /* required method */
    request: function(action, params, callback)
    {
        if (!this[action]) throw "Unknown ApiDriver action: " + action;

        this[action] (params, function(json){
            if (json.denied) mtt.errorDenied();
            if (callback) callback.call(mtt, json)
        });
    },


    loadTasks: function(params, callback)
    {
        var q = '';
        if (params.search && params.search != '') q += '&s=' + encodeURIComponent(params.search);
        if (params.tag && params.tag != '') q += '&t=' + encodeURIComponent(params.tag);
        if (params.setCompl && params.setCompl != 0) q += '&setCompl=1';
        if (params.saveSort && params.saveSort != 0) q += '&saveSort=1';

        $.getJSON(mtt.apiUrl + 'tasks' + (mtt.apiUrl.indexOf('?') > -1 ? '&' : '?') + 'list='+params.list+'&compl='+params.compl+'&sort='+params.sort+q, callback);
    },


    newTask: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'tasks',
            method: 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'simple',
                list: params.list,
                title: params.title,
                tag: params.tag,
            }),
            success: callback,
            dataType: 'json'
        });
    },


    fullNewTask: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'tasks',
            method: 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'full',
                list: params.list,
                title: params.title,
                note: params.note,
                prio: params.prio,
                tags: params.tags,
                duedate: params.duedate,
                /* tag: params.tag, // We do not send current tag filter, autotag should set it in the form and include in tags */
            }),
            success: callback,
            dataType: 'json'
        });
    },


    editTask: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'tasks/' + encodeURIComponent(params.id),
            method: 'PUT',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'edit',
                title: params.title,
                note: params.note,
                prio: params.prio,
                tags: params.tags,
                duedate: params.duedate,
            }),
            success: callback,
            dataType: 'json'
        });
    },


    editNote: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'tasks/' + encodeURIComponent(params.id),
            method: 'PUT',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'note',
                note: params.note
            }),
            success: callback,
            dataType: 'json'
        });
    },


    completeTask: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'tasks/' + encodeURIComponent(params.id),
            method: 'PUT',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'complete',
                compl: params.compl
            }),
            success: callback,
            dataType: 'json'
        });
    },


    deleteTask: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'tasks/' + encodeURIComponent(params.id),
            method: 'DELETE',
            success: callback,
            dataType: 'json'
        });
    },


    setTaskPriority: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'tasks/' + encodeURIComponent(params.id),
            method: 'PUT',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'priority',
                prio: params.priority,
            }),
            success: callback,
            dataType: 'json'
        });
    },

    changeOrder: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'tasks',
            method: 'PUT',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'order',
                order:  params.order,
            }),
            success: callback,
            dataType: 'json'
        });
    },

    suggestTags: function(params, callback)
    {
        $.getJSON(mtt.apiUrl + 'suggestTags', {list:params.list, q:params.q}, callback);
    },

    tagCloud: function(params, callback)
    {
        $.getJSON(mtt.apiUrl + 'tagCloud/' + encodeURIComponent(params.list), callback);
    },

    moveTask: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'tasks/' + encodeURIComponent(params.id),
            method: 'PUT',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'move',
                from: params.from,
                to: params.to
            }),
            success: callback,
            dataType: 'json'
        });
    },

    parseTaskStr: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'tasks/parseTitle',
            method: 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                list: params.list,
                title: params.title,
                tag: params.tag ,
            }),
            success: callback,
            dataType: 'json'
        });
    },


    // Lists
    loadLists: function(params, callback)
    {
        $.getJSON(mtt.apiUrl + 'lists', callback);
    },

    addList: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'lists',
            method: 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                name: params.name,
            }),
            success: callback,
            dataType: 'json'
        });

    },

    deleteList: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'lists/' + encodeURIComponent(params.list),
            method: 'DELETE',
            success: callback,
            dataType: 'json'
        });
    },

    renameList:  function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'lists/' + encodeURIComponent(params.list),
            method: 'PUT',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'rename',
                name: params.name,
            }),
            success: callback,
            dataType: 'json'
        });
    },

    setSort: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'lists/' + encodeURIComponent(params.list),
            method: 'PUT',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'sort',
                name: params.name,
                sort: params.sort
            }),
            success: callback,
            dataType: 'json'
        });
    },

    publishList: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'lists/' + encodeURIComponent(params.list),
            method: 'PUT',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'publish',
                publish: params.publish,
            }),
            success: callback,
            dataType: 'json'
        });
    },

    enableFeedKey: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'lists/' + encodeURIComponent(params.list),
            method: 'PUT',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'enableFeedKey',
                enable: params.enable,
            }),
            success: callback,
            dataType: 'json'
        });
    },

    setShowNotesInList: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'lists/' + encodeURIComponent(params.list),
            method: 'PUT',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'showNotes',
                shownotes: params.shownotes,
            }),
            success: callback,
            dataType: 'json'
        });
    },

    setHideList: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'lists/' + encodeURIComponent(params.list),
            method: 'PUT',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'hide',
                hide: params.hide,
            }),
            success: callback,
            dataType: 'json'
        });
    },

    changeListOrder: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'lists',
            method: 'PUT',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'order',
                order: params.order
            }),
            success: callback,
            dataType: 'json'
        });
    },

    clearCompletedInList: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'lists/' + encodeURIComponent(params.list),
            method: 'PUT',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'clearCompleted',
            }),
            success: callback,
            dataType: 'json'
        });
    },

    /* Auth */

    login: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'login',
            method: 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                password: params.password,
            }),
            success: callback,
            dataType: 'json'
        });
    },

    logout: function(params, callback)
    {
        $.ajax({
            url: mtt.apiUrl + 'logout',
            method: 'POST',
            success: callback,
            dataType: 'json'
        });
    }

};

})();
