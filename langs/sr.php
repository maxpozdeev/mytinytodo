<?php

/*
	myTinyTodo language pack (Serbian) v1.2.x
	Author: Goran Trajkovic
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "промени текст задатка",
		'actionEdit' => "промени цео задатак",
		'actionDelete' => "обриши задатак",
		'taskDate' => array("function(date) { return 'уписан '+date; }"),
		'confirmDelete' => "Да ли сте сигурни?",
		'actionNoteSave' => "сними",
		'actionNoteCancel' => "одустани",
		'error' => "Грешке у раду програма (кликните да бисте видели детаље)",
		'denied' => "Немогућ приступ апликацији",
		'invalidpass' => "Неисправна лозинка?",
		'readonly' => "само-за-читање",
		'tagfilter' => "Категорија: ",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "РОКОВНИК",
		'tab_newtask' => "нови задатак",
		'tab_search' => "претрага",
		'btn_add' => "Упиши",
		'btn_search' => "Тражи",
		'searching' => "Претраживање у току...",
		'tasks' => "Текући",
		'edit_task' => "Измена задатка",
		'priority' => "Приоритет",
		'task' => "Наслов",
		'note' => "Опис",
		'save' => "Сними",
		'cancel' => "Одустани",
		'password' => "Лозинка",
		'btn_login' => "Пријави се",
		'a_login' => "Пријављивање",
		'a_logout' => "Одјави се",
		'tags' => "Категорија",
		'tagfilter_cancel' => "искључивање филтра",
		'sortByHand' => "Ручно уређивање задатака",
		'sortByPriority' => "Уређивање по приоритету",
		'sortByDueDate' => "Уређивање по датуму обављања",
		'due' => "Датум обављања",
		'daysago' => "пре %d дана",
		'indays' => "за %d дана",
		'months_short' => array("Jан","Феб","Maр","Aпр","Maј","Jун","Jул","Aвг","Сеп","Oкт","Нов","Дец"),
		'months_long' => array("Јануар","Фебруар","Март","Април","Maј","Jун","Jул","Aвгуст","Септембар","Oктобар","Новембар","Децембар"),
		'days_min' => array("Не","По","Ут","Ср","Че","Пе","Су"),
		'date_md' => "%1\$s %2\$d",
		'date_ymd' => "%2\$s %3\$d, %1\$d",
		'today' => "данас",
		'yesterday' => "јуче",
		'tomorrow' => "сутра",
		'f_past' => "Пробивени",
		'f_today' => "Данас и сутра",
		'f_soon' => "Ускоро",
		'tasks_and_compl' => "Сви",
	);
}

?>