/*
    This file is a part of myTinyTodo.
    (C) Copyright 2010,2020-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

"use strict";

/**
 * @class
 */
function MytinytodoAjaxApi(props)
{
    if (typeof mytinytodo !== 'object') {
        throw "mytinytodo global object is not found!";
    }
    this.useREST = true;

    if (props.hasOwnProperty('useREST')) {
        this.useREST = !!props.useREST;
    }
}

MytinytodoAjaxApi.prototype = {

    /**
     * required method
     * @param {string} action
     * @param {Object.<string, any>} [params]
     * @param {ApiDriverCallback} [callback]
     *
     * @callback ApiDriverCallback
     * @param {object} json
     */
    request(action, params, callback) {
        if (typeof this[action] !== 'function') {
            throw "Unknown ApiDriver action: " + action;
        }
        this[action](params, function(json){
            if (json.denied) {
                mytinytodo.errorDenied();
            }
            if (callback) {
                callback.call(mytinytodo, json)
            }
        });
    },


    loadTasks(params, callback) {
        let q = '';
        if (params.search && params.search != '') q += '&s=' + encodeURIComponent(params.search);
        if (params.tag && params.tag != '') q += '&t=' + encodeURIComponent(params.tag);
        if (params.setCompl && params.setCompl != 0) q += '&setCompl=1';
        if (params.saveSort && params.saveSort != 0) q += '&saveSort=1';

        $.getJSON(mytinytodo.apiUrl + 'tasks' + (mytinytodo.apiUrl.indexOf('?') > -1 ? '&' : '?') +
            'list='+params.list+'&compl='+params.compl+'&sort='+params.sort+q,
            callback);
    },


    newTask(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'tasks',
            method: 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'newSimple',
                list: params.list,
                title: params.title,
                tag: params.tag,
            }),
            success: callback,
            dataType: 'json'
        });
    },


    fullNewTask(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'tasks',
            method: 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'newFull',
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


    editTask(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'tasks/' + encodeURIComponent(params.id),
            method: this.useREST ? 'PUT' : 'POST',
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


    editNote(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'tasks/' + encodeURIComponent(params.id),
            method: this.useREST ? 'PUT' : 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'note',
                note: params.note
            }),
            success: callback,
            dataType: 'json'
        });
    },


    completeTask(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'tasks/' + encodeURIComponent(params.id),
            method: this.useREST ? 'PUT' : 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'complete',
                compl: params.compl
            }),
            success: callback,
            dataType: 'json'
        });
    },


    deleteTask(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'tasks/' + encodeURIComponent(params.id),
            method: this.useREST ? 'DELETE' : 'POST',
            contentType : 'application/json', // contentType and data are required if method is POST
            data: JSON.stringify({
                action: 'delete',
            }),
            success: callback,
            dataType: 'json'
        });
    },


    setTaskPriority(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'tasks/' + encodeURIComponent(params.id),
            method: this.useREST ? 'PUT' : 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'priority',
                prio: params.priority,
            }),
            success: callback,
            dataType: 'json'
        });
    },

    changeOrder(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'tasks',
            method: this.useREST ? 'PUT' : 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'order',
                order:  params.order,
            }),
            success: callback,
            dataType: 'json'
        });
    },

    suggestTags(params, callback) {
        $.getJSON(mytinytodo.apiUrl + 'suggestTags', {list:params.list, q:params.q}, callback);
    },

    tagCloud(params, callback) {
        $.getJSON(mytinytodo.apiUrl + 'tagCloud/' + encodeURIComponent(params.list), callback);
    },

    moveTask(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'tasks/' + encodeURIComponent(params.id),
            method: this.useREST ? 'PUT' : 'POST',
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

    parseTaskStr(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'tasks/parseTitle',
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
    loadLists(params, callback) {
        $.getJSON(mytinytodo.apiUrl + 'lists', callback);
    },

    addList(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'lists',
            method: 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'new',
                name: params.name,
            }),
            success: callback,
            dataType: 'json'
        });

    },

    deleteList(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'lists/' + encodeURIComponent(params.list),
            method: this.useREST ? 'DELETE' : 'POST',
            contentType : 'application/json', // contentType and data are required if method is POST
            data: JSON.stringify({
                action: 'delete',
            }),
            success: callback,
            dataType: 'json'
        });
    },

    renameList(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'lists/' + encodeURIComponent(params.list),
            method: this.useREST ? 'PUT' : 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'rename',
                name: params.name,
            }),
            success: callback,
            dataType: 'json'
        });
    },

    setSort(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'lists/' + encodeURIComponent(params.list),
            method: this.useREST ? 'PUT' : 'POST',
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

    publishList(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'lists/' + encodeURIComponent(params.list),
            method: this.useREST ? 'PUT' : 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'publish',
                publish: params.publish,
            }),
            success: callback,
            dataType: 'json'
        });
    },

    enableFeedKey(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'lists/' + encodeURIComponent(params.list),
            method: this.useREST ? 'PUT' : 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'enableFeedKey',
                enable: params.enable,
            }),
            success: callback,
            dataType: 'json'
        });
    },

    setShowNotesInList(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'lists/' + encodeURIComponent(params.list),
            method: this.useREST ? 'PUT' : 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'showNotes',
                shownotes: params.shownotes,
            }),
            success: callback,
            dataType: 'json'
        });
    },

    setHideList(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'lists/' + encodeURIComponent(params.list),
            method: this.useREST ? 'PUT' : 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'hide',
                hide: params.hide,
            }),
            success: callback,
            dataType: 'json'
        });
    },

    changeListOrder(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'lists',
            method: this.useREST ? 'PUT' : 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'order',
                order: params.order
            }),
            success: callback,
            dataType: 'json'
        });
    },

    clearCompletedInList(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'lists/' + encodeURIComponent(params.list),
            method: this.useREST ? 'PUT' : 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                action: 'clearCompleted',
            }),
            success: callback,
            dataType: 'json'
        });
    },

    /* Auth */

    login(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'login',
            method: 'POST',
            contentType : 'application/json',
            data: JSON.stringify({
                password: params.password,
            }),
            success: callback,
            dataType: 'json'
        });
    },

    logout(params, callback) {
        $.ajax({
            url: mytinytodo.apiUrl + 'logout',
            method: 'POST',
            success: callback,
            dataType: 'json'
        });
    }

};
