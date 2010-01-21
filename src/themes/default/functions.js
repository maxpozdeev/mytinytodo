/* Default template */

mytinytodo.addAction('listsLoaded', listsLoaded);
mytinytodo.addAction('listAdded', listAdded);
mytinytodo.addAction('listRenamed', listRenamed);

function listsLoaded(opts)
{
	if(cmenu) cmenu.destroy();
	cmenu = null;

	var s = '';
	for(var i in tabLists) {
		s += '<li id="cmenu_list:'+tabLists[i].id+'">'+tabLists[i].name+'</li>';
	}
	$('#listsmenucontainer ul').html(s);
}

function listAdded(opts)
{
	if(cmenu) cmenu.destroy();
	cmenu = null;
	$('#listsmenucontainer ul').append('<li id="cmenu_list:'+opts.list.id+'">'+opts.list.name+'</li>');
}

function listRenamed(opts)
{
	$('#cmenu_list\\:'+opts.list.id).text(opts.list.name);
}