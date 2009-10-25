<?php

/*
	myTinyTodo language pack: Dutch
	by André Groendijk
	v1.2 compatible 
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "notitie",
		'actionEdit' => "wijzigen",
		'actionDelete' => "verwijderen",
		'taskDate' => array("function(date) { return 'toegevoegd op '+date; }"),
		'confirmDelete' => "Zeker weten?",
		'actionNoteSave' => "opslaan",
		'actionNoteCancel' => "annuleren",
		'error' => "Foutmelding... (klik voor details)",
		'denied' => "Verboden toegang",
		'invalidpass' => "Verkeerd wachtwoord",
		'readonly' => "alleen-lezen",
		'tagfilter' => "Tag:",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'tab_newtask' => "nieuwe taak",
		'tab_search' => "zoeken",
		'btn_add' => "Toevoegen",
		'btn_search' => "Zoeken",
		'searching' => "Zoekt naar",
		'tasks' => "Taak",
		'edit_task' => "Wijzig taak",
		'priority' => "Prioriteit",
		'task' => "Taak",
		'note' => "Notitie",
		'save' => "Opslaan",
		'cancel' => "Annuleren",
		'password' => "Wachtwoord",
		'btn_login' => "Login",
		'a_login' => "Login",
		'a_logout' => "Logout",
		'tags' => "Tags",
		'tagfilter_cancel' => "annuleer filter",
		'sortByHand' => "Handmatig sorteren",
		'sortByPriority' => "Sorteren op prioriteit",
		'sortByDueDate' => "Sorteren op datum",
		'due' => "Tot",
		'daysago' => "%d dagen geleden",
		'indays' => "in %d dagen",
		'months_short' => array("Jan","Feb","Mar","Apr","Mei","Jun","Jul","Aug","Sep","Okt","Nov","Dec"),
		'days_min' => array("Su","Mo","Tu","We","Th","Fr","Sa"),
		'date_md' => "%1\$s %2\$d",
		'date_ymd' => "%2\$s %3\$d, %1\$d",
		'today' => "vandaag",
		'yesterday' => "gister",
		'tomorrow' => "morgen",
		'f_past' => "Overdue",
		'f_today' => "Vandaag en morgen",
		'f_soon' => "Binnenkort",
		'tasks_and_compl' => "Taken + voltooid",
	);
}

?>