<?php

/*
	myTinyTodo language pack
	Language: Spanish
	Original name: Español
	Author: Sandro Jurado && Antonio Garcia Marin
	Author Url: http://www.alianzalima.com && antoniogarciamarin@gmail.com
	AppVersion: v1.3.4
	Date: 2010-05-09
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "¿Estás seguro de borrar la tarea?",
		'actionNoteSave' => "guardar",
		'actionNoteCancel' => "cancelar",
		'error' => "Ocurrió un error (click para ver detalles)",
		'denied' => "Acceso denegado",
		'invalidpass' => "Contraseña incorrecta",
		'tagfilter' => "Etiqueta:",
		'addList' => "Crear lista",
		'renameList' => "Renombrar lista",
		'deleteList' => "Esto eliminará la lista actual, así como las tareas que contenga. \\n¿Estás seguro?",
		'clearCompleted' => "Esto eliminará todas las tareas completadas en la lista.\\n?Estás seguro?",
		'settingsSaved' => "Configuración guardada. Recargando...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'htab_newtask' => "Tarea nueva",
		'htab_search' => "Buscar",
		'btn_add' => "Añadir",
		'btn_search' => "Buscar",
		'advanced_add' => "Avanzado",
		'searching' => "Buscando ",
		'tasks' => "Tareas",
		'taskdate_inline' => "añadido el %s",
		'taskdate_created' => "Fecha de creación",
		'taskdate_completed' => "Fecha de término",
		'edit_task' => "Editar tarea",
		'add_task' => "Añadir tarea",
		'priority' => "Prioridad",
		'task' => "Tarea",
		'note' => "Nota",
		'save' => "Guardar",
		'cancel' => "Cancelar",
		'password' => "Contraseña",
		'btn_login' => "Conectar",
		'a_login' => "Conectar",
		'a_logout' => "Desconectar",
		'public_tasks' => "Tareas Públicas",
		'tags' => "Etiquetas",
		'tagfilter_cancel' => "cancelar filtrado",
		'sortByHand' => "Ordenar a mano",
		'sortByPriority' => "Ordenar por prioridad",
		'sortByDueDate' => "Ordenar por fecha de vencimiento",
		'due' => "Vencimiento",
		'daysago' => "hace %d días",
		'indays' => "en %d días",
		'months_short' => array("Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Set","Oct","Nov","Dic"),
		'months_long' => array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Setiembre","Octubre","Noviembre","Diciembre"),
		'days_min' => array("Do","Lu","ma","Mi","Ju","Vi","Sa"),
		'days_long' => array("Domingo","Lunes","Martes","Miércoles","Jueves","Viernes","Sábado"),
		'today' => "hoy",
		'yesterday' => "ayer",
		'tomorrow' => "mañana",
		'f_past' => "Atrasado",
		'f_today' => "Hoy y mañana",
		'f_soon' => "pronto",
		'action_edit' => "Editar",
		'action_note' => "Editar Nota",
		'action_delete' => "Borrar",
		'action_priority' => "Prioridad",
		'action_move' => "Mover a",
		'notes' => "Notas:",
		'notes_show' => "Mostrar",
		'notes_hide' => "Ocultar",
		'list_new' => "Lista nueva",
		'list_rename' => "Renombrar lista",
		'list_delete' => "Borrar lista",
		'list_publish' => "Publicar lista",
		'list_showcompleted' => "Mostrar tareas completadas",
		'list_clearcompleted' => "Borrar tareas completadas",
		'alltags' => "Todas las etiquetas:",
		'alltags_show' => "Mostrar todas",
		'alltags_hide' => "Ocultar todas",
		'a_settings' => "Configuración",
		'rss_feed' => "Fuente RSS",
		'feed_title' => "%s",
		'feed_description' => "Tarea nueva en %s",

		/* Settings */
		'set_header' => "Configuración",
		'set_title' => "Título",
		'set_title_descr' => "(especifica si quieres cambiar el título por defecto)",
		'set_language' => "Idioma",
		'set_protection' => "Protección con contraseña",
		'set_enabled' => "Activado",
		'set_disabled' => "Desactivado",
		'set_newpass' => "Contraseña nueva",
		'set_newpass_descr' => "(deja en blanco si no has cambiado la contraseña actual)",
		'set_smartsyntax' => "Sintaxis inteligente",
		'set_smartsyntax_descr' => "(/prioridad/ tarea /etiquetas/)",
		'set_autotz' => "Zona horaria automática",
		'set_autotz_descr' => "(determina el huso horario del entorno del usuario mediante javascript)",
		'set_autotag' => "Auto etiquetado",
		'set_autotag_descr' => "(añade una etiqueta automáticamente desde el filtro de etiquetas actual, a la última tarea creada)",
		'set_sessions' => "Manejo de sesiones",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Archivos",
		'set_firstdayofweek' => "Primer día de la semana",
		'set_duedate' => "Formato de calendario para fechas de vencimiento",
		'set_date' => "Formato de fecha",
		'set_shortdate' => "Formato de Fecha corta",
		'set_clock' => "Formato de reloj",
		'set_12hour' => "12-horas",
		'set_24hour' => "24-horas",
		'set_submit' => "Enviar cambios",
		'set_cancel' => "Cancelar",
		'set_showdate' => "Mostrar fecha de la tarea en la lista",
	);
}

?>