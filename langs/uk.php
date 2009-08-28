<?php

/*
	myTinyTodo Ukrainian language pack
	v1.2 compatible
	Author: budulay (http://budulay.org.ua)
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "замітка",
		'actionEdit' => "редагувати",
		'actionDelete' => "видалити",
		'taskDate' => array("function(date) { return 'додана '+date; }"),
		'confirmDelete' => "Ви впевнені?",
		'actionNoteSave' => "зберегти",
		'actionNoteCancel' => "скасувати",
		'error' => "Помилка",
		'denied' => "Доступ заборонено",
		'invalidpass' => "Невірний пароль",
		'readonly' => "тільки для перегляду",
		'tagfilter' => "Мітка:",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Мій список завдань",
		'tab_newtask' => "нове завдання",
		'tab_search' => "пошук",
		'btn_add' => "Додати",
		'btn_search' => "Шукати",
		'searching' => "Пошук",
		'tasks' => "Завдання",
		'edit_task' => "Редагувати завдання",
		'priority' => "Пріорітет",
		'task' => "Завдання",
		'note' => "Замітка",
		'save' => "Зберегти",
		'cancel' => "Скасувати",
		'password' => "Пароль",
		'btn_login' => "Увійти",
		'a_login' => "Вхід",
		'a_logout' => "Вихід",
		'tags' => "Мітки",
		'tagfilter_cancel' => "скасувати фільтр по мітці",
		'sortByHand' => "Сортувати вручну",
		'sortByPriority' => "Сортувати за пріорітетом",
		'sortByDueDate' => "Сортувати за терміном",
		'due' => "Термін",
		'daysago' => "%d днів тому",
		'indays' => "через %d днів",
		'months_short' => array("Січ","Лют","Бер","Кві","Тра","Чер","Лип","Сер","Вер","Жов","Лис","Гру"),
		'days_min' => array("Нд","Пн","Вт","Ср","Чт","Пт","Сб"),
		'date_md' => "%2\$d %1\$s",
		'date_ymd' => "%3\$d %2\$s %1\$d",
		'today' => "сьогодні",
		'yesterday' => "вчора",
		'tomorrow' => "завтра",
		'f_past' => "Прострочені",
		'f_today' => "Сьогодні і завтра",
		'f_soon' => "Незабаром",
		'tasks_and_compl' => "Завдання + завершені",
	);
}

?>