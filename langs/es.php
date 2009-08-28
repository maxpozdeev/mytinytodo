<?php

/**
 * Spanish translation for My Tiny Todolist v1.2.5
 * Author: Diego Jiménez http://www.zasbang.com
 */

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "Notas",
		'actionEdit' => "Modificar",
		'actionDelete' => "Borrar",
		'taskDate' => array("function(date) { return 'añadido el '+date; }"),
		'confirmDelete' => "¿Estás seguro?",
		'actionNoteSave' => "Guardar",
		'actionNoteCancel' => "Cancelar",
		'error' => "Ha ocurrido algún error (Click para más detalles)",
		'denied' => "Acceso denegado",
		'invalidpass' => "Contraseña Incorrecta",
		'readonly' => "solo lectura",
		'tagfilter' => "Tag:",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'tab_newtask' => "Nueva tarea",
		'tab_search' => "Buscar",
		'btn_add' => "Añadir",
		'btn_search' => "Buscar",
		'searching' => "Buscando:",
		'tasks' => "Tareas",
		'edit_task' => "Editar tarea",
		'priority' => "Prioridad",
		'task' => "Tarea",
		'note' => "Nota",
		'save' => "Guardar",
		'cancel' => "Cancelar",
		'password' => "Contraseña",
		'btn_login' => "Login",
		'a_login' => "Login",
		'a_logout' => "Salir",
		'tags' => "Tags",
		'tagfilter_cancel' => "Cancelar filtro",
		'sortByHand' => "Ordenar manualmente",
		'sortByPriority' => "Ordenar por prioridad",
		'sortByDueDate' => "Ordenar por fecha de vencimiento",
		'due' => "Termina",
		'daysago' => "%d días atrás",
		'indays' => "en %d días",
		'months_short' => array("Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"),
		'days_min' => array("Lun","Mar","Mie","Jue","Vie","Sab","Dom"),
		'date_md' => "%1\$s %2\$d",
		'date_ymd' => "%2\$s %3\$d, %1\$d",
		'today' => "hoy",
		'yesterday' => "ayer",
		'tomorrow' => "mañana",
		'f_past' => "Atrasadas",
		'f_today' => "Hoy y mañana",
		'f_soon' => "Pronto",
		'tasks_and_compl' => "Tareas + completadas",
	);
}

?>