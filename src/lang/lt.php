<?php

/*
	myTinyTodo language pack
	Language: Lithuanian
	Original name: Lietuvių
	Author: Linas Pašviestis
	Author email: linas.pasviestis@gmail.com
	AppVersion: v1.3.5
	Date: 2010-06-05
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "Ar tikrai norite ištrinti užduotį?",
		'actionNoteSave' => "išsaugoti",
		'actionNoteCancel' => "atšaukti",
		'error' => "Atsirado keletas klaidų (spauskite čia norėdami sužinoti detaliau)",
		'denied' => "Neturite reikiamo leidimo",
		'invalidpass' => "Neteisingas slaptažodis",
		'tagfilter' => "Žymena:",
		'addList' => "Sukurti naują sąrašą",
		'renameList' => "Pakeisti sąrašo vardą",
		'deleteList' => "Ketinama ištrinti sąrašą ir visas jam priklausančias užduotis.\\nAr esate tikras?",
		'clearCompleted' => "Ketinama ištrinti visas šiame sąraše įvygdytas užduotis.\\nAr esate tikras?",
		'settingsSaved' => "Vyksta nustatymų išsaugojimas. Palaukite...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'htab_newtask' => "Nauja užduotis",
		'htab_search' => "Ieškoti",
		'btn_add' => "Pridėti",
		'btn_search' => "Ieškoti", 
		'advanced_add' => "Išplėstinis",
		'searching' => "Ieškoma",
		'tasks' => "Užduotys",
		'taskdate_inline' => "pridėta %s",
		'taskdate_created' => "Sukurtas",
		'taskdate_completed' => "Pabaigtas",
		'edit_task' => "Užduoties redagavimas",
		'add_task' => "Nauja užduotis",
		'priority' => "Prioritetas:",
		'task' => "Pavadinimas",
		'note' => "Aprašymas",
		'save' => "Išsaugoti",
		'cancel' => "Atšaukti",
		'password' => "Slaptažodis",
		'btn_login' => "Prisijungti",
		'a_login' => "Prisijungti",
		'a_logout' => "Atsijungti",
		'public_tasks' => "Viešos užduotys",
		'tags' => "Žymenys",
		'tagfilter_cancel' => "filtro atšaukimas",
		'sortByHand' => "Rankinis rikiavimas",
		'sortByPriority' => "Rikiavimas pagal prioritetą",
		'sortByDueDate' => "Rikiavimas pagal datą",
		'due' => "Iki:",
		'daysago' => "vėluoja %d d.",
		'indays' => "liko %d d.",
		'months_short' => array("Sau","Vas","Kov","Bal","Geg","Bir","Lie","Rugp","Rugs","Spa","Lap","Gruo"),
		'months_long' => array("Sausis","Vasaris","Kovas","Balandis","Gegužė","Birželis","Liepa","Rugpjūtis","Rugsėjis","Spalis","Lapkritis","Gruodis"),
		'days_min' => array("Se","Pi","An","Tr","Ke","Pe","Še"),
		'days_long' => array("Sekmadienis","Pirmadienis","Antradienis","Trečiadienis","Ketvirtadienis","Penktadienis","Šeštadienis"),
		'today' => "šiandien",
		'yesterday' => "vakar",
		'tomorrow' => "rytoj",
		'f_past' => "Vėluojančios",
		'f_today' => "Šiandien ir rytoj",
		'f_soon' => "Greitai",
		'action_edit' => "Redaguoti",
		'action_note' => "Aprašymo redagavimas",
		'action_delete' => "Ištrinti",
		'action_priority' => "Prioritetas",
		'action_move' => "Perkelti į",
		'notes' => "Aprašymai:",
		'notes_show' => "Rodyti",
		'notes_hide' => "Paslėpti",
		'list_new' => "Naujas sąrašas",
		'list_rename' => "Pervadinti sąrašą",
		'list_delete' => "Ištrinti sąrašą",
		'list_publish' => "Paviešinti sąrašą",
		'list_showcompleted' => "Rodyti užbaigtas užduotis",
		'list_clearcompleted' => "Išvalyti užbaigtas užduotis",
		'alltags' => "Visi žymenys:",
		'alltags_show' => "Rodyti visus",
		'alltags_hide' => "Paslėpti visus",
		'a_settings' => "Nustatymai",
		'rss_feed' => "RSS Pateikimas",
		'feed_title' => "%s",
		'feed_description' => "Naujos užduotys: %s",

		/* Settings */
		'set_header' => "Nustatymai",
		'set_title' => "Pavadinimas",
		'set_title_descr' => "(bus pakeistas standartinis pavadinimas)",
		'set_language' => "Kalba",
		'set_protection' => "Slaptažodžio apsauga",
		'set_enabled' => "Įjungtas",
		'set_disabled' => "Išjungtas",
		'set_newpass' => "Naujas slaptažodis",
		'set_newpass_descr' => "(palikite tuščią, jeigu neketinate keisti slaptažodžio)",
		'set_smartsyntax' => "Protinga sintaksė",
		'set_smartsyntax_descr' => "(/priority/ užduotis /tags/)",
		'set_autotz' => "Automatinė laiko zona",
		'set_autotz_descr' => "(automatiškas vartotojo laiko zonos skirtumo nustatymas)",
		'set_autotag' => "Automatinis žymėjimas",
		'set_autotag_descr' => "(automatiškas žymenų generavimas naujai sukurtoms užduotims)",
		'set_sessions' => "Sesijos valdymo metodas",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Failai",
		'set_firstdayofweek' => "Pirma savaitės diena",
		'set_duedate' => "Kalendoriaus datos formatas",
		'set_date' => "Datos formatas",
		'set_shortdate' => "Trumpos datos formatas",
		'set_clock' => "Laikrodžio formatas",
		'set_12hour' => "12 valandų",
		'set_24hour' => "24 valandos",
		'set_submit' => "Išsaugoti pakeitimus",
		'set_cancel' => "Atšaukti",
		'set_showdate' => "Rodyti datą šalia užduoties",
	);
}

?>