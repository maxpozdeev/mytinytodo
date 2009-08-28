<?php

/*

myTinyTodo language pack (German)

v1.1 		
Author: Sven Geisen (www.svengiesen.de)	
Note: Initial version

v1.2 		
Author: Tobias Gaekle (ike@gmx.de)					
Changes: Wording modified, new strings added
Note: Set $config['duedateformat'] = 3 in db/config.php for German date format!

**/

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "Notiz bearbeiten",
		'actionEdit' => "Aufgabe bearbeiten",
		'actionDelete' => "Aufgabe l&ouml;schen",
		'taskDate' => array("function(date) { return 'hinzugef&uuml;gt am '+date; }"),
		'confirmDelete' => "Sind Sie sicher?",
		'actionNoteSave' => "speichern",
		'actionNoteCancel' => "abbrechen",
		'error' => "FEHLER",
		'denied' => "Zugriff verweigert",
		'invalidpass' => "Falsches Passwort",
		'readonly' => "nur lesen",
		'tagfilter' => "Tag:",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Meine Aufgaben",
		'tab_newtask' => "Aufgabe",
		'tab_search' => "Suche",
		'btn_add' => "hinzuf&uuml;gen",
		'btn_search' => "suchen",
		'searching' => "Suchbegriff",
		'tasks' => "Offene Aufgaben",
		'edit_task' => "Aufgabe bearbeiten",
		'priority' => "Priorit&auml;t",
		'task' => "Aufgabe",
		'note' => "Notiz",
		'save' => "speichern",
		'cancel' => "abbrechen",
		'password' => "Passwort",
		'btn_login' => "Login",
		'a_login' => "Login",
		'a_logout' => "Logout",
		'tags' => "Tags",
		'tagfilter_cancel' => "Kein Filter",
		'sortByHand' => "Manuell sortieren",
		'sortByPriority' => "Nach Priorit&auml;t sortieren",
		'sortByDueDate' => "Nach F&auml;lligkeit sortieren",
		'due' => "F&auml;llig",
		'daysago' => "vor %d Tagen",
		'indays' => "in %d Tagen",
		'months_short' => array("Januar","Februar","M&auml;rz","April","Mai","Juni","Juli","August","September","Oktober","November","Dezember"),
		'days_min' => array("So","Mo","Di","Mi","Do","Fr","Sa"),
		'date_md' => "%2\$d. %1\$s",
		'date_ymd' => "%3\$d. %2\$s %1\$d",
		'today' => "Heute",
		'yesterday' => "Gestern",
		'tomorrow' => "Morgen",
		'f_past' => "&Uuml;berf&auml;llig",
		'f_today' => "Heute und Morgen",
		'f_soon' => "Demn&auml;chst",
		'tasks_and_compl' => "Alle Aufgaben"
	);
}

?>