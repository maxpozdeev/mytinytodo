<?php

/*
	myTinyTodo language pack
	Language: French
	Original name: Français
	Author: Olivier Gaillot
	Author Url: http://www.t1bis.com
	AppVersion: v1.3.4
	Date: 2010-03-19
	Modified by: Alexis Degrugillier, Pierre Lemay
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "êtes vous sûr ?",
		'actionNoteSave' => "Enregistrer",
		'actionNoteCancel' => "Annuler",
		'error' => "Erreurs détectées (cliquez pour consulter les détails)",
		'denied' => "Accès refusé",
		'invalidpass' => "Mauvais mot de passe",
		'tagfilter' => "Tag :",
		'addList' => "Créer une nouvelle liste",
		'renameList' => "Renommer la liste",
		'deleteList' => "Vous allez supprimer une liste et toutes les tâches incluses.\\nêtes vous sûr ?",
		'clearCompleted' => "Vous allez supprimer toutes les tâches complétées de la liste.\\nêtes vous sûr ?",
		'settingsSaved' => "Paramètres enregistrés. Chargement en cours...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Ma Todolist",
		'htab_newtask' => "Nouvelle tâche",
		'htab_search' => "Rechercher",
		'btn_add' => "Ajouter",
		'btn_search' => "Rechercher",
		'advanced_add' => "Avancé",
		'searching' => "Recherche de",
		'tasks' => "Tâches",
		'taskdate_inline' => "ajoutée à %s",
		'taskdate_created' => "Date de création",
		'taskdate_completed' => "Date de fin",
		'edit_task' => "Éditer tâche",
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
		'public_tasks' => "Tâches publiques",
		'tags' => "Tags",
		'tagfilter_cancel' => "Annuler le filtre",
		'sortByHand' => "Trier à la main",
		'sortByPriority' => "Trier par priorité",
		'sortByDueDate' => "Trier par date d'échéance",
		'due' => "Échéance",
		'daysago' => "il y a %d jours",
		'indays' => "dans %d jours",
		'months_short' => array("Jan","Fév","Mar","Avr","Mai","Juin","Juil","Aoû","Sep","Oct","Nov","Déc"),
		'months_long' => array("Janvier","Février","Mars","Avril","Mai","Juin","Juillet","Août","Septembre","Octobre","Novembre","Décembre"),
		'days_min' => array("Di","Lu","Ma","Me","Je","Ve","Sa"),
		'days_long' => array("Dimanche","Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi"),
		'today' => "aujourd'hui",
		'yesterday' => "hier",
		'tomorrow' => "demain",
		'f_past' => "En retard",
		'f_today' => "Aujourd'hui et demain",
		'f_soon' => "Bientôt",
		'action_edit' => "Éditer",
		'action_note' => "Éditer note",
		'action_delete' => "Supprimer",
		'action_priority' => "Priorité",
		'action_move' => "Déplacer vers",
		'notes' => "Notes :",
		'notes_show' => "Voir",
		'notes_hide' => "Masquer",
		'list_new' => "Nouvelle liste",
		'list_rename' => "Renommer liste",
		'list_delete' => "Supprimer liste",
		'list_publish' => "Publier liste",
		'list_showcompleted' => "Afficher les tâches terminées",
		'list_clearcompleted' => "Effacer les tâches complétées",
		'alltags' => "Tous les tags :",
		'alltags_show' => "Voir tous",
		'alltags_hide' => "Cacher tous",
		'a_settings' => "Paramètres",
		'rss_feed' => "Fil RSS",
		'feed_title' => "%s",
		'feed_description' => "Nouvelle tâche dans %s",

		/* Settings */
		'set_header' => "Paramètres",
		'set_title' => "Titre",
		'set_title_descr' => "(remplir si vous souhaitez modifier le titre par défaut)",
		'set_language' => "Langue",
		'set_protection' => "Protection par mot de passe",
		'set_enabled' => "Activer",
		'set_disabled' => "Désactiver",
		'set_newpass' => "Nouveau mot de passe",
		'set_newpass_descr' => "(laisser vide si vous ne souhaitez pas modifier votre mot de passe actuel)",
		'set_smartsyntax' => "Syntaxe smart",
		'set_smartsyntax_descr' => "(/priorité/ tâche /tags/)",
		'set_autotz' => "Fuseau horaire automatique",
		'set_autotz_descr' => "(détermine le fuseau horaire de l'utilisateur via javascript)",
		'set_autotag' => "Tag automatique",
		'set_autotag_descr' => "(ajoute automatiquement un tag du filtre de tags courant à la nouvelle tâche)",
		'set_sessions' => "Mécanisme de prise en charge des sessions",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Fichiers",
		'set_firstdayofweek' => "Premier jour de la semaine",
		'set_duedate' => "Format de la date d'échéance",
		'set_date' => "Format de la date",
		'set_shortdate' => "Format de date courte",
		'set_clock' => "Format de l'heure",
		'set_12hour' => "12 heures",
		'set_24hour' => "24 heures",
		'set_submit' => "Enregistrer les modifications",
		'set_cancel' => "Annuler",
		'set_showdate' => "Afficher la date des tâches",
	);
}

?>