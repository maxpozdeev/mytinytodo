<?php

/*
	myTinyTodo language pack
	Language: Polish
	Original name: Polski
	Author: Tomek Matoga 
	AppVersion: v1.3.4
	Date: 2010-03-17
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "Czy na pewno chcesz skasować zadanie?",
		'actionNoteSave' => "zapisz",
		'actionNoteCancel' => "cofnij",
		'error' => "Wystąpiły błędy (kliknij aby uzyskać detale)",
		'denied' => "Dostęp zabroniony",
		'invalidpass' => "Złe hasło",
		'tagfilter' => "Tag:",
		'addList' => "Dodaj nową listę",
		'renameList' => "Zmień nazwę listy",
		'deleteList' => "Usuwasz listę oraz wszyskie zawarte w niej zadania.\\nJesteś pewnien?",
		'clearCompleted' => "Usuwasz wszystkie zadania na tej liście.\\nJesteś pewien?",
		'settingsSaved' => "Ustawienia zapisane, wczytuję ponownie...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Moja mała lista TODO",
		'htab_newtask' => "Nowe zadanie",
		'htab_search' => "Szukaj",
		'btn_add' => "Dodaj",
		'btn_search' => "Szukaj",
		'advanced_add' => "Zaawansowane",
		'searching' => "Szukaj",
		'tasks' => "Zadania",
		'taskdate_inline' => "Dodane %s",
		'taskdate_created' => "Data utworzenia",
		'taskdate_completed' => "Data ukończenia",
		'edit_task' => "Edytuj zadanie",
		'add_task' => "Nowe zadanie",
		'priority' => "Priorytet",
		'task' => "Zadanie",
		'note' => "Notatka",
		'save' => "Zapisz",
		'cancel' => "Cofnij",
		'password' => "Hasło",
		'btn_login' => "Login",
		'a_login' => "Login",
		'a_logout' => "Logout",
		'public_tasks' => "Zadania publiczne",
		'tags' => "Tagi",
		'tagfilter_cancel' => "usuń filtr",
		'sortByHand' => "Sortuj ręcznie",
		'sortByPriority' => "Sortuj wg priorytetu",
		'sortByDueDate' => "Sortuj wg daty",
		'due' => "do",
		'daysago' => "%d dni temu",
		'indays' => "w ciągu %d dni",
		'months_short' => array("Sty","Lut","Mar","Kwi","Maj","Cze","Lip","Sie","Wrz","Paź","Lis","Gru"),
		'months_long' => array("Styczeń","Luty","Marzec","Kwiecień","Maj","Czerwiec","Lipiec","Sierpien","Wrzesien","Październik","Listopad","Grudzień"),
		'days_min' => array("Ni","Po","Wt","Śr","Cz","Pt","So"),
		'days_long' => array("Niedziela","Poniedziałek","Wtorek","Środa","Czwartek","Piątek","Sobota"),
		'today' => "dziś",
		'yesterday' => "wczoraj",
		'tomorrow' => "jutro",
		'f_past' => "opóźnione",
		'f_today' => "Dziś i jutro",
		'f_soon' => "Wkrótce",
		'action_edit' => "Edytuj",
		'action_note' => "Edytuj notatkę",
		'action_delete' => "Usuń",
		'action_priority' => "Priorytet",
		'action_move' => "Przenieś do",
		'notes' => "Notatki:",
		'notes_show' => "Pokaż",
		'notes_hide' => "Ukryj",
		'list_new' => "Nowa lista",
		'list_rename' => "Zmień nazwę listy",
		'list_delete' => "Usuń listę",
		'list_publish' => "Opublikuj listę",
		'list_showcompleted' => "Pokaż ukończone zadania",
		'list_clearcompleted' => "Usuń ukończone zadania",
		'alltags' => "Wszystkie tagi:",
		'alltags_show' => "Pokaż wszystkie",
		'alltags_hide' => "Ukryj wszystkie",
		'a_settings' => "Ustawienia",
		'rss_feed' => "Subskrybcja RSS",
		'feed_title' => "%s",
		'feed_description' => "Nowe zadanie %s",

		/* Settings */
		'set_header' => "Ustawienia",
		'set_title' => "Tytuł",
		'set_title_descr' => "(wpisz swoją nazwę)",
		'set_language' => "Język",
		'set_protection' => "Ochrona hasłem",
		'set_enabled' => "Włączona",
		'set_disabled' => "Wyłączona",
		'set_newpass' => "Nowe hasło",
		'set_newpass_descr' => "(zostaw puste jeśli nie chcesz zmieniać hasła)",
		'set_smartsyntax' => "Smart syntax",
		'set_smartsyntax_descr' => "(/priority/ task /tags/)",
		'set_autotz' => "Automatyczna strefa czasowa",
		'set_autotz_descr' => "(Ustawienia strefy czasowej użytkownia - javascript)",
		'set_autotag' => "Automatyczne tagowanie",
		'set_autotag_descr' => "(automatycznie dodaje tag of z filtra do nowo utworzonego zadania)",
		'set_sessions' => "Mechanizm utrzymania sesji",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Pliki",
		'set_firstdayofweek' => "Pierwszy dzień tygodnia",
		'set_duedate' => "Format daty",
		'set_date' => "Format daty",
		'set_shortdate' => "Skrócony format daty",
		'set_clock' => "Format czasu",
		'set_12hour' => "12-godzinny",
		'set_24hour' => "24-godzinny",
		'set_submit' => "Zapisz zamiany",
		'set_cancel' => "Cofnij",
		'set_showdate' => "Pokazuj datę zadania na liście",
	);
}

?>