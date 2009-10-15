<?php

/*
	myTinyTodo language pack (Turkish) v1.2.x
	Author: Uğur Okumuş (www.ugurokumus.net)
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'actionNote' => "not ekle",
		'actionEdit' => "düzenle",
		'actionDelete' => "sil",
		'taskDate' => array("function(date) { return date+' tarhinden eklendi'; }"),
		'confirmDelete' => "Emin misin?",
		'actionNoteSave' => "kaydet",
		'actionNoteCancel' => "iptal",
		'error' => "Hatalar var. Ayrıntılar için tıklayınız",
		'denied' => "Erişim izniniz yok",
		'invalidpass' => "Yanlış şifre",
		'readonly' => "sadece okunabilir",
		'tagfilter' => "Etiket:",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Görev Listem",
		'tab_newtask' => "yeni görev",
		'tab_search' => "ara",
		'btn_add' => "ekle",
		'btn_search' => "ara",
		'searching' => "ara",
		'tasks' => "Görevler",
		'edit_task' => "Görevi Düzenle",
		'priority' => "Öncelik",
		'task' => "Görev",
		'note' => "Not",
		'save' => "Kaydet",
		'cancel' => "İptal",
		'password' => "Şifre",
		'btn_login' => "Giriş",
		'a_login' => "Giriş",
		'a_logout' => "Çıkış",
		'tags' => "Etiketler",
		'tagfilter_cancel' => "filtreyi temizle",
		'sortByHand' => "sırala",
		'sortByPriority' => "önceliğe göre diz",
		'sortByDueDate' => "bitiş tarihine göre diz",
		'due' => "Bitiş Tarihi",
		'daysago' => "%d gün önce",
		'indays' => "%d gün içerisinde",
		'months_short' => array("Oca","Şub","Mar","Nis","May","Haz","Tem","Ağs","Eyl","Ekm","Kas","Ara"),
		'months_long' => array("Ocak","Şubat","Mart","Nisan","Mayıs","Haziran","Temmuz","Ağustos","Eylül","Ekim","Kasım","Aralık"),
		'days_min' => array("Pz","Pt","Sa","Çr","Pr","Cu","Ct"),
		'date_md' => "%1\$s %2\$d",
		'date_ymd' => "%2\$s %3\$d, %1\$d",
		'today' => "bugün",
		'yesterday' => "dün",
		'tomorrow' => "yarın",
		'f_past' => "zaman aşımı",
		'f_today' => "Bugün ve yarın",
		'f_soon' => "Yakında",
		'tasks_and_compl' => "Görev + tamamlanmış",
	);
}

?>