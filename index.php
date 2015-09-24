<?php

include_once("simple_html_dom.php");

$includeDir 	= get_cfg_var("include_directory");
$classDefFile 	= $includeDir . "class_definitions.php";
$staticsFile	= $includeDir . "statics.php";

include_once($classDefFile);
include_once($staticsFile);

$MAP_SIZE	= 101;
$TILE_SIZE	= 16;

function getNickFromURL() {

	$retVal = "";

	$uri = $_SERVER['REQUEST_URI'];
	$uri = trim($uri, '/');

	if ( !empty($uri) )
	{
		$retVal = $uri;
	}

	$retVal = urldecode($retVal);

	return $retVal;
}

/*
function getCharacterHTML($nick, &$html) {

	$home	= getenv("HOME");
	$path 	= "/home/minikeen/.blaventure/$nick.char";

	$handle		= fopen($path, "r");
	$serialData	= fread($handle, filesize($path));
	fclose($handle);

	$data		= unserialize($serialData);
	$name 		= $data->name;

	$nameHeader = $html->find("#name", 0);
	$nameHeader->innertext = $name;

	return $html;
}
*/

function getAllNicks() {

	$saveDir	= get_cfg_var("save_directory");
	$dir 		= new DirectoryIterator($saveDir);

	$nicks 		= [];

	foreach ($dir as $fileinfo) {
	    
	    if ( $fileinfo->isDot() ) {
	        continue;
	    }

	    $fileName 	= $fileinfo->getFilename();
	    $nickRegex	= "/\A([a-z_\-\[\]\\^{}|`][a-z0-9_\-\[\]\\^{}|`]*).[\w]+/i";

	    preg_match($nickRegex, $fileName, $matches);

	    // Ensure we got a nick match.
	    if ( count($matches) <= 1 ) {
	    	continue;
	    }

	    // We now have a nick.
	    $nick = $matches[1];

	    if ( !in_array($nick, $nicks) ) {

	    	$nicks[] = $nick;
	    }
	}

	return $nicks;
}

function getMapHTML($nick) {

	global $MAP_SIZE, $TILE_SIZE;

	$mapMid		= floor($MAP_SIZE / 2);

	$saveDir	= get_cfg_var("save_directory");
	$path 		= $saveDir . "$nick.map";

	if ( !file_exists($path) ){
		return null;
	}

	$handle		= fopen($path, "r");
	$serialData	= fread($handle, filesize($path));
	fclose($handle);

	$data		= unserialize($serialData);

	if ( !isset($data->map) ) {
		echo "Invalid data: no map";
		exit();
	}
	if ( !isset($data->map->grid) ) {
		echo "Invalid data: no grid";
		exit();
	}

	$grid		= $data->map->grid;
	$size 		= $MAP_SIZE * $TILE_SIZE;

	$output 	= "";

	$x 			= 0;
	$y 			= 0;

	for( $i = 0; $i < $MAP_SIZE; ++$i ) {

		if ( !isset($grid[$i]) || empty($grid[$i]) ) {

			$x += $TILE_SIZE;
			continue;
		}

		for( $j = 0; $j < $MAP_SIZE; ++$j ) {

			if ( !isset($grid[$i][$j]) ) {
				
				$y += $TILE_SIZE;
				continue;
			}

			$class = "";
			$isCurrent = ($i == $data->playerX && $j == $data->playerY);

			$gridItem = $grid[$i][$j];

			$dataItem = "";

			$rect = "<g><rect x='$x' y='$y' width='$TILE_SIZE' height='$TILE_SIZE' class='$nick' # />";

			if ( $isCurrent ) {
				
				$circle = getCircleHTML($x, $y, 0, 0, $nick, "current");

				$rect .= $circle;
			}
			else if ( $i == $mapMid && $j == $mapMid ) {

				$circle = getCircleHTML($x, $y, -5, 5, $nick, "start");

				$rect .= $circle;
			}
			else if ( !is_null($gridItem->occupant) ) {

				$circle = getCircleHTML($x, $y, 5, -5, $nick, "monster");

				$occupant = $gridItem->occupant;
				$occupantStr = "Level $occupant->level $occupant->name ($occupant->hp/$occupant->hpMax)";
				if ( $occupant->elite ) {
					$occupantStr = "ELITE " . $occupantStr;
				}

				$dataItem = "data-occupant='$occupantStr'";

				$rect .= $circle;
			}

			$rect = preg_replace("/#/", $dataItem, $rect);

			$rect .= "</g>";

			$output .= $rect;

			$y += $TILE_SIZE;
		}

		$x += $TILE_SIZE;
		$y = 0;
	}

	return $output;
}

function getCircleHTML($x, $y, $xOffsetFromCentre, $yOffsetFromCentre, $nick, $extraClass) {

	global $TILE_SIZE;

	$tileHalfSize = $TILE_SIZE / 2;
	$xMid = $x + $tileHalfSize;
	$yMid = $y + $tileHalfSize;

	$xPos = $xMid + $xOffsetFromCentre;
	$yPos = $yMid + $yOffsetFromCentre;

	return "<circle cx='$xPos' cy='$yPos' r='2' class='circle-marker $extraClass' data-owner='$nick' />";
}

function renderHTML($baseHTML, $html, $scriptHTML) {

	// Render the page.
	$container = $baseHTML->find("#viewport", 0);
	$container->innertext = $html;

	$phpinfo	= $baseHTML->find("#phpscript", 0);
	$phpinfo->innertext = $scriptHTML;

	echo $baseHTML;
}

function getScriptParams($nickParam) {

	global $MAP_SIZE, $TILE_SIZE;

	$sizeParams = "var mapSize = $MAP_SIZE; var tileSize = $TILE_SIZE;";

	if ( !is_null($nickParam) ) {
		$sizeParams .= " var targetNick = '$nickParam';";
	}

	return $sizeParams;
}

$gridHTML 		= "";
$baseHTML		= file_get_html("base.html");
$nickFromURL 	= getNickFromURL();

$nickParam = null;
if ( strlen($nickFromURL) > 0 ) {
	$nickParam = $nickFromURL;
}
$scriptHTML = getScriptParams($nickParam);

$nicks 		= getAllNicks();

foreach ( $nicks as $nick ) {

	$outHTML	= getMapHTML($nick);
	$gridHTML 	.= $outHTML;
}

renderHTML($baseHTML, $gridHTML, $scriptHTML);
