<?php

/*
	myTinyTodo language pack
	Language: Czech
	Original name: Čeština
	Author: Adam Heinrich
	Author Url: http://www.adamh.cz
	AppVersion: v1.3.4
	Date: 2010-04-09
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "Opravdu chcete smazat úkol?",
		'actionNoteSave' => "uložit",
		'actionNoteCancel' => "zrušit",
		'error' => "Objevil se problém (klikněte pro více informací)",
		'denied' => "Přístup odepřen",
		'invalidpass' => "Špatné heslo",
		'tagfilter' => "Tag:",
		'addList' => "Vytvořit nový seznam",
		'renameList' => "Přejmenovat seznam",
		'deleteList' => "Tímto smažete seznam a všechny úkoly v něm.\\nChcete pokračovat?",
		'clearCompleted' => "Tímto smažete všechny splněné úkoly.\\nChcete pokračovat?",
		'settingsSaved' => "Nastavení uloženo. Načítám...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'htab_newtask' => "Nový úkol",
		'htab_search' => "Hledat",
		'btn_add' => "Nový",
		'btn_search' => "Hledat",
		'advanced_add' => "Rozšířené",
		'searching' => "Vyhledávání",
		'tasks' => "Úkoly",
		'taskdate_inline' => "Přidáno v %s",
		'taskdate_created' => "Datum vytvoření",
		'taskdate_completed' => "Datum splnění",
		'edit_task' => "Upravit úkol",
		'add_task' => "Nový úkol",
		'priority' => "Priorita",
		'task' => "Úkol",
		'note' => "Poznámka",
		'save' => "Uložit",
		'cancel' => "Zrušit",
		'password' => "Heslo",
		'btn_login' => "Login",
		'a_login' => "Přihlásit",
		'a_logout' => "Odhlásit",
		'public_tasks' => "Veřejné úkoly",
		'tags' => "Tagy",
		'tagfilter_cancel' => "zrušit filtry",
		'sortByHand' => "Třídit ručně",
		'sortByPriority' => "Třídit podle priority",
		'sortByDueDate' => "Třídit podle termínu",
		'due' => "Termín",
		'daysago' => "před %d dny",
		'indays' => "ve %d dnech",
		'months_short' => array("Led","Úno","Bře","Dub","Kvě","Če6","Če7","Srp","Zář","Říj","Lis","Pro"),
		'months_long' => array("Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec"),
		'days_min' => array("Ne", "Po", "Út", "St", "Čt", "Pá", "So"),
		'days_long' => array("Neděle", "Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "Sobota"),
		'today' => "dnes",
		'yesterday' => "včera",
		'tomorrow' => "zítra",
		'f_past' => "Overdue",
		'f_today' => "Dnes a zítra",
		'f_soon' => "Brzy",
		'action_edit' => "Upravit",
		'action_note' => "Upravit poznámku",
		'action_delete' => "Smazat",
		'action_priority' => "Priorita",
		'action_move' => "Přesunout do",
		'notes' => "Poznámky:",
		'notes_show' => "Zobrazit",
		'notes_hide' => "Skrýt",
		'list_new' => "Nový seznam",
		'list_rename' => "Přejmenovat seznam",
		'list_delete' => "Smazat seznam",
		'list_publish' => "Zveřejnit seznam",
		'list_showcompleted' => "Zobrazit splněné úkoly",
		'list_clearcompleted' => "Smazat splněné úkoly",
		'alltags' => "Všechny tagy:",
		'alltags_show' => "Zobrazit vše",
		'alltags_hide' => "Skrýt vše",
		'a_settings' => "Nastavení",
		'rss_feed' => "RSS kanál",
		'feed_title' => "%s",
		'feed_description' => "Nové úkoly v %s",

		/* Settings */
		'set_header' => "Nastavení",
		'set_title' => "Titulek",
		'set_title_descr' => "(zadejte, pokud chcete změnit výchozí titulek)",
		'set_language' => "Jazyk",
		'set_protection' => "Zaheslováno",
		'set_enabled' => "Zapnuto",
		'set_disabled' => "Vypnuto",
		'set_newpass' => "Nové heslo",
		'set_newpass_descr' => "(nevyplňujte, pokud nechcete měnit stávající heslo)",
		'set_smartsyntax' => "\"Smart\" syntaxe",
		'set_smartsyntax_descr' => "(Zápis: \"/priorita/ test úkolu /tagy/\")",
		'set_autotz' => "Automatická časová zóna",
		'set_autotz_descr' => "(zjistí časovou zónu pomocí JavaScriptu)",
		'set_autotag' => "Automatické tagování",
		'set_autotag_descr' => "(automaticky přiřadí k tagům text z filtru)",
		'set_sessions' => "Správa sessions",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Soubory",
		'set_firstdayofweek' => "První den v týdnu",
		'set_duedate' => "Formát termínu splnění úkolu",
		'set_date' => "Formát data",
		'set_shortdate' => "Zkrácený formát data",
		'set_clock' => "Formát času",
		'set_12hour' => "12 hodinový",
		'set_24hour' => "24 hodinový",
		'set_submit' => "Uložit změny",
		'set_cancel' => "Zrušit",
		'set_showdate' => "Zobrazit v seznamu datum vytvoření úkolu",
	);
}

?>