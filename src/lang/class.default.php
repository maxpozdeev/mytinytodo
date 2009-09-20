<?php

/*
	myTinyTodo default language class
*/

class DefaultLang
{
	private $default_js = array
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
		'addListDefault' => "Todo",
		'renameList' => "Rename list",
	);

	private $default_inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'htab_newtask' => "New task",
		'htab_search' => "Search",
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

	var $js = array();
	var $inc = array();

	function makeJS()
	{
		$a = array();
		foreach($this->default_js as $k=>$v)
		{
			if(isset($this->js[$k])) $v = $this->js[$k];

			if(is_array($v)) {
				$a[] = "$k: ". $v[0];
			} else {
				$a[] = "$k: \"". str_replace('"','\\"',$v). "\"";
			}
		}
		$t = array();
		foreach($this->get('days_min') as $v) { $t[] = '"'.str_replace('"','\\"',$v).'"'; }
		$a[] = "daysMin: [". implode(',', $t). "]";
		$t = array();
		foreach($this->get('months_short') as $v) { $t[] = '"'.str_replace('"','\\"',$v).'"'; }
		$a[] = "monthsShort: [". implode(',', $t). "]";
		return "lang = {\n". implode(",\n", $a). "\n};";
	}

	function get($key)
	{
		if(isset($this->inc[$key])) return $this->inc[$key];
		if(isset($this->default_inc[$key])) return $this->default_inc[$key];
		return $key;
	}

	function formatMD($m, $d)
	{
		$months = $this->get('months_short');
		return sprintf($this->get('date_md'), $months[$m-1], $d);
	}

	function formatYMD($y, $m, $d)
	{
		$months = $this->get('months_short');
		return sprintf($this->get('date_ymd'), $y, $months[$m-1],  $d);
	}
}

?>