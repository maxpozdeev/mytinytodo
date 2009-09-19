<?php

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "note",
		'actionEdit' => "modify",
		'actionDelete' => "delete",
		'taskDate' => array("function(date) { return 'added at '+date; }"),
		'confirmDelete' => "Are you sure?",
		'actionNoteSave' => "save",
		'actionNoteCancel' => "cancel",
		'error' => "Some error occurred (click for details)",
		'denied' => "Access denied",
		'invalidpass' => "Wrong password",
		'readonly' => "read-only",
		'tagfilter' => "Tag:",
		'addList' => "Create new list",
		'renameList' => "Rename list",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'tab_newtask' => "new task",
		'tab_search' => "search",
		'btn_add' => "Add",
		'btn_search' => "Search",
		'searching' => "Searching for",
		'tasks' => "Tasks",
		'edit_task' => "Edit Task",
		'priority' => "Priority",
		'task' => "Task",
		'note' => "Note",
		'save' => "Save",
		'cancel' => "Cancel",
		'password' => "Password",
		'btn_login' => "Login",
		'a_login' => "Login",
		'a_logout' => "Logout",
		'tags' => "Tags",
		'tagfilter_cancel' => "cancel filter",
		'sortByHand' => "Sort by hand",
		'sortByPriority' => "Sort by priority",
		'sortByDueDate' => "Sort by due date",
		'due' => "Due",
		'daysago' => "%d days ago",
		'indays' => "in %d days",
		'months_short' => array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"),
		'months_long' => array("January","February","March","April","May","June","July","August","September","October","November","December"),
		'days_min' => array("Su","Mo","Tu","We","Th","Fr","Sa"),
		'date_md' => "%1\$s %2\$d",
		'date_ymd' => "%2\$s %3\$d, %1\$d",
		'today' => "today",
		'yesterday' => "yesterday",
		'tomorrow' => "tomorrow",
		'f_past' => "Overdue",
		'f_today' => "Today and tomorrow",
		'f_soon' => "Soon",
		'tasks_and_compl' => "Tasks + completed",
	);
}

?>