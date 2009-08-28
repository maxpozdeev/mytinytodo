<?php

/**
 * Italian translation for My Tiny Todolist v1.2.5
 * Author: Devis Lucato http://www.lucato.it
 */

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "nota",
		'actionEdit' => "modifica",
		'actionDelete' => "elimina",
		'taskDate' => array("function(date) { return 'aggiunto il '+date; }"),
		'confirmDelete' => "Sicuro?",
		'actionNoteSave' => "salva",
		'actionNoteCancel' => "annulla",
		'error' => "Si è verificato un errore (clicca per maggiori dettagli)",
		'denied' => "Accesso negato",
		'invalidpass' => "Password errata",
		'readonly' => "sola lettura",
		'tagfilter' => "Tag:",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "I miei compiti",
		'tab_newtask' => "nuovo compito",
		'tab_search' => "cerca",
		'btn_add' => "Aggiungi",
		'btn_search' => "Cerca",
		'searching' => "Cercando",
		'tasks' => "Compiti",
		'edit_task' => "Modifica Compito",
		'priority' => "Priorità",
		'task' => "Compito",
		'note' => "Nota",
		'save' => "Salva",
		'cancel' => "Annula",
		'password' => "Password",
		'btn_login' => "Login",
		'a_login' => "Login",
		'a_logout' => "Esci",
		'tags' => "Tags",
		'tagfilter_cancel' => "annulla filtro",
		'sortByHand' => "Ordine manuale",
		'sortByPriority' => "Ordina per priorità",
		'sortByDueDate' => "Ordina per scadenza",
		'due' => "Scadenza",
		'daysago' => "%d giorni fa",
		'indays' => "in %d giorni",
		'months_short' => array("Gen","Feb","Mar","Apr","Mag","Giu","Lug","Ago","Set","Ott","Nov","Dic"),
		'days_min' => array("Do","Lu","Ma","Me","Gi","Ve","Sa"),
		'date_md' => "%1\$s %2\$d",
		'date_ymd' => "%2\$s %3\$d, %1\$d",
		'today' => "oggi",
		'yesterday' => "ieri",
		'tomorrow' => "domani",
		'f_past' => "In ritardo",
		'f_today' => "Oggi e domani",
		'f_soon' => "Presto",
		'tasks_and_compl' => "Compiti + completati",
	);
}

?>