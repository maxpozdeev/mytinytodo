<?php

/*
	myTinyTodo language pack (Polish)
	by Pawel Frankowski, blog.elimu.pl
	v1.2.7
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "dodaj opis",
		'actionEdit' => "zmień",
		'actionDelete' => "usuń",
		'taskDate' => array("function(date) { return 'added at '+date; }"),
		'confirmDelete' => "Jesteś pewien?",
		'actionNoteSave' => "zapisz",
		'actionNoteCancel' => "anuluj",
		'error' => "Some error occurred (kliknij aby zobaczyć szczegóły)",
		'denied' => "Dostęp zabroniony",
		'invalidpass' => "Złe hasło",
		'readonly' => "tylko do odczytu",
		'tagfilter' => "Tag:",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Moja mała lista Todo (do zrobienia)",
		'tab_newtask' => "nowe zadanie",
		'tab_search' => "szukaj",
		'btn_add' => "Dodaj",
		'btn_search' => "Szukaj",
		'searching' => "Szukając",
		'tasks' => "Zadania",
		'edit_task' => "Edytuj Zadanie",
		'priority' => "Piorytet",
		'task' => "Zadanie",
		'note' => "Szczegółowy opis",
		'save' => "Zapisz",
		'cancel' => "Anuluj",
		'password' => "Hasło",
		'btn_login' => "Login",
		'a_login' => "Login",
		'a_logout' => "Wyloguj",
		'tags' => "Tagi",
		'tagfilter_cancel' => "Anuluj filtr",
		'sortByHand' => "Sortuj ręcznie",
		'sortByPriority' => "Sortuj piorytetem",
		'sortByDueDate' => "Sortuj Terminem",
		'due' => "Termin",
		'daysago' => "%d dni temu",
		'indays' => "w ciągu %d dni",
		'months_short' => array("Sty","Lut","Mar","Kwi","Maj","Cze","Lip","Sie","Wrz","Paź","Lis","Gru"),
		'months_long' => array("Styczeń","Luty","Marzec","Kwiecień","Maj","Czerwiec","Lipiec","Sierpień","Wrzesień","Październik","Listopad","Grudzień"),
		'days_min' => array("Ni","Pon","Wt","Śr","Cz","Pi","So"),
		'date_md' => "%2\$d %1\$s",
		'date_ymd' => "%3\$d %2\$s %1\$d",
		'today' => "dziś",
		'yesterday' => "wczoraj",
		'tomorrow' => "do jutra",
		'f_past' => "Opóźnione",
		'f_today' => "Dziś i jutro",
		'f_soon' => "Wkrótce",
		'tasks_and_compl' => "Zadania + zakończone",
	);
}

?>