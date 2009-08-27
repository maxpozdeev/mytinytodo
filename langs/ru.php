<?php

/*
	myTinyTodo Russian language pack
	v1.2.x compatible
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "заметка",
		'actionEdit' => "редактировать",
		'actionDelete' => "удалить",
		'taskDate' => array("function(date) { return 'добавленa '+date; }"),
		'confirmDelete' => "Вы уверены?",
		'actionNoteSave' => "сохранить",
		'actionNoteCancel' => "отмена",
		'error' => "Ошибка",
		'denied' => "Доступ запрещен",
		'invalidpass' => "Неверный пароль",
		'readonly' => "только для чтения",
		'tagfilter' => "Тег:",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Мой список задач",
		'tab_newtask' => "новая задача",
		'tab_search' => "поиск",
		'btn_add' => "Добавить",
		'btn_search' => "Искать",
		'searching' => "Поиск",
		'tasks' => "Задачи",
		'edit_task' => "Редактирование задачи",
		'priority' => "Приоритет",
		'task' => "Задача",
		'note' => "Заметка",
		'save' => "Сохранить",
		'cancel' => "Отмена",
		'password' => "Пароль",
		'btn_login' => "Войти",
		'a_login' => "Вход",
		'a_logout' => "Выйти",
		'tags' => "Теги",
		'tagfilter_cancel' => "отменить фильтр по тегу",
		'sortByHand' => "Сортировка вручную",
		'sortByPriority' => "Сортировка по приоритету",
		'sortByDueDate' => "Сортировка по сроку",
		'due' => "Срок",
		'daysago' => "%d дн. назад",
		'indays' => "через %d дн.",
		'months_short' => array("Янв","Фев","Мар","Апр","Май","Июн","Июл","Авг","Сен","Окт","Ноя","Дек"),
		'months_long' => array("Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"),
		'days_min' => array("Вс","Пн","Вт","Ср","Чт","Пт","Сб"), 
		'date_md' => "%2\$d %1\$s",
		'date_ymd' => "%3\$d %2\$s %1\$d",
		'today' => "сегодня",
		'yesterday' => "вчера",
		'tomorrow' => "завтра",
		'f_past' => "Просроченные",
		'f_today' => "Сегодня и завтра",
		'f_soon' => "Скоро",
		'tasks_and_compl' => "Задачи + завершенные", 
	);
}

?>