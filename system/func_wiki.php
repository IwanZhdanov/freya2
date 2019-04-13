<?php
	function flushWiki ($sym, $q, &$oneline, $open, &$flags) {
		$el = $sym.$q;
		if (isset ($open[$el])) {
			$oneline |= $open[$el][2];
			if (!isset ($flags[$el]) || !$flags[$el]) {
				if ($open[$el][1]) $flags[$el] = true;
				return $open[$el][0];
			} else {
				$flags[$el] = false;
				return $open[$el][1];
			}
		} else {
			$s = '';
			for ($a=0;$a<$q;$a++) $s .= $sym;
			return $s;
		}
	}

	function applyWiki ($txt) {
		$open = [
			'=2'=>['<div class="h1">','</div>', true],
			'=3'=>['<div class="h3">','</div>', true],
			'=4'=>['<div class="h5">','</div>', true],
			'-4'=>['<hr />','', true],
			'*2'=>['<b>','</b>', false],
			'/2'=>['<i>','</i>', false],
			'_2'=>['<u>','</u>', false],
			'-3'=>['<strike>','</strike>', false],
			'^2'=>['<sup>','</sup>', false],
			',2'=>['<sub>','</sub>', false],
		];
		$flags = [];
		
		$ret = '';
		$lineTypes = '*#|';
		$lineType = '';
		preg_match_all ('/[ \t]*([^\r\n]+)[ \t]*/ui', $txt, $line);
		$qua = count ($line[0]);
		for ($a=0;$a<$qua;$a++) {
			$lastLineType = $lineType;
			$ln = '';
			$sym = '';
			$symq = 0;
			$str = $line[1][$a];
			if (mb_substr($str, mb_strlen($str)-1,1) == '|') $str = mb_substr($str, 0, mb_strlen($str)-1);
			$str .= ' ';
			$oneline = (mb_strpos($str, '<') !== false);
			$str = preg_replace ('/\[\[([^\]\|]*?)\|([^\]]*?)\]\]/ui','<a href="$1">$2</a>',$str);
			$q = mb_strlen ($str);
			$canTab = mb_substr($str, 0, 1) == '|';
			for ($b=0;$b<$q;$b++) {
				$ch = mb_substr ($str, $b, 1);
				if ($canTab && $ch == '|' && $b) $ch = '</td><td>';
				if ($sym == $ch) $symq++; else {
					$ln .= flushWiki ($sym, $symq, $oneline, $open, $flags);
					$sym = $ch;
					$symq = 1;
				}
			}
			$ln = str_replace (':<i>', '://', $ln);
			if ($ln) {
				if (mb_strpos ($lineTypes, $ln[0]) !== false) $lineType = $ln[0]; else $lineType = '';
				if ($lineType != $lastLineType) {
					if ($lastLineType == '#') $ret .= "</ol>\n";
					if ($lastLineType == '*') $ret .= "</ul>\n";
					if ($lastLineType == '|') $ret .= "</table></div>\n";
					if ($lineType == "#") $ret .= "<ol>\n";
					if ($lineType == "*") $ret .= "<ul>\n";
					if ($lineType == "|") $ret .= "<div class=\"tab-wiki\"><table class=\"wiki\">\n";
				}
				if ($lineType == '') {
					if (!$oneline) $ln = '<p>'.$ln.'</p>';
				} else $ln = mb_substr ($ln, mb_strlen ($lineType));
				if ($lineType == '#' || $lineType == '*') $ln = '<li>'.$ln.'</li>';
				if ($lineType == '|') $ln = '<tr><td>'.$ln.'</td></tr>';
				$ret .= $ln."\n";
			}
			$lastLineType = $lineType;
		}
		if ($lastLineType == '#') $ret .= "</ol>\n";
		if ($lastLineType == '*') $ret .= "</ul>\n";
		if ($lastLineType == '|') $ret .= "</table></div>\n";
		return $ret;
	}
