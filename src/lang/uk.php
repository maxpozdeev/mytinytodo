<?php

/*
	myTinyTodo language pack
	Language: Ukrainian
	Original name: Українська
	Author: Sergii Iavorskyi
	AppVersion: v1.3.4
	Date: 2010-03-17
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "Чи дійсно Ви хочете видалити це завдання?",
		'actionNoteSave' => "зберегти",
		'actionNoteCancel' => "відмінити",
		'error' => "Помилка",
		'denied' => "Доступ заборонено",
		'invalidpass' => "Невірний пароль",
		'tagfilter' => "Тег:",
		'addList' => "Новий список",
		'renameList' => "Переіменувати список",
		'deleteList' => "Чи дійсно Ви хочете видалити список разом з усіма завданнями?",
		'clearCompleted' => "Видалити зі списку всі завершені завдання?",		
		'settingsSaved' => "Налаштування збережені. Перезавантаження&hellip;",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Мої завдання",
		'htab_newtask' => "Нове завдання",
		'htab_search' => "Пошук",		
		'btn_add' => "Додати",
		'btn_search' => "Шукати",
		'advanced_add' => "Розширена форма",
		'searching' => "Пошук",
		'tasks' => "Усі завдання",
		'taskdate_inline' => "додана %s",
		'taskdate_created' => "Дата створення",
		'taskdate_completed' => "Дата завершення",
		'edit_task' => "Редагування завдання",
		'add_task' => "Нове завдання",
		'priority' => "Пріорітет",
		'task' => "Завдання",
		'note' => "Нотатки",
		'save' => "Зберегти",
		'cancel' => "Відмінити",
		'password' => "Пароль",
		'btn_login' => "Увійти",
		'a_login' => "Вхід",
		'a_logout' => "Вийти",
		'public_tasks' => "Опубліковані завдання",
		'tags' => "Теги",
		'tagfilter_cancel' => "відмінити фільтрування по тегами",
		'sortByHand' => "Сортування вручну",
		'sortByPriority' => "Сортування за пріорітетом",
		'sortByDueDate' => "Сортування за датою",
		'due' => "Завершити до",
		'daysago' => "%d дн. тому",
		'indays' => "через %d дн.",
		'months_short' => array("Січ","Лют","Бер","Квіт","Трав","Чер","Лип","Сер","Вер","Жов","Лис","Груд"),
		'months_long' => array("Січень","Лютий","Березень","Квітень","Травень","Червень","Липень","Серпень","Вересень","Жовтень","Листопад","Грудень"),
		'days_min' => array("Нд","Пн","Вт","Ср","Чт","Пт","Сб"), 
		'days_long' => array("Неділя","Понеділок","Вівторок","Середа","Четвер","П'ятница","Субота"),
		'today' => "сьогодні",
		'yesterday' => "вчора",
		'tomorrow' => "завтра",
		'f_past' => "Просрочені",
		'f_today' => "Сьогодні і завтра",
		'f_soon' => "Скоро",
		'action_edit' => "Редагувати",
		'action_note' => "Нотатки",
		'action_delete' => "Видалити",
		'action_priority' => "Пріорітет",
		'action_move' => "Перемістити в",
		'notes' => "Нотатки:",
		'notes_show' => "Відобразити",
		'notes_hide' => "Сховати",
		'list_new' => "Новий список",
		'list_rename' => "Переіменувати список",
		'list_delete' => "Видалити список",
		'list_publish' => "Опублікувати список",
		'list_showcompleted' => "Показати завершені завдання",
		'list_clearcompleted' => "Видалити завершені задання",
		'alltags' => "Всі теги:",
		'alltags_show' => "Вибрати теги",
		'alltags_hide' => "Сховати теги",
		'a_settings' => "Налаштуваня",
		'rss_feed' => "RSS-стрічка",
		'feed_title' => "%s",
		'feed_description' => "%s &madsh; нові завдання",

		/* Settings */
		'set_header' => "Налаштування",
		'set_title' => "Заголовок сторінки",
		'set_title_descr' => "(якщо поле на заповнено, то буде використаний заголовок по замовчуванню)",
		'set_language' => "Мова (Language)",
		'set_protection' => "Захист паролем",
		'set_enabled' => "Увімкнено",
		'set_disabled' => "Вимкнено",
		'set_newpass' => "Новий пароль",
		'set_newpass_descr' => "(не заповнюйте поле якщо не хочете змінювати теперішній пароль)",
		'set_smartsyntax' => "Розширений синтаксис",
		'set_smartsyntax_descr' => "(можливість використовувати запис вигляду /пріорітет/завдання/теги/)",
		'set_autotz' => "Автоматичне встановлення часового поясу",
		'set_autotz_descr' => "(встановлення часового поясу за допомогою javascript)",
		'set_autotag' => "Автоматичне присвоєння тегів",
		'set_autotag_descr' => "(автоматичне додавання тегу з фільтру у нові завдання)",
		'set_sessions' => "Збереження сесій",
		'set_sessions_php' => "засобами PHP",
		'set_sessions_files' => "в файлах",
		'set_firstdayofweek' => "Перший день тижня",
		'set_duedate' => "Формат дати для календаря",
		'set_date' => "Формат дати",
		'set_shortdate' => "Скорочений формат дати",
		'set_clock' => "Формат часу",
		'set_12hour' => "12-годинний",
		'set_24hour' => "24-годинний",
		'set_submit' => "Зберегти зміни",
		'set_cancel' => "Відмінити",
		'set_showdate' => "Відображати дату завдання",
	);
}

?>