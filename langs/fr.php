<?php

/*
	myTinyTodo language pack (French) v1.2.x
	Authors:
	v1.2 - Mickael Fradin (http://blog.kewix.fr)
	v1.2.7 - Didier Corbière
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "note",
		'actionEdit' => "modifier",
		'actionDelete' => "supprimer",
		'taskDate' => array("function(date) { return 'ajouté le '+date; }"),
		'confirmDelete' => "Êtes-vous sûr?",
		'actionNoteSave' => "enregistrer",
		'actionNoteCancel' => "annuler",
		'error' => "Une erreur s'est produite",
		'denied' => "Accès interdit",
		'invalidpass' => "Mot de passe incorrect",
		'readonly' => "lecture-seule",
		'tagfilter' => "Tag:",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Liste des tâches",
		'tab_newtask' => "Nouvelle tâche",
		'tab_search' => "Rechercher",
		'btn_add' => "Ajouter",
		'btn_search' => "Rechercher",
		'searching' => "Recherche de",
		'tasks' => "Tâches",
		'edit_task' => "Modifier la tâche",
		'priority' => "Priorité",
		'task' => "Tâche",
		'note' => "Note",
		'save' => "Enregistrer",
		'cancel' => "Annuler",
		'password' => "Mot de passe",
		'btn_login' => "Connexion",
		'a_login' => "Connexion",
		'a_logout' => "Déconnexion",
		'tags' => "Tags",
		'tagfilter_cancel' => "annuler le filtre",
		'sortByHand' => "Trier à la main",
		'sortByPriority' => "Trier par priorité",
		'sortByDueDate' => "Trier par date d'échéance",
		'due' => "Échéance",
		'daysago' => "il y a %d jours",
		'indays' => "dans %d jours",
		'months_short' => array("Jan","Fev","Mar","Avr","Mai","Juin","Juil","Août","Sept","Oct","Nov","Dec"),
		'months_long' => array("Janvier","Fevrier","Mars","Avril","Mai","Juin","Juillet","Août","Septembre","Octobre","Novembre","Decembre"),
		'days_min' => array("Di","Lu","Ma","Me","Je","Ve","Sa"),
		'date_md' => "%2\$d %1\$s",
		'date_ymd' => "%3\$d %2\$s %1\$d",
		'today' => "aujourd'hui",
		'yesterday' => "hier",
		'tomorrow' => "demain",
		'f_past' => "En retard",
		'f_today' => "Aujourd'hui et demain",
		'f_soon' => "Bientôt",
		'tasks_and_compl' => "Tâches + Terminées",
	);
}

?>