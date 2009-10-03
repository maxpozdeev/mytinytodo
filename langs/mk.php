<?php

/*
	myTinyTodo language pack: Macedonian
	by nGen Solutions (http://www.ngen.mk)
	v1.2 compatible 
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "забелешка",
		'actionEdit' => "измени",
		'actionDelete' => "избриши",
		'taskDate' => array("function(date) { return 'added at '+date; }"),
		'confirmDelete' => "Дали си сигурен?",
		'actionNoteSave' => "зачувај",
		'actionNoteCancel' => "откажи",
		'error' => "Се појави грешка... (подетално)",
		'denied' => "Забранет пристап",
		'invalidpass' => "Неточна лозинка",
		'readonly' => "read-only",
		'tagfilter' => "Ознака:",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Моја Листа на Задачи",
		'tab_newtask' => "нова задача",
		'tab_search' => "пребарај",
		'btn_add' => "Додади",
		'btn_search' => "Барај",
		'searching' => "Пребарувам",
		'tasks' => "Задачи",
		'edit_task' => "Измени задача",
		'priority' => "Приоритет",
		'task' => "Задача",
		'note' => "Забелешка",
		'save' => "Сочувај",
		'cancel' => "Откажи",
		'password' => "Лозинка",
		'btn_login' => "Најави се",
		'a_login' => "Најава",
		'a_logout' => "Одјави се",
		'tags' => "Ознаки",
		'tagfilter_cancel' => "откажи филтер",
		'sortByHand' => "Подреди рачно",
		'sortByPriority' => "Подреди по приоритет",
		'sortByDueDate' => "Подреди по рок",
		'due' => "Рок",
		'daysago' => "пред %d денови",
		'indays' => "за %d денови",
		'months_short' => array("Јан","Фев","Мар","Апр","Мај","Јун","Јул","Авг","Сеп","Окт","Нов","Дек"),
		'months_long' => array("Јануари","Февруари","Март","Април","Мај","Јуни","Јули","Август","Септември","Октомври","Ноември","Декември"),
		'days_min' => array("Не","По","Вт","Ср","Че","Пе","Са"),
		'date_md' => "%1\$s %2\$d",
		'date_ymd' => "%2\$s %3\$d, %1\$d",
		'today' => "денес",
		'yesterday' => "вчера",
		'tomorrow' => "Утре",
		'f_past' => "Задоцнети",
		'f_today' => "Денес и Утре",
		'f_soon' => "Наскоро",
		'tasks_and_compl' => "Задачи + завршени",
	);
}

?>