<?php

/*
	myTinyTodo language pack
	Language: Slovak
	Original name: Slovenčina
	Author: Ľubomír Molent
	Author Url: 
	AppVersion: v1.3.6
	Date: 2010-12-16
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "Naozaj chcete vymazať úlohu?",
		'actionNoteSave' => "uložit",
		'actionNoteCancel' => "zrušit",
		'error' => "Vyskytol sa problém (kliknite pre viac informácií)",
		'denied' => "Prístup zamietnutý",
		'invalidpass' => "Nesprávne heslo",
		'tagfilter' => "Tag:",
		'addList' => "Vytvoriť nový zoznam",
		'renameList' => "Premenovat zoznam",
		'deleteList' => "Týmto vymažete zoznam a všetky úlohy v ňom.\\nChcete pokračovat?",
		'clearCompleted' => "Týmto vymažete všetky splnené úlohy.\\nChcete pokračovat?",
		'settingsSaved' => "Nastavenie uložené. Načítavam...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'htab_newtask' => "Nová úloha",
		'htab_search' => "Hľadať",
		'btn_add' => "Pridať",
		'btn_search' => "Hľadať",
		'advanced_add' => "Rozšírené",
		'searching' => "Vyhľadávanie",
		'tasks' => "Úlohy",
		'taskdate_inline' => "Pridané %s",
		'taskdate_created' => "Dátum vytvorenia",
		'taskdate_completed' => "Dátum splnenia",
		'edit_task' => "Upraviť úlohu",
		'add_task' => "Nová úloha",
		'priority' => "Priorita",
		'task' => "Úloha",
		'note' => "Poznámka",
		'save' => "Uložit",
		'cancel' => "Zrušit",
		'password' => "Heslo",
		'btn_login' => "Login",
		'a_login' => "Prihlásiť",
		'a_logout' => "Odhlásiť",
		'public_tasks' => "Verejné úlohy",
		'tags' => "Tagy",
		'tagfilter_cancel' => "zrušit filtre",
		'sortByHand' => "Triediť ručne",
		'sortByPriority' => "Triediť podľa priority",
		'sortByDueDate' => "Triediť podľa termínu",
		'due' => "Termín",
		'daysago' => "pred %d dňami",
		'indays' => "o %d dní",
		'months_short' => array("Jan","Feb","Mar","Apr","Maj","Jun","Jul","Aug","Sep","Okt","Nov","Dec"),
		'months_long' => array("Január","Február","Marec","Apríl","Máj","Jún","Júl","August","September","Október","November","December"),
		'days_min' => array("Ne", "Po", "Ut", "St", "Št", "Pi", "So"),
		'days_long' => array("Nedľa", "Pondelok", "Utorok", "Streda", "Štvrtok", "Piatok", "Sobota"),
		'today' => "dnes",
		'yesterday' => "včera",
		'tomorrow' => "zajtra",
		'f_past' => "Overdue",
		'f_today' => "Dnes a zajtra",
		'f_soon' => "Čoskoro",
		'action_edit' => "Upraviť",
		'action_note' => "Upraviť poznámku",
		'action_delete' => "Zmazať",
		'action_priority' => "Priorita",
		'action_move' => "Presunúť do",
		'notes' => "Poznámky:",
		'notes_show' => "Zobraziť",
		'notes_hide' => "Skryť",
		'list_new' => "Nový zoznam",
		'list_rename' => "Premenovať zoznam",
		'list_delete' => "Zmazať zoznam",
		'list_publish' => "Zverejniť zoznam",
		'list_showcompleted' => "Zobraziť splnené úlohy",
		'list_clearcompleted' => "Zmazať splnené úlohy",
		'alltags' => "Všetky tagy:",
		'alltags_show' => "Zobraziť všetko",
		'alltags_hide' => "Skryť všetko",
		'a_settings' => "Nastavenie",
		'rss_feed' => "RSS kanál",
		'feed_title' => "%s",
		'feed_description' => "Nové úlohy v %s",

		/* Settings */
		'set_header' => "Nastavenie",
		'set_title' => "Titulok",
		'set_title_descr' => "(zadajte, pokiaľ chcete zmeniť východzí titulok)",
		'set_language' => "Jazyk",
		'set_protection' => "Zaheslovanie",
		'set_enabled' => "Zapnuté",
		'set_disabled' => "Vypnuté",
		'set_newpass' => "Nové heslo",
		'set_newpass_descr' => "(nevypĺňajte, pokiaľ nechcete meniť nastavené heslo)",
		'set_smartsyntax' => "\"Smart\" syntax",
		'set_smartsyntax_descr' => "(Zápis: \"/priorita/ test úlohy /tagy/\")",
		'set_autotz' => "Automatická časová zóna",
		'set_autotz_descr' => "(zistí časovú zónu pomocou JavaScriptu)",
		'set_autotag' => "Automatické tagovanie",
		'set_autotag_descr' => "(automaticky priradí k tagom text z filtra)",
		'set_sessions' => "Správa sessions",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Súbory",
		'set_firstdayofweek' => "Prvný deň v týždni",
		'set_duedate' => "Formát termínu splnenej úlohu",
		'set_date' => "Formát dátumu",
		'set_shortdate' => "Zkrátený formát dátumu",
		'set_clock' => "Formát času",
		'set_12hour' => "12 hodinový",
		'set_24hour' => "24 hodinový",
		'set_submit' => "Uložiť zmeny",
		'set_cancel' => "Zrušit",
		'set_showdate' => "Zobrazit v zozname dátum vytvorenia úlohy",
	);
}

?>