<?php

/*
	myTinyTodo language pack: Swedish
	by Anders Ytterström
	v1.2 compatible 
*/


class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "anteckning",
		'actionEdit' => "ändra",
		'actionDelete' => "ta bort",
		'taskDate' => array("function(date) { return 'inlagd '+date; }"),
		'confirmDelete' => "Är du säker?",
		'actionNoteSave' => "spara",
		'actionNoteCancel' => "ångra",
		'error' => "Ett fel inträffade (klicka för detaljer)",
		'denied' => "Åtkomst nekad",
		'invalidpass' => "Fel lösenord",
		'readonly' => "skrivskyddad",
		'tagfilter' => "Etikett:",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Min enkla Att-Göra",
		'tab_newtask' => "Ny uppgift",
		'tab_search' => "Sök",
		'btn_add' => "Lägg till",
		'btn_search' => "Sök",
		'searching' => "Söker efter",
		'tasks' => "Uppgifter",
		'edit_task' => "redigera Uppgift",
		'priority' => "Prio",
		'task' => "Uppgift",
		'note' => "anteckning",
		'save' => "Spara",
		'cancel' => "Ångra",
		'password' => "Lösenord",
		'btn_login' => "Logga in",
		'a_login' => "Logga in",
		'a_logout' => "Logga ut",
		'tags' => "Etiketter",
		'tagfilter_cancel' => "ångra filter",
		'sortByHand' => "Sortera för hand",
		'sortByPriority' => "Sortera efter prio",
		'sortByDueDate' => "Sortera efter förfallodag",
		'due' => "tills",
		'daysago' => "för %d dagar sedan",
		'indays' => "om %d dagar",
		'months_short' => array("jan","feb","mar","apr","maj","jun","jul","aug","sep","okt","nov","dec"),
		'months_long' => array("januari","februari","mars","april","maj","juni","juli","augusti","september","oktober","november","december"),
		'days_min' => array("Sö","Må","Ti","On","To","Fr","Lö"),
		'date_md' => "%1\$s %2\$d",
		'date_ymd' => "%2\$s %3\$d, %1\$d",
		'today' => "idag",
		'yesterday' => "igår",
		'tomorrow' => "imorgon",
		'f_past' => "Försenad",
		'f_today' => "Idag och imorgon",
		'f_soon' => "Snart",
		'tasks_and_compl' => "Uppgifter + slutförda",
	);
}

?>