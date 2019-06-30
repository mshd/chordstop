<?php
namespace App\Libraries;

class ChordsClass {

//for two uses
//to validate the commands, make sure there no unknown commands
//to convert it to a more proper format, including adding {eoc}
private $_directives = array(
 //name  => [count,isopen,showntext,chords[]]
 'tab'	     => [0,false,"Tab: ",array(),"t"],
 'verse'	   => [0,false,"Verse: ",array(),"v"],
 'prechorus' => [0,false,"Pre-Chorus: ",array(),"p"],
 'chorus'    => [0,false,"Chorus: ",array(),"c"],
 'bridge'    => [0,false,"Bridge: ",array(),"b"],
 'outro'     => [0,false,"Outro: ",array(),"o"],
 'intro'	   => [0,false,"Intro: ",array(),"i"],
 'refrain'   => [0,false,"Refrain: ",array(),"r"],
 'solo'      => [0,false,"Solo: ",array(),"s"],
 'interlude' => [0,false,"Interlude:",array(),"l"],
 'nosection' => [0,false,false,array(),"n"],
);

public function parse($body,$rows=2){
  $parsed= $this->Chord_preparse($body);
  $chord = $this->ChordPro_Parse(explode("\n",$parsed['body']),["linebreak"=>$rows]);
  $chord["json"]=json_encode($chord['define']);
  return $chord;
}

public function Chord_preparse($body){

//define directives
 $directives = $this->_directives;
 $directives_options = array(
   //Display comments
   'comment' => '',
   //Only once in the beggining
   'capo'    => false,
   //define chords in the beggining ie. {define:C/D 032210}
   'define'  => array(),
   //song key, only once
   'key'     => false,
   'tempo'   => false,
 );
 $current_section = false;
 $chords = array();
 $body = str_replace("][","] [",$body);
 $cho = explode("\n",$body);

 $section_chord_no = 0;

 $retval ="";
 $linecount = count($cho);
   if( $linecount < 1 ) {
   	return ['error'=> __('validation.required',['attribute'=>'body']) ];
   }


//go trough each line
  for( $i=0; $i<$linecount; $i++ ) {
    # iterate through the ChordPro lines
    $choline = $cho[$i];

    $matches = array();
    $directive = '';
    $directiveargument = '';

    //TODO little confusion with \r
    if(@$choline{0}=="\r"){$choline = substr($choline,1);}
    $choline = str_replace(array("{chorus"),array("{roc}"),$choline);
    if( preg_match( '/\{([^:]+?)(:([^}]+?))?\}/', $choline, $matches ) ) {
      # get directive
      $directive = trim($matches[1]);
      if( count($matches) > 2 ) {
        $directiveargument = trim($matches[3]);
      } else {
        $directiveargument = '';
      }
    }
    //echo $choline."\n";
    //strlen($choline)<3 is added, because of sectionproblems due to short lines
    if( @$choline{0} == '#' || @substr($choline,0,2) == '//' ||$choline == '' || strlen($choline)<3 ) {
      # strip out code comments
      continue;

    } else if( strlen($directive)>0 ) {
        # process directives
   		$foundd=false;

   		foreach($directives as $name => $attr){
        //$name1 = (isset($attr[4])?$attr[4]:substr($name,0,1));
   			if( !strcasecmp($directive,'so'.$attr[4]) ||
            !strcasecmp($directive,'start_of_'.$name)
        	) {
        		$foundd=true;
        		if($name != "tab"){
   				foreach($directives as $name_ => $attr_){
		      		if($attr_[1]){//if is_open
		      			$retval .= '{eo'.$attr_[4].'}'. "\n";
		      			$directives[$name_][1] = false; #mark that we left the section
		      		}
		      	}}
			    $retval .= '{so'.$attr[4].($directiveargument?':'.$directiveargument:'')."}\n";
			    $current_section = $name;
				$section_chord_no= 0;
			    $directives[$name][1] = true; #mark that we are in the section
          $directives[$name][0]++; #increment count section
		    }

   			//end
   			if( !strcasecmp($directive,'eo'.$attr[4]) ||
            !strcasecmp($directive,'end_of_'.$name)
        	) {
        	$foundd=true;
		      	# it's a end of a #name block
		      	if( $attr[1] ) {
		        $retval .= '{eo'.$attr[4].'}'. "\n";
		      	$current_section = false;
				    $directives[$name][1] = false; #mark that we left the section
		      	}
		    }

		    //repetition
   			if( !strcasecmp($directive,'ro'.$attr[4]) ||
            !strcasecmp($directive,'repeat_of_'.$name)
        	) {

            //error handeling: check if section already exists, if not throw error
            foreach($directives as $name_ => $attr_){
              if($name_ == $name || $attr[4] == $attr_[4]){
                //echo 'ja';
                if($attr_[0] == 0){
                  return ['error'=> __('validation.regex',['attribute'=>'(Repeat of non initalized section)'])];
                }
              }
            }
            //end error handeling

        		$foundd=true;
        		if($name != "tab"){
   				  foreach($directives as $name_ => $attr_){
		      		if($attr_[1]){
		      			$retval .= '{eo'.substr($name_,0,1). '}'. "\n";
	   		      		$current_section = false;
		      			$directives[$name_][1] = false; #mark that we left the section
		      		}
		      	}
            }
			    $retval .= '{ro'.$attr[4]. ($directiveargument?':'.$directiveargument:'') .'}'. "\n";
			    //$directives[$name][0] = true; #mark that we are in the section
		    }
   		}//end foreach


        if( !strcasecmp($directive,'c') ||
             !strcasecmp($directive,'comment') ||
             !strcasecmp($directive,'key') ||
             !strcasecmp($directive,'define') ||
             !strcasecmp($directive,'tempo') ||
             !strcasecmp($directive,'st') ||
             !strcasecmp($directive,'capo') ) {
            # it's a comment line
            if( strlen($directiveargument) > 0 ) {
              $retval .= '{'.$directive.':'.$directiveargument.'}' . "\n";
              $foundd = true;
            }
            else{
              return ['error'=> __('validation.required',['attribute'=>'directiveargument  (' . $directive . ')']) ];
            }

        }
      if(!$foundd){
          # unknown directive
          return ['error'=> __('validation.not_in',['attribute'=>'directive "' . $directive .
            (strlen($directiveargument)>0?':'.$directiveargument.'':''). '"']) ];
        }


      }
      else if( $directives['tab'][1] ) {
          $retval .= $choline."\n";
      }
      else if( strlen(trim($choline)) > 0 ) {
        # format lyrics with embedded chords
        //$retval .= $choline;

        //optional: we are not in any section
        if(!$current_section){
          $retval .= "{son}\n";
          $current_section = "nosection";
          $section_chord_no= 0;
          $directives["nosection"][1] = true; #mark that we are in the section
          $directives["nosection"][0]++;
        }

		  $inchord = false;
		  $line_chords = array();
		  $lyrics = array();
		  $col = -1;
		  //array_push( $line_chords, '' );
		  //array_push( $lyrics, '' );
		  for( $j = 0; $j < strlen($choline); $j++ ) {
			$ch = $choline{$j};
			if($ch=='[') {
			  # at start of chord boundry
				$inchord = true;
				$col++;
				array_push( $line_chords, '' );
				//array_push( $lyrics, '' );
				$retval .= "[";
			} else if($ch==']') {
			  # at end of chord boundry
				$inchord = false;

        //debug if ] is found
        if(!isset($line_chords[$col])){
          return ['error'=> __('validation.regex',['attribute'=>'"]" (line '.$i.')'])];
        }

        #if chord is empty
				if(!$line_chords[$col]){
          $temp_chords = $directives[($current_section?$current_section:'nosection')][3];
          if(!count($temp_chords)){
            return ['error'=> __('validation.required',['attribute'=>'Unknown Chord in Line '.$i.' ('.$choline])];
          } elseif($section_chord_no>=count($temp_chords)){
            //start from the beggining
            $section_chord_no = 0;
          }
          $line_chords[$col] = $temp_chords[$section_chord_no];
					$retval .= $line_chords[$col] ;
				}
				$retval .= "]";
				$section_chord_no++; #next chord in section;
			} else {
			  # not at chord boundry
  			  if($inchord) {
    				$line_chords[$col] .= $ch;
    				$retval .= $ch;
  			  } else {
    				//$lyrics[$col] .= $ch;
    				$retval .= $ch;
  			  }
			}
		  }
		  $retval .= "\n";
		  if($current_section){
		  	$directives[$current_section][3] = array_merge($directives[$current_section][3],$line_chords);
		  }else{
        $directives['nosection'][3] = array_merge($directives['nosection'][3],$line_chords);
      }


      } else {
        # pass through unprocessed unknown text format
        # tacking on a trailing <br/>\n was making PmWiki replace with
        # a <p><br/></p> block in weird places...
        $retval .= $choline;
      }

   }//end for loop

   //check for open sections and close them
   foreach($directives as $name_ => $attr_){
       if($attr_[1]){
         $retval .= '{eo'.substr($name_,0,1). '}';
         $directives[$name_][1] = false; #mark that we left the section
       }
     }


     return ['error'=>false,'directives'=>$directives,'body'=>$retval];




 }

 private function ChordPro_section($name,$start){


 return '<div class="songverse">' . "\n";

 }

 public function ChordPro_Parse($cho,$given = array()) {
//$cho is an array
    $options = array(
      'output'    => array_key_exists("output",$given)?$given["output"]:"table",
      //use bootstrap to add two lines into one, set 0 or 2 or 3
      'linebreak' => array_key_exists("linebreak",$given)?$given["linebreak"]:2,
      //compress repeat section
      'repeats'   => false,
      'linebreak_open' =>false,
    );
  //dd($options);
  //die(var_dump($cho));
  if($options['repeats']){
    //$cho = preg_replace('/\{(ro[^}]+?)?\}/',
    //('/\{([^:]+?)(:([^}]+?))?\}/'
  }
  $debugon=false;
  $retval = '';
  $inchorus = false;
  $inverse = false;
  $intab = false;
  $chordblocks = array();
  $tables=array();
  $linecount = count($cho);
  //$chordkey=false;
  //$chordcapo=0;
  $chord=array(
    "key" => false,
    "capo" => 0,
    "define" => array(),
    "versions" => 0,
  );
  $versions=0;
  $directives = $this->_directives;


  $directives["verse"][2]=false;
  if( $linecount <= 1 ) {
    # TODO: debugging message, replace me with something intelligent
    return '<p><h1>Error: line count = ' . $linecount . '!</h1></p>';
  }

  $section_line = 0;
  for( $i=0; $i<$linecount; $i++ ) {
    # iterate through the ChordPro lines
    $choline = $cho[$i];
    $matches = array();
    $directive = '';
    $directiveargument = '';
    if( @$choline{0} == '#' ) {
      # strip out code comments
      continue;
    }
    else if( preg_match( '/\{([^:]+?)(:([^}]+?))?\}/', $choline, $matches ) ) {
      # get directive
      $directive = trim($matches[1]);
      if( count($matches) > 2 ) {
        $directiveargument = trim($matches[3]);
      }
      //reset section linecount
      $section_line = 0;
      //go trough sections
      foreach($directives as $name => $attr){

        //starting of a section
   			if( !strcasecmp($directive,'so'.$attr[4]) ||
            !strcasecmp($directive,'start_of_'.$name)
        	) {

            $divstyle="";
            if(@$cho[$i+1]{0}=="!" && @$cho[$i+2]{0}=="{"){
              $divstyle = " c_nobreak";
            }


				    $retval .= '<div class="c_sectiontitle c_sectiontitle_'.$name.'">'.$attr[2] . ($directiveargument?'('.$directiveargument.')':'').
            '</div>
            <div class="container-fluid c_section c_section_'.$name.$divstyle.'">';
				    $directives[$name][1] = true; #mark that we are in the section
            $directives[$name][0]++;
		    }
   			//end of a section
   			if( !strcasecmp($directive,'eo'.$attr[4]) ||
            !strcasecmp($directive,'end_of_'.$name)
        	) {
        	  $foundd=true;
				    $retval .= '</div><!-- end '.$name.' block -->'."\n";
				    $directives[$name][1] = false; #mark that we left the section
		    }

        //repeat of section_chord_no//repetition
   			if( !strcasecmp($directive,'ro'.$attr[4]) ||
            !strcasecmp($directive,'repeat_of_'.$name)
        	) {
              $retval .= '<!-- reapeat of '.$name.'  --><div class="c_sectionrepeat">('.$name.
                ($directiveargument?' '.$directiveargument:'');
              if($options['repeats']){
                //if the next line also features a repeat
                while(@substr($cho[$i+1],0,3)=="{ro"){
                  $retval .= " + ".substr($cho[$i+1],3,5);
                  $i++;
                }
              }
              $retval .= ')</div>';
            }
   		}//end foreach

   		if( !strcasecmp($directive,'c') ||
                   !strcasecmp($directive,'comment') ) {
          # it's a comment line
          if( strlen($directiveargument) > 0 ) {
            $retval .= '<div class="c_comment"><span class="c_comment_simple">' .
                       $directiveargument . '</span></div>' . "\n";
          }

        } else if( !strcasecmp($directive,'key')  ) {
          # it's a key comment line
          if( strlen($directiveargument) > 0 ) {
            $chord["key"] =  $directiveargument;
          }
        } else if( !strcasecmp($directive,'capo')  ) {
          # it's a key comment line
          if( strlen($directiveargument) > 0 ) {
            $chord["capo"] =  $directiveargument;
          }
        } else if( !strcasecmp($directive,'define')  ) {
          # it's a define line
          if( strlen($directiveargument) > 0 ) {
            //F#m7 base-fret 1 frets 2 4 2 2 2 2
            //            preg_match( '/([^ ]+?) base-fret (:([^}]+?))?\}/', $directiveargument, $matches );
          //  $directiveargument= "Fsdfm7 base-fret 4 frets 2 4 2 2 2 2";
          //fingers missing!
            preg_match('!([^ ]+?)[ ]*( base-fret (\d))? (frets ((\d|x| )+)|((\d|x| )+))!', $directiveargument, $matches );
            //dd($directiveargument,$matches);
            if(!$matches[5]){$matches[5]=$matches[7];}
            $m =  array($matches[3],str_replace(" ","",$matches[5]));
            if($m[0]){
              for($k=0;$k<6;$k++){
                if($m[1]{$k} != "x"){
                  $m[1]{$k} = intval($m[1]{$k})+$m[0]-1;
                }
              }
            }

            $m[2]= "http://chordgenerator.net/".urlencode($matches[1]).".png?p=".$m[1]."&s=2";
            $chord["define"][$matches[1]]=$m;

          }
        } else {
          # unknown directive
          # no error because it should be already been alterted by the preparser
        }
      } else if( $directives['tab'][1] ) {
                $retval .= $choline."\n";
      } else if( strlen(trim($choline)) > 0 ) {
        # format lyrics with embedded chords

        $line_break = true;
        $line_singleline=false;
        //This line should not be broken during when displayed
        if($choline{0}=="@"){
          $choline = substr($choline,1);
          $line_break = false;
          $section_line=-1;
        //this line should not be a table, but just a single line
        }elseif($choline{0}=="!"){
          //delete @ indicator and save as non break
          $choline = substr($choline,1);
          //$line_break = false;
          $line_singleline = true;
        }

        //add inline section title div
        $inline="";
        foreach($directives as $name_ => $attr_){
          if($attr_[1]===true && $name_ != "tab"){
            if($name_ == "verse"){
              $inline = $attr_[0].'.';
            }else{
              $inline .= $attr_[2];
            }
            $inline = '<span class="c_sectioninsidetitle c_sectioninsidetitle_'.$name.'">'.$inline.'&nbsp;</span>';
            $directives[$name_][1] = 10; #mark that we the section title has been put
          }
        }


        //a) Covert line into two arrays (chords[],lyrics[])
        $inchord = false;
        $line_chords = array();
        $line_lyrics = array();
        $col = -1;

        //if the line doesn't start with a chord, they first chord should be empty
        if($choline{0}!="["){
          $col = 0;
          array_push( $line_lyrics, '' );
          array_push( $line_chords, '' );
        }
        for( $j = 0; $j < strlen($choline); $j++ ) {
        $ch = $choline{$j};
        if($ch=='[') {
          # at start of chord boundry
          $inchord = true;
          $col++;
          array_push( $line_chords, '' );
          array_push( $line_lyrics, '' );
        } else if($ch==']') {
          # at end of chord boundry
          $inchord = false;
        } else {
            # not at chord boundry
            if($inchord) {
              $line_chords[$col] .= $ch;
            }
            else{
              if($inchord) {
                $line_chords[$col] .= $ch;
              } else {
                $line_lyrics[$col] .= $ch;
              }
            }
        }
        }
        //arrays have been created
        if(!$chord["key"]){
          $chord["key"]=@($line_chords[0]?$line_chords[0]:$line_chords[1]);
        }

        //b) Use the two arrays to create a table
        if($options["output"]=="table"){
        //if($line_lyrics[0]{0}=="\r"){$line_lyrics[0] = substr($line_lyrics[0],1);}

        //no songtext only chords or text: ex. play twice | G7 C |
        if($line_singleline){
          //var_dump($line_chords);var_dump($line_lyrics);
          $table="<table><tr class=\"c_line_chords\"><td>";
          $table .= $inline;
          for( $j = 0; $j <= $col; $j++ ) {
            $table .= $this->ChordPro_createcrdspan($line_chords[$j]).$line_lyrics[$j];
          }
          $table.='</td></tr><tr><td>&nbsp;</td></tr></table>';
        }
        //normal lyrics&chords line
        else{
          $table = '<table border=0 cellpadding=0 cellspacing=0 class="c_table">' . "\n";
          # generate chord line
          if( ! ($col == 0 && strlen($line_chords[0]) == 0) ) { # no chords in line
            $table .= '<tr class="c_line_chords">';//'<td></td>';
            if($inline){
                $table .= '<td></td>';
            }
            for( $j = 0; $j <= $col; $j++ ) {
              $table .= '<td>'.$this->ChordPro_createcrdspan($line_chords[$j]).'&nbsp;</td>';
            }
            $table .= '</tr>' . "\n";
          }
          # generate lyrics line
          $table .= '<tr class="c_line_lyrics">';
          if($inline){
              $table .= '<td>'.$inline.'</td>';
              $inline = false;
          }
          for( $j = 0; $j <= $col; $j++ ) {
            if(substr($line_lyrics[$j],-1)==" "){
              //very important to create a space between two words, replace last space with nbsp
              $line_lyrics[$j]=rtrim($line_lyrics[$j],' ').'&nbsp;';
            }
            if(substr($line_lyrics[$j],0,1)==" "){
              //very important to create a space between two words, replace last space with nbsp
              $line_lyrics[$j]='&nbsp;'.ltrim($line_lyrics[$j],' ');
            }
            $table .= '<td>' . $line_lyrics[$j] . '</td>';
          }
          if( $col == 0 ) {
            $table .= '<td>&nbsp;</td>'; # &nbsp; to forece content so empty table row is displayed
          }
          $table .= '</tr></table>' . "\n";

        }
      }//end table option
      elseif ($options["output"]=="css") {

        //c) create class
        $css = '<div class="chordline">' . "\n";
        # generate chord line
        //echo $inline;
        if($line_singleline){
          $css = '<div>' . "\n";
          $css .= $inline;
          for( $j = 0; $j <= $col; $j++ ) {
            $css .= $this->ChordPro_createcrdspan($line_chords[$j]).$line_lyrics[$j];
          }
        }else{
          if($inline){
              $css .= $inline;
              $inline = false;
          }
          //var_dump(json_encode($line_lyrics));
          for( $j = 0; $j <= $col; $j++ ) {
            $css .= '<span class="relc">'.$this->ChordPro_createcrdspan($line_chords[$j]).'</span><span class="rell">'.$this->ChordPro_createlyr($line_lyrics[$j]).'</span>';
            //($line_lyrics[$j]!="\r"?$line_lyrics[$j]:"&nbsp;")
            //if()
          }
        }
        $css .= '</div>' . "\n";

//<div class="chordline"><span class="bracket">[</span><span class="relc"><span class="absc E">E</span></span><span class="bracket">]</span>I've been around for <span class="bracket">[</span><span class="relc"><span class="absc D">D</span></span><span class="bracket">]</span>long long years, I've st<span class="bracket">[</span><span class="relc"><span class="absc A">A</span></span><span class="bracket">]</span>olen many a man's soul and <span class="bracket">[</span><span class="relc"><span class="absc E">E</span></span><span class="bracket">]</span>faith</div>

      }//end output css

        if($options["output"]=="css"){
          $table=$css;
        }



        //$options['linebreak_open']=false;
        //if($line_break && $options['linebreak']){
        if($options['linebreak']){
          //$nextline_firstchar = @$cho[($i+1)]{0};
          //$i==($linecount-1)
          $linebreak_lastline = @$cho[($i+1)]{0} == "{" || @$cho[($i+1)]{0} == "@";

            //rest of lines, 01010 or 012012012012
          $rest           = $section_line % $options['linebreak'];

          //echo $line_singleline.":".$line_break.":".$linebreak_lastline.":".$options['linebreak_open'].":".$rest."".$choline."\n";
          if($line_break && !$linebreak_lastline && $rest == 0){
            $retval .= '<!-- start splitting row --><div class="row align-items-end">';
            $options['linebreak_open']=true;
          }
          //$line_singleline ||
          if( $line_break==false || ($linebreak_lastline&&!$options['linebreak_open']) ){
            $retval .= '<div class="row align-items-end">'.$table.'</div>'."\n";
          }else{
            //floor rounddown
            $retval .= '<div class="small-12 medium-12 xxlarge-'.floor(12/$options['linebreak']).' columns">'.$table.'</div>'."\n";
          }
//&& $line_singleline==false
          if($line_break && $options['linebreak_open'] && ($rest == ($options['linebreak']-1) || $linebreak_lastline)){
            $retval .= '</div><!-- end splitting row -->'."\n";
            $options['linebreak_open'] = false;
          }

        }else{
        $retval .= $table;
        }
        $section_line++;

      } else {
        # pass through unprocessed unknown text format
        $retval .= $choline;
      }

   }//end for loop


  $chord["html"]='<div class="c_song c_'.$options["output"].'" data-key="'.$chord["key"].'">' . "\n" . $retval . '</div>';
  return $chord;
}
private function ChordPro_createlyr($l){
  if($l=="\r" ||
     $l==" " ||
     $l=="  " ||
     $l=="  \r" ||
     $l==" \r" ||
     $l==""
  ){
    return "&nbsp;";
  }
  return $l;

}
private function ChordPro_createcrdspan($crd){
  if(!$crd){return "";}
  //takt vorgabe
  elseif($crd == "|" || $crd == "'" || $crd == ":|" || $crd == "|:" || $crd == "||"){
    return $crd;
  }
  else{
    $chords = explode(",",$crd);
    //attr name="chord_'. $crd.'"
    if(count($chords)==1){
      return '<span class="c">' . $crd.'</span>';
    }else{
      $span="";
      foreach($chords as $key => $val){
        $span .= '<span class="c c'.$key.'">' . $val.'</span>';
      }
      return $span;
    }
  }
}

}

?>
