var txtCanClose = 0;
function txtNow () {
	var x = new Date();
	return x.getTime();
}
function txtHeader () {
	var ret = '';
	ret += '<input type="checkbox" id="txtHTML" onclick="txtSwapHtml();" /><label for="txtHTML">HTML</label> ';
	ret += '<input type="checkbox" id="txtFULL" onclick="txtCoords();" /><label for="txtFULL">Во весь экран</label> ';
	ret += '<br />';
	ret += '<button onclick="document.execCommand(\'bold\', false, \'\'); " title="Жирный"><b>B</b></button>';
	ret += '<button onclick="document.execCommand(\'italic\', false, \'\'); " title="Наклонный"><i>I</i></button>';
	ret += '<button onclick="document.execCommand(\'underline\', false, \'\'); " title="Подчеркнутый"><u>U</u></button>';
	ret += '<button onclick="document.execCommand(\'strikeThrough\', false, \'\'); " title="Зачеркнутый"><s>S</s></button> ';
	ret += '<button onclick="document.execCommand(\'superscript\', false, \'\'); " title="Верхний индекс">X<sup>n</sup></button>';
	ret += '<button onclick="document.execCommand(\'subscript\', false, \'\'); " title="Нижний индекс">X<sub>n</sub></button> ';
	ret += '<button onclick="document.execCommand(\'justifyLeft\', false, \'\'); " title="Выравнивание по левому краю">&lt;--</button>';
	ret += '<button onclick="document.execCommand(\'justifyCenter\', false, \'\'); " title="Выравнивание по центру">--</button>';
	ret += '<button onclick="document.execCommand(\'justifyRight\', false, \'\'); " title="Выравнивание по правому краю">--&gt;</button>';
	ret += '<button onclick="document.execCommand(\'justifyFull\', false, \'\'); " title="Выравнивание по ширине">&lt;--&gt;</button>';
	ret += '<br />';
	//ret += '<select onchange="document.execCommand (\'fontName\', false, this.value);">';
	//	ret += '<option value="Times New Roman">Times New Roman</option>';
	//	ret += '<option value="Arial">Arial</option>';
	//	ret += '<option value="Tahoma">Tahoma</option>';
	//ret += '</select>';
	ret += '<label></label><input type="color" onchange="document.execCommand (\'foreColor\', false, this.value);" title="Цвет текста"/>';
	ret += '<label></label><input type="color" onchange="document.execCommand (\'backColor\', false, this.value);" value="#ffffff" title="Цвет фона"/>';
	ret += '<select onchange="document.execCommand (\'heading\', false, this.value); document.execCommand (\'formatBlock\', false, this.value); this.value=\'\';" title="Заголовок">';
		ret += '<option value="">H*</option>';
		ret += '<option value="H1">H1</option>';
		ret += '<option value="H2">H2</option>';
		ret += '<option value="H3">H3</option>';
		ret += '<option value="H4">H4</option>';
		ret += '<option value="H5">H5</option>';
		ret += '<option value="H6">H6</option>';
	ret += '</select> ';
//	ret += '<button onclick="document.execCommand(\'removeFormat\', false, \'\'); " >Сбросить</button> ';
	ret += '<button onclick="document.execCommand(\'insertOrderedList\', false, \'\'); " title="Нумерованый список">123</button>';
	ret += '<button onclick="document.execCommand(\'insertUnorderedList\', false, \'\'); " title="Маркированый список">***</button> ';
	ret += '<button onclick="document.execCommand(\'insertHTML\', false, \'<hr />\'); " title="Горизонтальная черта">----</button> ';
	return ret;
}
function txtSwapHtml () {
	var div1 = document.getElementById("txtModal");
	var div2 = document.getElementById("txtTextArea");
	var div4 = document.getElementById("txtAreaDiv");
	var div_ed = document.getElementById("txtArea");
	if (document.getElementById('txtHTML').checked) {
		div2.style.display = 'none';
		div4.style.display = 'block';
		div_ed.value = div2.innerHTML;
	} else {
		div2.style.display = 'block';
		div4.style.display = 'none';
		div2.innerHTML = div_ed.value;
	}
}
function txtStart (elem) {
	var div1 = document.getElementById("txtModal");
	var div2 = document.getElementById("txtTextArea");
	var div3 = document.getElementById("txtButtons");
	var div4 = document.getElementById("txtAreaDiv");
	var div_ed = document.getElementById("txtArea");
	div1.style.display = 'block';
	div3.style.display = 'block';
	var coord = elem.getBoundingClientRect();
	div2.innerHTML = elem.value;
	div2.style.backgroundColor = getComputedStyle(elem).backgroundColor;
	div2.text = elem;

	div3.style.backgroundColor = getComputedStyle(elem).backgroundColor;

	div_ed.value = elem.value;
	div4.style.backgroundColor = getComputedStyle(elem).backgroundColor;
	div4.text = elem;
	div_ed.style.backgroundColor = getComputedStyle(elem).backgroundColor;

	txtCanClose = 0;
	
	document.getElementById('txtHTML').checked=false;
	document.getElementById('txtFULL').checked=false;
	txtUseHtml = false;
	div2.style.display = 'block';
	div4.style.display = 'none';

	txtSwapHtml ();
	txtCoords ();
}
function txtCoords () {
	var div1 = document.getElementById("txtModal");
	var div2 = document.getElementById("txtTextArea");
	var div3 = document.getElementById("txtButtons");
	var div4 = document.getElementById("txtAreaDiv");
	var elem = div2.text;
	var coord = elem.getBoundingClientRect();
	
	var heigh = div3.clientHeight;
	if (document.getElementById('txtFULL').checked) {
		div2.style.position = 'fixed';
		div2.style.left = '20px';
		div2.style.top = (heigh+20)+'px';
		div2.style.width = 'initial';
		div2.style.height = 'initial';
		div2.style.right = '20px';
		div2.style.bottom = '20px';

		div3.style.position = 'fixed';
		div3.style.left = '20px';
		div3.style.top = '20px';
		div3.style.right = '20px';
		div3.style.width = 'initial';

		div4.style.position = 'fixed';
		div4.style.left = '20px';
		div4.style.top = (heigh+20)+'px';
		div4.style.width = 'initial';
		div4.style.height = 'initial';
		div4.style.right = '20px';
		div4.style.bottom = '20px';
	} else {
		div2.style.position = 'absolute';
		div2.style.left = coord.left+window.pageXOffset+'px';
		div2.style.top = coord.top+window.pageYOffset+'px';
		div2.style.width = coord.width+'px';
		div2.style.height = elem.clientHeight+'px';
		div2.style.right = 'initial';
		div2.style.bottom = 'initial';

		div3.style.position = 'absolute';
		div3.style.left = coord.left+window.pageXOffset+'px';
		div3.style.top = (coord.top+window.pageYOffset-div3.clientHeight-2)+'px';
		div3.style.right = 'initial';
		div3.style.width = coord.width+'px';

		div4.style.position = 'absolute';
		div4.style.left = coord.left+window.pageXOffset+'px';
		div4.style.top = coord.top+window.pageYOffset+'px';
		div4.style.width = coord.width+'px';
		div4.style.height = elem.clientHeight+'px';
		div4.style.right = 'initial';
		div4.style.bottom = 'initial';
	}
}
function txtCorrect () {
	var div1 = document.getElementById("txtModal");
	var div2 = document.getElementById("txtTextArea");
	var div3 = document.getElementById("txtButtons");
	var div4 = document.getElementById("txtAreaDiv");
	if (txtCanClose+30 < txtNow()) {
		div1.style.display = "none";
		div2.style.display = "none";
		div3.style.display = "none";
		div4.style.display = "none";
	}
	txtCanClose = 0;
}

function txtPrepare() {
	var arr = document.getElementsByClassName ('editor');
	var a, q = arr.length;
	for (a=0;a<q;a++) {
		arr[a].onclick = function () {txtStart(this);}
	}
	var div1 = document.createElement ('div');
	div1.id = 'txtModal';
	div1.style.cssText = 'position: fixed; left:0px; top:0px; right:0px; bottom:0px; z-index:1000; display: none;';
	div1.onclick = function() {txtCorrect();};
	var div2 = document.createElement ('div');
	div2.id = 'txtTextArea';
	div2.style.cssText = 'border:1px black solid; overflow: auto; position: absolute; z-index:1001; display:none;';
	div2.contentEditable = true;
	div2.oninput = function () {this.text.value = this.innerHTML;}
	div2.onclick = function () {txtCanClose = txtNow();}
	document.body.appendChild (div2);
	var div3 = document.createElement ('div');
	div3.id = 'txtButtons';
	div3.style.cssText = 'position: absolute; border:1px black solid; z-index:1001; display:none;';
	div3.innerHTML = txtHeader();
	div3.onclick = function () {txtCanClose = txtNow();}
	document.body.appendChild(div3);
	var div4 = document.createElement ('div');
	div4.id = 'txtAreaDiv';
	div4.style.cssText = 'border:1px black solid; overflow: hidden; position: absolute; z-index:1001; display:none;';
	var div_ed = document.createElement ('textarea');
	div_ed.id = 'txtArea';
	div_ed.style.cssText = 'overflow: auto; width: 100%; height: 100%;';
	div_ed.onkeyup = function () {this.text.value = this.value;}
	div_ed.onclick = function () {txtCanClose = txtNow();}
	div4.appendChild (div_ed);
	document.body.appendChild (div4);
	document.body.appendChild (div1);
}
window.onload = function () {txtPrepare();}
