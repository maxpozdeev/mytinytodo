<?php

/*
	myTinyTodo language pack
	Language: Vietnamese
	Original name: Tiếng Việt
	Author: AloneRoad
	Author Url: http://aoi.vn
	AppVersion: v1.3.4
	Date: 2010-05-05
*/

class Lang extends DefaultLang
{
	var $js = array
	(
		'confirmDelete' => "Bạn muốn xóa công việc này?",
		'actionNoteSave' => "Lưu",
		'actionNoteCancel' => "Hủy",
		'error' => "Có lỗi đã xảy ra (nhấn vào đây để xem chi tiết)",
		'denied' => "Từ chối truy cập",
		'invalidpass' => "Sai mật khẩu",
		'tagfilter' => "Từ khóa phân loại:",
		'addList' => "Tạo danh sách mới",
		'renameList' => "Đổi tên",
		'deleteList' => "This will delete current list with all tasks in it.\\nAre you sure?",
		'clearCompleted' => "This will delete all completed tasks in the list.\\nAre you sure?",
		'settingsSaved' => "Các thiết lập đã lưu thành công...",
	);

	var $inc = array
	(
		'My Tiny Todolist' => "Danh sách các công việc cần làm",
		'htab_newtask' => "Công việc",
		'htab_search' => "Tìm kiếm",
		'btn_add' => "Thêm",
		'btn_search' => "Tìm",
		'advanced_add' => "Nâng cao",
		'searching' => "Kết quả tìm kiếm cho từ khóa:",
		'tasks' => "Công việc",
		'taskdate_inline' => "tạo vào %s",
		'taskdate_created' => "Ngày tạo",
		'taskdate_completed' => "Ngày hoàn thành",
		'edit_task' => "Chỉnh sửa",
		'add_task' => "Thêm việc mới",
		'priority' => "Độ ưu tiên",
		'task' => "Công việc",
		'note' => "Ghi chú",
		'save' => "Lưu",
		'cancel' => "Hủy",
		'password' => "Mật khẩu",
		'btn_login' => "Đăng nhập",
		'a_login' => "Đăng nhập",
		'a_logout' => "Đăng xuất",
		'public_tasks' => "Công việc chung",
		'tags' => "Từ khóa phân loại",
		'tagfilter_cancel' => "Hủy bộ lọc",
		'sortByHand' => "Xếp thủ công",
		'sortByPriority' => "Xếp theo mức độ ưu tiên",
		'sortByDueDate' => "Xếp theo ngày",
		'due' => "Hạn",
		'daysago' => "%d ngày trước",
		'indays' => "trong %d ngày nữa",
		'months_short' => array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"),
		'months_long' => array("Tháng 1","Tháng 2", "Tháng 3","Tháng 4","Tháng 5","Tháng 6","Tháng 7","Tháng 8","Tháng 9","Tháng 10","Tháng 11","Tháng 12"),
		'days_min' => array("CN","T.2","T.3","T.4","T.5","T.6","T.7"),
		'days_long' => array("Chủ Nhật","Thứ Hai","Thứ Ba","Thứ Tư","Thứ Năm","Thứ Sáu","Thứ Bảy"),
		'today' => "hôm nay",
		'yesterday' => "hôm qua",
		'tomorrow' => "ngày mai",
		'f_past' => "Quá hạn",
		'f_today' => "Hôm nay và ngày mai",
		'f_soon' => "Cần làm ngay",
		'action_edit' => "Chỉnh sửa",
		'action_note' => "Sửa ghi chú",
		'action_delete' => "Xóa",
		'action_priority' => "Mức độ ưu tiên",
		'action_move' => "Chuyển sang",
		'notes' => "Ghi chú:",
		'notes_show' => "Hiện",
		'notes_hide' => "Ẩn",
		'list_new' => "Danh sách mới",
		'list_rename' => "Đổi tên",
		'list_delete' => "Xóa danh sách",
		'list_publish' => "Công khai danh sách",
		'list_showcompleted' => "Hiển thị các công việc đã làm xong",
		'list_clearcompleted' => "Xóa các công việc đã làm xong",
		'alltags' => "Toàn bộ các từ khóa dùng để phân loại:",
		'alltags_show' => "Hiển thị toàn bộ",
		'alltags_hide' => "Ẩn toàn bộ",
		'a_settings' => "Thiết lập",
		'rss_feed' => "RSS",
		'feed_title' => "%s",
		'feed_description' => "Công việc mới %s",

		/* Settings */
		'set_header' => "Thiết lập",
		'set_title' => "Tiêu đề",
		'set_title_descr' => "(Thay đổi tiêu đề mặc định ở đây)",
		'set_language' => "Ngôn ngữ",
		'set_protection' => "Sử dụng mật khẩu",
		'set_enabled' => "Kích hoạt",
		'set_disabled' => "Vô hiệu hóa",
		'set_newpass' => "Mật khẩu mới",
		'set_newpass_descr' => "(để trống nếu bạn không muốn đổi mật khẩu đang dùng)",
		'set_smartsyntax' => "Cú pháp thông minh",
		'set_smartsyntax_descr' => "(/độ ưu tiên/công việc/phân loại/)",
		'set_autotz' => "Tự động phát hiện múi giờ",
		'set_autotz_descr' => "(xác định múi giờ bằng javascript)",
		'set_autotag' => "Tự động phân loại",
		'set_autotag_descr' => "(tự động thêm từ khóa của bộ lọc hiện tại vào danh sách từ khóa phân loại khi tạo một công việc mới)",
		'set_sessions' => "Cơ chế điều khiển phiên làm việc",
		'set_sessions_php' => "PHP",
		'set_sessions_files' => "Files",
		'set_firstdayofweek' => "Ngày bắt đầu của tuần",
		'set_duedate' => "Hạn thực hiện",
		'set_date' => "Ngày",
		'set_shortdate' => "Ngắn gọn",
		'set_clock' => "Đồng hồ",
		'set_12hour' => "Dạng 12 giờ",
		'set_24hour' => "Dạng 24 giờ",
		'set_submit' => "Lưu các thay đổi",
		'set_cancel' => "Hủy bỏ",
		'set_showdate' => "Hiện ngày tháng trong danh sách công việc",
	);
}

?>