<?php

/*
	myTinyTodo language pack
	Language: Russian
	Language Original: Русский
	Author: Max Pozdeev
	Author Url: http://www.mytinytodo.net
	AppVersion: v1.3.2
	Date: 2009-12-15
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "заметка",
		'actionEdit' => "редактировать",
		'actionDelete' => "удалить",
		'confirmDelete' => "Вы уверены?",
		'actionNoteSave' => "сохранить",
		'actionNoteCancel' => "отмена",
		'error' => "Ошибка",
		'denied' => "Доступ запрещен",
		'invalidpass' => "Неверный пароль",
		'tagfilter' => "Тег:",
		'addList' => "Новый список",
		'renameList' => "Переименовать список",
		'deleteList' => "Вы действительно хотите удалить этот список со всеми задачами?",
		'settingsSaved' => "Настройки сохранены. Перезагрузка...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Мои задачи",
		'htab_newtask' => "Новая задача",
		'htab_search' => "Поиск",		
		'btn_add' => "Добавить",
		'btn_search' => "Искать",
		'advanced_add' => "Расширенная форма",
		'searching' => "Поиск",
		'tasks' => "Задачи",
		'taskdate_inline' => "добавленa %s",
		'edit_task' => "Редактирование задачи",
		'add_task' => "Новая задача",
		'priority' => "Приоритет",
		'task' => "Задача",
		'note' => "Заметка",
		'save' => "Сохранить",
		'cancel' => "Отмена",
		'password' => "Пароль",
		'btn_login' => "Войти",
		'a_login' => "Вход",
		'a_logout' => "Выйти",
		'public_tasks' => "Опубликованные задачи",
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
		'days_long' => array("Воскресенье","Понедельник","Вторник","Среда","Четверг","Пятница","Суббота"),
		'today' => "сегодня",
		'yesterday' => "вчера",
		'tomorrow' => "завтра",
		'f_past' => "Просроченные",
		'f_today' => "Сегодня и завтра",
		'f_soon' => "Скоро",
		'tasks_and_compl' => "Задачи + завершенные",
		'notes' => "Заметки:",
		'notes_show' => "Показать",
		'notes_hide' => "Скрыть",
		'list_new' => "Новый список",
		'list_rename' => "Переименовать",
		'list_delete' => "Удалить",
		'list_publish' => "Опубликовать",
		'alltags' => "Все теги:",
		'alltags_show' => "Показать все",
		'alltags_hide' => "Скрыть все",
		'a_settings' => "Настройки",
		'rss_feed' => "RSS-лента",
		'feed_title' => "%s",
		'feed_description' => "%s - новые задачи",

		/* Settings */
		'set_header' => "Настройки",
		'set_title' => "Заголовок страницы",
		'set_title_descr' => "(если поле не заполнено, будет использован заголовок по-умолчанию)",
		'set_language' => "Язык (Language)",
		'set_protection' => "Парольная защита",
		'set_enabled' => "Включено",
		'set_disabled' => "Выключено",
		'set_newpass' => "Новый пароль",
		'set_newpass_descr' => "(не заполняйте поле если не хотите менять текущий пароль)",
		'set_smartsyntax' => "Smart syntax",
		'set_smartsyntax_descr' => "(возможность использовать синтаксис: /приоритет/ задача /теги/)",
		'set_autotz' => "Автоопределение часового пояса",
		'set_autotz_descr' => "(определение часового пояса пользователя с помощью javascript)",
		'set_autotag' => "Autotagging",
		'set_autotag_descr' => "(автодобавление текущего тега из фильтра в новую задачу)",
		'set_sessions' => "Хранилище сессий",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Файлы",
		'set_firstdayofweek' => "Первый день недели",
		'set_duedate' => "Формат даты для календаря",
		'set_date' => "Формат даты",
		'set_shortdate' => "Формат короткой даты",
		'set_clock' => "Формат часов",
		'set_12hour' => "12-часовой",
		'set_24hour' => "24-часовой",
		'set_submit' => "Сохранить изменения",
		'set_cancel' => "Отмена",
	);
}

?>