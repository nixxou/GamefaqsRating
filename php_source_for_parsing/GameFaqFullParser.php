<?php

//view-source:http://localhost/GameFaqFullParser.php


//PS C:\Users\Mehdi\Documents\NuSphere PhpED\Projects> C:\xampp\php\php.exe GameFaqFullParser.php
function startsWith( $haystack, $needle ) {
     $length = strlen( $needle );
     return substr( $haystack, 0, $length ) === $needle;
}
function endsWith( $haystack, $needle ) {
    $length = strlen( $needle );
    if( !$length ) {
        return true;
    }
    return substr( $haystack, -$length ) === $needle;
}

    function strcontains ( $haystack,  $needle)
    {
        return empty($needle) || strpos($haystack, $needle) !== false;
    }

function get_contents($url,$wait=2){
      if($wait > 5) die("erreur get");
    
    $filename = "cachewebgamefaqs/".md5($url);
    if(is_file($filename)) return file_get_contents($filename);
    else{
        
            Sleep($wait);
                
        
        $options = array(
  'http'=>array(
    'method'=>"GET",
    'header'=>"Accept-language: en\r\n" .
              "Cookie: foo=bar\r\n" .  // check function.stream-context-create on php.net
              "User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n" // i.e. An iPad 
  )
);
  


$context = stream_context_create($options);
         
       echo $url."\n";
       $res = file_get_contents($url,false,$context);
       //echo $res;
       
       
       if(strcontains($res,"List Criteria")==false){
           $res = get_contents($url,$wait+1);
       } 
       file_put_contents($filename,$res);
       return $res;
        
    } 
}

$bad = array_merge(
array_map('chr', range(0,31)),
array("<", ">", ":", '"', "/", "\\", "|", "?", "*"));

$plateform_list = unserialize(file_get_contents("plateform_match.txt"));

$nb_plateform = count($plateform_list);

$x = 0;
foreach($plateform_list as $launchboxPlateformName => $GamefaqsPlateformID){
    
    
    
    $x++;
    echo "$x / $nb_plateform : $launchboxPlateformName ($GamefaqsPlateformID) \n";
    
    //if($x>77) exit;

    $min_vote = 2;
    $current_minvote = $min_vote;

 
    $dataGameFaqs = array();
    while($current_minvote >= 0){
        
        
        $pageContent = get_contents("https://gamefaqs.gamespot.com/games/rankings?platform=".$GamefaqsPlateformID."&list_type=rate&min_votes=".$current_minvote."&page=0");
        $no_game = false;
        if(strcontains($pageContent,"There are no games")) $no_game = true;
        
        
        if($no_game){
            $current_minvote--;
            continue;
            
        }
        $nb_page = 1;
        if(preg_match('/;page=([0-9]*)">Last <i/ms', $pageContent, $matches)){
            $nb_page = @$matches[1]*1;
            if($nb_page < 1) $nb_page == 1;
            echo "Pages : $nb_page \n";
        }        

        
        for($i=0;$i<$nb_page;$i++){  
            //echo "https://gamefaqs.gamespot.com/games/rankings?platform=".$GamefaqsPlateformID."&list_type=rate&min_votes=".$current_minvote."&page=$i";
            $pageContent = get_contents("https://gamefaqs.gamespot.com/games/rankings?platform=".$GamefaqsPlateformID."&list_type=rate&min_votes=".$current_minvote."&page=$i"); 

            $filteredContent = explode("<tbody>",$pageContent,2)[1];
            $filteredContent = explode("<\tbody>",$filteredContent,2)[0];

        
            //<a href="(?P<url>[^"]*)">(?P<name>[^>]*)<\/a> <span class="flair">(.*?)<td>(?P<rating>[\-0-9\.]*)<\/td>(\s*)<td>(?P<difficulty>[\-0-9\.]*)<\/td>(\s*)<td>(?P<lenght>[\+\-0-9\.]*)<\/td>
            $re = '/<a href="(?P<url>[^"]*)">(?P<name>[^>]*)<\/a> <span class="flair">(.*?)<td>(?P<rating>[\-0-9\.]*)<\/td>(\s*)<td>(?P<difficulty>[\-0-9\.]*)<\/td>(\s*)<td>(?P<lenght>[\+\-0-9\.]*)<\/td>/ms';
            preg_match_all($re, $filteredContent, $matches, PREG_SET_ORDER, 0);
            
            $vote_count = 5;
            if($current_minvote == 1) $vote_count = 50;
            if($current_minvote == 0) $vote_count = 100;
            
            
            
            foreach($matches as $m){
                $filenameValidWindows = str_replace($bad, "", $m["name"]);
                
                $rating = trim($m["rating"]);
                if($rating != "---") $rating = number_format($rating,2);
                
                $difficulty = trim($m["difficulty"]);
                if($difficulty != "---") $difficulty = number_format($difficulty,2);
                
                $lenght = trim($m["lenght"]);
                $lenght = str_ireplace("+","",$lenght);
                if($lenght != "---"){
                    $lenght = number_format($lenght,2);
                    $lenght = sprintf('%05.2f', $lenght);
                }
                
                
                
                if(!isset($dataGameFaqs[$filenameValidWindows])){
                     $dataGameFaqs[$filenameValidWindows]=array("name" => $m["name"], "url" => $m["url"], "rating" => $rating, "difficulty" => $difficulty, "lenght" => $lenght, "vote" => $vote_count);
                }
                else{
                    
                    $dataGameFaqs[$filenameValidWindows]["vote"] = $vote_count;
                    
                }
            }                             
        }
        

        
           
        $current_minvote--;
    }
    
        $data_preparation = array();
        
        $data_preparation["plateform"] = $launchboxPlateformName;
        $data_preparation["plateform_safename"] = str_replace($bad, "", $launchboxPlateformName);
        $data_preparation["plateform_id"] = $GamefaqsPlateformID;
        $data_preparation["games"] = $dataGameFaqs;
        
        $file_prepare = 'gamefaqsdata/'.str_replace($bad, "", $launchboxPlateformName).".txt";
        file_put_contents($file_prepare,serialize($data_preparation));
        
        
        $dir_prepare = 'gamefaqsdata/prepare/'.str_replace($bad, "", $launchboxPlateformName);
        if(is_dir($dir_prepare)==false) mkdir($dir_prepare);
        foreach($dataGameFaqs as $m){
            $filenameValidWindows = str_replace($bad, "", $m["name"]);
            file_put_contents($dir_prepare."/".$filenameValidWindows.".bin", json_encode($m,true));           
        }
    
    
    //print_r($dataGameFaqs);
       
    
    /*
    $dataGameFaqs = array();
    for($i=0;$i<11;$i++){
        $n=$i+1;
        $pageContent = get_contents("https://gamefaqs.gamespot.com/games/rankings?platform=63&list_type=rate&min_votes=1&page=$n");

        $filteredContent = explode("<tbody>",$pageContent,2)[1];
        $filteredContent = explode("<\tbody>",$filteredContent,2)[0];


        //<a href="(?P<url>[^"]*)">(?P<name>[^>]*)<\/a> <span class="flair">(.*?)<td>(?P<rating>[0-9\.]*)<\/td>(\s*)<td>(?P<difficulty>[0-9\.]*)<\/td>(\s*)<td>(?P<lenght>[0-9\.]*)<\/td>
        $re = '/<a href="(?P<url>[^"]*)">(?P<name>[^>]*)<\/a> <span class="flair">(.*?)<td>(?P<rating>[0-9\.]*)<\/td>(\s*)<td>(?P<difficulty>[0-9\.]*)<\/td>(\s*)<td>(?P<lenght>[0-9\.]*)<\/td>/ms';
        preg_match_all($re, $filteredContent, $matches, PREG_SET_ORDER, 0);
        
        
        foreach($matches as $m){
            $filenameValidWindows = str_replace($bad, "", $m["name"]);
            $dataGameFaqs[$filenameValidWindows]=array("name" => $m["name"], "url" => $m["url"], "rating" => $m["rating"], "difficulty" => $m["difficulty"], "lenght" => $m["lenght"]);

            
            //file_put_contents("outgamefiles/".$filenameValidWindows.".bin", json_encode($dataGameFaqs,true));
        }    
    }    
    
    */
    
        
}






/*
file_put_contents("gamefaqs_parse_preparation.txt",serialize($dataGameFaqs));

print_r($dataGameFaqs);
*/
  
?>
