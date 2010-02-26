<?php

/*
	myTinyTodo language pack
	Language: Bulgarian
	Original name: Български
	Author: Vladimir Komarov
	Author Url: http://www.myastrodata.com
	AppVersion: v1.3.4
	Date: 2010-02-24
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "Сигурни ли сте, че искате да изтриете тази задача?",
		'actionNoteSave' => "Запис",
		'actionNoteCancel' => "Отказ",
		'error' => "Възникна проблем (кликнете за повече информация)",
		'denied' => "Достъп забранен",
		'invalidpass' => "Грешна парола!",
		'tagfilter' => "Етикет:",
		'addList' => "Създаване на нов списък",
		'renameList' => "Преименуване на списък",
		'deleteList' => "Ще изтриете всички задачи в този списък!\\nСигурни ли сте?",
		'clearCompleted' => "Ще изтриете всички изпълнени задачи в този списък!\\nСигурни ли сте?",
		'settingsSaved' => "Настройките са запазени! Презареждане...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Моят малък To-do списък",
		'htab_newtask' => "Нова задача",
		'htab_search' => "Търсене",
		'btn_add' => "Доабвяне",
		'btn_search' => "Търсене",
		'advanced_add' => "Разширено",
		'searching' => "Търсене за",
		'tasks' => "Задачи",
		'taskdate_inline' => "добавено на %s",
		'taskdate_created' => "Дата на създаване",
		'taskdate_completed' => "Дата на приключване",
		'edit_task' => "Редактирай задача",
		'add_task' => "Нова задача",
		'priority' => "Приоритет",
		'task' => "Задача",
		'note' => "Бележка",
		'save' => "Записване",
		'cancel' => "Отказ",
		'password' => "Парола",
		'btn_login' => "Вход",
		'a_login' => "Вход",
		'a_logout' => "Изход",
		'public_tasks' => "Публични задачи",
		'tags' => "Етикети",
		'tagfilter_cancel' => "Отказ от филтър",
		'sortByHand' => "Ръчно сортиране",
		'sortByPriority' => "Сортиране по приоритет",
		'sortByDueDate' => "Сортиране по дата на падеж",
		'due' => "Падеж",
		'daysago' => "преди %d ден(а)",
		'indays' => "в %d ден(а)",
		'months_short' => array("Яну","Фев","Мар","Апр","Май","Юни","Юли","Авг","Сеп","Окт","Нов","Дек"),
		'months_long' => array("Януари","Февруари","Март","Април","Май","Юни","Юли","Август","Септември","Октомври","Ноември","Декември"),
		'days_min' => array("Нд","Пн","Вт","Ср","Чт","Пт","Сб"),
		'days_long' => array("Неделя","Понеделник","Вторник","Сряда","Четвъртък","Петък","Събота"),
		'today' => "днес",
		'yesterday' => "вчера",
		'tomorrow' => "утре",
		'f_past' => "Просрочен падеж",
		'f_today' => "Днес и утре",
		'f_soon' => "Скоро",
		'action_edit' => "Редакция",
		'action_note' => "Редактиране на бележката",
		'action_delete' => "Изтриване",
		'action_priority' => "Приоритет",
		'action_move' => "Преместване в",
		'notes' => "Бележки:",
		'notes_show' => "Покажи",
		'notes_hide' => "Скрий",
		'list_new' => "Нов списък",
		'list_rename' => "Преименуване на списък",
		'list_delete' => "Изтриване на списък",
		'list_publish' => "Публикуване на списък",
		'list_showcompleted' => "Покажи приключените задачи",
		'list_clearcompleted' => "Изчисти приключените задачи",
		'alltags' => "Всички етикети:",
		'alltags_show' => "Покажи всички",
		'alltags_hide' => "Скрий всички",
		'a_settings' => "Настройки",
		'rss_feed' => "RSS емисия",
		'feed_title' => "%s",
		'feed_description' => "Нова задача в %s",

		/* Settings */
		'set_header' => "Настройки",
		'set_title' => "Заглави",
		'set_title_descr' => "(уточнете, ако искате да смените подразбиращото се заглавие)",
		'set_language' => "Език",
		'set_protection' => "Защита с парола",
		'set_enabled' => "Разрешено",
		'set_disabled' => "Забранено",
		'set_newpass' => "Нова парола",
		'set_newpass_descr' => "(оставете полето празно, ако не искате да сменяте паролата)",
		'set_smartsyntax' => "Кратък синтаксис",
		'set_smartsyntax_descr' => "(/приоритет/ задача /етикети/)",
		'set_autotz' => "Автоматична часова зона",
		'set_autotz_descr' => "(определяне на часовата зона на потребителя чрез JavaScript)",
		'set_autotag' => "Autotagging",
		'set_autotag_descr' => "(автоматично добавяне на етикет към текущия етикет-филтър за новодобавената задача)",
		'set_sessions' => "Механизъм за съхраняване на сесии",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Files",
		'set_firstdayofweek' => "Първи ден на седмицата",
		'set_duedate' => "Формат на календара по дати на падеж",
		'set_date' => "Формат на датата",
		'set_shortdate' => "Съкратен формат на датата",
		'set_clock' => "Формат за часовете",
		'set_12hour' => "12 часов",
		'set_24hour' => "24 часов",
		'set_submit' => "Запис на промените",
		'set_cancel' => "Отказ",
		'set_showdate' => "Показвай датата на задачите в списъка",
	);
}

?>