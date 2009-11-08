<?php

/*
	myTinyTodo language pack
	Language: French
	Author: v1.2 - Mickael Fradin (http://blog.kewix.fr)
	Author: v1.2.7 - Didier Corbière
	Author: v1.3b3 - Philippe ALEXANDRE
	Version: v1.3b3
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
		'addList' => "Créer une nouvelle liste",
		'renameList' => "Renommer la liste",
		'deleteList' => "Cela effacera la liste avec toutes les taches la composant.\\nEtes vous sûr?",
		'settingsSaved' => "Options sauvegardées. Chargement...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Liste des tâches",
		'htab_newtask' => "Nouvelle tâche",
		'htab_search' => "Rechercher",
		'btn_add' => "Ajouter",
		'btn_search' => "Rechercher",
		'advanced_add' => "Avancée",
		'searching' => "Recherche de",
		'tasks' => "Tâches",
		'edit_task' => "Modifier la tâche",
		'add_task' => "Nouvelle tâche",
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
		'today' => "aujourd'hui",
		'yesterday' => "hier",
		'tomorrow' => "demain",
		'f_past' => "En retard",
		'f_today' => "Aujourd'hui et demain",
		'f_soon' => "Bientôt",
		'tasks_and_compl' => "Tâches + Terminées",
		'notes' => "Notes:",
		'notes_show' => "Afficher",
		'notes_hide' => "Cacher",
		'list_new' => "Nouvelle liste",
		'list_rename' => "Renommer",
		'list_delete' => "Effacer",
		'alltags' => "Tous les tags:",
		'alltags_show' => "Tous les afficher",
		'alltags_hide' => "Tous les cacher",
		'a_settings' => "Options",
		'rss_feed' => "Flux RSS",
	);
}

?>