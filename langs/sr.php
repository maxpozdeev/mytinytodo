<?php

/*
	myTinyTodo language pack
	Language: Serbian
	Author: Goran Trajkovic
	AppVersion: v1.3.0
	Date: 2009-11-17
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
		'addList' => "Направи нову листу",
		'renameList' => "Преименуј листу",
		'deleteList' => "Ова акција ће обрисати текућу листу са свим припадајућим пословима.\\nДа ли сте сигурни?",
		'settingsSaved' => "Промене у подешавањима су сачуване. Поновно учитавање у току...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "РОКОВНИК",
		'htab_newtask' => "Нови задатак",
		'htab_search' => "Претрага",
		'btn_add' => "Упиши",
		'btn_search' => "Тражи",
		'advanced_add' => "Напредно додавање (*)",
		'searching' => "Претраживање у току...",
		'tasks' => "Текући",
		'edit_task' => "Измена задатка",
		'add_task' => "Нови задатак",
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
		'today' => "данас",
		'yesterday' => "јуче",
		'tomorrow' => "сутра",
		'f_past' => "Пробивени",
		'f_today' => "Данас и сутра",
		'f_soon' => "Ускоро",
		'tasks_and_compl' => "Сви",
		'notes' => "Напомене:",
		'notes_show' => "прикажи",
		'notes_hide' => "сакриј",
		'list_new' => "Нова листа",
		'list_rename' => "Преименуј",
		'list_delete' => "Обриши",
		'alltags' => "Све категорије (тагови):",
		'alltags_show' => "Прикажи све",
		'alltags_hide' => "Сакриј све",
		'a_settings' => "Подешавања",
		'rss_feed' => "RSS Feed",
		'feed_title' => "%s",
		'feed_description' => "Нови задаци у групи %s",
	);
}

?>