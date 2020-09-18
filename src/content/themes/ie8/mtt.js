if (window !== undefined && window.mytinytodo !== undefined)
{
	mytinytodo.options.history = false;

	mytinytodo.prepareTaskStr = function(item, noteExp) {
		// &mdash; = &#8212; = —
		var id = item.id; 
		var prio = item.prio;
		return '<li id="taskrow_'+id+'" class="' + (item.compl?'task-completed ':'') + item.dueClass + (item.note!=''?' task-has-note':'') +
					((this.curList.showNotes && item.note != '') || noteExp ? ' task-expanded' : '') + this.prepareTagsClass(item.tags_ids) + '">' +
			'<div class="task-actions"><a href="#" class="taskactionbtn"></a></div>'+"\n"+
			'<div class="task-left"><div class="task-toggle"></div>'+
			'<input type="checkbox" '+(this.flag.readOnly?'disabled="disabled"':'')+(item.compl?'checked="checked"':'')+'/></div>'+"\n"+
			'<div class="task-middle"><div class="task-through-right">'+prepareDuedate(item)+
			'<span class="task-date-completed"><span title="'+item.dateInlineTitle+'">'+item.dateInline+'</span>&#8212;'+
			'<span title="'+item.dateCompletedInlineTitle+'">'+item.dateCompletedInline+'</span></span></div>'+"\n"+
			'<div class="task-through">'+preparePrio(prio,id)+'<span class="task-title">'+prepareHtml(item.title)+'</span> '+
			(this.curList.id == -1 ? '<span class="task-listname">'+ tabLists.get(item.listId).name +'</span>' : '') +	"\n" +
			this.prepareTagsStr(item)+'<span class="task-date">'+item.dateInlineTitle+'</span></div>'+
			'<div class="task-note-block">'+
				'<div id="tasknote'+id+'" class="task-note"><span>'+prepareHtml(item.note)+'</span></div>'+
				'<div id="tasknotearea'+id+'" class="task-note-area"><textarea id="notetext'+id+'"></textarea>'+
					'<span class="task-note-actions"><a href="#" class="mtt-action-note-save">'+this.lang.get('actionNoteSave')+
					'</a> | <a href="#" class="mtt-action-note-cancel">'+this.lang.get('actionNoteCancel')+'</a></span></div>'+
			'</div>'+
			"</div></li>\n";
	};
}

function prepareHtml(s)
{
	// make URLs clickable
	s = s.replace(/(^|\s|>)(www\.([\w\#$%&~\/.\-\+;:=,\?\[\]@]+?))(,|\.|:|)?(?=\s|&quot;|&lt;|&gt;|\"|<|>|$)/gi, '$1<a href="http://$2" target="_blank">$2</a>$4');
	return s.replace(/(^|\s|>)((?:http|https|ftp):\/\/([\w\#$%&~\/.\-\+;:=,\?\[\]@]+?))(,|\.|:|)?(?=\s|&quot;|&lt;|&gt;|\"|<|>|$)/ig, '$1<a href="$2" target="_blank">$2</a>$4');
};

function preparePrio(prio,id)
{
	var cl =''; var v = '';
	if(prio < 0) { cl = 'prio-neg prio-neg-'+Math.abs(prio); v = '&#8722;'+Math.abs(prio); }	// &#8722; = &minus; = −
	else if(prio > 0) { cl = 'prio-pos prio-pos-'+prio; v = '+'+prio; }
	else { cl = 'prio-zero'; v = '&#177;0'; }													// &#177; = &plusmn; = ±
	return '<span class="task-prio '+cl+'">'+v+'</span>';
};

function prepareDuedate(item)
{
	if(!item.duedate) return '';
	return '<span class="duedate" title="'+item.dueTitle+'"><span class="duedate-arrow">→</span> '+item.dueStr+'</span>';
};
