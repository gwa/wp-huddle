(function ($) {

	var _workspace = null, _folder = null, _tree, _selected, _hasheight=false;

	function _init()
	{
		_tree = $('#gwahuddle-tree');

		$('#gwahuddle-add').on('click', function (ev) {
			ev.preventDefault();
			$(this).remove();
			_fetchWorkspaces();
		});

		_initRemoveButtons();
	}

	function _initRemoveButtons()
	{
		var f = function () {
			_addRemoveButtonToLI($(this));
		};
		$('#gwahuddle-selected').find('li').each(f);
	}

	function _addRemoveButtonToLI( li )
	{
		var a = $('<a class="del" href="#"></a>');
		a.on('click', function (ev) {
			ev.preventDefault();
			$(this).parents('li').remove();
		});
		li.find('div').append(a);
	}

	function _fetchWorkspaces()
	{
		_tree.empty().html('<div class="loading">loading...</div>');
		$.post(
			ajax_object.ajax_url,
			{
				action: 'gwahuddle_get_workspaces'
			},
			function (response) {
				_handleWorkspaces(response);
			},
			'json'
		);
	}

	function _handleWorkspaces( data )
	{
		var a, h, ul;

		_tree.empty();

		if (data.error) {
			alert(data.error);
			return;
		}

		h = $('<h4>Workspaces</h4>');
		_tree.append(h);

		ul = $('<ul class="workspaces" />');
		for (a in data.workspaces) {
			_addWorkspaceButton(data.workspaces[a], ul);
		};

		_tree.append(ul);

		_expandContainer();
	}

	function _addWorkspaceButton( ws, parent )
	{
		var el;
		el = $('<li class="workspace"><div><a data-idfolder="'+ws.idfolder+'" href="#">'+ws.displayname+'</a></div></li>');
		el.find('a').on('click', function (ev) {
			ev.preventDefault();
			_fetchFolder($(this).attr('data-idfolder'));
		});
		parent.append(el);
	}

	function _fetchFolder( idfolder )
	{
		_tree.empty().html('<div class="loading">loading...</div>');
		$.post(
			ajax_object.ajax_url,
			{
				action: 'gwahuddle_get_folder',
				idfolder: idfolder
			},
			function (response) {
				_handleFolder(response);
			},
			'json'
		);
	}

	function _handleFolder( data )
	{
		var a, h, ul;

		_tree.empty();

		if (data.error) {
			alert(data.error);
			return;
		}

		h = $('<h4><a class="folderup" href="#" data-idfolder="'+data.idparent+'"></a> '+data.displayname+'</h4>');
		h.find('a').on('click', function (ev) {
			ev.preventDefault();
			var idfolder = $(this).attr('data-idfolder');
			if (!idfolder || idfolder=='null') {
				_fetchWorkspaces();
			} else {
				_fetchFolder(idfolder);
			}
		});
		_tree.append(h);


		if (data.folders.length === 0 && data.documents.length === 0) {
			_tree.append('<div class="empty">- empty -</div>');
		}

		ul = $('<ul class="folders"/>');
		for (a in data.folders) {
			_addFolderButton(data.folders[a], ul);
		};
		_tree.append(ul);

		ul = $('<ul class="documents"/>');
		for (a in data.documents) {
			_addDocumentButton(data.documents[a], ul);
		};
		_tree.append(ul);

		_expandContainer();
	}

	function _addFolderButton( data, parent )
	{
		var el;
		el = $('<li class="folder"><div><a data-idfolder="'+data.idfolder+'" href="#"><span></span> '+data.displayname+'</a></div></li>');
		el.find('a').on('click', function (ev) {
			ev.preventDefault();
			_fetchFolder($(this).attr('data-idfolder'));
		});
		parent.append(el);
	}

	function _addDocumentButton( data, parent )
	{
		var el;
		el = $('<li class="document"><div><a class="'+data.classname+'" data-iddocument="'+data.iddocument+'" href="#">'+data.thumbnail+' '+data.displayname+'</a></div></li>');
		el.find('a').on('click', function (ev) {
			ev.preventDefault();
			_selectDocument(
				$(this).attr('class'),
				$(this).attr('data-iddocument'),
				$(this).html()
			);
		});
		parent.append(el);
	}

	function _selectDocument( classname, iddocument, content )
	{
		var m = $('<li><div class="'+classname+'"><input type="hidden" name="gwawp_huddledoc[]" value="'+iddocument+'" />'+content+'</div></li>')
		_addRemoveButtonToLI(m);
		$('#gwahuddle-selected').append(m);
	}

	function _expandContainer()
	{
		var
		ho = $('#gwa-huddle .inside').height(),
		hi = $('#gwa-huddle .gwahuddle').height();
		if (!_hasheight || hi > ho) {
			$('#gwa-huddle .inside').height(hi);
			_hasheight = true;
		}
	}

	$(document).ready(function () {
		_init();
	});

})(jQuery);
