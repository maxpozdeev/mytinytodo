<?php

/*
	myTinyTodo language pack
	Language: Swedish
	Original name: Svenska
	Author: Martin Danielsson
	Author Url:
	AppVersion: v1.3.4
	Date: 2010-05-25
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "Vill du verkligen radera den här uppgiften?",
		'actionNoteSave' => "spara",
		'actionNoteCancel' => "avbryt",
		'error' => "Ett fel har uppstått (Tryck för mer info)",
		'denied' => "Tillträde nekas",
		'invalidpass' => "Fel lösenord",
		'tagfilter' => "Tagg:",
		'addList' => "Skapa ny lista",
		'renameList' => "Döp om lista",
		'deleteList' => "Det här tar bort listan och alla uppgifter.\\nVill du fortsätta?",
		'clearCompleted' => "Det här tar bort alla avslutade uppgifter.\\nFortsätt?",
		'settingsSaved' => "Inställningar sparade. Laddar om...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'htab_newtask' => "Ny uppgift",
		'htab_search' => "Sök",
		'btn_add' => "Lägg till",
		'btn_search' => "Sök",
		'advanced_add' => "Avancerat",
		'searching' => "Söker efter",
		'tasks' => "Uppgifter",
		'taskdate_inline' => "skapad %s",
		'taskdate_created' => "Skapad",
		'taskdate_completed' => "Avslutad",
		'edit_task' => "Ändra uppgift",
		'add_task' => "Ny uppgift",
		'priority' => "Prioritet",
		'task' => "Uppgift",
		'note' => "Notering",
		'save' => "Spara",
		'cancel' => "Avbryt",
		'password' => "Lösenord",
		'btn_login' => "Logga in",
		'a_login' => "Logga in",
		'a_logout' => "Logga ut",
		'public_tasks' => "Allmänna uppgifter",
		'tags' => "Taggar",
		'tagfilter_cancel' => "ta bort filter",
		'sortByHand' => "Sortera för hand",
		'sortByPriority' => "Sortera efter prioritet",
		'sortByDueDate' => "Sortera efter deadline",
		'due' => "Deadline",
		'daysago' => "%d dagar sen",
		'indays' => "om %d dagar",
		'months_short' => array("Jan","Feb","Mar","Apr","Maj","Jun","Jul","Aug","Sep","Okt","Nov","Dec"),
		'months_long' => array("Januari","Februari","Mars","April","Maj","Juni","July","Augusti","September","Oktober","November","December"),
		'days_min' => array("Sö","Må","Ti","On","To","Fr","Lö"),
		'days_long' => array("Söndag","Månday","Tisdag","Onsdag","Torsdag","Fredag","Lördag"),
		'today' => "idag",
		'yesterday' => "igår",
		'tomorrow' => "imorgon",
		'f_past' => "Försenad",
		'f_today' => "Idag och imorgon",
		'f_soon' => "Snart",
		'action_edit' => "Ändra",
		'action_note' => "Ändra notering",
		'action_delete' => "Ta bort",
		'action_priority' => "Prioritet",
		'action_move' => "Flytta till",
		'notes' => "Noteringar:",
		'notes_show' => "Visa",
		'notes_hide' => "Dölj",
		'list_new' => "Ny lista",
		'list_rename' => "Döp om lista",
		'list_delete' => "Ta bort lista",
		'list_publish' => "Publicera lista",
		'list_showcompleted' => "Visa avslutade uppgifter",
		'list_clearcompleted' => "Töm avslutade uppgifter",
		'alltags' => "Alla taggar:",
		'alltags_show' => "Visa alla",
		'alltags_hide' => "Dölj alla",
		'a_settings' => "Inställningar",
		'rss_feed' => "RSS-flöde",
		'feed_title' => "%s",
		'feed_description' => "Ny uppgifter för: %s",

		/* Settings */
		'set_header' => "Inställningar",
		'set_title' => "Titel",
		'set_title_descr' => "(specifiera om du vill ändra standardtitel)",
		'set_language' => "Språk",
		'set_protection' => "Lösenordsskydd",
		'set_enabled' => "På",
		'set_disabled' => "Av",
		'set_newpass' => "Nytt lösenord",
		'set_newpass_descr' => "(lämna blank för att inte byta lösenord)",
		'set_smartsyntax' => "Smart syntax",
		'set_smartsyntax_descr' => "(/prioritet/ uppgift /taggar/)",
		'set_autotz' => "Automatisk tidszon",
		'set_autotz_descr' => "(bestämmer tidszon baserat på användare via javascript)",
		'set_autotag' => "Autotaggning",
		'set_autotag_descr' => "(lägger automatiskt till taggar för det aktuella filtret när man skapar nya uppgifter)",
		'set_sessions' => "Hantering av sessioner",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Filer",
		'set_firstdayofweek' => "Vilken veckodag börjar veckan på",
		'set_duedate' => "Kalenderformat för deadline",
		'set_date' => "Datumformat",
		'set_shortdate' => "Kort datumformat",
		'set_clock' => "Tidsformat",
		'set_12hour' => "12-timmars",
		'set_24hour' => "24-timmars",
		'set_submit' => "Spara ändringar",
		'set_cancel' => "Avbryt",
		'set_showdate' => "Visa datum i listan",
	);
}

?>