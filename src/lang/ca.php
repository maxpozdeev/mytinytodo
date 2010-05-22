<?php

/*
	myTinyTodo language pack
	Language: Catalan
	Original name: Català
	Author: Ariel vb
	Author Url: http://www.arielvb.com
	AppVersion: v1.3.4
	Date: 2010-05-04
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "Estàs segur d'esborrar la tasca?",
		'actionNoteSave' => "desar",
		'actionNoteCancel' => "cancel·lar",
		'error' => "Hi ha hagut un error (clica per més detalls)",
		'denied' => "Accés denegat",
		'invalidpass' => "Contrasenya incorrecta",
		'tagfilter' => "Etiqueta:",
		'addList' => "Crear nova llista",
		'renameList' => "Reanomenar llista",
		'deleteList' => "Això esborrarà la llista actual i totes les seves tasques.\\nEstàs segur?",
		'clearCompleted' => "Això esborrarà totes les tasques realitzades de la llista.\\nEstàs segur?",
		'settingsSaved' => "Configuració desada. Recarregant...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'htab_newtask' => "Nova tasca",
		'htab_search' => "Cercar",
		'btn_add' => "Afegir",
		'btn_search' => "Cercar",
		'advanced_add' => "Avançat",
		'searching' => "Cercant",
		'tasks' => "Tasques",
		'taskdate_inline' => "afegit at %s",
		'taskdate_created' => "Data de creació",
		'taskdate_completed' => "Data de realització",
		'edit_task' => "Editar Tasca",
		'add_task' => "Nova Tasca",
		'priority' => "Prioritat",
		'task' => "Tasca",
		'note' => "Nota",
		'save' => "Desar",
		'cancel' => "Cancel·lar",
		'password' => "Contrasenya",
		'btn_login' => "Iniciar sessió",
		'a_login' => "Iniciar sessió",
		'a_logout' => "Tancar sessió",
		'public_tasks' => "Tasques públiques",
		'tags' => "Etiquetes",
		'tagfilter_cancel' => "cancel·lar filtre",
		'sortByHand' => "Ordenar a mà",
		'sortByPriority' => "Ordenar per prioritat",
		'sortByDueDate' => "Ordenar per data de venciment",
		'due' => "Venciment",
		'daysago' => "fa %d dies",
		'indays' => "en %d dies",
		'months_short' => array("Gen","Feb","Mar","Abr","Mai","Jun","Jul","Ago","Set","Oct","Nov","Dec"),
		'months_long' => array("Gener","Febrer","Març","Abril","Maig","Juny","Juliol","Agost","Setembre","Octubre","Novembre","Decembre"),
		'days_min' => array("Dmg.","Dl.","Dm.","Dmc.","Dj.","Dv.","Ds."),
		'days_long' => array("Diumenge","Dilluns","Dimarts","Dimecres","Dijous","Divendres","Dissabte"),
		'today' => "avui",
		'yesterday' => "ahir",
		'tomorrow' => "demà",
		'f_past' => "Endarrerides",
		'f_today' => "Avui i demà",
		'f_soon' => "Aviat",
		'action_edit' => "Editar",
		'action_note' => "Editar Nota",
		'action_delete' => "Esborrar",
		'action_priority' => "Prioritat",
		'action_move' => "Mou a",
		'notes' => "Notes:",
		'notes_show' => "Mostrar",
		'notes_hide' => "Ocultar",
		'list_new' => "Nova llista",
		'list_rename' => "Reanomenar llista",
		'list_delete' => "Esborrar llista",
		'list_publish' => "Publicar llista",
		'list_showcompleted' => "Mostrar tasques realitzades",
		'list_clearcompleted' => "Esborrar tasques titolarealitzades",
		'alltags' => "Totes les etiquetes:",
		'alltags_show' => "Mostrar totes",
		'alltags_hide' => "Ocultar totes",
		'a_settings' => "Configuració",
		'rss_feed' => "RSS Feed",
		'feed_title' => "%s",
		'feed_description' => "Noves tasques %s",
		'send_mail' => "Enviar per correu",

		/* Settings */
		'set_header' => "Configuració",
		'set_title' => "Títol",
		'set_title_descr' => "(omplir si es vol canviar el títol per defecte)",
		'set_language' => "Idioma",
		'set_protection' => "Protecció per contrasenya",
		'set_enabled' => "Activar",
		'set_disabled' => "Desactivar",
		'set_newpass' => "Nova contrasenya",
		'set_newpass_descr' => "(deixar en blanc si no es vol canviar la contrasenya actual)",
		'set_smartsyntax' => "Sintaxi intel·ligent",
		'set_smartsyntax_descr' => "(/prioritat/ tasca /etiquetes/)",
		'set_autotz' => "Franja horària automàtica",
		'set_autotz_descr' => "(determina la franja horària de l'usuari fent servir javascript)",
		'set_autotag' => "Auto etiquetar",
		'set_autotag_descr' => "(afegir automàticament etiquetes del filtre actual a les noves tasques)",
		'set_sessions' => "Mecanisme de gestió de sessions",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Fitxers",
		'set_firstdayofweek' => "Primer dia de la setmana",
		'set_duedate' => "Format de dates realitzades",
		'set_date' => "Format de data",
		'set_shortdate' => "Format curt de data",
		'set_clock' => "Format de l'hora",
		'set_12hour' => "12 hores",
		'set_24hour' => "24 hores",
		'set_submit' => "Desar canvis",
		'set_cancel' => "Cancel·lar",
		'set_showdate' => "Mostrar data de la tasca a la llista",
	);
}

?>