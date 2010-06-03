<?php

/*
	myTinyTodo language pack
	Language: German
	Original name: Deutsch
	Author: Sebastian
	Author Url:
	AppVersion: v1.3.4
	Date: 2010-02-26
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "Willst du die Aufgabe wirklich löschen?",
		'actionNoteSave' => "speichern",
		'actionNoteCancel' => "abbrechen",
		'error' => "Fehler aufgetreten (click für Details)",
		'denied' => "Zugriff verweigert",
		'invalidpass' => "Falsches Passwort",
		'tagfilter' => "Schlagwort:",
		'addList' => "Neue Liste anlegen",
		'renameList' => "Liste umbenennen",
		'deleteList' => "This will delete current list with all tasks in it.\\nAre you sure?",
		'clearCompleted' => "Löscht die aktuelle Liste mit allen darin enthaltenen Aufgaben.\\nBist du sicher?",
		'settingsSaved' => "Einstellugnen gespeichert. Aktualisierung...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'htab_newtask' => "Neue Aufgabe",
		'htab_search' => "Suchen",
		'btn_add' => "Hinzufügen",
		'btn_search' => "Suchen",
		'advanced_add' => "Erweitert",
		'searching' => "Suchen nach",
		'tasks' => "Aufgaben",
		'taskdate_inline' => "hinzugefügt am %s",
		'taskdate_created' => "Erstelldatum",
		'taskdate_completed' => "Abschlussdatum",
		'edit_task' => "Aufgabe bearbeiten",
		'add_task' => "Neue Aufgabe",
		'priority' => "Priorität",
		'task' => "Aufgabe",
		'note' => "Notiz",
		'save' => "Speichern",
		'cancel' => "Abbrechen",
		'password' => "Passwort",
		'btn_login' => "Login",
		'a_login' => "Login",
		'a_logout' => "Logout",
		'public_tasks' => "Öffentliche Aufgabe",
		'tags' => "Schlagwörter",
		'tagfilter_cancel' => "Filter aufheben",
		'sortByHand' => "Manuell sortieren",
		'sortByPriority' => "Nach Priorität sortieren",
		'sortByDueDate' => "Nach Fälligkeitsdatum sortieren",
		'due' => "Fällig",
		'daysago' => "vor %d Tagen",
		'indays' => "in %d Tagen",
		'months_short' => array("Jan","Feb","Mar","Apr","Mai","Jun","Jul","Aug","Sep","Okt","Nov","Dez"),
		'months_long' => array("Januar","Februar","M&auml;rz","April","Mai","Juni","Juli","August","September","Oktober","November","Dezember"),
		'days_min' => array("So","Mo","Di","Mi","Do","Fr","Sa"),
		'days_long' => array("Sonntag","Montag","Dienstag","Mittwoch","Donnerstag","Freitag","Samstag"),
		'today' => "heute",
		'yesterday' => "gestern",
		'tomorrow' => "morgen",
		'f_past' => "Überfällig",
		'f_today' => "Heute und morgen",
		'f_soon' => "Bald",
		'action_edit' => "Bearbeiten",
		'action_note' => "Notiz bearbeiten",
		'action_delete' => "Löschen",
		'action_priority' => "Priorität",
		'action_move' => "Verschieben nach",
		'notes' => "Notizen:",
		'notes_show' => "Anzeigen",
		'notes_hide' => "Verbergen",
		'list_new' => "Neue Liste",
		'list_rename' => "Liste umbenennen",
		'list_delete' => "Liste löschen",
		'list_publish' => "Liste veröffentlichen",
		'list_showcompleted' => "Abgeschlossene Aufgaben anzeigen",
		'list_clearcompleted' => "Abgeschlossene Aufgaben löschen",
		'alltags' => "Alle Schlagwörter:",
		'alltags_show' => "Alle anzeigen",
		'alltags_hide' => "Alle verbergen",
		'a_settings' => "Einstellungen",
		'rss_feed' => "RSS Feed",
		'feed_title' => "%s",
		'feed_description' => "Neue Aufgaben in %s",

		/* Settings */
		'set_header' => "Einstellungen",
		'set_title' => "Titel",
		'set_title_descr' => "(angeben, um Standardtitel zu ändern)",
		'set_language' => "Sprache",
		'set_protection' => "Passwortschutz",
		'set_enabled' => "Aktiviert",
		'set_disabled' => "Deaktiviert",
		'set_newpass' => "Neues Passwort",
		'set_newpass_descr' => "(leer lassen, um aktuelles Passwort nicht zu ändern)",
		'set_smartsyntax' => "Smartsyntax",
		'set_smartsyntax_descr' => "(/Priorität/ Aufgabe /Schlagwörter/)",
		'set_autotz' => "Automatische Zeitzone",
		'set_autotz_descr' => "(erkennt die Zeitzone des Nutzers mittels Javascript)",
		'set_autotag' => "Automatische Schlagwörter",
		'set_autotag_descr' => "(fügt Schlagwort des aktuellen Filters automatisch der neu erstellten Aufgabe hinzu)",
		'set_sessions' => "Sessionhandling-Mechanismus",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Dateien",
		'set_firstdayofweek' => "Erster Tag der Woche",
		'set_duedate' => "Datumsformat Fälligkeitsdatum",
		'set_date' => "Datumsformat",
		'set_shortdate' => "Kurzes Datumsformat",
		'set_clock' => "Zeitformat",
		'set_12hour' => "12 Stunden",
		'set_24hour' => "24 Stunden",
		'set_submit' => "Änderungen speichern",
		'set_cancel' => "Abbrechen",
		'set_showdate' => "Aufgabendatum in Liste anzeigen",
	);
}

?>