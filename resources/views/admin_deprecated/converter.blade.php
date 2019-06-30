@extends('layouts.master')

@section('content')
<h1>Welcome</h1>
<div>
<p>Reads a &ldquo;position formatted&rdquo; song (chords on separate line above the lyrics) and converts it to a basic ChordPro format -- chords merged inline with the lyrics.</p>
<input type="button" class="button green" id="runTestBtn" value="Run Formater" />
<input type="button" class="button green" id="runDeleBtn" value="Delete Chords" />

<p style="padding-top:.4em;"><input type="checkbox" checked="checked" id="useStandard" value="true" /> <label for="useStandard">Use only common chords (safest &amp; most reliable - recommended)</label></p>



  <div class="container-fluid">
    <div class="row row-offcanvas row-offcanvas-right">
    <div class="col-12 col-md-6">
      <textarea id="inputSong" class="demoInput" wrap="off" style="width:100%">

</textarea>
</div>
<div class="col-12 col-md-6">
  <textarea id="outputSong" class="mergedOutput" wrap="off" style="width:100%"></textarea>
</div>
</div>
<div id="echoOutput" class="echoOutput"></div>


	<div class="preFooter"></div>
</section>

<script type="text/javascript">
/*!
 * Uke Geeks . Namespace
 */
var uke = window.uke ? uke : {};

/**
 * Entity namespace for Classes and Enums.
 * @class data
 * @namespace uke
 */
uke.data = new function() {
	this.lineTypes = {
		blank: 0,
		chords: 1,
		lyrics: 2,
		tabs: 3
	};

	/**
	 *
	 * class line
	 * for uke.data
	 * namespace uke.data
	 */
	this.line = function() {
		this.source = '';
		this.sourceLength = 0;
		this.wordCount = 0;
		this.spaceCount = 0;
		this.words = [];
		this.capCount = 0;
		this.average = new uke.data.average();
		this.lineType = uke.data.lineTypes.blank;
	};

	this.average = function() {
		this.mean = 0;
		this.variance = 0;
		this.deviation = 0;
	};
}();

/*!
 * Uke Geeks . Auto Sort
 * activate tablesorter
 */
uke.cpmImport = new function() {
	/*
		I'm saying a line of chords is any line that:
		+ isn't blank (duh)
		+ has average word length under 4
		+ the word length standard deviation is uner 0.75
		+ has at least one capitalized root note (A - G)
	*/

	var re = {
		words: /\b(\S+)\b/gi,
		spaces: /(\s+)/g,
		leadingSpace: /(^\s+)/,
		caps: /[A-G]/g,
		chrdBlock: /\b(\S+\s*)/g,
		tabs: /^\s*(\|{0,2}[A-Gb]?\|{0,2}[-x0-9|:]{4,})/
	};
	// /(\s*\w+)\b/g

	/**
	 * Accepts a text block, returns "ChordPro" text block with chord lines merged into lyric lines with chords enclosed in square-brackets (i.e. [Cdim])
	 * @method reformat
	 * @param text {string} songstring
	 * @return {string} ChordPro format text block
	 */
	this.reformat = function(text) {
		// console.log('reformat ' + text.length + ' characters.');
		var lines = _read(text);
		_echoDebug(lines);
		return _merge(lines);
	};

	/**
	 * Accepts a text block
	 * @method _read
	 * @param text {string} string RAW song
	 * @return {array of Lines}
	 */
	var _read = function(text) {
		var lineAry = [];
		text = text.replace('	', '    ');
		var lines = text.split('\n');
		for (var i = 0; i < lines.length; i++) {
			var words = lines[i].match(re.words);
			//console.log('wordCount : ' + words );
			var l = new uke.data.line();
			l.source = lines[i];
			if ((words != null) && (words.length > 0)) {
				l.wordCount = words.length;
				l.words = words;
				l.average = _average(words);
				l.capCount = _countChordCaps(words);
			}
			var spaces = lines[i].match(re.spaces);
			//console.log('spaces : ' + spaces );
			if ((spaces != null) && (spaces.length > 0)) {
				l.spaceCount = spaces.length;
			}
			l.lineType = _toLineType(l);
			lineAry.push(l);
		}
		return lineAry;
	};

	/**
	 * Guesses as to the line's tyupe --
	 * @method _toLineType
	 * @param line {line}
	 * @return {uke.data.lineTypes}
	 */
	var _toLineType = function(line) {
		if ((line.spaceCount + line.wordCount) < 1) {
			return uke.data.lineTypes.blank;
		}

		var tabs = line.source.match(re.tabs);
		if (tabs != null) {
			return uke.data.lineTypes.tabs;
		}

		var t = uke.data.lineTypes.lyrics;
		if ((line.capCount > 0) && (line.wordCount == line.capCount)) {
			t = uke.data.lineTypes.chords;
		}
		/*
		if ((line.average.mean < 4) && (line.average.deviation < 0.75) && (line.capCount > 0)){
			t = uke.data.lineTypes.chords;
		}
		*/
		return t;
	};

	/**
	 * Return average info for a given array based on http://jsfromhell.com/array/average
	 * @method _average
	 * @param lines {array of Lines}
	 * @return [void]
	 */
	var _average = function(sourceArray) {
		var r = new uke.data.average(); // {mean: 0, variance: 0, deviation: 0};
		var t = sourceArray.length;
		for (var m, s = 0, l = t; l--; s += sourceArray[l].length);
		for (m = r.mean = s / t, l = t, s = 0; l--; s += Math.pow(sourceArray[l].length - m, 2));
		return r.deviation = Math.sqrt(r.variance = s / t), r;
	};

	/**
	 * Looks for capitalized chord root note: A, B, C... G
	 * @method _countChordCaps
	 * @param words {array of words}
	 * @return [int] number found
	 */
	var _countChordCaps = function(words) {
		var count = 0;
		for (var i = 0; i < words.length; i++) {
			if (words[i].match(re.caps)) {
				count++;
			}
		}
		return count;
	};

	/**
	 * Return merged song -- chords embedded within lyrics
	 * @method _merge
	 * @param lines {array of Lines}
	 * @return [string]
	 */
	var _merge = function(lines) {
		var s = '';
		var thisLine, nextLine;
		for (var i = 0; i < lines.length;) {
			thisLine = lines[i];
			nextLine = lines[i + 1];
			i++;
			// If this line's blank or its the last line...
			if (!nextLine || (thisLine.lineType == uke.data.lineTypes.blank)) {
				s += thisLine.source + '\n';
				continue;
			}

			// OK, we've complicated things a bit by adding tabs, so we'll handle this in a helper...
			if ((thisLine.lineType == uke.data.lineTypes.tabs) && _isTabBlock(lines, i)) {
				//console.log(thisLine.source);
				s += '{start_of_tab}\n' + thisLine.source.replace(re.leadingSpace, '') + '\n' + nextLine.source.replace(re.leadingSpace, '') + '\n' + lines[i + 1].source.replace(re.leadingSpace, '') + '\n' + lines[i + 2].source.replace(re.leadingSpace, '') + '\n' + '{end_of_tab}\n';
				i += 3;
				continue;
			}

			// finally, look for a "mergable" pair: this line is chords and the next is lyrics -- if not this we'll just output the current line
			if ((thisLine.lineType != uke.data.lineTypes.chords) || (nextLine.lineType != uke.data.lineTypes.lyrics)) {
				s += (thisLine.lineType == uke.data.lineTypes.chords) ? _wrapChords(thisLine.source) + '\n' : thisLine.source + '\n';
				continue;
			}

			// OK, if you're here it's because the current line is chords and the next lyrics, meaning, we're gonna merge them!
			i++;
			s += _mergeLines(thisLine.source, nextLine.source) + '\n';
		}
		return s;
	};

	/**
	 * TRUE if we can make a Tab block using this and the following 3 linrd (we need a set of four tab lines followed by a non-tab line)
	 * @method _isTabBlock
	 * @param lines {array of Lines}
	 * @param index {int} current line's index within line array
	 * @return [bool]
	 */
	var _isTabBlock = function(lines, index) {
		if (index + 3 >= lines.length) {
			return false;
		}
		for (var i = index; i < index + 3; i++) {
			if (lines[i].lineType != uke.data.lineTypes.tabs) {
				return false;
			}
		}
		return true;
	};

	/**
	 * Return a single line
	 * @method _mergeLines
	 * @param chordLine {string} the line containing the chord names
	 * @param lyricsLine {string} the line of lyrics
	 * @return [string] merged lines
	 */
	var _mergeLines = function(chordLine, lyricsLine) {
		while (lyricsLine.length < chordLine.length) {
			lyricsLine += ' ';
		}
		var s = '';
		var blocks = chordLine.match(re.chrdBlock);
		var lead = chordLine.match(re.leadingSpace);
		var offset = 0;
		if (lead) {
			s += lyricsLine.substr(offset, lead[0].length);
			offset = lead[0].length;
		}
		for (var j = 0; j < blocks.length; j++) {
			s += '[' + blocks[j].replace(re.spaces, '') + ']' + lyricsLine.substr(offset, blocks[j].length);
			offset += blocks[j].length;
		}
		if (offset < lyricsLine.length) {
			s += lyricsLine.substr(offset, lyricsLine.length);
		}
		return s;
	};

	/**
	 * Wraps the words on the line within square brackets " C D " is returned as "[C] [D]"
	 * @method _wrapChords
	 * @param chordLine {string} the line containing the chord names
	 * @return [string] each word of input line gets bracketed
	 */
	var _wrapChords = function(chordLine) {
		var chords = chordLine.replace(re.spaces, ' ').split(' ');
		var s = '';
		for (var i = 0; i < chords.length; i++) {
			if (chords[i].length > 0) {
				s += '[' + chords[i] + '] ';
			}
		}
		return s;
	};

	/**
	 * Line array
	 * @method _echoDebug
	 * @param lines {array of Lines}
	 * @return [void]
	 */
	var _echoDebug = function(lines) {
		var out = '';
		var lineType = '';
		for (var i = 0; i < lines.length; i++) {
			lineType = _typeToString(lines[i].lineType);
			out += '<li class="' + lineType + 'Type">';
			out += '<pre>' + lines[i].source + ' </pre>';
			out += '<p>type: <strong>' + lineType + '</strong>';
			if (lines[i].lineType != uke.data.lineTypes.blank) {
				out +=
					', words: <em>' + lines[i].wordCount + '</em>, spaces: <em>' + lines[i].spaceCount + '</em>, caps: <em>' + lines[i].capCount + '</em>,  average: <em>' + _decToString(lines[i].average.mean) + '</em>, std dev: = <em>' + _decToString(lines[i].average.deviation) + '</em>';
			}
			out += '</p></li>';
			// console.log(i);
			// console.log(lines[i].source)
			// console.log(lines[i].wordCount + ' words & ' + lines[i].spaceCount + ' spaces. average: ' + lines[i].average.mean + ' std dev: = ' + lines[i].average.deviation);
		}
		var o = document.getElementById('echoOutput');
		o.innerHTML += '<ol>' + out + '</ol>';
	};

	var _decToString = function(val) {
		return val.toString().substr(0, 6);
	};

	var _typeToString = function(val) {
		switch (val) {
			case uke.data.lineTypes.chords:
				return 'chords';
			case uke.data.lineTypes.lyrics:
				return 'lyrics';
			default:
				return 'blank';
		}
	};
}();

var ugsEditorPlus = window.ugsEditorPlus || {};

/**
 * TK
 * @class reformat
 * @namespace ugsEditorPlus
 * @singleton
 */
ugsEditorPlus.reformat = (function() {
	/**
	 * attach public members to this object
	 * @property _public
	 * @type JsonObject
	 */
	var _public = {};

	var _hasChords = false;

	/**
	 *
	 * @property _enums
	 * @private
	 */
	var _enums = {
		lineTypes: {
			blank: 0,
			chords: 1,
			lyrics: 2,
			tabs: 3
		}
	};

	/**
	 * Line Object Class Definition (sets defaults)
	 * lass reformat.LineObj
	 * prvate
	 * constructor
	 * for reformat
	 */
	var LineObj = function() {
		this.source = '';
		this.wordCount = 0;
		this.spaceCount = 0;
		this.words = [];
		this.chordCount = 0;
		this.lineType = _enums.lineTypes.blank;
	};

	var _re = {
		words: /\b(\S+)\b/gi,
		spaces: /(\s+)/g,
		leadingSpace: /(^\s+)/,
		chordNames: /\b[A-G][#b]?(m|m6|m7|m9|dim|dim7|maj7|sus2|sus4|aug|6|7|9|add9|7sus4)?\b/,
		chrdBlock: /\b(\S+\s*)/g,
		tabs: /^\s*(\|{0,2}[A-Gb]?\|{0,2}[-x0-9|:]{4,})/
	};

	// Hal Leonard Uke Chord Finder:
	// + aug
	// o dim
	// -----------------
	// F Fm F+ Fdim
	// F5 Fadd9 Fm(add9) Fsus4
	// Fsus2 F6 Fm6 Fmaj7
	// Fmaj9 Fm7 Fm(maj7) Fm7b5
	// Fm9 Fm11 F7 Fsus4
	// F+7 F7b5 F9 F7#9
	// F7b9 F11 F13 Fdim7

	/**
	 * Accepts a text block, returns "ChordPro" text block with chord lines merged into lyric lines with chords enclosed in square-brackets (i.e. [Cdim])
	 * @method run
	 * @public
	 * @param text {string} songstring
	 * @return {string} ChordPro format text block
	 * for reformat
	 */
	_public.run = function(text) {
		_hasChords = false;
		var lines = read(text);
		return merge(lines);
	};

	/**
	 * TRUE if one or more chord lines detected
	 * @method hasChords
	 * @return {bool}
	 */
	_public.hasChords = function() {
		return _hasChords;
	};

	/**
	 * Accepts a text block
	 * @method read
	 * @param text {string} string RAW song
	 * @return {array of Lines}
	 */
	var read = function(text) {
		var lineAry = [];
		text = text.replace('	', '    ');
		var lines = text.split('\n');
		for (var i = 0; i < lines.length; i++) {
			var words = lines[i].match(_re.words);
			var l = new LineObj();
			l.source = lines[i];
			if ((words != null) && (words.length > 0)) {
				l.wordCount = words.length;
				l.words = words;
				l.chordCount = countChords(words);
			}
			var spaces = lines[i].match(_re.spaces);
			if ((spaces != null) && (spaces.length > 0)) {
				l.spaceCount = spaces.length;
			}
			l.lineType = toLineType(l);
			lineAry.push(l);
		}
		return lineAry;
	};

	/**
	 * Guesses as to the line's tyupe --
	 * @method toLineType
	 * @param line {line}
	 * @return {_enums.lineTypes}
	 */
	var toLineType = function(line) {
		if ((line.spaceCount + line.wordCount) < 1) {
			return _enums.lineTypes.blank;
		}

		var tabs = line.source.match(_re.tabs);
		if (tabs != null) {
			return _enums.lineTypes.tabs;
		}

		var t = _enums.lineTypes.lyrics;
		if ((line.chordCount > 0) && (line.wordCount == line.chordCount)) {
			t = _enums.lineTypes.chords;
			_hasChords = true;
		}

		return t;
	};

	/**
	 * Looks for supported chords.
	 * @method countChords
	 * @param words {array of words}
	 * @return [int] number found
	 */
	var countChords = function(words) {
		var count = 0;
		for (var i = 0; i < words.length; i++) {
			if (words[i].match(_re.chordNames)) {
				count++;
			}
		}
		return count;
	};

	/**
	 * Return merged song -- chords embedded within lyrics
	 * @method merge
	 * @param lines {array of Lines}
	 * @return [string]
	 */
	var merge = function(lines) {
		var s = '';
		var thisLine, nextLine;
		for (var i = 0; i < lines.length;) {
			thisLine = lines[i];
			nextLine = lines[i + 1];
			i++;
			// If this line's blank or its the last line...
			if (!nextLine || (thisLine.lineType == _enums.lineTypes.blank)) {
				s += thisLine.source + '\n';
				continue;
			}

			// OK, we've complicated things a bit by adding tabs, so we'll handle this in a helper...
			if ((thisLine.lineType == _enums.lineTypes.tabs) && isTabBlock(lines, i)) {
				s += '{start_of_tab}\n' + thisLine.source.replace(_re.leadingSpace, '') + '\n' + nextLine.source.replace(_re.leadingSpace, '') + '\n' + lines[i + 1].source.replace(_re.leadingSpace, '') + '\n' + lines[i + 2].source.replace(_re.leadingSpace, '') + '\n' + '{end_of_tab}\n';
				i += 3;
				continue;
			}

			// finally, look for a "mergable" pair: this line is chords and the next is lyrics -- if not this we'll just output the current line
			if ((thisLine.lineType != _enums.lineTypes.chords) || (nextLine.lineType != _enums.lineTypes.lyrics)) {
				s += (thisLine.lineType == _enums.lineTypes.chords) ? wrapChords(thisLine.source) + '\n' : thisLine.source + '\n';
				continue;
			}

			// OK, if you're here it's because the current line is chords and the next lyrics, meaning, we're gonna merge them!
			i++;
			s += mergeLines(thisLine.source, nextLine.source) + '\n';
		}
		return s;
	};

	/**
	 * TRUE if we can make a Tab block using this and the following 3 linrd (we need a set of four tab lines followed by a non-tab line)
	 * @method isTabBlock
	 * @param lines {array of Lines}
	 * @param index {int} current line's index within line array
	 * @return [bool]
	 */
	var isTabBlock = function(lines, index) {
		if (index + 3 >= lines.length) {
			return false;
		}
		for (var i = index; i < index + 3; i++) {
			if (lines[i].lineType != _enums.lineTypes.tabs) {
				return false;
			}
		}
		return true;
	};

	/**
	 * Return a single line
	 * @method mergeLines
	 * @param chordLine {string} the line containing the chord names
	 * @param lyricsLine {string} the line of lyrics
	 * @return [string] merged lines
	 */
	var mergeLines = function(chordLine, lyricsLine) {
		while (lyricsLine.length < chordLine.length) {
			lyricsLine += ' ';
		}
		var s = '';
		var blocks = chordLine.match(_re.chrdBlock);
		var lead = chordLine.match(_re.leadingSpace);
		var offset = 0;
		if (lead) {
			s += lyricsLine.substr(offset, lead[0].length);
			offset = lead[0].length;
		}
		for (var j = 0; j < blocks.length; j++) {
			s += '[' + blocks[j].replace(_re.spaces, '') + ']' + lyricsLine.substr(offset, blocks[j].length);
			offset += blocks[j].length;
		}
		if (offset < lyricsLine.length) {
			s += lyricsLine.substr(offset, lyricsLine.length);
		}
		return s;
	};

	/**
	 * Wraps the words on the line within square brackets " C D " is returned as "[C] [D]"
	 * @method wrapChords
	 * @param chordLine {string} the line containing the chord names
	 * @return [string] each word of input line gets bracketed
	 */
	var wrapChords = function(chordLine) {
		var chords = chordLine.replace(_re.spaces, ' ').split(' ');
		var s = '';
		for (var i = 0; i < chords.length; i++) {
			if (chords[i].length > 0) {
				s += '[' + chords[i] + '] ';
			}
		}
		return s;
	};

	// ---------------------------------------
	// return public interface
	// ---------------------------------------
	return _public;

}());




var runFormater = function(){
	var MIN_HEIGHT = 225; // px

	var resizeText = function(textElement){
		textElement.style.height = MIN_HEIGHT + "px";
		var h = parseInt(textElement.scrollHeight);
		h = (h < MIN_HEIGHT) ? MIN_HEIGHT : h;
		textElement.style.height = h + "px";
	};

	var songEle = document.getElementById('inputSong');
	var outputEle = document.getElementById('outputSong');
	// document.getElementById('echoOutput').innerHTML = '';

	var isUseStandard = document.getElementById('useStandard').checked;
	//console.log(isUseStandard);
  var song=songEle.value;
  song.replace("/Intro:/g", "{soi}");
	var formatted = (isUseStandard)
		? ugsEditorPlus.reformat.run(song)
		: uke.cpmImport.reformat(song);
	outputEle.value = formatted;

	resizeText(songEle);
	resizeText(outputEle);
	//document.getElementById('analysisOutput').style.display = isUseStandard ? 'none' : 'block';

};

var btn = document.getElementById('runTestBtn');
if (btn){
	btn.onclick = function(){ runFormater(); };
};
runFormater();

var btnb = document.getElementById('runDeleBtn');
if (btnb){
	btnb.onclick = function(){
    var text = document.getElementById('outputSong').value.replace(/\[[^\]]*] */g, "[]");
                                                                   // *\[[^\]]*]/
 //    var text = document.getElementById('outputSong').value.replace(/ *\[[^\]]*\] */g, "[]");

    document.getElementById('outputSong').value = text;
    //console.log(text);
  };
};


//.replace(/ *\([^)]*\) */g, "");
</script>
@stop
