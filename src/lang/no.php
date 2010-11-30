<?php

/*
	myTinyTodo language pack
	Language: Norwegian
	Original name: Norsk
	Author: Simen Aas Henriksen
	Author Url: http://www.sweb.no
	AppVersion: v1.3.4
	Date: 2010-10-30
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "Er du sikker på at du vil slette denne oppgaven?",
		'actionNoteSave' => "lagre",
		'actionNoteCancel' => "avbryt",
		'error' => "En feil har oppstått (klikk for detaljer)",
		'denied' => "Tilgang nektet!",
		'invalidpass' => "Feil passord",
		'tagfilter' => "Tag:",
		'addList' => "La ny liste",
		'renameList' => "Bytt navn på liste",
		'deleteList' => "Du sletter nå hele listen, og da innholdet i listen.\\nEr du sikker på at du vil gjøre dette?",
		'clearCompleted' => "Du sletter nå alle oppgaver som er markert som ferdig, fra listen.\\nEr du sikker på at du vil gjøre dette?",
		'settingsSaved' => "Innstillinger er lagret. Oppdaterer...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Min lille ToDo liste",
		'htab_newtask' => "Ny oppgave",
		'htab_search' => "Søk",
		'btn_add' => "Legg til",
		'btn_search' => "Søk",
		'advanced_add' => "Avansert",
		'searching' => "Søker etter",
		'tasks' => "Oppgaver",
		'taskdate_inline' => "lagt til %s",
		'taskdate_created' => "Opprettelsesdato",
		'taskdate_completed' => "Dato ferdig",
		'edit_task' => "Rediger oppgave",
		'add_task' => "Ny oppgave",
		'priority' => "Prioritet",
		'task' => "Oppgave",
		'note' => "Notat",
		'save' => "Lagre",
		'cancel' => "Avbryt",
		'password' => "Passord",
		'btn_login' => "Logg inn",
		'a_login' => "Logg inn",
		'a_logout' => "Logg ut",
		'public_tasks' => "Offentlige oppgaver",
		'tags' => "Tags",
		'tagfilter_cancel' => "avbryt filter",
		'sortByHand' => "Sorter manuelt",
		'sortByPriority' => "Sorter etter prioritering",
		'sortByDueDate' => "Sorter etter dato",
		'due' => "forventes ferdig",
		'daysago' => "%d dager siden",
		'indays' => "om %d dager",
		'months_short' => array("Jan","Feb","Mar","Apr","Mai","Jun","Jul","Aug","Sep","Okt","Nov","Des"),
		'months_long' => array("Januar","Februar","Mars","April","Mai","Juni","July","August","September","Oktober","November","Desember"),
		'days_min' => array("Søn","Man","Tir","Ons","Tor","Fre","Lør"),
		'days_long' => array("Søndag","Mandag","Tirsdag","Onsdag","Torsdag","Fredag","Lørdag"),
		'today' => "idag",
		'yesterday' => "igår",
		'tomorrow' => "imorgen",
		'f_past' => "Forfalt",
		'f_today' => "Idag og imorgen",
		'f_soon' => "Snart",
		'action_edit' => "Rediger",
		'action_note' => "Rediger notat",
		'action_delete' => "Slett",
		'action_priority' => "Prioritet",
		'action_move' => "Flytt til",
		'notes' => "Notater:",
		'notes_show' => "Vis",
		'notes_hide' => "Gjem",
		'list_new' => "Ny liste",
		'list_rename' => "Bytt navn på liste",
		'list_delete' => "Slett liste",
		'list_publish' => "Offentliggjør list",
		'list_showcompleted' => "Vis ferdigstilte oppgaver",
		'list_clearcompleted' => "Slett ferdigstilte oppgaver",
		'alltags' => "Alle tags:",
		'alltags_show' => "Vise alle",
		'alltags_hide' => "Gjem alle",
		'a_settings' => "Innstillinger",
		'rss_feed' => "RSS Feed",
		'feed_title' => "%s",
		'feed_description' => "Nye oppgaver i %s",

		/* Settings */
		'set_header' => "Innstillinger",
		'set_title' => "Tittel",
		'set_title_descr' => "(endre om du vil redigere originaltittelen)",
		'set_language' => "Språk",
		'set_protection' => "Passordsbeskyttelse",
		'set_enabled' => "Aktivert",
		'set_disabled' => "Deaktivert",
		'set_newpass' => "Nytt passord",
		'set_newpass_descr' => "(la stå om du ikke vil endre passord)",
		'set_smartsyntax' => "Smart syntax",
		'set_smartsyntax_descr' => "(/prioritet/oppgaver/tags/)",
		'set_autotz' => "Automatisk tidssone",
		'set_autotz_descr' => "(bestemmer tidssonen)",
		'set_autotag' => "Autotagging",
		'set_autotag_descr' => "(Legger automatisk til tags fra nåværende tag filter på nyopprettede oppgaver)",
		'set_sessions' => "Session handling mechanism",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Filer",
		'set_firstdayofweek' => "Første dagen i uken",
		'set_duedate' => "Forfallsdato kalenderen format",
		'set_date' => "Dato format",
		'set_shortdate' => "'Kort' Dato format",
		'set_clock' => "Klokkeformat",
		'set_12hour' => "12-timer",
		'set_24hour' => "24-timer",
		'set_submit' => "Godta endringer",
		'set_cancel' => "Avbryt",
		'set_showdate' => "Vis oppgavedato i listen",
	);
}

?>