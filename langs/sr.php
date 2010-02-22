<?php

/*
	myTinyTodo language pack
	Language: Serbian
	Original name: Српски
	Author: Goran Trajkovic
	Author Url: http://www.crelativ.com
	AppVersion: v1.3.4
	Date: 2010-02-21
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "Да ли сте сигурни?",
		'actionNoteSave' => "сними",
		'actionNoteCancel' => "одустани",
		'error' => "Грешке у раду програма (кликните да бисте видели детаље)",
		'denied' => "Немогућ приступ апликацији",
		'invalidpass' => "Неисправна лозинка",
		'tagfilter' => "Категорија: ",
		'addList' => "Направи нову листу",
		'renameList' => "Унесите нови назив листе",
		'deleteList' => "Брисање текуће листе са свим припадајућим задацима\\nДа ли сте сигурни?",
		'clearCompleted' => "Брисање свих завршених задатака у листи\\nДа ли сте сигурни?",
		'settingsSaved' => "Промене у подешавањима су сачуване. Поновно учитавање...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "РОКОВНИК",
		'htab_newtask' => "Нови задатак",
		'htab_search' => "Претрага",
		'btn_add' => "Упиши",
		'btn_search' => "Тражи",
		'advanced_add' => "Датаљан упис задатка",
		'searching' => "Претраживање у току...",
		'tasks' => "Текући",
		'taskdate_inline' => "додат у (added at) %s",
		'taskdate_created' => "Дан и час креирања",
		'taskdate_completed' => "Дан и час завршетка",
		'edit_task' => "Измена задатка",
		'add_task' => "Нови задатак",
		'priority' => "Приоритет",
		'task' => "Наслов",
		'note' => "Опис",
		'save' => "   Упиши   ",
		'cancel' => "Одустани",
		'password' => "Лозинка",
		'btn_login' => "Пријави се",
		'a_login' => "Пријављивање",
		'a_logout' => "Одјави се",
		'public_tasks' => "Јавни задаци",
		'tags' => "Филтер по категоријама",
		'tagfilter_cancel' => "искључи филтер",
		'sortByHand' => "Ручно уређивање задатака",
		'sortByPriority' => "Уређивање по приоритету",
		'sortByDueDate' => "Уређивање по датуму обављања",
		'due' => "Датум обављања",
		'daysago' => "пре %d дана",
		'indays' => "за %d дана",
		'months_short' => array("Jан","Феб","Maр","Aпр","Maј","Jун","Jул","Aвг","Сеп","Oкт","Нов","Дец"),
		'months_long' => array("Јануар","Фебруар","Март","Април","Maј","Jун","Jул","Aвгуст","Септембар","Oктобар","Новембар","Децембар"),
		'days_min' => array("Нед","Пон","Уто","Сре","Чет","Пет","Суб"),
		'days_long' => array("Недеља","Понедељак","Уторак","Среда","Четвртак","Петак","Субота"),
		'today' => "данас",
		'yesterday' => "јуче",
		'tomorrow' => "сутра",
		'f_past' => "Пробивени",
		'f_today' => "Данас и сутра",
		'f_soon' => "Ускоро",
		'action_edit' => "Измена задатка",
		'action_note' => "Промена описа",
		'action_delete' => "Брисање",
		'action_priority' => "Промена приоритета",
		'action_move' => "Премештање у категорију",
		'notes' => "Опис задатка:",
		'notes_show' => "прикажи",
		'notes_hide' => "сакриј",
		'list_new' => "<em>Додавање нове листе</em>",
		'list_rename' => "Преименовање текуће листе",
		'list_delete' => "Брисање текуће листе",
		'list_publish' => "Постављање текуће листе за јавну",
		'list_showcompleted' => "Приказ завршених задатака",
		'list_clearcompleted' => "Брисање завршених задатака",
		'alltags' => "Све категорије:",
		'alltags_show' => "Прикажи категорије",
		'alltags_hide' => "Сакриј  категорије",
		'a_settings' => "Подешавања",
		'rss_feed' => "RSS Feed",
		'feed_title' => "%s",
		'feed_description' => "Задаци у категорији: %s",	


		/* Подешавања */
		'set_header' => "Подешавања",
		'set_title' => "Наслов",
		'set_title_descr' => "унесите уколико желите да промените подразумевани наслов",
		'set_language' => "Језик",
		'set_protection' => "Уптреба лозинке код приступа",
		'set_enabled' => "Да",
		'set_disabled' => "Не",
		'set_newpass' => "Нова лозинка",
		'set_newpass_descr' => "оставите поље празно ако не желите да промените лозинку",
		'set_smartsyntax' => "Smart синтакса",
		'set_smartsyntax_descr' => "(/priority/ task /tags/)",
		'set_autotz' => "Аутоматско подешавање временске зоне",
		'set_autotz_descr' => "одређује одступање временске зоне корисничког окружења помоћу Јава скрипта",
		'set_autotag' => "Аутоматско задавање тагова",
		'set_autotag_descr' => "код уноса новог задатка аутоматски задаје тага користећи вредност из текућег филтра",
		'set_sessions' => "Механизам за чување сесија",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Фајл систем",
		'set_firstdayofweek' => "Први дан у недељи",
		'set_duedate' => "Формат за приказивање датума у календару",
		'set_date' => "Формат за приказ датума задатка",
		'set_shortdate' => "Кратки формат датума",
		'set_clock' => "Формат времена часовника",
		'set_12hour' => "12-часовни",
		'set_24hour' => "24-часовни",
		'set_submit' => "   Упиши   ",
		'set_cancel' => "Одустани",
		'set_showdate' => "Прикажи датум задатка у листи",
	);
}

?>