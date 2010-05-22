<?php

/*
	myTinyTodo Belarusian language pack
	v1.2 beta2 compatible
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "нататка",
		'actionEdit' => "зьмяніць",
		'actionDelete' => "выдаліць",
		'taskDate' => array("function(date) { return 'дадана '+date; }"),
		'confirmDelete' => "Вы ўпэўнены?",
		'actionNoteSave' => "захаваць",
		'actionNoteCancel' => "скасаваць",
		'error' => "Памылка",
		'denied' => "Доступ забаронены",
		'invalidpass' => "Няверны пароль",
		'readonly' => "толькі чытаньне",
		'tagfilter' => "Бірка:",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Мой сьпіс задач",
		'tab_newtask' => "новая задача",
		'tab_search' => "пошук",
		'btn_add' => "Дадаць",
		'btn_search' => "Шукаць",
		'searching' => "Пошук",
		'tasks' => "Задачы",
		'show_completed' => "Адлюстраваць завершаныя задачы",
		'hide_completed' => "Схаваць завершаныя задачы",
		'edit_task' => "Рэдагаваць задачу",
		'priority' => "Прыярытэт",
		'task' => "Задача",
		'note' => "Нататка",
		'save' => "Захаваць",
		'cancel' => "Скасаваць",
		'password' => "Пароль",
		'btn_login' => "Увайсьці",
		'a_login' => "Уваход",
		'a_logout' => "Выйсьці",
		'tags' => "Біркі",
		'tagfilter_cancel' => "скасаваць",
		'sortByHand' => "Сартаваць уручную",
		'sortByPriority' => "Сартаваць па прыярытэту",
		'sortByDueDate' => "Сартаваць па тэрміну",
		'due' => "Тэрмін",
		'daysago' => "%d дзён таму",
		'indays' => "праз %d дзён",
		'months_short' => array("Сту","Лют","Сак","Кра","Тра","Чэр","Ліп","Жні","Вер","Кас","Ліс","Сне"),
		'date_md' => "%2\$d %1\$s",
		'date_ymd' => "%3\$d %2\$s %1\$d",
		'today' => "сёньня",
		'yesterday' => "учора",
		'tomorrow' => "заўтра",
		'f_past' => "Пратэрмінаваныя",
		'f_today' => "Сёньня й заўтра",
		'f_soon' => "Хутка",
	);
}

?>