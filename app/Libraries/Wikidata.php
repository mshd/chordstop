<?php
namespace App\Libraries;
use freearhey\sparql\QueryBuilder;
use GuzzleHttp\Client;
class Wikidata {

    const SPARQL_ENDPOINT = 'https://query.wikidata.org/sparql';
    const API_ENDPOINT = 'https://www.wikidata.org/w/api.php';
    /**
     * Wikidata prefixes for query
     * @var string[]
     */
    private $prefixes = array(
        'wd' => 'http://www.wikidata.org/entity/',
        'wdv' => 'http://www.wikidata.org/value/',
        'wdt' => 'http://www.wikidata.org/prop/direct/',
        'wikibase' => 'http://wikiba.se/ontology#',
        'p' => 'http://www.wikidata.org/prop/',
        'ps' => 'http://www.wikidata.org/prop/statement/',
        'pq' => 'http://www.wikidata.org/prop/qualifier/',
        'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
        'bd' => 'http://www.bigdata.com/rdf#',
    );
    /**
     * Format of results
     * @var string
     */
    public $format = 'json';
    /**
     * Language of results
     * @var string
     */
    public $language;
    /**
     * @param string $language
     */
    public function __construct($language = 'en')
    {
        $this->language = $language;
        //$this->properties = $this->getallproperties();
    }
    public function lastslash($string,$limit = PHP_INT_MAX,$second=false){
      if($second){$string=str_replace($second,"/",$string);}
      $parameter = explode("/",$string,$limit);
      return $parameter[ (count($parameter)-1) ];
    }
    private function getallproperties(){
      $url = 'https://quarry.wmflabs.org/run/45013/output/1/json';
      $data =  $this->jsonurl( $url );
      dd($data);
      return $data;
    }
    //https://www.wikidata.org/wiki/Help:Wikidata_datamodel
    public $entityId = false;
    public $entities = [];
    //public $properties = $this->getallproperties();
    private $id;

    public function sparqlquery($sparqlQuery){
      $endpointUrl = 'https://query.wikidata.org/sparql';
      $data =  $this->jsonurl( $endpointUrl . '?format=json&query=' . urlencode( $sparqlQuery ) );
      return $data["results"]["bindings"];
    }
    public function queryandfetch($query){
      $query = $this->sparqlquery($query);
      //dd($query);
      $ids = [];
      foreach($query as $key => $val){
         $id = explode("/",$val["item"]["value"],5);
         $entity1 = substr($id[4],0,1);
         $id = intval(substr($id[4],1));
         $this->loadentity($id,$entity1);
         foreach($val as $e_key => $e_val){
           //$e_key != "item" &&
           if( $e_val["value"]){
             $this->addclaim($e_key,$e_val["value"],$id);
           }
         }
       }
    }
    public function loadentity($entity = 42,$entity1 = "Q"){
      //$data =  json_decode(file_get_contents("https://www.wikidata.org/w/api.php?format=json&action=wbgetentities&sites=enwiki&titles=Afghanistan"),true);
      if(!isset($this->entities[$entity])){
      $data = $this->jsonurl("https://www.wikidata.org/wiki/Special:EntityData/".$entity1.$entity.".json");
      $data = $data["entities"][$entity1.$entity];
      $this->entities[$entity] = $data;
      //$this->entities[$entity]["query"] = $extradata;
      $this->entityId = $entity;
    }
    }
    public function createentity($entity){
      if(!isset($this->entities[$entity])){
        $this->entities[$entity] = ["type"=>"item","labels"=>[],"claims"=>[]];
        $this->entityId = $entity;
      }
    }
    public function getentity($Q){
      $this->loadentity($Q);
      echo "<pre>";
      print_r($this->entities[$Q]);
      return "";
    }
    private function jsonurl($url){
      return json_decode(file_get_contents($url),true);
    }
    public function addQs($entities){
      foreach($entities as $k => $v){
        $entities[$k] = "Q".$v;
      }
      return $entities;
    }
    public function QtoINT($Q){
      return intval(substr($Q,1));
    }
    public function querylabels($entities,$languages=["en"]){
      $entity_chunk = array_chunk(array_unique($entities),50);
      $return = [];
      foreach($entity_chunk as $entities){
      $e = $this->jsonurl("https://www.wikidata.org/w/api.php?action=wbgetentities&ids=".implode("|",$this->addQs($entities)). "&format=json&props=labels&languages=".implode("|",$languages));
      if(isset($e["error"])){
        dd($e["error"]);
      }
      foreach($e["entities"] as $entityId => $val){
        if(count($val["labels"])){
          $return[$this->QtoINT($entityId)] = @$val["labels"][$languages[0]]["value"];
        }
      }
    }
    return $return;

    }
    public function fetchlabels($languages=["en"]){
      //dd($this->claim_entities);
      //$languages = ["id","en"];
      $labels = $this->querylabels(array_keys($this->claim_entities),$languages);
      foreach($labels as $entityId => $val){
        $this->claim_entities[$entityId] = $val;
      }
      // keep in mind that same claim_entities will still be empty in lesser known languages
      $labels_not_set = array_diff(array_keys($this->claim_entities),array_keys($labels));
      if(count($labels_not_set) && $languages[0] != "en"){
        $labels = $this->querylabels($labels_not_set,["en"]);
        foreach($labels as $entityId => $val){
          $this->claim_entities[$entityId] = $val."(en)";
        }
      }
    }
    public function getid(){
      return $this->entities[$entity]["id"];
    }
    public function getlabels(){
      return $this->entities[$entity]["labels"];
    }
    private function search_multi($array, $key, $value){
      foreach($array as $key_ => $val_){
        if (isset($val_[$key]) && $val_[$key] == $value) {
            return $val_;
        }
      }
        return false;
    }
    public function createtable($header,$lang="en"){
      $table =[];
      $i = 0;
//dd($this->entities);//
//dd($header);
      foreach(array_keys($this->entities) as $entityId){
        foreach($header as $header_key => $header_val){
            $this->collectQs($header_val["property"],$entityId);
         }
      }
      $this->fetchlabels([$lang]);
      foreach($this->entities as $entityId => $entity){
        if(count($entity)){
        foreach($header as $header_key => $header_val){
          if(!isset($header_val["options"])){$header_val["options"]=[];}
          $string = $this->getclaim($header_val["property"],$entityId,$header_val["options"],$lang);
          $type = ( isset($header_val["type"]) ? $header_val["type"] : "Q");
          if($type=="check" || $type=="stars"){
            if(count($string)){
            $table[$i][$header_key] =  ["int" => $string[0] ] ;
            //"type"=>$type,
          }
          }else{
          $table[$i][$header_key] =  ["type"=>$type,"text" => (is_array($string) ? @implode(", ",array_unique($string)) : $string ) ] ;
        }
          if(isset($header_val["url"])){
            $table[$i][$header_key]["url"] = @$this->getclaim($header_val["url"],$entityId)[0];
          }
        }
        }
        $i++;
        }
      return $table;
    }
        /**
     * Case-insensitive in_array() wrapper.
     *
     * @param  mixed $needle   Value to seek.
     * @param  array $haystack Array to seek in.
     *
     * @return bool
     */
    function in_arrayi($needle, $haystack)
    {
    	return in_array(strtolower($needle), array_map('strtolower', $haystack));
    }
    public $leftovers;
    private $entityIdNew = 100000000;
    public function adddataset($newset,$para){

      //$c = array_column($newset,$commonkey);
      //dd($newset);
      $constraint = @$para["constraint"];
      $properties = @$para["properties"];
      $isfunction = (isset($para["function"]) ? true : false);
      $addrest = (isset($para["addrest"]) ? $para["addrest"] : false);
      $caseinsensitive = (isset($para["caseinsensitive"]) ? $para["caseinsensitive"] : false);
      $qualifier  = (isset($para["qualifier"]) ? $para["qualifier"] : []);
      if(!$constraint){
        foreach($newset as $key_ => $val_){
          $this->createentity($this->entityIdNew);
          $this->entities[$this->entityIdNew]["labels"]["en"] = ["value" => @$val_["name"]];
          foreach($val_ as $key => $val){
            if($properties){
              if(array_key_exists($key,$properties)){
                $key = $properties[$key];
              }else{
                break;
              }
            }
            $this->addclaim($key,$val,$this->entityIdNew,$qualifier);
          }
          $this->entityIdNew++;
        }
      //dd($this->entities);
        return;
      }
      foreach($this->entities as $entityId => $entity){
        //find the common key in the entity
        $match = [];
        //find all the constrain keys in the entity
        foreach($constraint as $entity_key => $foreign_key) {
          $match[$foreign_key] = $this->getclaim($entity_key,$entityId);
          //$match[$foreign_key] = @$match[$foreign_key][0];
          if(!$match[$foreign_key]){$match=[];break;}
        }
        if(count($match)){
          if($isfunction){
            //incase of function only needs the first value in a string format (such as alexa, trustpilot)
            if(count($constraint)==1 && $foreign_key == 0){
              $match = $match[0][0];
            }
            $function = call_user_func($newset,$match);
            if($function){
              foreach($function as $f_key => $f_val){
                if(array_key_exists($f_key,$properties)){
                  $this->addclaim($properties[$f_key],$f_val,$entityId,$qualifier);
                }
              }
            }
          }
          else{
          foreach($newset as $key_ => $val_){
            $found = true;
            //arraykey => de
            foreach($match as $foreign_key => $foreign_values){
              //$val_[$foreign_key] != $foreign_val
              if (empty($val_[$foreign_key]) || $val_[$foreign_key]== "" || !$this->in_arrayi($val_[$foreign_key],$foreign_values)) {
                  $found = false;break;
              }
            }
            // found or check array key
            if ($found || ($foreign_key=="arraykey" && $this->in_arrayi($key_,$foreign_values)) ) {
              //found it
                if(!is_array($val_)){$val_ = [$val_];}
                foreach($val_ as $key => $val){
                if($properties){
                  if(array_key_exists($key,$properties)){
                    $key = $properties[$key];
                  }else{
                    $key = false;
                  }
                }
                if(is_array($val) && $qualifier){
                  foreach($val as $year => $yearval){
                    $this->addclaim($key,$yearval,$entityId,[$qualifier => $year]);
                  }
                }elseif($key){
                  $this->addclaim($key,$val,$entityId);
                }
                }
                //remove datum from new dataset;
                unset($newset[$key_]);
                break;
            }
          }
        }
      }
      }
      if(true){
        $this->leftovers=$newset;
        /*$this->leftovers=[];
        foreach($newset as $datum){
            //if($datum["International"]>0){
            //echo print_r($datum)."<br>";
            $this->leftovers[] = $datum;
          //}
        }*/
      }
      if(isset($para["alternative_constraint"])){
        $para["constraint"] = $para["alternative_constraint"];
        unset($para["alternative_constraint"]);
        $this->adddataset($newset,$para);
        return;
      }
      if($addrest){
        $this->adddataset($newset,["properties" => $properties]);
      }
    }
    private $claim_entities = [];

  //https://github.com/google/freebase-wikidata-converter/blob/master/wikidata/wikidata-tsv.php
  private function convertValue($datavalue) {

	}
    private function getqualifer($claim){

    }
    private function claim_getvalue($claim,$options){
      $snak = $claim["mainsnak"];
      if($snak['snaktype'] !== 'value') {
        if($snak['snaktype'] == 'novalue') {
			       return "⌀";
           }
        return "?";
		  }
      //return $this->convertValue($snak['datavalue'])[0];
      $value = $snak['datavalue']['value'];
  		switch($snak['datavalue']['type']) {
  			case 'globecoordinate':
  				return '@' . $value['latitude'] . '/' . $value['longitude']; //TODO: proches
  			case 'monolingualtext':
  				//return [$value['language'] . ':"' . str_replace(["\n", '"'], [' ', ' '], $value['text']) . '"'];
          return str_replace(["\n", '"'], [' ', ' '], $value['text']) ;
  			case 'quantity':
  				return $value['amount'];//str_replace(["+"], [''], $value['amount']);
  			case 'string':
  				return str_replace(["\n", '"'], [' ', ' '], $value) ;
  			case 'time':
          //https://www.wikidata.org/wiki/Help:Dates#Time_datatype
  				$match = [];
          //$time = strtotime($value['time']);
          if($value['precision'] == 9 || $value['precision'] == 10 || (isset($options["date"]) &&  $options["date"]=="year" ) ) {
            $time = substr($value["time"],1,4);
          }
  				elseif($value['precision'] > 10) {
  					if(preg_match('/([+-]\d+\-\d{2})\-/', $value['time'], $match)) {
  						$time = $match[1] . '-00T00:00:00Z/'.$value['precision'];
  					}
  				}
  				elseif($value['precision'] > 9) {
  					if(preg_match('/([+-]\d+)\-/', $value['time'], $match)) {
  						$time = $match[1] . '-00-00T00:00:00Z/'.$value['precision'];
  					}
  				}
  				return $time;
  			case 'wikibase-entityid':
  				switch($value['entity-type']) {
  					case 'item':
              if(isset($options["raw"]) || (!@$this->claim_entities[$value["numeric-id"]])  ){
                return $value["id"];
              }
              return $this->claim_entities[$value["numeric-id"]];
  						//return ['Q' . $value['numeric-id']];
  					case 'property':
  						return 'P' . $value['numeric-id'];
  				}
  		}
      return "";
    }
    public function allclaims(){
      $allclaims = [];
      $claims = $this->entity["claims"];
      foreach($claims as $property => $property_value){
        $allclaims[$property]=[];
        foreach($property_value as $singleprop){
        $value = $this->claim_getvalue($singleprop);
        array_push($allclaims[$property],$value);
        }
      }
      return $allclaims;
    }
    public function claimm(){

    }
    public function collectQs($property,$entityId = "default",$lang = "en"){
      $claims = @$this->entities[$entityId]["claims"][$property];
      if(!$claims){return;}
      foreach($claims as $claim){
        //$dv= $claim["mainsnak"]["datavalue"];
        if(@$claim["mainsnak"]["datavalue"]["type"] == "wikibase-entityid"){
          $this->claim_entities[$claim["mainsnak"]["datavalue"]["value"]["numeric-id"]] = "";
        }
      }
      return;
    }
    public function getclaim($property,$entityId = "default",$options=[],$lang = "en"){
      $return = [];
      if($entityId == "default" ){$entityId = $this->entityId;}
      if($property == "label"){
        return [$this->getlabel($lang,$entityId)];
      }
      elseif($property == "description"){
        return [$this->getdescription($lang,$entityId)];
      }
      elseif($property == "aliases"){
        return $this->getaliases($lang,$entityId);
      }
      elseif($property == "id"){
        return [@$this->entities[$entityId]["id"]];
      }
      $claims = @$this->entities[$entityId]["claims"][$property];
      if(!$claims){return [];}

      if(empty($options["round"])){$options["round"]=false;}

      foreach($claims as $claim){
        //only if it doesnt have an end date
        $qualifier_ok = true;
        if(isset($options["qualifiers"])){
          foreach($options["qualifiers"] as $q_key => $q_val){
            if(!isset($claim["qualifiers"][$q_key])){
              $qualifier_ok = false;
              break;
            }
            dd($claim["qualifiers"][$q_key]);
            if($q_val != $claim["qualifiers"][$q_key][0]["value"]){
              $qualifier_ok = false;
              break;
            }
          }
        }

        if($qualifier_ok && !isset($claim["qualifiers"]["P582"])){
        $value = (isset($claim["value"]) ? $claim["value"] : $this->claim_getvalue($claim,$options));
        if(count($options)){
          if($options["round"] !== false){
            $value = round($value,$options["round"]);
          }
        }
        if($value !== ""){
        array_push($return,$value);
      }
        }
      }
      return $return;
    }
    private function getkey($key,$lang = "en",$entityId = "default"){
      if($entityId == "default" ){$entityId = $this->entityId;}
     $r = @$this->entities[$entityId][$key][$lang]["value"];
     if($r == null && $lang != "en"){
       $r = @$this->entities[$entityId][$key]["en"]["value"];
     }
     return $r;
    }
    public function getlabel($lang = "en",$entityId = "default"){
      return $this->getkey("labels",$lang,$entityId);
    }
    public function getdescription($lang = "en",$entityId = "default"){
      return $this->getkey("descriptions",$lang,$entityId);
    }
    public function getaliases($lang = "en",$entityId = "default"){
      return array_column($this->entities[$entityId]["aliases"][$lang],"value");
    }
    public function loadarray($load){
      $this->entities = $load;
    }
    public function addclaim($property,$value,$entityId = null,$qualifier = []){
      if(is_array($value)){
        foreach($value as $val){
          $this->addclaim($property,$val,$entityId,$qualifier);
        }
        return;
      }
      $entityId = (is_null($entityId)) ? $this->entityId : $entityId;
      if($value === null){return;}
      if(!isset($this->entities[$entityId]["claims"][$property])){
        $this->entities[$entityId]["claims"][$property] = [];
      }
      $qualifiers = [];
      foreach($qualifier as $q_key => $q_val){
        $qualifiers[$q_key] = [[
        "snaktype"=>"value",
        "value"=> $q_val,
        ]];
      }

      array_push($this->entities[$entityId]["claims"][$property],[
        "value" => $value,
        "qualifiers" => $qualifiers,
      ]);
      /*
      "id" => 0,
      "rank" => "normal",
      "mainsnak" => [
        "snaktype" => "value",
        "datavalue" => [
          "type"  => "quantity",
          "value" => [
            "amount" => $value,
          ],
        ]
      ]*/
    }

//public saerch
  //  https://www.wikidata.org/w/api.php?action=wbsearchentities&search=abc&language=en&limit=1
  /**
   * Search entities by term
   *
   * @param string $term Search term
   *
   * @return \Illuminate\Support\Collection Return collection of \Wikidata\Result
   */
  public function searchwikipedia($term)
  {
      $client = new Client();//?action=query&list=search&srsearch=Albert%20Einstein&utf8=
      $response = $client->get("https://en.wikipedia.org/w/api.php", [
          'query' => [
              'action' => 'query',
              'format' => $this->format,
              //'language' => $this->language,
              'srsearch' => $term,
              "list" => "search",
          ]
      ]);
      $data = json_decode($response->getBody(),true);
      return $data["query"]["search"];
  }
  public function wikipediaID2wikidataID($ids){

    $entity_chunk = array_chunk(array_unique($ids),50);
  foreach($entity_chunk as $entities){
    $client = new Client();//?action=query&list=search&srsearch=Albert%20Einstein&utf8=
    $response = $client->get("https://en.wikipedia.org/w/api.php", [
        'query' => [
            'action' => 'query',
            'format' => $this->format,
            'prop' => 'pageprops',
            'pageids' => implode("|",$entities),
        ]
    ]);
    $e = json_decode($response->getBody(),true);
    foreach($e["query"]["pages"] as $entityId => $val){
        $return[$val["pageid"]] = @$val["pageprops"]["wikibase_item"];
    }
    }
    return $return;

  }
//https://en.wikipedia.org/w/api.php?action=query&format=json&prop=pageprops&pageids=736
    /**
     * Search entities by term
     *
     * @param string $term Search term
     *
     * @return \Illuminate\Support\Collection Return collection of \Wikidata\Result
     */
    public function search($term)
    {
        $client = new Client();
        $response = $client->get(self::API_ENDPOINT, [
            'query' => [
                'action' => 'wbsearchentities',
                'format' => $this->format,
                'language' => $this->language,
                'search' => $term,
                'limit' => 1,
            ]
        ]);
        $results = json_decode($response->getBody(),true);
        return $results["search"];
        $data = $this->formatSearchResults($results);
        return $data;
    }
    /**
     * Search entities by property and value
     *
     * @param string $property Wikidata ID of property (e.g.: P646)
     * @param string $value String value of property or Wikidata entity ID (e.g.: Q11696)
     *
     * @return \Illuminate\Support\Collection Return collection of \Wikidata\Result
     */
    public function searchBy($property, $value)
    {
        if(!$this->is_pid($property)) {
            throw new Exception("First argument in searchBy() must by a valid Wikidata property ID (e.g.: P646).", 1);
        }
        $query = $this->is_qid($value) ? 'wd:'.$value : '"'.$value.'"';
        $queryBuilder = new QueryBuilder($this->prefixes);
        $queryBuilder
             ->select('?item', '?itemLabel', '?itemAltLabel', '?itemDescription')
             ->where('?item', 'wdt:'.$property, $query)
             ->service('wikibase:label', 'bd:serviceParam', 'wikibase:language', '"'. $this->language .'"');
        $queryBuilder->format();
        $queryExecuter = new QueryExecuter(self::SPARQL_ENDPOINT);
        $results = $queryExecuter->execute( $queryBuilder->getSPARQL() );
        $data = $this->formatSearchByResults($results);
        return $data;
    }
    /**
     * Get entity by ID
     *
     * @param string $entityId Wikidata entity ID (e.g.: Q11696)
     *
     * @return \Wikidata\Entity Return entity
     */
    public function get($entityId)
    {
        $subject = 'wd:'.$entityId;
        $queryBuilder = new QueryBuilder($this->prefixes);
        $queryBuilder
            ->select('?property', '?valueLabel')
            ->where('?prop', 'wikibase:directClaim', '?claim')
            ->where($subject, '?claim', '?value')
            ->where('?prop', 'rdfs:label', '?property')
            ->service('wikibase:label', 'bd:serviceParam', 'wikibase:language', '"'. $this->language .'"')
            ->filter('LANG(?property) = "en"');
        $queryExecuter = new QueryExecuter(self::SPARQL_ENDPOINT);
        $results = $queryExecuter->execute($queryBuilder->getSPARQL());
        $data = $this->formatProps($results);
        $snippet = $this->getEntitySnippet($entityId);
        dd($data);
        return new Entity($snippet->id, $snippet->label, $snippet->aliases, $snippet->description, $data);
    }
    /**
     * Get entity snippet by ID
     *
     * @param string $entityId Wikidata entity ID (e.g.: Q11696)
     *
     * @return \Wikidata\Result|null Return entity snippet, including id, label, aliases and description
     */
    private function getEntitySnippet($entityId)
    {
        $client = new Client();
        $response = $client->get(self::API_ENDPOINT, [
            'query' => [
                'action' => 'wbgetentities',
                'format' => $this->format,
                'languages' => $this->language,
                'props' => 'labels|aliases|descriptions',
                'ids' => $entityId,
            ]
        ]);
        $results = json_decode($response->getBody());
        return $this->formatGetEntitySnippet($results);
    }
    /**
     * Convert array of getEntitySnippet() results to \Wikidata\Result
     *
     * @param array $results
     *
     * @return \Wikidata\Result
     */
    private function formatGetEntitySnippet($results)
    {
        $snippet = [
            'id' => null,
            'label' => null,
            'aliases' => [],
            'description' => null,
        ];
        if(!property_exists($results, 'error')) {
            $collection = collect($results->entities);
            $item = $collection->first();
            if($item) {
                $lang = $this->language;
                $aliases = [];
                if(property_exists($item, 'aliases') && property_exists($item->aliases, $lang)) {
                    $aliases = array_map(function($alias) {
                        return $alias->value;
                    }, $item->aliases->$lang);
                }
                $snippet['id'] = property_exists($item, 'id') ? $item->id : null;
                $snippet['label'] = property_exists($item, 'labels') && property_exists($item->labels, $lang) ? $item->labels->$lang->value : null;
                $snippet['aliases'] = $aliases;
                $snippet['description'] = property_exists($item, 'descriptions') && property_exists($item->descriptions, $lang) ? $item->descriptions->$lang->value : null;
            }
        }
        return new Result(collect($snippet));
    }
    /**
     * Convert array of search() results to collection of \Wikidata\Results
     *
     * @param array $results
     *
     * @return \Illuminate\Support\Collection
     */
    private function formatSearchResults($results)
    {
        $collection = collect($results->search);
        $collection = $collection->map(function($item) {
            return new Result(collect([
                'id' => property_exists($item, 'id') ? $item->id : null,
                'label' => property_exists($item, 'label') ? $item->label : null,
                'aliases' => property_exists($item, 'aliases') ? $item->aliases : null,
                'description' => property_exists($item, 'description') ? $item->description : null,
            ]));
        });
        return $collection;
    }
    /**
     * Convert array of searchBy() results to collection of \Wikidata\Results
     *
     * @param array $results
     *
     * @return \Illuminate\Support\Collection
     */
    private function formatSearchByResults($results)
    {
        $collection = collect($results['bindings']);
        $collection = $collection->map(function($item) {
            return new Result(collect([
                'id' => isset($item['item']) ? str_replace("http://www.wikidata.org/entity/", "", $item['item']['value']) : null,
                'label' => isset($item['itemLabel']) ? $item['itemLabel']['value'] : null,
                'aliases' => isset($item['itemAltLabel']) ? explode(', ', $item['itemAltLabel']['value']) : [],
                'description' => isset($item['itemDescription']) ? $item['itemDescription']['value'] : null
            ]));
        });
        return $collection;
    }
    /**
     * Convert array of properties to collection
     *
     * @param array $results
     *
     * @return \Illuminate\Support\Collection
     */
    private function formatProps($results)
    {
        $collection = collect($results['bindings']);
        $collection = $collection->groupBy(function ($item, $key) {
            return $this->slug($item['property']['value']);
        });
        $collection = $collection->map(function($item) {
            return $item->pluck('valueLabel.value');
        });
        return $collection;
    }
    /**
     * Check if given string is valid Wikidata entity ID
     *
     * @param string $value
     *
     * @return bool Return true if string is valid or false
     */
    private function is_qid($value)
    {
        return preg_match("/^Q[0-9]+/", $value);
    }
    /**
     * Check if given string is valid Wikidata property ID
     *
     * @param string $value
     *
     * @return bool Return true if string is valid or false
     */
    private function is_pid($value)
    {
        return preg_match("/^P[0-9]+/", $value);
    }
    /**
     * Convert name of property to slug
     *
     * @param string $string
     *
     * @return string Return slug name of property
     */
    private function slug($string)
    {
      $separator = '_';
      $flip = '-';
      $string = preg_replace('!['.preg_quote($flip).']+!u', $separator, $string);
      $string = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', mb_strtolower($string));
      $string = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $string);
      return trim($string, $separator);
    }
}
