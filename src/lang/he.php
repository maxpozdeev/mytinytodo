<?php

/*
	myTinyTodo language pack
	Language: Hebrew
	Original name: עברית
	Author: Ohad Raz
	Author Url: http://www.Bainternet.info
	AppVersion: v1.3.6
	Date: 2010-08-01
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "האם אתה בטוח למחוק את המשימה?",
		'actionNoteSave' => "אשר",
		'actionNoteCancel' => "בטל",
		'error' => "אירעה שגיאה (לחץ כדי להציג פרטים)",
		'denied' => "הגישה נדחתה",
		'invalidpass' => "סיסמה שגויה",
		'tagfilter' => "לייבל:",
		'addList' => "יצירת רשימה",
		'renameList' => "שינוי שם הרשימה",
		'deleteList' => "זה יבטל את הרשימה הנוכחית, ואת המשימות שהיא מכילה. \\nהאם אתה בטוח?",
		'clearCompleted' => "פעולה זו תמחק את כל המשימות שהושלמו ברשימה. \\nהאם אתה בטוח?",
		'settingsSaved' => "הגדרות שנשמרו. טוען ...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'htab_newtask' => "משימה חדשה",
		'htab_search' => "חיפוש",
		'btn_add' => "הוספה",
		'btn_search' => "לחפש",
		'advanced_add' => "הוספה מתקדמת",
		'searching' => "מחפש ",
		'tasks' => "משימות",
		'taskdate_inline' => "נוסף %s",
		'taskdate_created' => "תאריך היצירה",
		'taskdate_completed' => "תאריך סיום",
		'edit_task' => "ערוך משימה",
		'add_task' => "הוסף משימה",
		'priority' => "עדיפות",
		'task' => "משימה",
		'note' => "פרטים",
		'save' => "שמור",
		'cancel' => "בטל",
		'password' => "סיסמה",
		'btn_login' => "התחבר",
		'a_login' => "התחבר",
		'a_logout' => "התנתק",
		'public_tasks' => "משימות פתוחות לציבור",
		'tags' => "ליבלים",
		'tagfilter_cancel' => "הסר מסנן",
		'sortByHand' => "סדר לפי רשימה",
		'sortByPriority' => "סדר לפי עדיפות",
		'sortByDueDate' => "סדר לפי תאריך סיום",
		'due' => "יש לסיים עד",
		'daysago' => "% d ימים לפני",
		'indays' => "עודב% d ימים",
		'months_short' => array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "July", "Aug", "Sep", "Oct", "Nov", "Dec"),
		'months_long' => array("ינואר", "פברואר", "מרץ", "אפריל", "מאי", "יוני", "יולי", "אוגוסט", "ספטמבר", "אוקטובר", "נובמבר", "דצמבר"),
		'days_min' => array("א","ב","ג","ד","ה","ו","ש"),
		'days_long' => array("ראשון", "שני", "שלישי", "רביעי", "חמישי", "שישי", "שבת"),
		'today' => "היום",
		'yesterday' => "אתמול",
		'tomorrow' => "מחר",
		'f_past' => "מאוחר",
		'f_today' => "היום ומחר",
		'f_soon' => "בקרוב",
		'action_edit' => "ערוך",
		'action_note' => "ערוך פתק",
		'action_delete' => "מחק",
		'action_priority' => "עדיפות",
		'action_move' => "הזז",
		'notes' => "פתקים:",
		'notes_show' => "הצג",
		'notes_hide' => "הסתר",
		'list_new' => "רשימה חדשה",
		'list_rename' => "שנה שם",
		'list_delete' => "מחק רשימה",
		'list_publish' => "פרסם רשימה",
		'list_showcompleted' => "הצג משימות שהסתימו",
		'list_clearcompleted' => "הסר משימות שהסתימו",
		'alltags' => "הכל:",
		'alltags_show' => "הצג הכל",
		'alltags_hide' => "הסתר",
		'a_settings' => "הגדרות",
		'rss_feed' => "פיד RSS",
		'feed_title' => "%s",
		'feed_description' => "משימה חדשה ב %s",

		/* Settings */
		'set_header' => "הגדרות",
		'set_title' => "שם",
		'set_title_descr' => "(ציין אם אתה רוצה לשנות את הכותרת כברירת מחדל)",
		'set_language' => "שפה",
		'set_protection' => "סגור בסיסמה",
		'set_enabled' => "כן",
		'set_disabled' => "לא",
		'set_newpass' => "סיסמה חדשה",
		'set_newpass_descr' => "(השאר ריק אם אתה לא לשנות את הסיסמה הנוכחית)",
		'set_smartsyntax' => "תחביר מתקדם",
		'set_smartsyntax_descr' => "(/ עדיפות / משימה / תגיות /)",
		'set_autotz' => "איזור זמן אוטומטי",
		'set_autotz_descr' => "(קובע את אזור הזמן של סביבת המשתמש על ידי javascript)",
		'set_autotag' => "תיוג אוטומטי",
		'set_autotag_descr' => "(הוספת מסנן התג אוטומטית של תוויות הנוכחי, המשימה האחרונה נוצר)",
		'set_sessions' => "ניהול הפעלות",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "קבצים",
		'set_firstdayofweek' => "יום ראשון של השבוע",
		'set_duedate' => "פורמט לוח השנה",
		'set_date' => "תאריך",
		'set_shortdate' => "תאריך מקוצר",
		'set_clock' => "שעון",
		'set_12hour' => "12-שעות",
		'set_24hour' => "24-שעות",
		'set_submit' => "שמור שינויים",
		'set_cancel' => "בטל",
		'set_showdate' => "הצג את התאריך של הפעילות ברשימה",
	);
}

?>