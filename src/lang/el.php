<?php

/*
	myTinyTodo language pack
	Language: Greek
	Original name: Ελληνικά
	Author: Κορναράκης Νίκος
	Author Url: http://www.kornarakis.gr
	AppVersion: v1.3.6
	Date: 2010-08-29
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "Θέλετε σίγουρα να σβήσετε αυτήν την υποχρέωση?",
		'actionNoteSave' => "αποθήκευση",
		'actionNoteCancel' => "άκυρο",
		'error' => "Σφάλμα (κάντε κλικ για πληροφορίες)",
		'denied' => "Απαγορεύετε η πρόσβαση",
		'invalidpass' => "Λάθος κωδικός",
		'tagfilter' => "Λέξη κλειδί:",
		'addList' => "Δημιουργία νέας λίστας",
		'renameList' => "Μετονομασία λίστας",
		'deleteList' => "Θα διαγραφεί η λίστα μαζί με όλες τις υποχρεώσεις?\\nΣίγουρα;",
		'clearCompleted' => "Θα διαγραφούν όλες οι υποχρεώσεις στην λίστα.\\nΣίγουρα;",
		'settingsSaved' => "Οι ρυθμίσεις αποθηκεύτηκαν. Επαναφόρτωση...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Η λίστα μου",
		'htab_newtask' => "Νέα υποχρέωση",
		'htab_search' => "Αναζήτηση",
		'btn_add' => "Προσθήκη",
		'btn_search' => "Αναζήτηση",
		'advanced_add' => "Για προχωρημένους",
		'searching' => "Αναζήτηση για",
		'tasks' => "Υποχρεώσεις",
		'taskdate_inline' => "προστέθηκε στις %s",
		'taskdate_created' => "Ημερομηνία δημιουργίας",
		'taskdate_completed' => "Ημερομηνία ολοκλήρωσης",
		'edit_task' => "Επεξεργασία υποχρέωσης",
		'add_task' => "Νέα υποχρέωση",
		'priority' => "Προτεραιότητα",
		'task' => "Υποχρέωση",
		'note' => "Σημείωση",
		'save' => "Αποθήκευση",
		'cancel' => "Άκυρο",
		'password' => "Κωδικός",
		'btn_login' => "Σύνδεση",
		'a_login' => "Σύνδεση",
		'a_logout' => "Αποσύνδεση",
		'public_tasks' => "Δημόσιες υποχρεώσεις",
		'tags' => "Λέξεις κλειδιά",
		'tagfilter_cancel' => "ακύρωση φίλτρου",
		'sortByHand' => "Ταξινόμηση με το χέρι",
		'sortByPriority' => "Ταξινόμηση ανα προτεραιότητα",
		'sortByDueDate' => "Ταξινόμηση ανά ημερομηνία",
		'due' => "Πρέπει",
		'daysago' => "πριν %d μέρες",
		'indays' => "σε %d μέρες",
		'months_short' => array("Ιαν","Φεβ","Mαρ","Απρ","Μαι","Ιουν","Ιουλ","Aύγ","Σεπτ","Οκτ","Νοέμ","Δεκ"),
		'months_long' => array("Ιανουάριος","Φεβρουάριος","Mάρτιος","Aπρίλιος","Mάιος","Ιούνιος","Ιούλιος","Aύγουστος","Σεπτέμβριος","Οκτώβριος","Νοέμβριος","Δεκέμριος"),
		'days_min' => array("Κυ","Δε","Τρ","Τε","Πε","Πα","Σα"),
		'days_long' => array("Κυριακή","Δευτέρα","Tρίτη","Τετάρτη","Πέμπτη","Παρασκευή","Σάββατο"),
		'today' => "σήμερα",
		'yesterday' => "χτες",
		'tomorrow' => "αύριο",
		'f_past' => "Εκπρόσθεσμο",
		'f_today' => "Σήμερα και αύριο",
		'f_soon' => "Σύντομα",
		'action_edit' => "Επεξεργασία",
		'action_note' => "επεξεργασία σημείωσης",
		'action_delete' => "Διαγραφή",
		'action_priority' => "Προτεραιότητα",
		'action_move' => "Μετακίνηση στο",
		'notes' => "Σημειώσεις:",
		'notes_show' => "Εμφάνιση",
		'notes_hide' => "Απόκρυψη",
		'list_new' => "Nέα λίστα",
		'list_rename' => "Μετονομασία λίστας",
		'list_delete' => "Διαγραφή λίστας",
		'list_publish' => "Δημοσίευση λίστας",
		'list_showcompleted' => "Εμφάνιση ολοκληρωμένων υποχρεώσεων",
		'list_clearcompleted' => "Διαγραφή ολοκληρωμένων υποχρεώσεων",
		'alltags' => "Όλες οι λέξεις κλειδιά:",
		'alltags_show' => "Εμφάνιση όλων",
		'alltags_hide' => "Απόκρυψη όλων",
		'a_settings' => "Ρυθμίσεις",
		'rss_feed' => "RSS Τροφοδοσία",
		'feed_title' => "%s",
		'feed_description' => "Νέες υποχρεώσεις στις %s",

		/* Settings */
		'set_header' => "Ρυθμίσεις",
		'set_title' => "Tίτλος",
		'set_title_descr' => "(συμπληρώστε το αν θέλετε να αλλάξετε τον τίτλο)",
		'set_language' => "Γλώσσας",
		'set_protection' => "Προστασία με κωδικό",
		'set_enabled' => "Ενεργοποιημένο",
		'set_disabled' => "Απενεργοποιημένο",
		'set_newpass' => "Νέος κωδικός",
		'set_newpass_descr' => "(αφήστε το κενό αν δεν αλλάξετε τον κωδικό)",
		'set_smartsyntax' => "Έξυπνο συντακτικό",
		'set_smartsyntax_descr' => "(/προτεραιότητα/ υποχρέωση /λέξεις κλειδιά/)",
		'set_autotz' => "Αυτόματη ώρα",
		'set_autotz_descr' => "(καθορίζει την μετατόπιση της ώρας του χρήστη χρησιμοποιώντας javascript)",
		'set_autotag' => "Αυτόματος καθορισμός λέξεων κλειδιών",
		'set_autotag_descr' => "(αυτόματη προσθήκη λέξεων κλειδιών του φίλτρου στις νέες υποχρεώσεις)",
		'set_sessions' => "Τρόπος διαχείρησης συνεδρίας",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Αρχείο",
		'set_firstdayofweek' => "Πρώτη μέρα της εβδομάδας",
		'set_duedate' => "Τύπος ημερομηνίας λήξης",
		'set_date' => "Τύπος ημερομηνίας",
		'set_shortdate' => "Εμφάνιση τύπου ημερομηνίας",
		'set_clock' => "Τύπος ώρας",
		'set_12hour' => "12ωρο",
		'set_24hour' => "24ωρο",
		'set_submit' => "Αποθήκευση αλλαγών",
		'set_cancel' => "Άκυρο",
		'set_showdate' => "Εμφάνιση ημερομηνίας στην λίστα",
	);
}

?>