<?php

/*
	myTinyTodo language pack
	Language: German
	Author: Isabelle
	E-Mail: 
	Url: http://aileesh.blogspot.com/
	Version: v1.3b3
	Date: 2009-11-04
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "Notiz",
		'actionEdit' => "&auml;ndern",
		'actionDelete' => "l&ouml;schen",
		'taskDate' => array("function(date) { return 'added at '+date; }"),
		'confirmDelete' => "Bist du sicher?",
		'actionNoteSave' => "speichern",
		'actionNoteCancel' => "abbrechen",
		'error' => "Fehler aufgetreten (click für Details)",
		'denied' => "Zugriff verweigert",
		'invalidpass' => "Falsches Passwort",
		'readonly' => "read-only",
		'tagfilter' => "Tag:",
		'addList' => "neue Liste anlegen",
		'renameList' => "Liste umbenennen",
		'deleteList' => "Löscht die aktuelle Liste mit allen darin enthaltenen Aufgaben.\\nBist du sicher?",
		'settingsSaved' => "Einstellugnen gespeichert. Aktualisierung...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'htab_newtask' => "Neue Aufgabe",
		'htab_search' => "suchen",
		'btn_add' => "hinzuf&uuml;gen",
		'btn_search' => "suchen",
		'advanced_add' => "erweitert",
		'searching' => "suchen nach",
		'tasks' => "Aufgaben",
		'edit_task' => "Aufgabe bearbeiten",
		'add_task' => "Neue Aufgabe",
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
		'tagfilter_cancel' => "Filter aufheben",
		'sortByHand' => "sortieren nach",
		'sortByPriority' => "sortieren nach Priorit&auml;t",
		'sortByDueDate' => "sortieren nach F&auml;llig-Datum",
		'due' => "F&auml;llig",
		'daysago' => "vor %d Tagen",
		'indays' => "in %d Tagen",
		'months_short' => array("Jan","Feb","Mar","Apr","Mai","Jun","Jul","Aug","Sep","Okt","Nov","Dez"),
		'months_long' => array("Januar","Februar","M&auml;rz","April","Mai","Juni","Juli","August","September","Oktober","November","Dezember"),
		'days_min' => array("So","Mo","Di","Mi","Do","Fr","Sa"),
		'today' => "heute",
		'yesterday' => "gestern",
		'tomorrow' => "morgen",
		'f_past' => "&uuml;berf&auml;llig",
		'f_today' => "heute und morgen",
		'f_soon' => "bald",
		'tasks_and_compl' => "Aufgaben + erledigt",
		'notes' => "Notizen:",
		'notes_show' => "Anzeigen",
		'notes_hide' => "Verbergen",
		'list_new' => "neue Liste",
		'list_rename' => "umbenennen",
		'list_delete' => "l&ouml;schen",
		'alltags' => "Alle Tags:",
		'alltags_show' => "alle anzeigen",
		'alltags_hide' => "alle verbergen",
		'a_settings' => "Einstellungen",
		'rss_feed' => "RSS Feed",
	);
}

?>