/* Default template */

function tplSingleTabLoaded()
{
	var $lc = $('#mylistscontainer');
	if($lc.find('.list-block-delimiter').length == 0) {
		$lc.find('ul').append('<li class="mtt-btnmenu-delimiter list-block-delimiter"></li>');
	} else {
		$lc.find('.list-ref').remove();
	}
	var ti = '';
	for(var i in tabLists) {
		ti += '<li class="list-ref '+(i==0?'mtt-item-checked':'')+' list-id-'+tabLists[i].id+'" onClick="tplTabMenuSelect('+tabLists[i].id+','+i+');"><div class="menu-icon"></div><a href="#list'+tabLists[i].id+'" onClick="return false">'+tabLists[i].name+'</a></li>';
	}
	$lc.find('.list-block-delimiter').after(ti);
}

function tplSingleTabRenamed(opts)
{
	$('#mylistscontainer li.list-id-'+opts.list.id).find('a').html(opts.list.name);
}

function tplSingleTabAdded(opts)
{
	$('#mylistscontainer ul').append('<li class="list-ref list-id-'+opts.list.id+'" onClick="tplTabMenuSelect('+opts.list.id+','+opts.list.i+');"><div class="menu-icon"></div><a href="#list'+opts.list.id+'" onClick="return false">'+opts.list.name+'</a></li>');
}

function tplTabMenuSelect(id, indx)
{
	mttTabSelected(id, indx);
	$('#mylistscontainer li.list-ref').removeClass('mtt-item-checked');
	$('#mylistscontainer li.list-id-'+id).addClass('mtt-item-checked');
	return false;
}