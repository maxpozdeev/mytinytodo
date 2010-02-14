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
		ti += '<li class="list-ref '+(i==0?'mtt-item-checked':'')+' list-id-'+tabLists[i].id+'" onClick="tplTabMenuSelect('+tabLists[i].id+','+i+');"><div class="menu-icon"></div><a href="#list/'+tabLists[i].id+'" onClick="return false">'+tabLists[i].name+'</a></li>';
	}
	$lc.find('.list-block-delimiter').after(ti);
}

function tplSingleTabRenamed(list)
{
	$('#mylistscontainer li.list-id-'+list.id).find('a').html(list.name);
}

function tplSingleTabAdded(list)
{
	$('#mylistscontainer ul').append('<li class="list-ref list-id-'+list.id+'" onClick="tplTabMenuSelect('+list.id+','+list.i+');"><div class="menu-icon"></div><a href="#list/'+list.id+'" onClick="return false">'+list.name+'</a></li>');
}

function tplTabMenuSelect(id, indx)
{
	mttTabSelected(id, indx);
	$('#mylistscontainer li.list-ref').removeClass('mtt-item-checked');
	$('#mylistscontainer li.list-id-'+id).addClass('mtt-item-checked');
	return false;
}