<?php

/*
	myTinyTodo language pack
	Language: Thai
	Original name: ไทย
	Author: Maxasus123
	Author Url: http://www.bob.in.th
	AppVersion: v1.3.4
	Date: 2010-11-15
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "คุณแน่ใจหรือว่าต้องการลบงาน?",
		'actionNoteSave' => "บันทึก",
		'actionNoteCancel' => "ยกเลิก",
		'error' => "ข้อผิดพลาดบางอย่างเกิดขึ้น (คลิกเพื่อดูรายละเอียด)",
		'denied' => "ปฏิเสธการเข้าใช้",
		'invalidpass' => "รหัสผ่านผิด",
		'tagfilter' => "แท็ก:",
		'addList' => "การสร้างรายการใหม่",
		'renameList' => "เปลี่ยนชื่อรายการใหม่",
		'deleteList' => "นี้จะลบรายการปัจจุบันกับงานทั้งหมดในนั้น. \\nคุณแน่ใจหรือไม่?",
		'clearCompleted' => "นี้จะลบรายการที่ทำเสร็จทั้งหมดในรายการ\\nคุณแน่ใจหรือไม่?",
		'settingsSaved' => "บันทึกการตั้งค่า โหลด ...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Todolist ของฉัน",
		'htab_newtask' => "งานใหม่",
		'htab_search' => "ค้นหา",
		'btn_add' => "เพิ่ม",
		'btn_search' => "ค้นหา",
		'advanced_add' => "ขั้นสูง",
		'searching' => "การค้นหา",
		'tasks' => "งาน",
		'taskdate_inline' => "เพิ่มที่ %s",
		'taskdate_created' => "วันที่สร้าง",
		'taskdate_completed' => "วันที่เสร็จสิ้น",
		'edit_task' => "แก้ไขงาน",
		'add_task' => "เพิ่มงานใหม่",
		'priority' => "ลำดับความสำคัญ",
		'task' => "งาน",
		'note' => "Note",
		'save' => "บันทึก",
		'cancel' => "ยกเลิก",
		'password' => "รหัสผ่าน",
		'btn_login' => "เข้าสู่ระบบ",
		'a_login' => "เข้าสู่ระบบ",
		'a_logout' => "ออกจากระบบ",
		'public_tasks' => "Public Tasks",
		'tags' => "แท็ก",
		'tagfilter_cancel' => "ยกเลิกการกรอง",
		'sortByHand' => "เรียงด้วยมือ",
		'sortByPriority' => "เรียงตามลำดับความสำคัญ",
		'sortByDueDate' => "เรียงตามวันที่กำหนด",
		'due' => "ครบกำหนา",
		'daysago' => "%d วันที่ผ่านมา",
		'indays' => "ใน %d วัน",
		'months_short' => array("ม.ค.","ก.พ.","มี.ค.","เม.ย.","พ.ค.","มิ.ย.","ก.ค.","ส.ค.","ก.ย.","ต.ค.","พ.ย.","ธ.ค."),
		'months_long' => array("มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"),
		'days_min' => array("อา.","จ.","อ.","พ.","พฤ.","ศ.","ส."),
		'days_long' => array("อาทิตย์","จันทร์","อังคาร","พุธ","พฤหัสบดี","ศุกร์","เสาร์"),
		'today' => "วันนี้",
		'yesterday' => "เมื่อวาน",
		'tomorrow' => "พรุ่งนี้",
		'f_past' => "เกินกำหนด",
		'f_today' => "วันนี้และวันพรุ่งนี้",
		'f_soon' => "ในไม่ช้า",
		'action_edit' => "แก้ไข",
		'action_note' => "แก้ไข Note",
		'action_delete' => "ลบ",
		'action_priority' => "ลำดับความสำคัญ",
		'action_move' => "ย้ายไป",
		'notes' => "Notes:",
		'notes_show' => "โชว์",
		'notes_hide' => "ซ่อน",
		'list_new' => "รายการใหม่",
		'list_rename' => "เปลี่ยนชื่อรายการ",
		'list_delete' => "ลบรายการ",
		'list_publish' => "เผยแพร่รายการ",
		'list_showcompleted' => "แสดงงานที่เสร็จแล้ว",
		'list_clearcompleted' => "Clear งานที่เสร็จแล้ว",
		'alltags' => "แท็กทั้งหมด:",
		'alltags_show' => "โชว์ ทั้งหมด",
		'alltags_hide' => "ซ่อนทั้งหม",
		'a_settings' => "การตั้งค่า",
		'rss_feed' => "RSS Feed",
		'feed_title' => "%s",
		'feed_description' => "งานใหม่ใน %s",

		/* Settings */
		'set_header' => "การตั้งค่า",
		'set_title' => "ชื่อเรื่อง",
		'set_title_descr' => "(ระบุหากคุณต้องการเปลี่ยนชื่อเรื่องเริ่มต้น)",
		'set_language' => "ภาษา",
		'set_protection' => "รหัสป้องกัน",
		'set_enabled' => "เปิดใช้งาน",
		'set_disabled' => "ปิดใช้งาน",
		'set_newpass' => "รหัสผ่านใหม่",
		'set_newpass_descr' => "(เว้นว่างไว้หากจะไม่มีการเปลี่ยนแปลงรหัสผ่านปัจจุบัน)",
		'set_smartsyntax' => "Smart syntax",
		'set_smartsyntax_descr' => "(/ลำดับความสำคัญ/ งาน /แท็ก/)",
		'set_autotz' => "เขตเวลาอัตโนมัติ",
		'set_autotz_descr' => "(กำหนดเขตเวลาชดเชยของสภาพแวดล้อมที่ผู้ใช้ที่มีจาวาสคริปต์)",
		'set_autotag' => "Autotagging",
		'set_autotag_descr' => "(โดยอัตโนมัติเพิ่มแท็กแท็กของตัวกรองปัจจุบันกับงานที่สร้างขึ้นใหม่)",
		'set_sessions' => "Session handling mechanism",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "ไฟล์",
		'set_firstdayofweek' => "วันแรกของสัปดาห์",
		'set_duedate' => "Duedate calendar format",
		'set_date' => "รูปแบบวันที่",
		'set_shortdate' => "วันที่แบบย่อ",
		'set_clock' => "รูปแบบเวลา",
		'set_12hour' => "12 ชั่วโมง",
		'set_24hour' => "24 ชั่วโมง",
		'set_submit' => "บันทึก",
		'set_cancel' => "ยกเลิก",
		'set_showdate' => "วันที่งานแสดงในรายการ",
	);
}

?>