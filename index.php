<?php

try {

//header("Content-type: text/calendar;");
error_reporting(E_ALL);
include('bennu/lib/bennu.inc.php');
$a = new iCalendar;
function leading_zeros($value, $places){
// Function written by Marcus L. Griswold (vujsa)
// Can be found at http://www.handyphp.com
// Do not remove this header!
$leading = "";
    if(is_numeric($value)){
        for($x = 1; $x <= $places; $x++){
            $ceiling = pow(10, $x);
            if($value < $ceiling){
                $zeros = $places - $x;
                for($y = 1; $y <= $zeros; $y++){
                    $leading .= "0";
                }
            $x = $places + 1;
            }
        }
        $output = $leading . $value;
    }
    else{
        $output = $value;
    }
    return $output;
}


// Parse config file
$config_file = './config.ini';
$config = parse_ini_file($config_file, true, INI_SCANNER_TYPED);
if(false === $config) {
	$e = error_get_last();
	throw new ErrorException($e[message], $e[type], $severity, $e[file], $e[line]);
}

// Parse API-code file
$apicode = file_get_contents($config["FILES"]["API-CODE"]);
if(false === $apicode) {
	$e = error_get_last();
	throw new ErrorException($e[message], $e[type], $severity, $e[file], $e[line]);
}

// Remove leading and trailing whitespaces
$apicode = trim($apicode);




$fav = file_get_contents("http://www.thetvdb.com/api/User_Favorites.php?accountid=".$_GET["accountid"]);
$favxml = new SimpleXMLElement($fav);

foreach($favxml->Series as $id){
	$xmlstr = file_get_contents("http://www.thetvdb.com/api/$apicode/series/$id/all/en.xml");
	if($xmlstr === false) {
		error_log("Serie " + $apicode + "couldn't be fetched");
		break;
	}
	$xml = new SimpleXMLElement($xmlstr);
	foreach($xml->Episode as $episode){
		$episodenr = $episode->EpisodeNumber;
		$episodenr = leading_zeros((int)$episodenr,2);
		$id = $episode->id;
		$season = $episode->Combined_season;
		$season = leading_zeros((int)$season,2);
		$naam = $episode->EpisodeName;
		$datum = $episode->FirstAired;
		$formatdatum = strtotime($datum);
		$datum = str_replace("-","",$datum);
		$uitleg = $episode->Overview;
		if($formatdatum >= (time()-604800)){
			if($datum <> ""){
				$ev = new iCalendar_event;
				$ev->add_property('uid', $id);
				$ev->add_property('summary', $xml->Series->SeriesName." | ".$naam." (S".$season."E".$episodenr.")");
				if($uitleg <> ""){
					$ev->add_property('description', "$uitleg");
				}
				$ev->add_property('dtstart', $datum, array('value' => 'DATE'));
				$ev->add_property('dtend', $datum, array('value' => 'DATE'));
				$ev->add_property('dtstamp', $datum.'T120000Z');
				$a->add_component($ev);
			}
		}
	}
}
echo $a->serialize();
}

catch(Exception $e)
{
    $exception_message = 'TVCalendar: Exception: '.$e->getMessage()."\n";
    error_log($exception_message);
    echo $exception_message;
    http_response_code(500);
    exit($e->getCode());
}

?>
