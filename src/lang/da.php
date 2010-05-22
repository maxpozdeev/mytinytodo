<?php

/*
	myTinyTodo language pack
	Language: Danish
	Original name: Dansk
	Author: Per Jensen
	Author Url: http://www.plads9000.dk
	AppVersion: v1.3.5
	Date: 2010-05-22
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "Er du sikker på at du vil slette denne opgave?",
		'actionNoteSave' => "gem",
		'actionNoteCancel' => "annuller",
		'error' => "Der opstod en fejl (klik for detaljer)",
		'denied' => "Adgang nægtet",
		'invalidpass' => "Forkert password",
		'tagfilter' => "Tag:",
		'addList' => "Opret ny liste",
		'renameList' => "Omdøb liste",
		'deleteList' => "Dette vil slette den aktuelle liste og alle opgaver i den.\\nEr du sikker?",
		'clearCompleted' => "Dette vil slette alle afsluttede opgaver i listen.\\nEr du sikker?",
		'settingsSaved' => "Indstillinger gemt. Genindlæser...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'htab_newtask' => "Ny opgave",
		'htab_search' => "Søg",
		'btn_add' => "Tilføj",
		'btn_search' => "Søg",
		'advanced_add' => "Udvidet",
		'searching' => "Søger efter",
		'tasks' => "Opgaver",
		'taskdate_inline' => "tilføjet den %s",
		'taskdate_created' => "Dato for oprettelse",
		'taskdate_completed' => "Dato for færdiggørelse",
		'edit_task' => "Rediger opgave",
		'add_task' => "Ny opgave",
		'priority' => "Prioritet",
		'task' => "Opgave",
		'note' => "Note",
		'save' => "Gem",
		'cancel' => "Annuller",
		'password' => "Password",
		'btn_login' => "Login",
		'a_login' => "Login",
		'a_logout' => "Logout",
		'public_tasks' => "Offentlige opgaver",
		'tags' => "Tags",
		'tagfilter_cancel' => "Annuller filter",
		'sortByHand' => "Sorter manuelt",
		'sortByPriority' => "Sorter efter prioritet",
		'sortByDueDate' => "Sorter efter forfaldsdato",
		'due' => "Forfald",
		'daysago' => "%d dage siden",
		'indays' => "om %d dage",
		'months_short' => array("Jan","Feb","Mar","Apr","Maj","Jun","Jul","Aug","Sep","Okt","Nov","Dec"),
		'months_long' => array("Januar","Februar","Marts","April","Maj","Juni","Juli","August","September","Oktober","November","December"),
		'days_min' => array("Sø","Ma","Ti","On","To","Fr","Lø"),
		'days_long' => array("Søndag","Mandag","Tirsdag","Onsdag","Torsdag","Fredag","Lørdag"),
		'today' => "I dag",
		'yesterday' => "I går",
		'tomorrow' => "I morgen",
		'f_past' => "Forfalden",
		'f_today' => "I dag og i morgen",
		'f_soon' => "Snart",
		'action_edit' => "Rediger",
		'action_note' => "Rediger note",
		'action_delete' => "Slet",
		'action_priority' => "Prioritet",
		'action_move' => "Flyt til",
		'notes' => "Noter:",
		'notes_show' => "Vis",
		'notes_hide' => "Gem",
		'list_new' => "Ny liste",
		'list_rename' => "Omdøb liste",
		'list_delete' => "Slet liste",
		'list_publish' => "Udgiv liste",
		'list_showcompleted' => "Vis fuldførte opgaver",
		'list_clearcompleted' => "Slet fuldførte opgaver",
		'alltags' => "Alle tags:",
		'alltags_show' => "Vis alle",
		'alltags_hide' => "Gem alle",
		'a_settings' => "Indstillinger",
		'rss_feed' => "RSS Feed",
		'feed_title' => "%s",
		'feed_description' => "Nye opgaver i %s",

		/* Settings */
		'set_header' => "Indstillinger",
		'set_title' => "Titel",
		'set_title_descr' => "(angiv hvis du ønsker at ændre standard titel)",
		'set_language' => "Sprog",
		'set_protection' => "Password beskyttelse",
		'set_enabled' => "Aktiveret",
		'set_disabled' => "Deaktiveret",
		'set_newpass' => "Nyt password",
		'set_newpass_descr' => "(lad være tomt hvis aktuelt password ikke skal ændres)",
		'set_smartsyntax' => "Smart syntaks",
		'set_smartsyntax_descr' => "(/ prioritet / opgave / tags /)",
		'set_autotz' => "Automatisk tidszone",
		'set_autotz_descr' => "(Fastsætter tidszonen for brugermiljøet med javascript)",
		'set_autotag' => "Automatisk tagging",
		'set_autotag_descr' => "(Tilføjer automatisk tags til nyoprettede opgaver)",
		'set_sessions' => "Sessions håndtering",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Filer",
		'set_firstdayofweek' => "Første ugedag",
		'set_duedate' => "Forfaldsdato format",
		'set_date' => "Dato format",
		'set_shortdate' => "Kort datoformat",
		'set_clock' => "Tidsformat",
		'set_12hour' => "12-timer",
		'set_24hour' => "24-timer",
		'set_submit' => "Gem ændringer",
		'set_cancel' => "Annuller",
		'set_showdate' => "Vis opgavedato i listen",
	);
}

?>