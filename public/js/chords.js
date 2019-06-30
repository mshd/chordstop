<!-- Initialize Bootstrap functionality -->

function t(a){
	console.log(a);
	return "a";
}

function getTextWidth(text, font) {
    // re-use canvas object for better performance
    var canvas = getTextWidth.canvas || (getTextWidth.canvas = document.createElement("canvas"));
    var context = canvas.getContext("2d");
    context.font = font;
    var metrics = context.measureText(text);
    return Math.round(metrics.width);
}

//wikidata_api
var wikidataSuggestion = new Bloodhound({
	datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title'),
	queryTokenizer: Bloodhound.tokenizers.whitespace,
	//prefetch: '../data/films/post_1960.json',
	remote: {
		url: 'https://www.wikidata.org/w/api.php?action=wbsearchentities&search=%QUERY&format=json&language=en&uselang=en&type=item&callback=?',
		wildcard: '%QUERY',
		filter: function (response) {
			return response.search;
		}
	}
});
var wikidataTypeahead =  {
 name: 'wikidata-suggestion',
 display: 'wikidata',
 source: wikidataSuggestion,
 templates: {
	 empty: [
		 '<div class="empty-message">',
			 'no results',
		 '</div>'
	 ].join('\n'),
	suggestion: Handlebars.compile('<div><span class="c_suggest_artist">{{ label }}</span> – {{ description }}</div>')
 }
};
function adjustchords(){
		console.log("Adjusting Chords");
		$(".rell").css("padding-right","0px")
		$('.chordline').each(function(){
			var $this = $(this);
			var $items = $this.find(".rell");
			//now next loop
			console.log("Line start");
			var chords=[];
			$.each($this.find(".relc"), function(n,e)
			{
				chords[n]=$(this).text();
			});
			console.log("test"+getTextWidth(chords));
			$.each($items, function(n, e)
			{
				var off= Math.round($(this).offset().left);
				var outer= Math.round($(this).outerWidth());
				console.log(chords[n]+"test"+getTextWidth(chords[n]));
				if(outer<45 && chords[n] ){//&& n != chords.length-1
					$(this).css("padding-right",(10 + chords[n].length*12 - outer)+"px");
				}
				console.log(off+","+outer);
					//this is each li in this ul
			});
		});
}
$(document).ready(function () {


adjustchords();
/*		$(this)('.rell').each(function(){
				var off= $(this).offset().left;
				console.log(off);

		});*/

	//console.log($('#song_search').offset().right );

    //$('[data-toggle="popover"]').popover();
		$('span.c').popover({
			//title: "Header",
			html:true,
			content:function (e,f) {
				var key=$(this).html();
				//console.log(key);
				if(defined[key] == undefined){
					return false;
				}
				else{
					//console.log(defined);
					//http://chordgenerator.net/D.png?p=xx0232&&s=3
					return '<img src="'+defined[key][2]+'" />';
	        //return defined[key][1];
	    	}
		}}); ///, placement: "top"
/*
				$('body').on('click', function (e) {
		    //did not click a popover toggle or popover
		    if ($(e.target).data('toggle') !== 'popover'
		        && $(e.target).parents('.popover.in').length === 0) {
		        $('span.c').popover('hide');
		    }
		});
		*/

if(!$('.c_section_tab').length ){
	console.log('hide');
		$('#form_toggle_tabs').hide();
}

	$('#toggle_tabs').click(function () {
    $('.c_sectiontitle_tab').toggle();
		$('.c_section_tab').toggle();
	});
	if($('.c1').length){
		$('#chord_version').toggle();
	$('#chord_version').click(function () {
		$('.c0').toggle();
		$('.c1').toggle();
	});
}
$('#toggle_chords').click(function () {
  //toggle chords
  $('.c_line_chords').toggle();
  //toggle tabs
  $('.c_section_tab').toggle();
  $('.c_sectiontitle_tab').toggle();

	//for css
	$('.relc').toggle();
});

$('#toggle_sectiontitles').click(function () {
  //toggle chords
  $('.c_sectiontitle').toggle();
  $('.c_sectionrepeat').toggle();
  if($('#toggle_insidetitles').attr('disabled')=="disabled"){
		$('#toggle_insidetitles').attr('disabled',false);

	}else{
	$('#toggle_insidetitles').attr('disabled',true);
}
});

$('#toggle_insidetitles').click(function () {
  //toggle chords

  $('.c_sectiontitle').toggle();
  $('.c_sectioninsidetitle').toggle();

	//put repeat sections at the end of each paragraph
	$('.c_sectionrepeat').toggle();
	$('.c_section_end').toggle();

});

$('#test').click(function () {
  //toggle chords
  //$.lco();
	location.href ="";
  $('.c_sectioninsidetitle').toggle();
});

$('#expandsong').click(function () {

  $('c_sectionrepeat').each(function(i, key) {

  });
});

$('.c_section').each(function(i, key) {
	if($(this).next().attr("class")=="c_sectionrepeat"){
		$('td',key).last().append('&nbsp;<span class="c_section_end">'+$(this).next().html()+'</span>');
		$('div',key).last().append('&nbsp;<span class="c_section_end">'+$(this).next().html()+'</span>');
		console.log($(this).next().html());
	}
});



//$('.c_section').$('td').last().css("background-color","red");

});

	// Initialize tooltip component
	$(function () {
	  $('[data-toggle="tooltip"]').tooltip()
	})

	// Initialize popover component
	$(function () {
	  $('[data-toggle="popover"]').popover()
	})
	$(document).ready(function () {
	  $('[data-toggle="offcanvas"]').click(function () {
		$('.row-offcanvas').toggleClass('active')
	  });
	});


/*
$('#wikidata_search').typeahead({minLength:2}, {
	name: 'wikidata-suggestion',
	display: 'wikidata',
	source: wikidataSuggestion,
	templates: {
		empty: [
			'<div class="empty-message">',
				'no results',
			'</div>'
		].join('\n'),
	 suggestion: Handlebars.compile('<div><span class="c_suggest_artist">{{ label }}</span> – {{ description }}</div>')
	}
}).bind('typeahead:selected',function(event, selectedo, dataset){
	$(this).val(selectedo.id);
});
*/

//song suggest

  var songSuggestion = new Bloodhound({
    datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title'),
    queryTokenizer: Bloodhound.tokenizers.whitespace,
    //prefetch: '../data/films/post_1960.json',
    remote: {
      url: '/api/search/%QUERY',
      wildcard: '%QUERY'
    }
  });

  $('#song_search').typeahead({minLength:2}, {
    name: 'song-suggestion',
    display: 'songs',
    source: songSuggestion,
    templates: {
      empty: [
        '<div class="empty-message">',
          'no results',
        '</div>'
      ].join('\n'),
      //suggestion: Handlebars.compile('<div><a href="/song/{{id}}"><strong>{{artist}}</strong> – {{title}}</a></div>')
     suggestion: Handlebars.compile('<div><span class="c_suggest_artist">{{artist}}</span> – {{title}}</div>')

}
  })
	.bind('typeahead:selected',function(event, selectedo, dataset){

		//if(window.location.pathname.substr(0,6)=="/admin"){
		if($('meta[name="description"]').attr("content")=="admin"){
			window.location.href = '/song/'+selectedo.entity+'/edit';

		}else if(selectedo.entity){
			window.location.href = '/song/'+selectedo.entity;
		}
	});

	//artist search_artist
	var artistSuggestion = new Bloodhound({
	  datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
	  queryTokenizer: Bloodhound.tokenizers.whitespace,
	    remote: {
	    url: '/api/search/artist/%QUERY',
	    wildcard: '%QUERY'
	  }
	});

	$('#artist_search').typeahead({minLength:2}, {
	  name: 'artist-suggestion',
	  display: 'name',
	  source: artistSuggestion,
	  templates: {
	    empty: [
	      '<div class="empty-message">',
	        'no results',
	      '</div>'
	    ].join('\n'),
	   suggestion: Handlebars.compile('<div>{{name}}</div>')

	}
})	.bind('typeahead:selected',function(event, selectedo, dataset){
		console.log(selectedo.id);
		$("#discogs_artist").val(selectedo.id);
	});
