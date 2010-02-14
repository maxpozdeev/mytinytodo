<?php

/*
	myTinyTodo language pack
	Language: French
	Original name: Français
	Author: Olivier Gaillot
	Author Url: http://www.t1bis.com
	AppVersion: v1.3.3
	Date: 2010-02-12
	Modified by: Alexis Degrugillier	
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "\u00EAtes vous s\u00FBr ?",
		'actionNoteSave' => "Enregistrer",
		'actionNoteCancel' => "Annuler",
		'error' => "Erreurs d\u00E9tect\u00E9es (cliquez pour consulter les d\u00E9tails)",
		'denied' => "Acc\u00E8s refus\u00E9",
		'invalidpass' => "Mauvais mot de passe",
		'tagfilter' => "Tag :",
		'addList' => "Cr\u00E9er une nouvelle liste",
		'renameList' => "Renommer la liste",
		'deleteList' => "Vous allez supprimer une liste et toutes les t\u00E2ches incluses.\\n\u00EAtes vous s\u00FBr ?",
		'settingsSaved' => "Param\u00E8tres enregistr\u00E9s. Chargement en cours...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Ma Todolist",
		'htab_newtask' => "Nouvelle t&acirc;che",
		'htab_search' => "Rechercher",
		'btn_add' => "Ajouter",
		'btn_search' => "Rechercher",
		'advanced_add' => "Avanc&eacute;",
		'searching' => "Recherche de",
		'tasks' => "T&acirc;ches",
		'taskdate_inline' => "ajout&eacute;e &agrave; %s",
		'taskdate_created' => "Date de cr&eacute;ation",
		'taskdate_completed' => "Date de fin",
		'edit_task' => "&Eacute;diter t&acirc;che",
		'add_task' => "Nouvelle t&acirc;che",
		'priority' => "Priorit&eacute;",
		'task' => "T&acirc;che",
		'note' => "Note",
		'save' => "Enregistrer",
		'cancel' => "Annuler",
		'password' => "Mot de passe",
		'btn_login' => "Connexion",
		'a_login' => "Connexion",
		'a_logout' => "D&eacute;connexion",
		'public_tasks' => "T&acirc;ches publiques",
		'tags' => "Tags",
		'tagfilter_cancel' => "Annuler le filtre",
		'sortByHand' => "Trier &agrave; la main",
		'sortByPriority' => "Trier par priorit&eacute;",
		'sortByDueDate' => "Trier par date d'&eacute;ch&eacute;ance",
		'due' => "&Eacute;ch&eacute;ance",
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
		'f_soon' => "Bient&ocirc;t",
		'action_edit' => "&Eacute;diter",
		'action_note' => "&Eacute;diter note",
		'action_delete' => "Supprimer",
		'action_priority' => "Priorit&eacute;",
		'action_move' => "D&eacute;placer vers",
		'notes' => "Notes :",
		'notes_show' => "Voir",
		'notes_hide' => "Masquer",
		'list_new' => "Nouvelle liste",
		'list_rename' => "Renommer liste",
		'list_delete' => "Supprimer liste",
		'list_publish' => "Publier liste",
		'list_showcompleted' => "Afficher les t&acirc;ches termin&eacute;es",
		'alltags' => "Tous les tags :",
		'alltags_show' => "Voir tous",
		'alltags_hide' => "Cacher tous",
		'a_settings' => "Param&egrave;tres",
		'rss_feed' => "Fil RSS",
		'feed_title' => "%s",
		'feed_description' => "Nouvelle t&acirc;che dans %s",

		/* Settings */
		'set_header' => "Param&egrave;tres",
		'set_title' => "Titre",
		'set_title_descr' => "(remplir si vous souhaitez modifier le titre par d&eacute;faut)",
		'set_language' => "Langue",
		'set_protection' => "Protection par mot de passe",
		'set_enabled' => "Activer",
		'set_disabled' => "D&eacute;sactiver",
		'set_newpass' => "Nouveau mot de passe",
		'set_newpass_descr' => "(laisser vide si vous ne souhaitez pas modifier votre mot de passe actuel)",
		'set_smartsyntax' => "Syntaxe smart",
		'set_smartsyntax_descr' => "(/priorit&eacute;/ t&acirc;che /tags/)",
		'set_autotz' => "Fuseau horaire automatique",
		'set_autotz_descr' => "(d&eacute;termine le fuseau horaire de l'utilisateur via javascript)",
		'set_autotag' => "Tag automatique",
		'set_autotag_descr' => "(ajoute automatiquement un tag du filtre de tags courant &agrave; la nouvelle t&acirc;che)",
		'set_sessions' => "M&eacute;canisme de prise en charge des sessions",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Fichiers",
		'set_firstdayofweek' => "Premier jour de la semaine",
		'set_duedate' => "Format de la date d'&eacute;ch&eacute;ance",
		'set_date' => "Format de la date",
		'set_shortdate' => "Format de date courte",
		'set_clock' => "Format de l'heure",
		'set_12hour' => "12 heures",
		'set_24hour' => "24 heures",
		'set_submit' => "Enregistrer les modifications",
		'set_cancel' => "Annuler",
	);
}

?>