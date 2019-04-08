function Tabs (group, elem) {
	var arr = document.getElementsByClassName (group);
	var a, q = arr.length;
	for (a=0;a<q;a++) arr[a].classList.remove('active');
	arr = document.getElementsByClassName (elem);
	q = arr.length;
	for (a=0;a<q;a++) arr[a].classList.add('active');
}

function setTab (event, textarea){
		// не кропка tab - выходим
		if( event.keyCode !== 9 )
			return;

		event.preventDefault();

		// Opera, FireFox, Chrome
		//var textarea = $(this)[0],
		var	selStart = textarea.selectionStart,
			selEnd   = textarea.selectionEnd,
			slection = textarea.value.substring( selStart, selEnd ),
			slection_new = '',
			before   = textarea.value.substring( 0, selStart ),
			after    = textarea.value.substring( selEnd, textarea.value.length );

		// добавляем tab
		if( ! event.shiftKey ){
			selStart++;
			if( slection.trim() )
				slection_new = slection.replace(/^/gm, function(){ selEnd++; return "\t"; });
			else {
				slection_new = "\t";
				selEnd++;
			}
		}
		// убриаем табы
		else {
			// если символ до выделения тоже \t удаляем и его
			if( before[ before.length -1 ] === "\t" ){
				before = before.substring( 0, before.length - 1 );
				selStart--;
				selEnd--;
			}

			slection_new = slection.replace(/^\t/gm, function(){ selEnd--; return ""; });
		}

		textarea.value = before + slection_new + after;

		// курсор
		textarea.setSelectionRange( selStart, selEnd );
}

function noCtrlS (ev) {
	if (ev.ctrlKey && (ev.which || ev.keyCode) == 115) {
		alert ('Ctrl + S');
		return false;
	}
}

