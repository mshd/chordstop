<?php
namespace App\Libraries;

class DiscogsClass {
  private function discogs_construct($query,$options=false){

  $service_url = 'https://api.discogs.com/'.$query.'';
  $params = array(
    'key' => 'OYrGCqkxEhZoOmEbrUTl',
    'secret' => 'jhQNmlpTENwxPNzKTWpPGfgJDKWUCRXz',
    );
    if(is_array($options)){
      $params= array_merge($params,$options);
    }elseif($options){
      $service_url .= '/'.$options;
    }
  $url = $service_url . '?' . http_build_query($params);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'ChordStop Php');
  $response = json_decode(curl_exec($ch), true);
  curl_close($ch);
  return $response;
  }

  public function discogs_search($q,$type="artist",$per_page=1,$page=1){
    return $this->discogs_construct("database/search",array(
    "q"       => $q,
    "type"    => $type,
    "per_page"=> $per_page,
    "page"    => $page,
    ));
  }


  public function discogs_artist_by_id($id){
    return $this->discogs_construct("artists",$id);

  }
  public function discogs_artist($artist){
  $search = $this->discogs_construct("database/search",array(
  "q" => $artist,
  "type" => "artist",
  "per_page"=>1,
  "page"=>1,
  ));
  if(!count($search["results"])){
  return false;
  }
  /*
  array:2 [▼
  "pagination" => array:5 [▶]
  "results" => array:1 [▼
    0 => array:6 [▼
      "thumb" => "https://api-img.discogs.com/IRaHA3FvIGOEvWzYzz69tNvx_oo=/150x150/smart/filters:strip_icc():format(jpeg):mode_rgb():quality(40)/discogs-images/A-106474-1465426516-9873.jpeg.jpg ◀"
      "title" => "Paul Simon"
      "uri" => "/artist/106474-Paul-Simon"
      "resource_url" => "https://api.discogs.com/artists/106474"
      "type" => "artist"
      "id" => 106474
    ]
  ]
  ]*/
  return $this->discogs_construct("artists",$search["results"][0]["id"]);
  }
  public function discogs_bestpic($array){
  return $array[0]["uri150"];
  }
  public function discogs_master($id){
  return $this->discogs_construct("masters",$id);
  }
}

?>
