<?php
$plateform_safe = "Sony PSP";
$file_prepare = 'gamefaqsdata/'.$plateform_safe.".txt";


$data_preparation = unserialize(file_get_contents($file_prepare));

$plateform_name = $data_preparation["plateform"];
$dataGameFaqs = $data_preparation["games"];


$xmlRaw = file_get_contents('C:\LaunchBox\Data\Platforms\Gamefaqs.xml');

  //$xml = simplexml_load_string($xmlstring, "SimpleXMLElement", LIBXML_NOCDATA);
$xml = simplexml_load_string( $xmlRaw , null , LIBXML_NOCDATA ); 
$json = json_encode($xml);
$launchboxData = json_decode($json,TRUE);
  
//print_r($launchboxData);


$outData = array();

foreach($launchboxData["Game"] as $game){
    if(!isset($game["DatabaseID"])) continue;
    $gameFaqsKey = pathinfo($game["ApplicationPath"],PATHINFO_FILENAME);
    $launchboxKey = $game["DatabaseID"];
    $launchboxTitle = $game["Title"];
      
    if(isset($dataGameFaqs[$gameFaqsKey])){
        $outData[$launchboxKey] = $dataGameFaqs[$gameFaqsKey];
        $outData[$launchboxKey]["LaunchboxID"] =$launchboxKey;
        $outData[$launchboxKey]["LaunchboxTitle"] =$launchboxTitle;
        $outData[$launchboxKey]["url"] = "https://gamefaqs.gamespot.com".$outData[$launchboxKey]["url"];                
    }
} 

file_put_contents("gamefaqsdata/res/".$plateform_safe.".json",json_encode($outData,JSON_PRETTY_PRINT));
  
?>
