/*
    This file is a part of myTinyTodo.
    (C) Copyright 2009-2010,2020-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

(function(){

"use strict";

var taskList = new Array(), taskOrder = new Array();
var filter = { compl:0, search:'', due:'' };
var sortOrder; //save task order before dragging
var searchTimer;
var objPrio = {};
var flag = {
    needAuth: false,
    isLogged: false,
    tagsChanged: true,
    readOnly: false,
    editFormChanged: false,
    firstLoad: true,
    dontChangeHistoryOnce: false,
    showTagsFromAllLists: false
};
var taskCnt = { total:0, past: 0, today:0, soon:0 };
var tabLists = {
    _lists: {},
    _length: 0,
    _order: [],
    _alltasks: {},
    lastTime: 0,
    clear: function(){
        this._lists = {}; this._length = 0; this._order = [];
        this._alltasks = { id:-1, showCompl:0, sort:3, name:_mtt.lang.get('alltasks') };
    },
    length: function(){ return this._length; },
    exists: function(id){ if(this._lists[id] || id==-1) return true; else return false; },
    add: function(list){ this._lists[list.id] = list; this._length++; this._order.push(list.id); },
    replace: function(list){ this._lists[list.id] = list; },
    get: function(id){ if(id==-1) return this._alltasks; else return this._lists[id]; },
    getAll: function(){ var r = []; for(var i in this._order) { r.push(this._lists[this._order[i]]); }; return r; },
    reorder: function(order){ this._order = order; }
};
var curList = 0;
var tagsList = [];
var _mtt; /* internal alias for window.mytinytodo */

var mytinytodo = window.mytinytodo = _mtt = {

    theme: {
        newTaskFlashColor: '#ffffaa',
        editTaskFlashColor: '#bbffaa',
        deleteTaskFlashColor: '#ffaaaa',
        msgFlashColor: '#ffffff'
    },

    actions: {},
    menus: {},
    mttUrl: '',
    homeUrl: '',
    apiUrl: '',
    options: {
        token: '',
        title: '',
        openList: 0,
        autotag: false,
        instantSearch: true,
        tagPreview: false,
        tagPreviewDelay: 700, //milliseconds
        ajaxAnimationDelay: 200,
        saveShowNotes: false,
        showdate: false,
        showdateInline: false,
        firstdayofweek: 1,
        touchDevice: false,
        calendarIcon: 'calendar.png', // need themeUrl+icon
        history: true,
        markdown: true,
        viewTaskOnClick: false,
        newTaskCounter: false,
    },

    timers: {
        previewtag: 0,
        ajaxAnimation: 0,
        newTaskCounter: 0,
    },

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
            this.monthsShort =this.__lang.monthsLong; //this.__lang.monthsShort;
            this.monthsLong = this.__lang.monthsLong;
        },

        isRTL: function() {
            return this.get('_rtl') > 0 ? true : false;
        }
    },

    pages: {
        current: null,
        prev: []
    },

    pageDefault: {
        page: 'tasks',
        pageClass: '',
        lastScrollTop: 0,
        onOpen: function() { this.loadLists(); }
    },

    curList: function(){
        return curList;
    },

    flag: flag,
    lastHistoryState: null,

    // procs
    setApiDriver: function(driver)
    {
        this.db = new driver({
            useREST: false
        });
        return this;
    },

    init: function(options)
    {
        // required properties
        if (options.hasOwnProperty('lang')) {
            this.lang.init(options.lang);
            delete options.lang;
        }
        if (options.hasOwnProperty('mttUrl')) {
            this.mttUrl = options.mttUrl;
            delete options.mttUrl;
        }
        if (options.hasOwnProperty('apiUrl')) {
            this.apiUrl = options.apiUrl;
            delete options.apiUrl;
        }
        else {
            this.apiUrl = this.mttUrl + 'api.php?_path=/';
        }
        if (options.hasOwnProperty('db')) {
            delete options.db;
        }
        if (options.hasOwnProperty('homeUrl')) {
            this.homeUrl = options.homeUrl;
            delete options.homeUrl;
        }
        else {
            this.homeUrl = this.mttUrl;
        }
        if ( ! options.hasOwnProperty('touchDevice') ) {
            this.options.touchDevice = ('ontouchend' in document);
        }

        jQuery.extend(this.options, options);

        if (this.options.token) {
            jQuery.ajaxSetup( { headers: { "MTT-Token": this.options.token } } )
        }

        flag.needAuth = options.needAuth ? true : false;
        flag.isLogged = options.isLogged ? true : false;

        if (this.options.showdate) {
            $('#mtt').addClass('show-date');
        }
        if (this.options.showdateInline) {
            $('#mtt').addClass('date-inline');
        }

        // handlers
        $('.mtt-tabs-new-button').click(function(){
            addList();
        });

        $('.mtt-tabs-select-button').click(function(event){
            if (!_mtt.menus.selectlist) {
                _mtt.menus.selectlist = new mttMenu( 'slmenucontainer', { onclick:slmenuSelect, alignRight: true } );
            }
            _mtt.menus.selectlist.show(this);
        });


        $('#newtask_form').submit(function(){
            submitNewTask(this);
            return false;
        });

        $('#newtask_submit').mousedown(function(e){
            e.preventDefault(); //keep the focus in #task
            $('#newtask_form').submit();
        });

        $('#newtask_adv').click(function(){
            showEditForm(1);
            return false;
        });

        $('#task').keydown(function(event){
            if(event.keyCode == 27) {
                $(this).val('');
            }
        }).focusin(function(){
            $('#task_placeholder').removeClass('placeholding');
            $('#toolbar').addClass('mtt-intask');
        }).focusout(function(){
            if('' == $(this).val()) $('#task_placeholder').addClass('placeholding');
            $('#toolbar').removeClass('mtt-intask');
        });


        $('#search_close').click(function(){
            liveSearchToggle(0);
            return false;
        });

        $('#search').keyup(function(event){
            if(event.keyCode == 27) return;
            if($(this).val() == '') $('#search_close').hide();  //actual value is only on keyup
            else $('#search_close').show();
            if (_mtt.options.instantSearch) {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function(){searchTasks()}, 400);
            }
        })
        .keydown(function(event){
            if(event.keyCode == 27) {  // cancel on Esc (NB: no esc event on keypress in Chrome and on keyup in Opera)
                if($(this).val() != '') {
                    $(this).val('');
                    $('#search_close').hide();
                    searchTasks();
                }
                else {
                    liveSearchToggle(0);
                }
                return false; //need to return false in firefox (for AJAX?)
            }
            else if ( event.keyCode == 13 ) {
                searchTasks(1);
                return false;
            }
        }).focusin(function(){
            $('#toolbar').addClass('mtt-insearch');
        }).focusout(function(){
            $('#toolbar').removeClass('mtt-insearch');
        });


        $('#taskview').click(function(){
            if(!_mtt.menus.taskview) _mtt.menus.taskview = new mttMenu('taskviewcontainer');
            _mtt.menus.taskview.show(this);
        });

        $('#mtt-tag-filters').on('click', '.mtt-filter-close', function(){
            cancelTagFilter($(this).attr('tagid'));
        });

        $('#mtt-tag-toolbar-close').click(function(){
            cancelTagFilter(0);
        });

        $('#tagcloudbtn').click(function(){
            if (flag.readOnly) {
                $('#tagcloudAllLists').prop('checked', false).prop('disabled', true);
            }
            else if (curList.id == -1) {
                $('#tagcloudAllLists').prop('checked', true).prop('disabled', true);
            }
            else {
                $('#tagcloudAllLists').prop('checked', flag.showTagsFromAllLists).prop('disabled', false);
            }
            if(!_mtt.menus.tagcloud) _mtt.menus.tagcloud = new mttMenu('tagcloud', {
                beforeShow: function(){
                    if(flag.tagsChanged) {
                        $('#tagcloudcontent').html('');
                        $('#tagcloudload').show();
                        loadTags(curList.id, function(){$('#tagcloudload').hide();});
                    }
                },
                alignRight:true
            });
            _mtt.menus.tagcloud.show(this);
        });

        $('#tagcloudcancel').click(function(){
            if(_mtt.menus.tagcloud) _mtt.menus.tagcloud.close();
        });

        $('#tagcloudcontent').on('click', '.tag', function(event){
            //tag is not escaped
            addFilterTag( this.dataset.tag, this.dataset.tagId, (event.metaKey || event.ctrlKey ? true : false) );
            if(_mtt.menus.tagcloud) _mtt.menus.tagcloud.close();
            return false;
        });

        $('#tagcloudAllLists').click(function(){
            flag.showTagsFromAllLists = this.checked;
            $('#tagcloudcontent').html('');
            $('#tagcloudload').show();
            loadTags(curList.id, function(){$('#tagcloudload').hide();});
        });

        $('#mtt-notes-show').click(function(e){
            toggleAllNotes(1, e);
            this.blur();
            return false;
        });

        $('#mtt-notes-hide').click(function(e){
            toggleAllNotes(0, e);
            this.blur();
            return false;
        });

        $('#taskviewcontainer li').click(function(){
            if(this.id == 'view_tasks') setTaskview(0);
            else if(this.id == 'view_past') setTaskview('past');
            else if(this.id == 'view_today') setTaskview('today');
            else if(this.id == 'view_soon') setTaskview('soon');
        });


        // Tabs
        $('#lists').on('click', 'li.mtt-tab', function(event) {
            var listId = this.id.split('_', 2)[1];
            if (listId === 'all') listId = -1;
            if(event.metaKey || event.ctrlKey) {
                // hide the tab
                hideList(listId);
                return false;
            }
            tabSelect(listId);
            return false;
        });

        $('#lists').on('click', 'li.mtt-tab .list-action', function(){
            listMenu(this);
            return false;   //stop bubble to tab click
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

        $('#alltags').on('click', '.tag', function(){
            addEditTag(this.dataset.tag);
            return false;
        });

        $("#duedate").datepicker({
            dateFormat: _mtt.duedatepickerformat(),
            firstDay: _mtt.options.firstdayofweek,
            showOn: 'button',
            buttonImage: _mtt.options.calendarIcon,
            buttonImageOnly: true,
            constrainInput: false,
            duration:'',
            dayNamesMin:_mtt.lang.daysMin,
            dayNames:_mtt.lang.daysLong,
            monthNamesShort:_mtt.lang.monthsShort,
            monthNames:_mtt.lang.monthsLong,
            changeMonth: true,
            changeYear: true,
            isRTL: _mtt.lang.isRTL()
        });

        function ac_split( val ) {
            return val.split( /,\s*/ );
        }
        function ac_extractLast( term ) {
            return ac_split( term ).pop();
        }

        $("#edittags").autocomplete({
            source: function(request, response) {
                var taskId = document.getElementById('taskedit_form').id.value;
                var listId = (taskId != '') ? taskList[taskId].listId : curList.id;
                _mtt.db.request('suggestTags', {list:listId, q:ac_extractLast(request.term)}, function(json){
                    response(json);
                })
            },/*
            search: function() {
                // custom minLength
                var term = ac_extractLast( this.value );
                if ( term.length < 2 ) {
                  return false;
                }
            },*/
            focus: function() {
                // prevent value inserted on focus using keyboard
                return false;
            },
            select: function( event, ui ) {
                var terms = ac_split( this.value );
                terms.pop(); // remove the current input
                terms.push( ui.item.value ); // add the selected item
                terms.push( "" ); // add placeholder to get the comma-and-space at the end
                this.value = terms.join( ", " );
                return false;
            }
        });

        $('#taskedit_form').find('select,input,textarea').bind('change keypress', function(){
            flag.editFormChanged = true;
        });

        $('#taskviewer_edit_btn').on('click', function() {
            const id = document.getElementById('page_taskviewer').dataset.id;
            editTask(id);
        });

        if (this.options.touchDevice) {
            this.options.viewTaskOnClick = true;
        }

        if (this.options.viewTaskOnClick) {
            $('#mtt').addClass('view-task-on-click');
        }

        // tasklist handlers
        $("#tasklist").on('click', '> li.task-row .task-title', function(e) {
            if ( findParentNode(e.target, 'A') ) {
                return; //ignore clicks on links
            }
            const li = findParentNode(this, 'LI');
            if (li && li.id) {
                if (e.altKey) {
                    viewTask(li.dataset.id);
                    return;
                }
                if (_mtt.options.viewTaskOnClick) {
                    viewTask(li.dataset.id);
                }
            }
        });

        $('#tasklist').on('dblclick', '> li.task-row .task-middle, > li.task-row .task-note-block', function(){
            let id = parseInt(getLiTaskId(this));
            if (id) {
                //clear selection
                if (document.selection && document.selection.empty && document.selection.createRange().text)
                    document.selection.empty();
                else if (window.getSelection)
                    window.getSelection().removeAllRanges();
                editTask(id);
            }
        });

        $('#tasklist').on('click', '.taskactionbtn', function(){
            var id = parseInt(getLiTaskId(this));
            if(id) taskContextMenu(this, id);
            return false;
        });

        $('#tasklist').on('click', 'input[type=checkbox]', function(){
            var id = parseInt(getLiTaskId(this));
            if(id) completeTask(id, this);
            //return false;
        });

        $('#tasklist').on('click', '.task-toggle', function(){
            var id = getLiTaskId(this);
            if(id) $('#taskrow_'+id).toggleClass('task-expanded');
            return false;
        });

        $('#tasklist').on('click', '.tag', function(event){
            clearTimeout(_mtt.timers.previewtag);
            $('#tasklist li').removeClass('not-in-tagpreview');
            //tag is not escaped
            addFilterTag(this.dataset.tag, this.dataset.tagId, (event.metaKey || event.ctrlKey ? true : false) );
            return false;
        });

        if(!this.options.touchDevice) {
            $('#tasklist').on('mouseover mouseout', '.task-prio', function(event){
                var id = parseInt(getLiTaskId(this));
                if(!id) return;
                if(event.type == 'mouseover') prioPopup(1, this, id);
                else prioPopup(0, this);
            });
        }

        $('#tasklist').on('click', '.mtt-action-note-cancel', function(){
            var id = parseInt(getLiTaskId(this));
            if(id) cancelTaskNote(id);
            return false;
        });

        $('#tasklist').on('click', '.mtt-action-note-save', function(){
            var id = parseInt(getLiTaskId(this));
            if(id) saveTaskNote(id);
            return false;
        });

        if (this.options.tagPreview && !this.options.touchDevice) {
            $('#tasklist').on('mouseover mouseout', '.tag', function(event){
                const cl = 'tag-id-' + this.dataset.tagId;
                const sel = (event.metaKey || event.ctrlKey) ? 'li.'+cl : 'li:not(.'+cl+')';
                if (event.type == 'mouseover') {
                    _mtt.timers.previewtag = setTimeout( function(){
                        $('#tasklist '+sel).addClass('not-in-tagpreview');
                    }, _mtt.options.tagPreviewDelay);
                }
                else {
                    clearTimeout(_mtt.timers.previewtag);
                    $('#tasklist li').removeClass('not-in-tagpreview');
                }
            });
        }

        $("#tasklist").sortable({
            items: '> :not(.task-completed)',
            cancel: 'span,input,a,textarea,.task-note-block',
            delay: 150,
            start: tasklistSortStart,
            update: tasklistSortUpdated,
            placeholder: 'mtt-task-placeholder',
            cursor: 'grabbing'
        });


        $("#lists ul").sortable({
            delay: 150,
            update: listOrderChanged,
            items: '> :not(#list_all)',
            forcePlaceholderSize : true,
            placeholder: 'mtt-tab mtt-tab-sort-placeholder',
            cursor: 'grabbing'
        });


        if (this.options.touchDevice) {
            $("#tasklist").disableSelection();
            $("#tasklist").sortable('option', {
                axis: 'y',
                delay: 50,
                cancel: 'input',
                distance: 0
            });
            /*$('#cmenu_note').hide();*/
            $("#lists ul").sortable('disable');
            $("#mtt").addClass("touch-device");
        }


        // AJAX Errors
        $(document).ajaxSend(function(r,s){
            hideAlert();
            clearTimeout(_mtt.timers.ajaxAnimation);
            _mtt.timers.ajaxAnimation = setTimeout( function(){
                $("#mtt").addClass("ajax-loading");
            }, _mtt.options.ajaxAnimationDelay );
        });

        $(document).ajaxStop(function(r,s){
            clearTimeout(_mtt.timers.ajaxAnimation);
            $("#mtt").removeClass("ajax-loading");
        });

        $(document).ajaxError(function(event, request, settings){
            var errtxt;
            if (request.status == 0) errtxt = 'Bad connection';
            else if(request.status == 403) errtxt = request.responseText;
            else if (request.status != 200) errtxt = 'HTTP: '+request.status+'/'+request.statusText + "\n" + request.responseText;
            else errtxt = request.responseText;
            flashError(_mtt.lang.get('error'), errtxt);
        });


        // Error Message details
        $("#msg>.msg-text").click(function(){
            $("#msg>.msg-details").toggle();
        });


        // Authentication
        $('#login_btn').click(function(){
            showLogin();
            return false;
        });

        $('#logout_btn').click(function(){
            logout();
            return false;
        });

        $('#login_form').submit(function(){
            doAuth(this);
            return false;
        });


        // Settings
        $(document).on('click', 'a[data-settings-link]', function(event) {
            var settingsPage = this.dataset.settingsLink;
            if (settingsPage == 'index') {
                showSettings( (event.metaKey || event.ctrlKey) ? 1 : 0 );
            }
            else if (settingsPage == 'ext-activate' || settingsPage == 'ext-deactivate') {
                activateExtension(settingsPage == 'ext-activate' ? true : false, this.dataset.ext);
            }
            else if (settingsPage == 'ext-index') {
                showExtensionSettings(this.dataset.ext);
            }
            return false;
        });

        $("#page_ajax").on('submit', '#settings_form', function() {
            saveSettings(this);
            return false;
        });

        $("#page_ajax").on('submit', '#ext_settings_form', function() {
            saveExtensionSettings(this);
            return false;
        });

        $(document).on('click', '.mtt-back-button', function() {
            _mtt.pageBack(true);
            this.blur();
            return false;
        });

        $(window).bind('beforeunload', function() {
            if (_mtt.pages.current && _mtt.pages.current.page == 'taskedit' && flag.editFormChanged) {
                return _mtt.lang.get('confirmLeave');
            }
        });

        $("#page_ajax").on('click', 'a[data-ext-settings-action],button[data-ext-settings-action]', function() {
            extensionSettingsAction(this.dataset.extSettingsAction, this.dataset.ext);
            return false;
        });


        // tab menu
        this.addAction('listSelected', tabmenuOnListSelected);

        // task context menu
        this.addAction('listsLoaded', cmenuOnListsLoaded);
        this.addAction('listRenamed', cmenuOnListRenamed);
        this.addAction('listAdded', cmenuOnListAdded);
        this.addAction('listSelected', cmenuOnListSelected);
        this.addAction('listOrderChanged', cmenuOnListOrderChanged);
        this.addAction('listHidden', cmenuOnListHidden);

        // select list menu
        this.addAction('listsLoaded', slmenuOnListsLoaded);
        this.addAction('listRenamed', slmenuOnListRenamed);
        this.addAction('listAdded', slmenuOnListAdded);
        this.addAction('listSelected', slmenuOnListSelected);
        this.addAction('listHidden', slmenuOnListHidden);

        //History
        if (this.options.history) {
            window.onpopstate = historyOnPopState;
            window.history.scrollRestoration = 'manual';
        }

        // Appearance mode for CSS
        if (window.matchMedia) {
            document.documentElement.setAttribute('data-system-appearance', window.matchMedia("(prefers-color-scheme: dark)").matches ? 'dark' : 'light');
            // TODO: use MediaQueryList.onchange since Safari 14 (macos 10.14) is min target
            window.matchMedia('(prefers-color-scheme: dark)').addListener(function (e) {
              document.documentElement.setAttribute('data-system-appearance', e.matches ? 'dark' : 'light');
            });
        }

        // Counter
        if (this.options.newTaskCounter) {
            this.addAction('listsLoaded', newTaskCounterStart);
        }

        this.doAction( 'init' );

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

    run: function()
    {
        var path = this.parseAnchor();

        updateAccessStatus();

        if (path.settings) {
            showSettings(path.settings == 'json' ? 1 : 0);
        }
        else if (path.search && path.list) {
            filter.search = path.search;
            this.pageSet('tasks', '');
            this.loadLists();
        }
        else {
            this.pageSet('tasks', '');
            this.loadLists();
        }
    },

    loadLists: function()
    {
        if(filter.search != '') {
            //filter.search = '' will be in tabSelect
            $('#searchbarkeyword').text('');
            $('#searchbar').hide();
        }
        $('#page_tasks').hide();
        $('#tasklist').html('');
        $('#tasks_info').hide();

        tabLists.clear();

        this.db.loadLists(null, function(res)
        {
            var ti = '';
            var openListId = 0;

            if (res && res.total && res.list)
            {
                // open required or last opened or first non-hidden list
                let list;
                if (_mtt.options.openList) {
                    list = res.list.find( item => _mtt.options.openList == item.id );
                }
                else {
                    const lastOpenList = getLocalStorageItem('lastList');
                    if (lastOpenList && !flag.readOnly) {
                        list = res.list.find( item => !item.hidden && lastOpenList == item.id );
                    }
                    if (!list) {
                        list = res.list.find( item => !item.hidden );
                    }
                }
                if (list) {
                    openListId = list.id;
                }
                tabLists.lastTime = res.time;

                res.list.forEach( (item) => {
                    item.lastTime = res.time;
                    if ( item.id == -1 ) {
                        tabLists._alltasks = item;
                        ti += prepareListHtml(item);
                    }
                    else {
                        tabLists.add(item);
                        ti += prepareListHtml(item);
                    }
                });
            }

            if (openListId == 0) {
                curList = 0;
            }

            if (_mtt.options.markdown == true) {
                $('#mtt').addClass('markdown-enabled');
            }

            if (tabLists.length() > 0) {
                $('#mtt').removeClass('no-lists');
            }
            else {
                $('#mtt').addClass('no-lists');
            }

            if (_mtt.options.openList != 0 && openListId == 0) {
                // cant open list - not found
                $('#tasks_info .v').text(_mtt.lang.get('listNotFound'))
                $('#tasks_info').show();
            }
            else if (tabLists.length() == 0) {
                if (flag.readOnly) $('#tasks_info .v').text(_mtt.lang.get('noPublicLists'));
                else $('#tasks_info .v').text(_mtt.lang.get('listNotFound'))
                $('#tasks_info').show();
            }

            _mtt.options.openList = 0;
            $('#lists .mtt-tab-selected').removeClass('mtt-tab-selected');
            $('#mtt').addClass('no-list-selected');
            $('#lists ul').html(ti);
            $('#lists').show();
            _mtt.doAction('listsLoaded');

            if (tabLists.length() > 0 && openListId != 0) {
                tabSelect(openListId);
            }
            $('#page_tasks').show();
        });
    },

    duedatepickerformat: function()
    {
        if(!this.options.duedatepickerformat) return 'yy-mm-dd';

        var s = this.options.duedatepickerformat.replace(/(.)/g, function(t,s) {
            switch(t) {
                case 'Y': return 'yy';
                case 'y': return 'yy';
                case 'd': return 'dd';
                case 'j': return 'd';
                case 'm': return 'mm';
                case 'n': return 'm';
                case '/':
                case '.':
                case '-': return t;
                default: return '';
            }
        });

        if(s == '') return 'yy-mm-dd';
        return s;
    },

    errorDenied: function()
    {
        flashError(this.lang.get('denied'));
    },

    pageSet: function(page, pageClass)
    {
        if (this.pages.current) {
            var prev = this.pages.current;
            prev.lastScrollTop = $(window).scrollTop();
            this.pages.prev.push(this.pages.current);
            $('#mtt').removeClass('page-' + prev.page);
            $('#page_'+ prev.page).removeClass('mtt-page-'+prev.page.pageClass).hide();
        }
        $(window).scrollTop(0);
        this.pages.current = { page:page, pageClass:pageClass };
        $('#mtt').addClass('page-' + page);
        $('#page_'+ this.pages.current.page).show().addClass('mtt-page-'+ this.pages.current.pageClass);
    },

    pageBack: function(clicked)
    {
        hideAlert();
        $(document).off('keydown.mttback');
        // If clicked on back button in settings or taskviewer we'll use history navigation
        if ( clicked && this.pages.current && this.pages.prev.length > 0 &&
            ((_mtt.pages.current.page == 'ajax' && _mtt.pages.current.pageClass == 'settings')
              || _mtt.pages.current.page == 'taskviewer') ) {
            window.history.back();
            return;
        }
        if (this.pages.current.page == 'tasks') {
            return;
        }
        if (this.pages.current) {
            var prev = this.pages.current;
            $('#mtt').removeClass('page-' + prev.page);
            $('#page_'+ prev.page).removeClass('mtt-page-'+prev.pageClass);
            $('#page_'+ prev.page).hide();
        }
        var cur = this.pages.prev.pop();
        this.pages.current = cur ? cur : this.pageDefault;
        $('#mtt').addClass('page-' + this.pages.current.page);
        $('#page_'+ this.pages.current.page).addClass('mtt-page-'+ this.pages.current.pageClass).show();
        $(window).scrollTop(this.pages.current.lastScrollTop);
        if (!cur && this.pages.current.onOpen) {
            this.pages.current.onOpen.call(this);
        }
    },


    filter: {
        _filters: [],
        clear: function() {
            this._filters = [];
            $('#mtt-tag-toolbar').hide();
            $('#mtt-tag-filters').html('');
        },
        addTag: function(tagId, tag, exclude)
        {
            tagId += 0;
            for (let i in this._filters) {
                if (this._filters[i].tagId && this._filters[i].tagId == tagId)
                    return false;
            }
            this._filters.push({tagId:tagId, tag:tag, exclude:exclude});
            const tagHtml = this.prepareTagHtml(tagId, tag, ['tag-filter', 'tag-id-'+tagId, exclude ? 'tag-filter-exclude' : '']) ;
            $('#mtt-tag-filters').append(tagHtml);
            $('#mtt-tag-toolbar').show();
            return true;
        },
        cancelTag: function(tagId)
        {
            for (let i in this._filters) {
                if (this._filters[i].tagId && this._filters[i].tagId == tagId) {
                    this._filters.splice(i, 1);
                    $('#mtt-tag-filters .tag-filter.tag-id-'+tagId).remove();
                    if (this._filters.length == 0) {
                        $('#mtt-tag-toolbar').hide();
                    }
                    return true;
                }
            }
            return false;
        },
        getTags: function(withExcluded)
        {
            let a = [];
            for (let i in this._filters) {
                if (this._filters[i].tagId) {
                    if (this._filters[i].exclude && withExcluded)
                        a.push('^'+ this._filters[i].tag);
                    else if (!this._filters[i].exclude)
                        a.push(this._filters[i].tag)
                }
            }
            return a.join(', ');
        },
        prepareTagHtml: function(tagId, tag, classes)
        {
            // tag is not escaped
            return '<span class="' + classes.join(' ') + ' mtt-filter-close" tagid="' + tagId + '">' + escapeHtml(tag) + '<span class="tag-filter-btn"></span></span>';
        }
    },

    parseAnchor: function()
    {
        if(location.hash == '') return false;
        var h = location.hash.substr(1);
        var a = h.split("/");
        var p = {};
        var s = '';

        for(var i=0; i<a.length; i++)
        {
            s = a[i];
            switch(s) {
                case "list": if(a[++i].match(/^-?\d+$/)) { p[s] = a[i]; } break;
                case "alltasks": p.list = '-1'; break;
                case "settings": p.settings = true; break;
                case "settings.json": p.settings = 'json'; break;
                case "search":   p.search = decodeURIComponent(a[++i]); break;
            }
        }

        if(p.list) this.options.openList = p.list;

        return p;
    },

    urlForList: function(list)
    {
        var l = list || curList;
        if (l === undefined) return '';
        if (l.id == -1) return '#alltasks';
        return '#list/' + l.id;
    },

    urlForExport: function(format, list)
    {
        var l = list || curList;
        if (l === undefined) return '';
        if (!format.match(/^[a-z0-9-]+$/i)) return '';
        return this.mttUrl + 'export.php?list='+l.id +'&format='+format;
    },

    urlForFeed: function(list)
    {
        list = list || curList;
        if (list === undefined) return '';
        return _mtt.mttUrl + 'feed.php?list=' + list.id;
    },

    urlForSettings: function(json = 0)
    {
        if (json == 1) return '#settings.json';
        return '#settings';
    },

    urlForExtSettings: function(ext)
    {
        return '#settings/ext/' + ext;
    }

}; // End of mytinytodo object

function addList()
{
    mttPrompt( _mtt.lang.get('addList'), _mtt.lang.get('addListDefault'), function(r)
    {
        _mtt.db.request('addList', {name:r}, function(json){
            if (!parseInt(json.total)) return;
            var item = json.list[0];
            var i = tabLists.length();
            tabLists.add(item);
            if (i > 0) {
                $('#lists ul').append(prepareListHtml(item));
                mytinytodo.doAction('listAdded', item);
            }
            else {
                _mtt.loadLists();
            }
        });
    });
};

function renameCurList()
{
    if (!curList) return;
    mttPrompt( _mtt.lang.get('renameList'), dehtml(curList.name), function(r)
    {
        _mtt.db.request('renameList', {list:curList.id, name:r}, function(json){
            if (!parseInt(json.total)) return;
            var item = json.list[0];
            curList = item;
            tabLists.replace(item);
            $('#list_'+curList.id).replaceWith(prepareListHtml(curList, true));
            mytinytodo.doAction('listRenamed', item);
        });
    });
};

function deleteCurList()
{
    if (!curList) return false;
    mttConfirm( _mtt.lang.get('deleteList'), function()
    {
        _mtt.db.request('deleteList', {list:curList.id}, function(json){
            if (!parseInt(json.total)) return;
            _mtt.loadLists();
        });
    });
};

function publishCurList()
{
    if(!curList) return false;
    _mtt.db.request('publishList', { list:curList.id, publish:curList.published?0:1 }, function(json){
        if(!parseInt(json.total)) return;
        curList.published = curList.published?0:1;
        if(curList.published) {
            $('#btnPublish').addClass('mtt-item-checked');
            $('#btnRssFeed').removeClass('mtt-item-disabled');
        }
        else {
            $('#btnPublish').removeClass('mtt-item-checked');
            $('#btnRssFeed').addClass('mtt-item-disabled');
        }
    });
};

function enableFeedKeyInCurList()
{
    if (!curList) return false;
    _mtt.db.request('enableFeedKey', {
        list: curList.id,
        enable: (curList.feedKey === undefined || curList.feedKey === '') ? 1 : 0
    }, function(json){
        if (!parseInt(json.total)) return;
        var item = json.list[0];
        curList.feedKey = item.feedKey;
        if (curList.feedKey) {
            $('#btnFeedKey').addClass('mtt-item-checked');
            $('#btnShowFeedKey').removeClass('mtt-item-disabled');
            mttAlert(curList.feedKey);
        }
        else {
            $('#btnFeedKey').removeClass('mtt-item-checked');
            $('#btnShowFeedKey').addClass('mtt-item-disabled');
        }
    });
};

function showFeedKeyInCurList()
{
    if (!curList) return false;
    if (curList.feedKey === undefined || curList.feedKey === '') return false;
    mttAlert(curList.feedKey);
};


function loadTasks(opts)
{
    if(!curList) return false;
    updateSortUI(curList.sort);
    opts = opts || {};
    if(opts.clearTasklist) {
        $('#tasklist').html('');
        $('#total').html('0');
    }

    _mtt.db.request('loadTasks', {
        list: curList.id,
        compl: curList.showCompl,
        sort: curList.sort,
        search: filter.search,
        tag: _mtt.filter.getTags(true),
        setCompl: opts.setCompl,
        saveSort: opts.saveSort
    }, function(json){
        taskList.length = 0;
        taskOrder.length = 0;
        taskCnt.total = taskCnt.past = taskCnt.today = taskCnt.soon = 0;
        var tasks = '';
        $.each(json.list, function(i,item){
            tasks += _mtt.prepareTaskStr(item);
            taskList[item.id] = item;
            taskOrder.push(parseInt(item.id));
            changeTaskCnt(item, 1);
        });
        curList.lastTime = json.time;
        setNewTaskCounterForList(curList.id, 0);
        if(opts.beforeShow && opts.beforeShow.call) {
            opts.beforeShow();
        }
        refreshTaskCnt();
        $('#tasklist').html(tasks);
    });
};

function prepareListHtml(list, isSelected)
{
    const classSelected = isSelected ? 'mtt-tab-selected' : '';
    const classHidden = list.hidden ? 'mtt-tab-hidden' : '';
    const liId = list.id == -1 ? 'list_all' : 'list_' + list.id;
    return `<li id="${liId}" class="mtt-tab ${classSelected} ${classHidden}" data-id="${list.id}">` +
           '<a href="' + _mtt.urlForList(list) + '" title="' + list.name + '">'+
             '<div class="title-block"><span class="counter hidden"></span>'+
             '<span class="title">' + list.name + '</span></div>' +
             '<div class="list-action mtt-img-button"><span></span></div>'+
           '</a></li>';
}

function prepareTaskStr(item, noteExp)
{
    return '<li id="taskrow_'+item.id+'" ' + 'data-id="'+item.id + '" class="task-row ' + (item.compl?'task-completed ':'') + item.dueClass + (item.note!=''?' task-has-note':'') +
                ((curList.showNotes && item.note != '') || noteExp ? ' task-expanded' : '') + prepareDomClassOfTags(item.tags_ids) + '">' +
                    prepareTaskBlocks(item) + "</li>\n";
};
_mtt.prepareTaskStr = prepareTaskStr;

function prepareTaskBlocks(item)
{
    const id = item.id;
    let markdown = '';
    if (_mtt.options.markdown == true) markdown = 'markdown-note';
    return '' +
        '<div class="task-block">' +
            '<div class="task-left">' +
                '<div class="task-toggle"></div>' +
                '<label><input type="checkbox" '+(flag.readOnly?'disabled="disabled"':'')+(item.compl?'checked="checked"':'')+'></label>' +
            "</div>\n" +

            '<div class="task-middle">' +
                '<div class="task-middle-top">' +
                    '<div class="task-through">' +
                        preparePrio(item.prio,id) +
                        '<span class="task-title">' + prepareTaskTitleInlineHtml(item.title) + '</span> ' +
                        (curList.id == -1 ? prepareListNameInline(item) : '') +
                        '<span class="task-tags">' + prepareTagsStr(item) + '</span>' +
                        '<div class="task-date">' + prepareInlineDate(item) + '</div>' +
                    '</div>' +
                    '<div class="task-through-right">' + prepareDueDate(item) + "</div>" +
                '</div>' +
            "</div>" +

            '<div class="task-actions"><div class="taskactionbtn"></div></div>' +
        '</div>' +

        '<div class="task-note-block">' +
            '<div id="tasknote' + id + '" class="task-note ' + markdown + '">' + prepareTaskNoteInlineHtml(item.note, item.noteText) + '</div>' +
            '<div id="tasknotearea'+id+'" class="task-note-area"><textarea id="notetext'+id+'"></textarea>'+
                '<span class="task-note-actions"><a href="#" class="mtt-action-note-save">'+_mtt.lang.get('actionNoteSave') +
                    '</a> | <a href="#" class="mtt-action-note-cancel">'+_mtt.lang.get('actionNoteCancel')+'</a></span>' +
            '</div>' +
        '</div>';
};
_mtt.prepareTaskBlocks = prepareTaskBlocks;

function prepareTaskTitleInlineHtml(s)
{
    // Task title is already escaped on back-end
    return s;
}
_mtt.prepareTaskTitleInlineHtml = prepareTaskTitleInlineHtml;

function prepareListNameInline(item)
{
    // Used in AllTasks list
    // List name is already escaped on back-end
    return '<span class="task-listname">'+ item.listName +'</span>';
}
_mtt.prepareListNameInline = prepareListNameInline;

function prepareTaskNoteInlineHtml(s, rawText)
{
    // Task note is already escaped on back-end
    return s;
};
_mtt.prepareTaskNoteInlineHtml = prepareTaskNoteInlineHtml;

function preparePrio(prio,id)
{
    var cl =''; var v = '';
    if(prio < 0) { cl = 'prio-neg prio-neg-'+Math.abs(prio); v = '&#8722;'+Math.abs(prio); }    // &#8722; = &minus; = −
    else if(prio > 0) { cl = 'prio-pos prio-pos-'+prio; v = '+'+prio; }
    else { cl = 'prio-zero'; v = '&#177;0'; }                                                   // &#177; = &plusmn; = ±
    return '<span class="task-prio '+cl+'">'+v+'</span>';
};
_mtt.preparePrio = preparePrio;

function prepareTagsStr(item, delimiter = ', ')
{
    if (!item.tags || item.tags == '') return '';
    let a = item.tags.split(',');
    if (!a.length) return '';
    const b = item.tags_ids.split(',')
    for (let i in a) {
        // tag is escaped
        a[i] = '<span class="tag" data-tag="'+a[i]+'" data-tag-id="'+b[i]+'">'+a[i]+'</span>';
    }
    return a.join(delimiter);
};
_mtt.prepareTagsStr = prepareTagsStr;

function prepareDomClassOfTags(ids)
{
    if(!ids || ids == '') return '';
    var a = ids.split(',');
    if(!a.length) return '';
    for(var i in a) {
        a[i] = 'tag-id-'+a[i];
    }
    return ' '+a.join(' ');
};
_mtt.prepareDomClassOfTags = prepareDomClassOfTags;

function prepareDueDate(item)
{
    if(!item.duedate) return '';
    return '<span class="duedate" title="'+item.dueTitle+'">'+item.dueStr+'</span>';
};
_mtt.prepareDueDate = prepareDueDate;

function prepareInlineDate(item)
{
    var inlineDate = item.dateInlineTitle;
    var title = item.dateFull;
    if (item.compl) {
        inlineDate = item.dateCompletedInlineTitle;
        title = item.dateCompletedFull;
    }
    else if ( item.isEdited && (curList.sort == 4 || curList.sort == 104) ) {
        inlineDate = item.dateEditedInlineTitle;
        title = item.dateEditedFull;
    }
    return '<span class="task-id">#' + item.id + '</span> <span title="' + title +'">' + inlineDate + '</span>';
}
_mtt.prepareInlineDate = prepareInlineDate;

function submitNewTask(form)
{
    if(form.task.value == '') return false;
    _mtt.db.request('newTask', { list:curList.id, title: form.task.value, tag:_mtt.filter.getTags() }, function(json){
        if(!json.total) return;
        $('#total').text( parseInt($('#total').text()) + 1 );
        taskCnt.total++;
        form.task.value = '';
        var item = json.list[0];
        taskList[item.id] = item;
        taskOrder.push(parseInt(item.id));
        $('#tasklist').append(_mtt.prepareTaskStr(item));
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
    if (taskOrder.length < 2) {
        return;
    }
    if (id && (curList.sort == 5 || curList.sort == 105)) {
        // re-sort the whole list in case of database sorting is not the same due to collation
        changeTaskOrder();
    }
    const oldOrder = taskOrder.slice();
    function firstNonZero(order, compl,  ...args) {
        const m = (order < 100) ? 1 : -1;
        if (compl != 0) return compl;
        for (const arg of args) {
            if (arg != 0) return arg * m;
        }
        return 0;
    }
    // sortByHand
    if (curList.sort == 0 || curList.sort == 100) {
        taskOrder.sort( (a, b) => firstNonZero(
            curList.sort,
            taskList[a].compl - taskList[b].compl,
            taskList[a].ow - taskList[b].ow
        ))
    }
    // sortByPrio and reverse
    else if (curList.sort == 1 || curList.sort == 101) {
        taskOrder.sort( (a, b) => firstNonZero(
            curList.sort,
            taskList[a].compl - taskList[b].compl,
            taskList[b].prio - taskList[a].prio,
            taskList[a].dueInt - taskList[b].dueInt,
            taskList[a].ow - taskList[b].ow
        ));
    }
    // sortByDueDate and reverse
    else if (curList.sort == 2 || curList.sort == 102) {
        taskOrder.sort( (a, b) => firstNonZero(
            curList.sort,
            taskList[a].compl - taskList[b].compl,
            taskList[a].dueInt - taskList[b].dueInt,
            taskList[b].prio - taskList[a].prio,
            taskList[a].ow - taskList[b].ow
        ))
    }
    // sortByDateCreated and reverse
    else if (curList.sort == 3 || curList.sort == 103) {
        taskOrder.sort( (a, b) => firstNonZero(
            curList.sort,
            taskList[a].compl - taskList[b].compl,
            taskList[a].dateInt - taskList[b].dateInt,
            taskList[b].prio - taskList[a].prio,
            taskList[a].ow - taskList[b].ow
        ));
    }
    // sortByDateModified and reverse
    else if (curList.sort == 4 || curList.sort == 104) {
        taskOrder.sort( (a, b) => firstNonZero(
            curList.sort,
            taskList[a].compl - taskList[b].compl,
            taskList[a].dateEditedInt - taskList[b].dateEditedInt,
            taskList[b].prio - taskList[a].prio,
            taskList[a].ow - taskList[b].ow
        ))
    }
    // sortByTitle and reverse
    else if (curList.sort == 5 || curList.sort == 105) {
        taskOrder.sort( (a, b) => firstNonZero(
            curList.sort,
            taskList[a].compl - taskList[b].compl,
            taskList[a].title.localeCompare(taskList[b].title, 'en', {sensitivity: 'base'}),
            taskList[b].prio - taskList[a].prio,
            taskList[a].ow - taskList[b].ow
        ))
    }
    else {
        return;
    }
    if (oldOrder.toString() == taskOrder.toString()) {
        return;
    }
    if (id && taskList[id]) {
        // optimization: determine where to insert task: top or after some task
        const indx = $.inArray(id, taskOrder);
        if (indx == 0) {
            $('#tasklist').prepend($('#taskrow_'+id))
        } else {
            const after = taskOrder[indx-1];
            $('#taskrow_' + after).after($('#taskrow_'+id));
        }
    }
    else {
        const o = $('#tasklist');
        for (const i in taskOrder) {
            o.append($('#taskrow_' + taskOrder[i]));
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
    _mtt.db.request('setTaskPriority', {id:id, priority:prio});
    taskList[id].prio = prio;
    var $t = $('#taskrow_'+id);
    $t.find('.task-prio').replaceWith(preparePrio(prio, id));
    if (curList.sort != 0 && curList.sort != 100) changeTaskOrder(id);
    $t.effect("highlight", {color:_mtt.theme.editTaskFlashColor}, 'normal');
};

function setSort(v, init)
{
    if (v < 0 || (v > 5 && v < 100) || v > 105) {
        return;
    }
    curList.sort = v;
    loadTasks({saveSort:1});
};


function updateSortUI(v)
{
    $('#listmenucontainer .sort-item').removeClass('mtt-item-checked').children('.mtt-sort-direction').text('');
    if (v == 0 || v == 100) $('#sortByHand').addClass('mtt-item-checked').children('.mtt-sort-direction').text(v==0 ? '↓' : '↑');
    else if(v==1 || v==101) $('#sortByPrio').addClass('mtt-item-checked').children('.mtt-sort-direction').text(v==1 ? '↑' : '↓');
    else if(v==2 || v==102) $('#sortByDueDate').addClass('mtt-item-checked').children('.mtt-sort-direction').text(v==2 ? '↑' : '↓');
    else if(v==3 || v==103) $('#sortByDateCreated').addClass('mtt-item-checked').children('.mtt-sort-direction').text(v==3 ? '↓' : '↑');
    else if(v==4 || v==104) $('#sortByDateModified').addClass('mtt-item-checked').children('.mtt-sort-direction').text(v==4 ? '↓' : '↑');
    else if(v==5 || v==105) $('#sortByTitle').addClass('mtt-item-checked').children('.mtt-sort-direction').text(v==5 ? '↓' : '↑');
    else return;

    curList.sort = v;
    if ( (v == 0 || v == 100) && !flag.readOnly) $("#tasklist").sortable('enable');
    else $("#tasklist").sortable('disable');
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


function toggleAllNotes(show, event)
{
    for (let id in taskList)
    {
        if (taskList[id].note == '') continue;
        if (show) $('#taskrow_'+id).addClass('task-expanded');
        else $('#taskrow_'+id).removeClass('task-expanded');
    }
    curList.showNotes = show;
    if (_mtt.options.saveShowNotes || (event && (event.metaKey || event.ctrlKey)) ) {
        _mtt.db.request('setShowNotesInList', {list:curList.id, shownotes:show}, function(json){});
    }
};


function tabSelect(elementOrId)
{
    let id;
    if (typeof elementOrId == 'number') id = elementOrId;
    else if(typeof elementOrId == 'string') id = parseInt(elementOrId);
    else {
        id = $(elementOrId).attr('id');
        if (!id) return;
        id = id.split('_', 2)[1];
        if (id === 'all') id = -1;
    }

    if ( !tabLists.exists(id) ) {
        $('#tasks_info .v').text(_mtt.lang.get('listNotFound'))
        $('#tasks_info').show();
        $('.mtt-need-list').addClass('mtt-item-disabled');
        return;
    }
    else {
        $('#tasks_info').hide();
        $('.mtt-need-list').removeClass('mtt-item-disabled');
        $('#mtt').removeClass('no-list-selected');
    }

    var prevList = curList;
    curList = tabLists.get(id);

    $('#lists .mtt-tab-selected').removeClass('mtt-tab-selected');

    if (id == -1) {
        $('#list_all').addClass('mtt-tab-selected').removeClass('mtt-tab-hidden');
        $('#listmenucontainer .mtt-need-real-list').addClass('mtt-item-hidden');
    }
    else {
        $('#list_'+id).addClass('mtt-tab-selected').removeClass('mtt-tab-hidden');
        $('#listmenucontainer .mtt-need-real-list').removeClass('mtt-item-hidden');
    }

    if (prevList.id != id) {
        if (id == -1) $('#mtt').addClass('show-all-tasks');
        else $('#mtt').removeClass('show-all-tasks');
        if (filter.search != '') liveSearchToggle(0, 1);
        mytinytodo.doAction('listSelected', tabLists.get(id));
    }
    const newTitle = curList.name + ' - ' + _mtt.options.title;
    const isFirstLoad = flag.firstLoad;
    //replaceHistoryState( 'list', { list:id }, _mtt.urlForList(curList), newTitle );
    updateHistoryState( { list:id }, _mtt.urlForList(curList), newTitle );
    if (!flag.readOnly) {
        setLocalStorageItem('lastList', ''+id);
    }

    if (curList.hidden && flag.readOnly != true) {
        curList.hidden = false;
        _mtt.db.request('setHideList', {list:curList.id, hide:0});
    }
    flag.tagsChanged = true;
    cancelTagFilter(0, 1);
    setTaskview(0);

    if (isFirstLoad && filter.search != '') {
        $('#search').val(filter.search);
        $('#search_close').show();
        searchTasks(true);
    }
    else {
        filter.search = '';
        loadTasks({clearTasklist:1});
    }
};



function listMenu(el)
{
    if(!mytinytodo.menus.listMenu) mytinytodo.menus.listMenu = new mttMenu('listmenucontainer', {onclick:listMenuClick, onhover:listMenuHover});
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
        case 'btnFeedKey': enableFeedKeyInCurList(); break;
        case 'btnShowFeedKey': showFeedKeyInCurList(); break;
        case 'btnHideList': hideList(curList.id); break;
        case 'btnExportCSV': return true;
        case 'btnExportICAL': return true;
        case 'btnRssFeed': return true;
        case 'btnShowCompleted': showCompletedToggle(); break;
        case 'btnClearCompleted': clearCompleted(); break;
        case 'sortByHand': setSort(curList.sort==0 ? 100 : 0); break;
        case 'sortByPrio': setSort(curList.sort==1 ? 101 : 1); break;
        case 'sortByDueDate': setSort(curList.sort==2 ? 102 : 2); break;
        case 'sortByDateCreated': setSort(curList.sort==3 ? 103 : 3); break;
        case 'sortByDateModified': setSort(curList.sort==4 ? 104 : 4); break;
        case 'sortByTitle': setSort(curList.sort==5 ? 105 : 5); break;
    }
    return false;
};

function listMenuHover(el, menu)
{
    if(!el.id) return;
    switch(el.id) {
        case 'btnExportCSV': $('#'+el.id+'>a').attr('href', _mtt.urlForExport('csv')) ; break;
        case 'btnExportICAL': $('#'+el.id+'>a').attr('href', _mtt.urlForExport('ical')) ; break;
        case 'btnRssFeed': $('#'+el.id+'>a').attr('href', _mtt.urlForFeed()) ; break;
    }
}

function deleteTask(id)
{
    mttConfirm( _mtt.lang.get('confirmDelete'), function()
    {
        flag.tagsChanged = true;
        _mtt.db.request('deleteTask', {id:id}, function(json){
            if (!parseInt(json.total)) return;
            var item = json.list[0];
            taskOrder.splice($.inArray(id,taskOrder), 1);
            $('#taskrow_'+id).effect("highlight", {color:_mtt.theme.deleteTaskFlashColor}, 'normal', function(){ $(this).remove() });
            changeTaskCnt(taskList[id], -1);
            refreshTaskCnt();
            delete taskList[id];
        });
    })
    return false;
};

function completeTask(id, ch)
{
    if(!taskList[id]) return; //click on already removed from the list while anim. effect
    var compl = 0;
    if(ch.checked) compl = 1;
    _mtt.db.request('completeTask', {id:id, compl:compl, list:curList.id}, function(json){
        if(!parseInt(json.total)) return;
        var item = json.list[0];
        if(item.compl) $('#taskrow_'+id).addClass('task-completed');
        else $('#taskrow_'+id).removeClass('task-completed');
        taskList[id] = item;
        changeTaskCnt(taskList[id], 0);
        if(item.compl && !curList.showCompl) {
            delete taskList[id];
            taskOrder.splice($.inArray(id,taskOrder), 1);
            $('#taskrow_'+id).fadeOut('normal', function(){ $(this).remove() });
        }
        else if(curList.showCompl) {
            $('#taskrow_'+item.id).replaceWith(_mtt.prepareTaskStr(taskList[id]));
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
        $('#tasknote'+id).html(prepareTaskNoteInlineHtml(item.note, item.noteText));
        if(item.note == '') $('#taskrow_'+id).removeClass('task-has-note task-expanded');
        else $('#taskrow_'+id).addClass('task-has-note task-expanded');
        cancelTaskNote(id);
    });
    return false;
};

function fillTaskViewer(id)
{
    const item = taskList[id];
    if (!item) return false;
    $('#page_taskviewer').attr('data-id', item.id);
    $('#taskviewer_id').text('#' + item.id);
    $('#page_taskviewer .title').html(item.title);
    $('#page_taskviewer .note').html(item.note);
    $('#page_taskviewer .prio .content').html(preparePrio(item.prio,item.id));
    $('#page_taskviewer .due .content').html(item.duedate);
    $('#page_taskviewer .tags .content').html(prepareTagsStr(item, ''));
    $('#page_taskviewer .list .content').text(curList.name);
    if (item.note == '') {
        $('#page_taskviewer').addClass('no-note');
    }
    else {
        $('#page_taskviewer').removeClass('no-note');
    }
    return item;
}

function viewTask(id)
{
    const item = fillTaskViewer(id);
    if (!item) return;
    _mtt.pageSet('taskviewer');
    updateHistoryState({ task: item.id, list: item.listId }, '#task/'+item.id, dehtml(item.title) + ' - ' + curList.name + ' - ' + _mtt.options.title);
}


function editTask(id)
{
    var item = taskList[id];
    if(!item) return false;
    // no need to clear form
    var form = document.getElementById('taskedit_form');
    form.task.value = item.titleText;
    form.note.value = item.noteText;
    form.id.value = item.id;
    form.tags.value = dehtml(item.tags).split(',').join(', ');
    form.duedate.value = item.duedate;
    form.prio.value = item.prio;
    $('#taskedit_id').text('#' + item.id);
    $('#taskedit_info .date-created-value').text(item.date).attr('title', item.dateFull);;
    if (item.isEdited && !item.compl) {
        $('#taskedit_info .date-edited-value').text(item.dateEdited).attr('title', item.dateEditedFull);
        $('#taskedit_info .date-edited').show()
    }
    else {
        $('#taskedit_info .date-edited').hide();
    }
    if (item.compl) {
        $('#taskedit_info .date-completed-value').text(item.dateCompleted).attr('title', item.dateCompletedFull);;
        $('#taskedit_info .date-completed').show()
    }
    else {
        $('#taskedit_info .date-completed').hide();
    }
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
    form.id.value = '';
    toggleEditAllTags(0);
};

function showEditForm(isAdd)
{
    let form = document.getElementById('taskedit_form');
    if (isAdd)
    {
        clearEditForm();
        $('#page_taskedit').removeClass('mtt-inedit').addClass('mtt-inadd');
        form.isadd.value = 1;
        if (_mtt.options.autotag) form.tags.value = _mtt.filter.getTags();
        if ($('#task').val() != '')
        {
            _mtt.db.request('parseTaskStr', { list:curList.id, title:$('#task').val(), tag:_mtt.filter.getTags() }, function(json){
                if(!json) return;
                form.task.value = json.title
                form.tags.value = (form.tags.value != '') ? form.tags.value +', '+ json.tags : json.tags;
                form.prio.value = json.prio;
                form.duedate.value = json.duedate;
                $('#task').val('');

            });
        }
    }
    else {
        $('#page_taskedit').removeClass('mtt-inadd').addClass('mtt-inedit');
        form.isadd.value = 0;
    }
    $(document).on('keydown.mttback', function(event) {
        if (event.keyCode == 27) { //Esc pressed
            _mtt.pageBack(true);
        }
    });

    flag.editFormChanged = false;
    _mtt.pageSet('taskedit');
};

function saveTask(form)
{
    $("#edittags").autocomplete('close');
    if (flag.readOnly)
        return false;
    if (form.isadd.value != 0)
        return submitFullTask(form);

    _mtt.db.request('editTask', {id:form.id.value, title: form.task.value, note:form.note.value,
        prio:form.prio.value, tags:form.tags.value, duedate:form.duedate.value},
        function(json) {
            if (!parseInt(json.total))
                return;
            const item = json.list[0];
            changeTaskCnt(item, 0, taskList[item.id]);
            taskList[item.id] = item;
            const noteExpanded = (item.note != '' && $('#taskrow_'+item.id).is('.task-expanded')) ? 1 : 0;
            $('#taskrow_'+item.id).replaceWith(_mtt.prepareTaskStr(item, noteExpanded));
            if (curList.sort != 0 && curList.sort != 100) {
                changeTaskOrder(item.id);
            }
            refreshTaskCnt();
            _mtt.pageBack(); //back to list or viewer
            if (_mtt.pages.current.page == 'taskviewer') {
                fillTaskViewer(item.id);
            }
            else {
                $('#taskrow_'+item.id).effect("highlight", {color:_mtt.theme.editTaskFlashColor}, 'normal', function(){$(this).css('display','')});
            }

    });
    flag.tagsChanged = true;
    return false;
};

function toggleEditAllTags(show)
{
    if (show)
    {
        if (curList.id == -1) {
            const taskId = document.getElementById('taskedit_form').id.value;
            loadTags(taskList[taskId].listId, fillEditAllTags);
        }
        else if (flag.tagsChanged)
            loadTags(curList.id, fillEditAllTags);
        else
            fillEditAllTags();
        showhide($('#alltags_hide'), $('#alltags_show'));
    }
    else {
        $('#alltags').hide();
        showhide($('#alltags_show'), $('#alltags_hide'))
    }
};

function fillEditAllTags()
{
    const a = [];
    tagsList.forEach( (item) => {
        a.push('<span class="tag" data-tag="' + item.tag +'">' + item.tag + '</span>');
    });
    const content = (a.length == 0)  ?  _mtt.lang.get('noTags')  :  a.join('');
    $('#alltags').html(content);
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

function loadTags(listId, callback)
{
    if (flag.showTagsFromAllLists) listId = -1;
    _mtt.db.request('tagCloud', {list:listId}, function(json){
        if (!parseInt(json.total)) tagsList = [];
        else tagsList = json.items;
        let cloud = '';
        tagsList.forEach( item => {
            // item.tag is escaped with htmlspecialchars()
            cloud += ' <span class="tag" data-tag="' + item.tag + '" data-tag-id="' + item.id + '">' + item.tag + '</span>';
        });
        if (cloud == '') {
            cloud = _mtt.lang.get('noTags');
        }
        $('#tagcloudcontent').html(cloud)
        flag.tagsChanged = false;
        callback();
    });
};

function cancelTagFilter(tagId, dontLoadTasks)
{
    if(tagId)  _mtt.filter.cancelTag(tagId);
    else _mtt.filter.clear();
    if(dontLoadTasks==null || !dontLoadTasks) loadTasks();
};

function addFilterTag(tag, tagId, exclude)
{
    if(!_mtt.filter.addTag(tagId, tag, exclude)) return false;
    loadTasks();
};

function liveSearchToggle(toSearch, dontLoad)
{
    if(toSearch)
    {
        $('#search').focus();
    }
    else
    {
        if($('#search').val() != '') {
            filter.search = '';
            $('#search').val('');
            $('#searchbarkeyword').text('');
            $('#searchbar').hide();
            $('#search_close').hide();
            if(!dontLoad) loadTasks();
        }

        $('#search').blur();
    }
};

function searchTasks(force)
{
    var newkeyword = $('#search').val();
    if(newkeyword == filter.search && !force) return false;
    filter.search = newkeyword;
    if (filter.search != '') {
        $('#searchbarkeyword').text(filter.search);
        $('#searchbar').fadeIn('fast');
    }
    else $('#searchbar').fadeOut('fast');
    loadTasks();
    return false;
};


function submitFullTask(form)
{
    if(flag.readOnly) return false;

    _mtt.db.request( 'fullNewTask',
        {
            list: curList.id,
            tag: _mtt.filter.getTags(),
            title: form.task.value,
            note: form.note.value,
            prio: form.prio.value,
            tags: form.tags.value,
            duedate: form.duedate.value
        },
        function(json) {
            if (!parseInt(json.total)) return;
            form.task.value = '';
            var item = json.list[0];
            taskList[item.id] = item;
            taskOrder.push(parseInt(item.id));
            $('#tasklist').append(_mtt.prepareTaskStr(item));
            changeTaskOrder(item.id);
            _mtt.pageBack();
            $('#taskrow_'+item.id).effect("highlight", {color:_mtt.theme.newTaskFlashColor}, 2000);
            changeTaskCnt(item, 1);
            refreshTaskCnt();
        }
    );

    flag.tagsChanged = true;
    return false;
};


function tasklistSortStart(event, ui)
{
    // remember initial order before sorting
    sortOrder = $(this).sortable('toArray');
};

function tasklistSortUpdated(event, ui)
{
    if (!ui.item[0]) {
        return;
    }
    const itemId = ui.item[0].id;
    const n = $(this).sortable('toArray');

    // remove possible empty id's
    for (let i = 0; i < sortOrder.length; i++) {
        if (sortOrder[i] == '') {
            sortOrder.splice(i,1); i--;
        }
    }
    if (n.toString() == sortOrder.toString()) {
        return;
    }

    // make index: id=>position
    const posBefore = {};
    for (let j = 0; j < sortOrder.length; j++) {
        posBefore[sortOrder[j]] = j;
    }
    const posAfter = {};
    for (let j = 0; j < n.length; j++) {
        posAfter[n[j]] = j;
        taskOrder[j] = parseInt(n[j].split('_')[1]);
    }

    // prepare params
    const o = [];
    const newWeight = taskList[sortOrder[posAfter[itemId]].split('_')[1]].ow;
    let diff;
    for (const j in posBefore)
    {
        diff = posAfter[j] - posBefore[j]; // depends on position
        if (curList.sort == 100) {
            diff *= -1;
        }
        if (diff != 0) {
            const taskId = j.split('_')[1];
            if (j == itemId) {
                diff = newWeight - taskList[taskId].ow; // just for new weight
            }
            o.push({id:taskId, diff:diff});
            taskList[taskId].ow += diff;
        }
    }

    _mtt.db.request('changeOrder', {order:o});
};


function mttMenu(container, options)
{
    var menu = this;
    this.container = document.getElementById(container);
    this.$container = $(this.container);
    this.isOpen = false;
    this.options = options || {};
    this.submenu = [];
    this.curSubmenu = null;
    this.showTimer = null;
    this.ts = (new Date).getTime();
    this.container.mttmenu = this.ts;

    if (!this.options.hasOwnProperty('isRTL')) {
        this.options.isRTL = ($('body').css('direction') == 'rtl') ? true : false;
    }
    if (!this.options.hasOwnProperty('alignRight')) {
        this.options.alignRight = false;
    }
    if (!this.options.hasOwnProperty('adjustWidth')) {
        this.options.adjustWidth = true;
    }

    this.$container.find('li').click(function(){
        var r = menu.onclick(this, menu);
        return (typeof r === 'undefined') ? false : r;
    })
    .each(function(){

        var submenu = 0;
        if($(this).is('.mtt-menu-indicator'))
        {
            submenu = new mttMenu($(this).attr('submenu'), menu.options);
            submenu.$caller = $(this);
            submenu.parent = menu;
            if(menu.root) submenu.root = menu.root;  //!! be careful with circular references
            else submenu.root = menu;
            menu.submenu.push(submenu);
            submenu.ts = submenu.container.mttmenu = submenu.root.ts;
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

                if(menu.curSubmenu && menu.curSubmenu.isOpen && menu.curSubmenu != submenu && !menu.curSubmenu.hideTimer)
                {
                    menu.$container.find('li').removeClass('mtt-menu-item-active');
                    var curSubmenu = menu.curSubmenu;
                    curSubmenu.hideTimer = setTimeout(function(){
                        curSubmenu.hide();
                        curSubmenu.hideTimer = null;
                    }, 300);
                }

                if (menu.options.onhover) menu.options.onhover(this, menu);

                if(!submenu || menu.curSubmenu == submenu && menu.curSubmenu.isOpen)
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
        if ($(item).is('.mtt-item-disabled,.mtt-menu-indicator,.mtt-item-hidden')) return;
        var r = undefined;
        if (this.options.onclick) r = this.options.onclick(item, fromMenu);
        if (menu.root) menu.root.close();
        else menu.close();
        return r;
    };

    this.hide = function()
    {
        for(var i in this.submenu) this.submenu[i].hide();
        clearTimeout(this.showTimer);
        this.$container.hide();
        this.$container.find('li').removeClass('mtt-menu-item-active');
        this.isOpen = false;
    };

    this.close = function(event)
    {
        if(!this.isOpen) return;
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
        $(document).off('mousedown.mttmenu');
        $(document).off('keydown.mttmenu');

        // onClose trigger
        if(this.options.onClose && this.options.onClose.call) {
            this.options.onClose.call(this);
        }
    };

    this.show = function(caller)
    {
        if(this.isOpen)
        {
            this.close();
            if(this.caller && this.caller == caller) return;
        }
        $(document).triggerHandler('mousedown.mttmenu'); //close any other open menu
        $(document).on('keydown.mttmenu', function(event) {
            if (event.keyCode == 27) {
                menu.close(); //close the menu on Esc pressed
            }
        });
        this.caller = caller;
        var $caller = $(caller);

        // beforeShow trigger
        if(this.options.beforeShow && this.options.beforeShow.call) {
            this.options.beforeShow.call(this);
        }

        // adjust width
        if (this.options.adjustWidth) {
            this.$container.width('');
            this.$container.removeClass('mtt-left-adjusted mtt-right-adjusted');
            if ( this.$container.outerWidth(true) > $(window).width() ) {
                this.$container.addClass('mtt-left-adjusted mtt-right-adjusted');
                this.$container.width( $(window).width() - (this.$container.outerWidth(true) - this.$container.width()) );
            }
        }

        //round the width to avoid overflow issues
        this.$container.width( Math.ceil(this.$container.width()) );

        $caller.addClass('mtt-menu-button-active');
        var offset = $caller.offset();
        var containerWidth = this.$container.outerWidth(true);
        var alignRight = this.options.isRTL ^ this.options.alignRight; //alignRight is not for submenu

        var x2 = $(window).width() + $(document).scrollLeft() - containerWidth - 1; // TODO: rtl?
        var x = alignRight ? offset.left + $caller.outerWidth() - containerWidth : offset.left;

        if (x > x2) {
            x = x2; //move left if container overflows right edge
            this.$container.addClass('mtt-right-adjusted');
        }
        if (x < 0) {
            x = 0; //do not cross left edge
            this.$container.addClass('mtt-left-adjusted');
        }

        var y = offset.top + caller.offsetHeight - 1;
        if(y + this.$container.outerHeight(true) > $(window).height() + $(document).scrollTop()) y = offset.top - this.$container.outerHeight();
        if(y<0) y=0;

        this.$container.css({ position: 'absolute', top: y, left: x, width:this.$container.width() /*, 'min-width': $caller.width()*/ }).show();
        var menu = this;
        $(document).on('mousedown.mttmenu', function(e) { menu.close(e) });
        this.isOpen = true;
    };

    this.showSub = function()
    {
        // adjust width
        if (this.options.adjustWidth) {
            this.$container.width('');
            this.$container.removeClass('mtt-left-adjusted mtt-right-adjusted');
            if ( this.$container.outerWidth(true) > $(window).width() ) {
                this.$container.addClass('mtt-left-adjusted mtt-right-adjusted');
                this.$container.width( $(window).width() - (this.$container.outerWidth(true) - this.$container.width()) );
            }
        }

        //round the width to avoid overflow issues
        this.$container.width( Math.ceil(this.$container.width()) );

        this.$caller.addClass('mtt-menu-item-active');
        var offset = this.$caller.offset();
        var containerWidth = this.$container.outerWidth(true);

        var x = 0;
        if (this.options.isRTL) {
            x = offset.left - containerWidth - 1;
            if (x < 0) {
                x = offset.left + this.$caller.outerWidth();
            }
            if ( x + containerWidth > $(window).width() + $(document).scrollLeft() ) {
                x = $(window).width() + $(document).scrollLeft() - containerWidth; // TODO: rtl?
                this.$container.addClass('mtt-right-adjusted');
            }
        }
        else {
            x = offset.left + this.$caller.outerWidth();
            if ( x + containerWidth > $(window).width() + $(document).scrollLeft() ) { // TODO: rtl?
                x = offset.left - containerWidth - 1;
            }
            if (x < 0) {
                x = 0;
                this.$container.addClass('mtt-left-adjusted');
            }
        }

        var y = offset.top + this.parent.$container.offset().top-this.parent.$container.find('li:first').offset().top;
        if(y +  this.$container.outerHeight(true) > $(window).height() + $(document).scrollTop()) y = $(window).height() + $(document).scrollTop()- this.$container.outerHeight(true) - 1;
        if(y<0) y=0;

        this.$container.css({ position: 'absolute', top: y, left: x, width:this.$container.width() /*, 'min-width': this.$caller.outerWidth()*/ }).show();
        this.isOpen = true;
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
    if(!_mtt.menus.cmenu) _mtt.menus.cmenu = new mttMenu('taskcontextcontainer', {
        onclick: taskContextClick,
        beforeShow: function() {
            var taskId = this.tag;
            $('#taskrow_'+taskId).addClass('menu-active');
            $('#cmenupriocontainer li').removeClass('mtt-item-checked');
            $('#cmenu_prio\\:'+ taskList[taskId].prio).addClass('mtt-item-checked');
        },
        onClose: function() {
            $('#tasklist li').removeClass('menu-active');
        },
        alignRight: true
    });
    _mtt.menus.cmenu.tag = id;
    _mtt.menus.cmenu.show(el);
};

function taskContextClick(el, menu)
{
    if(!el.id) return;
    var taskId = parseInt(_mtt.menus.cmenu.tag);
    var id = el.id, value;
    var a = id.split(':');
    if(a.length == 2) {
        id = a[0];
        value = a[1];
    }
    switch(id) {
        case 'cmenu_edit': editTask(taskId); break;
        /*case 'cmenu_note': toggleTaskNote(taskId); break;*/
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
        if(curList.id == -1)
        {
            // leave the task in current tab (all tasks tab)
            var item = json.list[0];
            changeTaskCnt(item, 0, taskList[item.id]);
            taskList[item.id] = item;
            var noteExpanded = (item.note != '' && $('#taskrow_'+item.id).is('.task-expanded')) ? 1 : 0;
            $('#taskrow_'+item.id).replaceWith(_mtt.prepareTaskStr(item, noteExpanded));
            if (curList.sort != 0 && curList.sort != 100) {
                changeTaskOrder(item.id);
            }
            refreshTaskCnt();
            $('#taskrow_'+item.id).effect("highlight", {color:_mtt.theme.editTaskFlashColor}, 'normal', function(){$(this).css('display','')});
        }
        else {
            // remove the task from currrent tab
            changeTaskCnt(taskList[taskId], -1)
            delete taskList[taskId];
            taskOrder.splice($.inArray(taskId,taskOrder), 1);
            $('#taskrow_'+taskId).fadeOut('normal', function(){ $(this).remove() });
            refreshTaskCnt();
        }
    });

    flag.tagsChanged = true;
};


function cmenuOnListsLoaded()
{
    if(_mtt.menus.cmenu) _mtt.menus.cmenu.destroy();
    _mtt.menus.cmenu = null;
    var s = '';
    var all = tabLists.getAll();
    for(var i in all) {
        s += '<li id="cmenu_list:'+all[i].id+'" class="'+(all[i].hidden?'mtt-list-hidden':'')+'">'+all[i].name+'</li>';
    }
    $('#cmenulistscontainer ul').html(s);
};

function cmenuOnListAdded(list)
{
    if(_mtt.menus.cmenu) _mtt.menus.cmenu.destroy();
    _mtt.menus.cmenu = null;
    $('#cmenulistscontainer ul').append('<li id="cmenu_list:'+list.id+'">'+list.name+'</li>');
};

function cmenuOnListRenamed(list)
{
    $('#cmenu_list\\:'+list.id).text(list.name);
};

function cmenuOnListSelected(list)
{
    $('#cmenulistscontainer li').removeClass('mtt-item-disabled');
    $('#cmenu_list\\:'+list.id).addClass('mtt-item-disabled').removeClass('mtt-list-hidden');
};

function cmenuOnListOrderChanged()
{
    cmenuOnListsLoaded();
    $('#cmenu_list\\:'+curList.id).addClass('mtt-item-disabled');
};

function cmenuOnListHidden(list)
{
    if (list.id == -1) return;
    $('#cmenu_list\\:'+list.id).addClass('mtt-list-hidden');
};


function tabmenuOnListSelected(list)
{
    if (list.published) {
        $('#btnPublish').addClass('mtt-item-checked');
        $('#btnRssFeed').removeClass('mtt-item-disabled');
    }
    else {
        $('#btnPublish').removeClass('mtt-item-checked');
        $('#btnRssFeed').addClass('mtt-item-disabled');
    }
    if (list.showCompl) {
        $('#btnShowCompleted').addClass('mtt-item-checked');
    }
    else {
        $('#btnShowCompleted').removeClass('mtt-item-checked');
    }
    if (list.feedKey !== undefined && list.feedKey !== '') {
        $('#btnFeedKey').addClass('mtt-item-checked');
        $('#btnShowFeedKey').removeClass('mtt-item-disabled');
    }
    else {
        $('#btnFeedKey').removeClass('mtt-item-checked');
        $('#btnShowFeedKey').addClass('mtt-item-disabled');
    }
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
    if (!curList) return false;
    mttConfirm( _mtt.lang.get('clearCompleted'), function()
    {
        _mtt.db.request('clearCompletedInList', {list:curList.id}, function(json){
            if(!parseInt(json.total)) return;
            flag.tagsChanged = true;
            if(curList.showCompl) loadTasks();
        });
    });
};

function showhide(a,b)
{
    a.show();
    b.hide();
};

function findParentNode(el, node)
{
    // in html nodename is in uppercase, in xhtml nodename in in lowercase
    if (el.nodeName.toUpperCase() == node) return el;
    while (el.parentNode) {
        el = el.parentNode;
        if (el.nodeName.toUpperCase() == node) return el;
    }
    return null;
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
    return str.replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
};

function escapeHtml(str) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return str.replace(/[&<>"']/g, (m) => map[m]);
}


function slmenuOnListsLoaded()
{
    if(_mtt.menus.selectlist) {
        _mtt.menus.selectlist.destroy();
        _mtt.menus.selectlist = null;
    }

    var s = '';
    var all = tabLists.getAll();
    for(var i in all) {
        s += '<li id="slmenu_list:'+all[i].id+'" class="'+(all[i].id==curList.id?'mtt-item-checked':'')+' list-id-'+all[i].id+(all[i].hidden?' mtt-list-hidden':'')+'"><div class="menu-icon"></div><a href="'+ _mtt.urlForList(all[i])+ '">'+all[i].name+'</a></li>';
    }
    $('#slmenucontainer ul>.slmenu-lists-begin').nextAll().remove();
    $('#slmenucontainer ul>.slmenu-lists-begin').after(s);
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
    $('#slmenucontainer ul').append('<li id="slmenu_list:'+list.id+'" class="list-id-'+list.id+'"><div class="menu-icon"></div><a href="'+ _mtt.urlForList(list)+ '">'+list.name+'</a></li>');
};

function slmenuOnListSelected(list)
{
    $('#slmenucontainer li').removeClass('mtt-item-checked');
    $('#slmenucontainer li.list-id-'+list.id).addClass('mtt-item-checked').removeClass('mtt-list-hidden');

};

function slmenuOnListHidden(list)
{
    if (list.id == -1) return;
    $('#slmenucontainer li.list-id-'+list.id).addClass('mtt-list-hidden');
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
        tabSelect(parseInt(value));
    }
    return false;
};

function hideList(listId)
{
    if (typeof listId === 'string') {
        listId = parseInt(listId);
    }
    else if (typeof listId !== 'number') {
        return;
    }

    if(!tabLists.get(listId)) return false;

    // if we hide current tab
    var listIdToSelect = 0;
    if(curList.id == listId) {
        var all = tabLists.getAll();
        for(var i in all) {
            if(all[i].id != curList.id && !all[i].hidden) {
                listIdToSelect = all[i].id;
                break;
            }
        }
        // do not hide the tab if others are hidden
        if(!listIdToSelect) return false;
    }

    if(listId == -1) {
        $('#list_all').addClass('mtt-tab-hidden').removeClass('mtt-tab-selected');
    }
    else {
        $('#list_'+listId).addClass('mtt-tab-hidden').removeClass('mtt-tab-selected');
    }

    tabLists.get(listId).hidden = true;

    _mtt.db.request('setHideList', {list:listId, hide:1});
    _mtt.doAction('listHidden', tabLists.get(listId));

    if(listIdToSelect) {
        tabSelect(listIdToSelect);
    }
}

function getLocalStorageItem(key)
{
    try {
        return localStorage.getItem(key);
    }
    catch (e) {
        console.log(e);
    }
    return null;
}

function setLocalStorageItem(key, value)
{
    try {
        localStorage.setItem(key, value);
    }
    catch (e) {
        console.log(e);
    }
}

function newTaskCounterStart()
{
    clearInterval(_mtt.timers.newTaskCounter);
    _mtt.timers.newTaskCounter = setInterval(newTaskCounter, 60*1000); //every 60 sec
}

function newTaskCounter()
{
    const params = {
        list: curList.id,
        later: curList.lastTime,
        showCompl: curList.showCompl,
        lists: [],
    };
    tabLists.getAll().forEach( (list) => {
        if (list.hidden || list.id == -1 || list.id == curList.id) {
            return;
        }
        params.lists.push({
            listId: list.id,
            later: list.lastTime
        });
    });

    fetch(_mtt.apiUrl + 'tasks/newCounter', {
        method: 'POST',
        credentials: 'same-origin', // old browsers
        headers: {
            'Content-Type': 'application/json',
            'MTT-Token': _mtt.options.token,
        },
        body: JSON.stringify(params)
    })
    .then(response => response.json())
    .then(json => {
        if (json && json.ok) {
            let counters = {};
            let curCounter = 0;
            if (Array.isArray(json.tasks)) {
                json.tasks.forEach((id) => {
                    if (!taskList[id]) {
                        curCounter++;
                    }
                });
            }
            counters[curList.id] = curCounter;

            if (Array.isArray(json.lists)) {
                json.lists.forEach((item) => {
                    counters["" + item.listId] = +item.counter;
                });
            }

            tabLists.getAll().forEach( (list) => {
                if (!list.hidden || list.id != -1) {
                    setNewTaskCounterForList(list.id, counters[list.id]);
                }
            });
        }
    });
}

function setNewTaskCounterForList(listId, counter)
{
    if (counter > 0) {
        $('#list_' + listId).find('.counter').text(counter).removeClass('hidden');
    } else {
        $('#list_' + listId).find('.counter').text('').addClass('hidden');
    }
}

/*
    Errors and Info messages
*/

function flashError(str, details)
{
    if (details === undefined) details = '';
    $("#msg>.msg-text").text(dehtml(str))
    $("#msg>.msg-details").text(dehtml(details));
    $("#loading").hide();
    $("#msg").addClass('mtt-error').effect("highlight", {color:_mtt.theme.msgFlashColor}, 700);
}

function flashInfo(str, details)
{
    if (details === undefined) details = '';
    $("#msg>.msg-text").text(dehtml(str))
    $("#msg>.msg-details").text(dehtml(details));
    $("#loading").hide();
    $("#msg").addClass('mtt-info').effect("highlight", {color:_mtt.theme.msgFlashColor}, 700);
}

function hideAlert()
{
    $("#msg>.msg-text").text('');
    $("#msg>.msg-details").text('');
    $("#msg").hide().removeClass('mtt-error mtt-info').find('.msg-details').hide();
}


/*
    Authorization
*/
function updateAccessStatus()
{
    // flag.needAuth is not changed after pageload
    if(flag.needAuth)
    {
        if (flag.isLogged) {
            showhide( $("#logout_btn"), $("#login_btn") );
        }
        else {
            showhide( $("#login_btn"), $("#logout_btn") );
        }
    }
    else {
        $('#mtt').addClass('no-need-auth');
    }
    if(flag.needAuth && !flag.isLogged) {
        flag.readOnly = true;
        $("#bar_public").show();
        $('#mtt').addClass('readonly')
        liveSearchToggle(1);
        // remove some tab menu items
        $('#btnRenameList,#btnDeleteList,#btnClearCompleted,#btnPublish').remove();
    }
    else {
        flag.readOnly = false;
        $('#mtt').removeClass('readonly')
        $("#bar_public").hide();
        liveSearchToggle(0);
    }
    $('#page_ajax').hide();
}

function showLogin()
{
    if (_mtt.pages.current && _mtt.pages.current.page == 'login') {
        return false;
    }
    _mtt.pageSet('login', '');
    $('#password').val('').focus();
}

function doAuth(form)
{
    _mtt.db.request( 'login', { password: form.password.value }, function(json) {
        form.password.value = '';
        if(json.logged)
        {
            flag.isLogged = true;
            window.location.hash = '';
            window.location.reload();
        }
        else {
            flashError(_mtt.lang.get('invalidpass'));
            $('#password').focus();
        }
    });
}

function logout()
{
    _mtt.db.request( 'logout', {}, function(json) {
        flag.isLogged = false;
        window.location.hash = '';
        window.location.reload();
    });
    return false;
}


/*
    Settings
*/

function showSettings(json = 0)
{
    let reload = false;
    if (_mtt.pages.current && _mtt.pages.current.page == 'ajax' && _mtt.pages.current.pageClass == 'settings') {
        reload = true;
    }
    const jsonParam = (json == 1) ? '&json=1' : '';
    $('#page_ajax').load(_mtt.mttUrl + 'settings.php?ajax=yes' + jsonParam, null, function(){
        if (!reload) {
            _mtt.pageSet('ajax','settings');
            const newTitle = _mtt.lang.get('set_header') + ' - ' + _mtt.options.title;
            updateHistoryState( { settings:1, settingsJson:json }, _mtt.urlForSettings(json), newTitle );
            _mtt.doAction('settingsLoaded');
        }
    })
}

function saveSettings(frm)
{
    if(!frm) return false;
    var params = { save:'ajax' };
    $(frm).find("input:hidden,input:text,input:password,input:checked,select,textarea").filter(":enabled").each(function() { params[this.name || '__'] = this.value; });
    $(frm).find(":submit").attr('disabled','disabled').blur();
    $.post(_mtt.mttUrl+'settings.php', params, function(json){
        if(json.saved) {
            flashInfo(_mtt.lang.get('settingsSaved'));
            setTimeout( function(){
                window.location.assign(_mtt.homeUrl); //window.location.reload();
            }, 1000);
        }
    }, 'json');
}

function activateExtension(activate, ext)
{
    var params = {
        'activate': activate ? 1 : 0,
        'ext': ext
    }
    $.post(_mtt.mttUrl+'settings.php', params, function(json){
        if(json.saved) {
            flashInfo(_mtt.lang.get('settingsSaved'));
            showSettings(0);
        }
    }, 'json');
}

function showExtensionSettings(ext, callback, reload)
{
    if (_mtt.pages.current && _mtt.pages.current.page == 'ajax' && _mtt.pages.current.pageClass == 'settings') {
        $('#page_ajax').load(_mtt.apiUrl + 'ext-settings/' + ext, null, function() {
            if (callback) callback();
            if (!reload) {
                _mtt.pageSet('ajax','settings');
                const newTitle = `${ext} - ${_mtt.lang.get('set_header')} - ${_mtt.options.title}`;
                replaceHistoryState('extSettings', { extSettings:true, ext:ext }, _mtt.urlForExtSettings(ext), newTitle );
            }
        });
    }
}

function saveExtensionSettings(frm)
{
    if (!frm) return false;
    var ext = frm.dataset.ext;
    var params = {};
    $(frm).find("input:hidden,input:text,input:password,input:checked,select,textarea").filter(":enabled").each(function() { params[this.name || '__'] = this.value; });
    $.ajax({
        url: _mtt.apiUrl + 'ext-settings/' + ext,
        method: 'PUT',
        contentType : 'application/json',
        data: JSON.stringify(params),
        dataType: 'json',
        success: function(json) {
            if (json.saved) {
                if (json.msg) showExtensionSettings(ext, function(){ flashInfo(json.msg); }, true);
                else showExtensionSettings(ext, null, true);
            }
            else if (json.msg) {
                flashError(json.msg);
            }
        }
    });
}

function extensionSettingsAction(actionString, ext, formData)
{
    if (actionString === undefined || ext === undefined) return false;
    const a = actionString.split(':', 2);
    if (a.length !== 2) return false;
    const method = a[0],
          action = a[1];
    const success = function(json) {
        if (json.total && json.total > 0) {
            if (json.redirect) {
                window.location.assign(json.redirect);
                return;
            }
            if (json.html) {
                $('#page_ajax .mtt-settings-table').html(json.html); //FIXME: maybe whole page?
                return;
            }
            if (json.alertText) {
                mttAlert(json.alertText);
                return;
            }
            const callback = function() {
                if (json.alertTextOnLoad) {
                    mttAlert(json.alertTextOnLoad);
                }
                else if (json.msg) {
                    flashInfo(json.msg, json.details);
                }
                if (json.reload) {
                    setTimeout( function(){
                        //window.location.hash = '';
                        window.location.reload();
                    }, 1000);
                }
            }
            showExtensionSettings(ext, callback, true);
        }
        else if (json.msg) {
            flashInfo(json.msg, json.details);
        }
    };
    if (formData === undefined) {
        $.ajax({
            url: _mtt.apiUrl + 'ext/' + ext + '/' + action,
            method: method.toUpperCase(),
            contentType : 'application/json',
            data: '{}',
            dataType: 'json',
            success: success
        });
    }
    else {
        $.ajax({
            url: _mtt.apiUrl + 'ext/' + ext + '/' + action,
            method: method.toUpperCase(),
            contentType : false,
            data: formData,
            processData: false,
            success: success
        });
    }
}
_mtt.extensionSettingsAction = extensionSettingsAction;

/*
 *  Dialogs
 */

function mttConfirm(msg, callbackOk, callbackCancel)
{
    mttModalDialog('confirm').message(msg).ok(callbackOk).cancel(callbackCancel).show();
}

function mttPrompt(msg, defaultValue, callbackOk, callbackCancel)
{
    mttModalDialog('prompt').message(msg).default(defaultValue).ok(callbackOk).cancel(callbackCancel).show();
}

function mttAlert(msg, callbackOk)
{
    mttModalDialog().ok(callbackOk).message(msg).show();
}

function mttModalDialog(dialogType = 'alert')
{
    if ( ! (this instanceof mttModalDialog) ) return new mttModalDialog(dialogType);
    let dialog = this;
    this.type = dialogType;
    let lastScrollTop = 0;

    this.close = function() {
        //restore scrolling
        $('body').css({
            'position': '',
            'top': ''
        });
        window.scrollTo(window.pageXOffset,  lastScrollTop);
        $("html").removeClass('mtt-modal-dialog-active');
        $("#modal_overlay, #modal").hide();
        $("#btnModalOk").off('click');
        $("#btnModalCancel").off('click');
        $("#modalMessage").text('');
        $("#modalTextInput").val('').off('keyup.mttmodal');
        $(document).off('keydown.mttmodal');
    } ;

    this.ok = function(callback) {
        $("#btnModalOk").on('click', function() {
            const value = $("#modalTextInput").val();
            dialog.close();
            if (typeof callback === 'function')
                callback( dialog.type === 'prompt' ? value : null );
        });
        return dialog;
    };

    this.cancel = function(callback) {
        $("#btnModalCancel").on('click', function() {
            dialog.close();
            if (typeof callback === 'function')
                callback();
        });
        return dialog;
    };

    this.message = function(msg = '') {
        $("#modalMessage").text(msg);
        return dialog;
    };

    this.default = function(value = '') {
        $("#modalTextInput").val(value);
        return dialog;
    }

    this.show = function() {
        let modalOverlay = document.getElementById("modal_overlay");
        if (!modalOverlay) {
            modalOverlay = document.createElement("div");
            modalOverlay.id = "modal_overlay";
            modalOverlay.style.cssText = "position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background-color: black; opacity: 0.6; display:none;";
            document.getElementsByTagName('body')[0].appendChild(modalOverlay);
        }

        if (dialog.type === 'confirm') {
            $("#btnModalCancel").show();
            $("#modalTextInput").hide();
        }
        else if(dialog.type === 'prompt') {
            $("#btnModalCancel").show();
            $("#modalTextInput").show();
            $("#modalTextInput").on("keyup.mttmodal", function(e) {
                if (e.keyCode == 13) {
                    $("#btnModalOk").click();
                }
            });
        }
        else {
            $("#btnModalCancel").hide();
            $("#modalTextInput").hide();
        }

        $(document).on('keydown.mttmodal', function(event) {
            if (event.keyCode == 27) {
                dialog.close();
            }
        });

        //disable background scrolling
        lastScrollTop = window.pageYOffset;
        $('body').css({
            'position': 'fixed',
            'top': `-${lastScrollTop}px`
        })

        $("html").addClass('mtt-modal-dialog-active');
        $("#modal_overlay, #modal").show();
        $("#modalTextInput").focus();
        return dialog;
    };
}

/*
 *  History and Hash change
 */


/**
 * Manipulate browser history manually.
 * //TODO: use window.location and hashchange event ?
 * @param {object} state History Api state data
 * @param {string} url   document url. appended to the state.
 * @param {string} title document title to set. appended to the state.
 */
function updateHistoryState(state, url, title)
{
    if (!_mtt.options.history) {
        document.title = title;
        return;
    }
    if (flag.dontChangeHistoryOnce) {
        flag.dontChangeHistoryOnce = false;
    }
    else {
        if (_mtt.lastHistoryState) {
            //_mtt.lastHistoryState.title = document.title;
            window.history.pushState(_mtt.lastHistoryState, _mtt.lastHistoryState.title, _mtt.lastHistoryState.url);
        }
        state.url = url;
        state.title = title;
        window.history.replaceState(state, title, url); //also refresh visible URL
    }
    _mtt.lastHistoryState = history.state;
    flag.firstLoad = false;
    document.title = title;
}

function replaceHistoryState(param, _state, url, title)
{
    if (!_mtt.options.history) {
        document.title = title;
        return;
    }
    if (flag.dontChangeHistoryOnce) {
        flag.dontChangeHistoryOnce = false;
    }
    const state = history.state;
    if (state && state[param]) {
        _state.url = url;
        _state.title = title;
        history.replaceState(_state, '', url);
        document.title = title;
        _mtt.lastHistoryState = history.state;
    }
    else {
        updateHistoryState(_state, url, title);
    }
}

function historyOnPopState(event)
{
    if (!event.state) return;
    if (event.state.list && _mtt.pages.current &&
        ((_mtt.pages.current.page == 'ajax' && _mtt.pages.current.pageClass == 'settings') || _mtt.pages.current.page == 'taskviewer') ) {
        // Here we go back to tasklist from settings or view task, no reload.
        // Just show and hide pages without history actions.
        _mtt.pageBack();
        flag.dontChangeHistoryOnce = true;
        updateHistoryState( { list:event.state.list }, event.state.url, event.state.title );
    }
    else if (event.state.task) {
        flag.dontChangeHistoryOnce = true;
        viewTask(event.state.task);
    }
    else if (event.state.list) {
        flag.dontChangeHistoryOnce = true;
        tabSelect(event.state.list);
    }
    else if (event.state.settings) {
        _mtt.pageBack(); // will do nothing if back from tasks
        flag.dontChangeHistoryOnce = true;
        _mtt.lastHistoryState = event.state;
        // will pageSet() if back from tasks
        // will not pageSet() if back from extSettings
        showSettings(event.state.settingsJson);
    }
    else if (event.state.extSettings) {
        flag.dontChangeHistoryOnce = true;
        showExtensionSettings(event.state.ext);
    }
    else {
        console.log("unexpected: nothing to pop");
    }
}


})();
