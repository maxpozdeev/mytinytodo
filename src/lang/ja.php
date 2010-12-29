<?php

/*
	myTinyTodo language pack
	Language: Japanese
	Original name: 日本語
	Author: Calltella
	Author Url: http://calltella.com/
	AppVersion: v1.3.6
	Date: 2010-12-17
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "タスクを削除してもよろしいですか？",
		'actionNoteSave' => "保存",
		'actionNoteCancel' => "キャンセル",
		'error' => "エラーが発生しました。 (クリックで詳細)",
		'denied' => "アクセスが拒否されました。",
		'invalidpass' => "パスワードが違います。",
		'tagfilter' => "タグ:",
		'addList' => "新規リスト作成",
		'renameList' => "リスト名変更",
		'deleteList' => "全てのタスクと現在のリストを削除します。\\nよろしいですか？",
		'clearCompleted' => "完了した全てのリストを削除します。\\nよろしいですか？",
		'settingsSaved' => "設定保存中...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "My Tiny Todolist",
		'htab_newtask' => "新規タスク",
		'htab_search' => "検索",
		'btn_add' => "追加",
		'btn_search' => "検索",
		'advanced_add' => "詳細追加",
		'searching' => "検索中－",
		'tasks' => "タスク",
		'taskdate_inline' => "作成時間 %s",
		'taskdate_created' => "作成時間",
		'taskdate_completed' => "完了時間",
		'edit_task' => "タスク編集",
		'add_task' => "新規タスク",
		'priority' => "優先度",
		'task' => "タスク",
		'note' => "詳細",
		'save' => "保存",
		'cancel' => "キャンセル",
		'password' => "パスワード",
		'btn_login' => "ログイン",
		'a_login' => "ログイン",
		'a_logout' => "ログアウト",
		'public_tasks' => "公開タスク",
		'tags' => "タグ",
		'tagfilter_cancel' => "キャンセル",
		'sortByHand' => "手動で並び替え",
		'sortByPriority' => "優先度で並び替え",
		'sortByDueDate' => "日付で並び替え",
		'due' => "期限",
		'daysago' => "%d 日経過",
		'indays' => "あと %d 日",
		'months_short' => array("1月","２月","３月","４月","５月","６月","７月","８月","９月","１０月","１１月","１２月"),
		'months_long' => array("January","February","March","April","May","June","July","August","September","October","November","December"),
		'days_min' => array("日","月","火","水","木","金","土"),
		'days_long' => array("日曜日","月曜日","火曜日","水曜日","木曜日","金曜日","土曜日"),
		'today' => "今日",
		'yesterday' => "昨日",
		'tomorrow' => "明日",
		'f_past' => "期限切れ",
		'f_today' => "今日と明日",
		'f_soon' => "もうすぐ",
		'action_edit' => "編集",
		'action_note' => "ノート編集",
		'action_delete' => "削除",
		'action_priority' => "優先度",
		'action_move' => "移動先",
		'notes' => "詳細:",
		'notes_show' => "表示",
		'notes_hide' => "非表示",
		'list_new' => "新規リスト",
		'list_rename' => "リスト名変更",
		'list_delete' => "リスト削除",
		'list_publish' => "公開リスト",
		'list_showcompleted' => "完了済みタスクを表示",
		'list_clearcompleted' => "完了済みタスクをクリア",
		'alltags' => "全てのタグ:",
		'alltags_show' => "全表示",
		'alltags_hide' => "全非表示",
		'a_settings' => "設定",
		'rss_feed' => "RSSフィード",
		'feed_title' => "%s",
		'feed_description' => "新規タスク %s",

		/* Settings */
		'set_header' => "設定",
		'set_title' => "タイトル",
		'set_title_descr' => "(指定の無い場合はデフォルトのタイトルを使用します。)",
		'set_language' => "言語",
		'set_protection' => "パスワード保護",
		'set_enabled' => "有効",
		'set_disabled' => "無効",
		'set_newpass' => "新規パスワード",
		'set_newpass_descr' => "(空白の場合はパスワード変更されません。)",
		'set_smartsyntax' => "Smart syntax",
		'set_smartsyntax_descr' => "(/priority/ task /tags/)",
		'set_autotz' => "タイムゾーンの自動設定",
		'set_autotz_descr' => "(Javascriptでタイムゾーンを取得します。)",
		'set_autotag' => "自動タグ設定",
		'set_autotag_descr' => "(タスクフィルターしている場合は自動的にタグを挿入します。)",
		'set_sessions' => "セッション処理",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Files",
		'set_firstdayofweek' => "週の始まり",
		'set_duedate' => "カレンダーフォーマット",
		'set_date' => "日付フォーマット",
		'set_shortdate' => "短縮日付フォーマット",
		'set_clock' => "時刻フォーマット",
		'set_12hour' => "12時間表示",
		'set_24hour' => "24時間表示",
		'set_submit' => "設定変更",
		'set_cancel' => "キャンセル",
		'set_showdate' => "タスクに日付を表示",
	);
}

?>