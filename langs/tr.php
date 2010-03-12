<?php

/*
	myTinyTodo language pack
	Language: Turkish
	Original name: Türkçe
	Author: Feyyaz Esatoğlu
	Author Url: http://www.feyyazesat.com
	AppVersion: v1.3.4
	Date: 2010-03-11
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "Görevi silmek istediğinizden emin misiniz?",
		'actionNoteSave' => "kaydet",
		'actionNoteCancel' => "iptal",
		'error' => "Bazı problemler oluştu (detaylar için tıklayın)",
		'denied' => "İzin Yok",
		'invalidpass' => "Yanlış Şifre",
		'tagfilter' => "Etiket:",
		'addList' => "Yeni Liste Oluştur",
		'renameList' => "Listeyi Yeniden Adlandır",
		'deleteList' => "Geçerli liste ve içindeki tüm görevler silinecek.\\nEmin misiniz?",
		'clearCompleted' => "Listedeki tamamlanmış tüm görevler silinecek.\\nEmin misiniz?",
		'settingsSaved' => "Ayarlar kaydedildi.Yükleniyor...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'htab_newtask' => "Yeni Görev",
		'htab_search' => "Ara",
		'btn_add' => "Ekle",
		'btn_search' => "Ara",
		'advanced_add' => "İleri seviye",
		'searching' => "Aranıyor",
		'tasks' => "Görevler",
		'taskdate_inline' => " %s a eklendi",
		'taskdate_created' => "Oluşturulma tarihi",
		'taskdate_completed' => "Tamamlanma tarihi",
		'edit_task' => "Görevi düzenle",
		'add_task' => "Yeni görev",
		'priority' => "Öncelik",
		'task' => "Görev",
		'note' => "Not",
		'save' => "Kaydet",
		'cancel' => "İptal",
		'password' => "Şifre",
		'btn_login' => "Giriş",
		'a_login' => "Giriş",
		'a_logout' => "Çıkış",
		'public_tasks' => "Genel(Kamuya açık) Görevler",
		'tags' => "Etiketler",
		'tagfilter_cancel' => "filtre iptal",
		'sortByHand' => "Elle sırala",
		'sortByPriority' => "Önceliğe göre sırala",
		'sortByDueDate' => "vadesi gelmiş olanlara göre sırala",
		'due' => "Vadesi gelmiş",
		'daysago' => "%d gün önce",
		'indays' => "in %d gün",
		'months_short' => array("Oca","Şub","Mar","Nis","May","Haz","Tem","Ağu","Eyl","Eki","Kas","Ara"),
		'months_long' => array("Ocak","Şubat","Mart","Nisan","Mayıs","Haziran","Temmuz","Ağustos","Eylül","Ekim","Kasım","Aralık"),
		'days_min' => array("Pz","Pt","Sa","Ça","Pe","Cu","Ct"),
		'days_long' => array("Pazar","Pazartesi","Salı","Çarşamba","Perşembe","Cuma","Cumartesi"),
		'today' => "bugün",
		'yesterday' => "dün",
		'tomorrow' => "yarın",
		'f_past' => "Vadesi geçmiş",
		'f_today' => "bugün ve yarın",
		'f_soon' => "Yakın zamanda",
		'action_edit' => "Düzenle",
		'action_note' => "Not düzenle",
		'action_delete' => "Sil",
		'action_priority' => "Öncelik",
		'action_move' => "Hareket ettir",
		'notes' => "Notlar:",
		'notes_show' => "Göster",
		'notes_hide' => "Gizle",
		'list_new' => "Yeni Liste",
		'list_rename' => "Listeyi yeniden adlandır",
		'list_delete' => "Listeyi sil",
		'list_publish' => "Listeyi yayımla",
		'list_showcompleted' => "Biten görevleri göster",
		'list_clearcompleted' => "Biten görevleri temizle",
		'alltags' => "Tüm Etiketler:",
		'alltags_show' => "Tümünü göster",
		'alltags_hide' => "Tümünü gizle",
		'a_settings' => "Ayarlar",
		'rss_feed' => "RSS beslemesi",
		'feed_title' => "%s",
		'feed_description' => "%s da yeni görevler",

		/* Settings */
		'set_header' => "Ayarlar",
		'set_title' => "Etiket",
		'set_title_descr' => "(eğer varsayılan etiketi değiştirecekseniz belirtiniz.)",
		'set_language' => "Dil",
		'set_protection' => "Şifre koruma",
		'set_enabled' => "Erişilebilir",
		'set_disabled' => "Engelli",
		'set_newpass' => "Yeni şifre",
		'set_newpass_descr' => "(geçerli şifreyi değiştirmek istemiyorsanız boş bırakınız)",
		'set_smartsyntax' => "Akıllı sözdizimi",
		'set_smartsyntax_descr' => "(/öncelik/ görev /etiketler/)",
		'set_autotz' => "Otomatik zaman sınırı",
		'set_autotz_descr' => "(kullanıcı şartlarının zaman sınırına javascript karar versin)",
		'set_autotag' => "Otomatik etiketleme",
		'set_autotag_descr' => "(Yeni oluşturulmuş görevler için geçerli etiket filtresine otomatik tag ekler)",
		'set_sessions' => "Oturuma müdahale mekanizması",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Dosyalar",
		'set_firstdayofweek' => "Haftanın ilk günü",
		'set_duedate' => "Vade tarihi takvim formatı",
		'set_date' => "Tarih formatı",
		'set_shortdate' => "Kısa tarih formatı",
		'set_clock' => "Saat formatı",
		'set_12hour' => "12-saat",
		'set_24hour' => "24-saat",
		'set_submit' => "değişiklikleri onayla",
		'set_cancel' => "İptal",
		'set_showdate' => "listedeki görev tarihini göster",
	);
}

?>