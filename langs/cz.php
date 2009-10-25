<?php

/*
	myTinyTodo language pack (Czech)
	by Michal Brůha www.prdka.cz
	v1.2.7
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "poznámka",
		'actionEdit' => "upravit",
		'actionDelete' => "smazat",
		'taskDate' => array("function(date) { return 'added at '+date; }"),
		'confirmDelete' => "Jste si jist?",
		'actionNoteSave' => "uložit",
		'actionNoteCancel' => "zrušit",
		'error' => "Vyskytla se chyba (klikněte pro detaily)",
		'denied' => "Přístup zamítnut",
		'invalidpass' => "Chybné heslo",
		'readonly' => "pouze pro čtení",
		'tagfilter' => "Klíčové slovo:",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'tab_newtask' => "nový úkol",
		'tab_search' => "hledat",
		'btn_add' => "Přidat",
		'btn_search' => "Hledat",
		'searching' => "Hledání",
		'tasks' => "Aktuální úkoly",
		'edit_task' => "Upravit Úkol",
		'priority' => "Důležitost",
		'task' => "Úkol",
		'note' => "Poznámka",
		'save' => "Uložit",
		'cancel' => "Zrušit",
		'password' => "Heslo",
		'btn_login' => "Přihlásit",
		'a_login' => "Přihlásit",
		'a_logout' => "Odhlásit",
		'tags' => "Klíčová slova",
		'tagfilter_cancel' => "zrušit filtr",
		'sortByHand' => "Seřadit manuálně",
		'sortByPriority' => "Seřadit podle důležitosti",
		'sortByDueDate' => "Seřadit podle datumu dokončení",
		'due' => "Dokončení",
		'daysago' => " před %d dny",
		'indays' => "během %d dnů",
		'months_short' => array("Led","Úno","Bře","Dub","Kvě","Črn","Črc","Srp","Zář","Říj","Lis","Pro"),
		'months_long' => array("Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec"),
		'days_min' => array("Ne","Po","Út","St","Čt","Pá","So"),
		'date_md' => "%2\$d %1\$s",
		'date_ymd' => "%3\$d %2\$s %1\$d",
		'today' => "dnes",
		'yesterday' => "včera",
		'tomorrow' => "zítra",
		'f_past' => "Zpožděné",
		'f_today' => "Dnes a zítra",
		'f_soon' => "Brzy",
		'tasks_and_compl' => "Aktuální úkoly + dokončené",
	);
}

?>