// Переключение табов
function Tabs (group, elem) {
	var arr = document.getElementsByClassName (group);
	var a, q = arr.length;
	for (a=0;a<q;a++) arr[a].classList.remove('active');
	arr = document.getElementsByClassName (elem);
	q = arr.length;
	for (a=0;a<q;a++) arr[a].classList.add('active');
}

// Ленивая загрузка стилей
function LazyCss (src) {
	window.addEventListener ('load', function () {
		var link = document.createElement('link');
		link.href = src;
		link.rel = 'stylesheet';
		link.type = 'text/css';
		document.getElementsByTagName('head')[0].appendChild(link);
	});
}

// Ленивая загрузка изображений
function Lazy () {
 return {
  arr : [],
  init : function () {
   var arr = document.getElementsByTagName ('img');
   var a, q = arr.length;
   this.arr = [];
   for (a=0;a<q;a++) {
    this.arr[this.arr.length] = arr[a];
    arr[a].dataset.src = arr[a].src;
    arr[a].src = '';
   }
  },
  scroll: function () {
   var a, q = this.arr.length;
   var rect;
   for (a=q-1;a>=0;a--) {
    rect = this.arr[a].getBoundingClientRect();
    if (rect.top <= window.innerHeight) {
     this.arr[a].src = this.arr[a].dataset.src;
     this.arr.splice (a, 1);
    }
   }
  },
 }
}
var LazyElem = Lazy();
window.addEventListener ('load', LazyElem.init);
window.addEventListener ('load', LazyElem.scroll);
window.addEventListener ('scroll', LazyElem.scroll);

// Работа с textarea
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

// Календарик
var calendarDiv;
var calendarContainer;
var calendarElem;
var calendarTime = [0];
function showCalendar (elem) {
	elem.onkeyup = function () {calendarGetTime();}
	var coord = elem.getBoundingClientRect();
	calendarElem = elem;
	calendarContainer = document.createElement ('div');
	calendarContainer.style.cssText = 'position:fixed; left:0px; top:0px; right:0px; bottom:0px; z-index:1000;';
	calendarContainer.onclick = function(){calendarHide()};
	calendarDiv = document.createElement ('div');
	calendarDiv.className = 'calendar-div';
	calendarDiv.style.cssText = 'left:'+coord.left+'px; top:'+(coord.bottom+window.pageYOffset)+'px; z-index: 1001;';
	document.body.appendChild (calendarContainer);
	document.body.appendChild (calendarDiv);
	calendarGetTime();
}
function calendarGetTime() {
	var s = calendarElem.value;
	var reg = /\d+/gui;
	calendarTime[0] = 0;
	while (x = reg.exec(s)) {
		calendarTime[0]++;
		calendarTime[calendarTime[0]] = parseInt (x, 10);
	}
	if (calendarTime[0] < 2) calendarDraw(0,0); else calendarDraw(calendarTime[2]-1, calendarTime[1]);
}
function calendarDraw (nMonth, nYear) {
	var D = new Date();
	var today = D.getDate();
	if (nYear > 0 && (nYear != D.getFullYear() || nMonth != D.getMonth())) {
	  today = 0;
	  D.setDate(1);
	  D.setMonth(nMonth);
	  D.setFullYear(nYear);
	}
	var start = D.getDay() - D.getDate();
	while (start < 0) start += 7;
	var doms = [31,0,31,30,31,30,31,31,30,31,30,31];
	var a, dom = doms[D.getMonth()];
	if (dom == 0) {
	  dom = 28;
	  if (D.getFullYear() % 4 == 0) dom = 29;
	  if (D.getFullYear() % 100 == 0) dom = 28;
	  if (D.getFullYear() % 400 == 0) dom = 29;
	}
	var cl, ret = '';
	var mon = ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
	var m1 = D.getMonth()-1, y1 = D.getFullYear();
	var m2 = D.getMonth()+1, y2 = D.getFullYear();
	if (m1 < 0) {m1+=12; y1--;}
	if (m2 > 11) {m2-=12; y2++;}
	var capt = '';
	capt += '<a href="javascript:" onclick="calendarDraw('+m1+','+y1+');" style="float:left;">←</a>';
	capt += '<a href="javascript:" onclick="calendarDraw('+m2+','+y2+');" style="float:right;">→</a>';
	capt += mon[D.getMonth()]+' '+D.getFullYear();
	ret += '<table class="calendar">';
	ret += '<tr><th colspan="7">'+capt+'</th></tr>';
	ret += '<tr><th>Пн</th><th>Вт</th><th>Ср</th><th>Чт</th><th>Пт</th><th>Сб</th><th>Вс</th></tr>';
	ret += '<tr>';
	if (start > 0) ret += '<td colspan="'+start+'"></td>';
	for (a=1;a<=dom;a++) {
	  start++;
	  if (start > 7) {
		start -= 7;
		ret += '</tr><tr>';
	  }
	  cl = 'day ';
	  if (start > 5) cl += 'holiday ';
	  if (a == today) cl += 'today ';
	  ret += '<td class="'+cl+'" onclick="calendarSelected('+a+','+D.getMonth()+','+D.getFullYear()+', true);">'+a+'</td>';
	}
	ret += '</tr>';
	ret += '</table>';
	calendarDiv.innerHTML = ret;
}
function calendarSelected (day, month, year, flag) {
	if (calendarTime[0] >= 4) hour = calendarTime[4]; else hour = 0;
	if (calendarTime[0] >= 5) minute = calendarTime[5]; else minute = 0;
	var ret = '';
	ret = year+'-';
	if (month < 9) ret += '0';
	ret += (month+1) + '-';
	if (day < 10) ret += '0';
	ret += day + ' ';
	if (hour < 10) ret += '0';
	ret += hour + ':';
	if (minute < 10) ret += '0';
	ret += minute;
	calendarElem.value = ret;
	if (flag) calendarHide ();
}
function calendarHide () {
	document.body.removeChild (calendarContainer);
	document.body.removeChild (calendarDiv);
}


