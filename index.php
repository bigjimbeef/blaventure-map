<?php

include_once("simple_html_dom.php");

include_once("/home/minikeen/Code/blaventure/class_definitions.php");
include_once("/home/minikeen/Code/blaventure/statics.php");

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
	    $nickRegex	= "/\A([a-z_\-\[\]\\^{}|`][a-z0-9_\-\[\]\\^{}|`]*).[\w]+/";

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

			if ( $isCurrent ) {
				$class = "current";
			}
			else if ( $i == $mapMid && $j == $mapMid ) {
				$class = "start";
			}
			else if ( !is_null($gridItem->occupant) ) {
				$class = "monster";
			}

			$rect = "<rect x='$x' y='$y' width='$TILE_SIZE' height='$TILE_SIZE' class='$nick' />";
			$output .= $rect;

			$y += $TILE_SIZE;
		}

		$x += $TILE_SIZE;
		$y = 0;
	}

	return array($output, $numX, $data->playerY);
}

function renderHTML($baseHTML, $html, $x, $y) {

	// Render the page.
	$container = $baseHTML->find("#viewport", 0);
	$container->innertext = $html;

	$scriptHTML = getSizesScriptTag();
	$phpinfo	= $baseHTML->find("#phpscript", 0);
	$phpinfo->innertext = $scriptHTML;

	echo $baseHTML;
}

function getSizesScriptTag() {

	global $MAP_SIZE, $TILE_SIZE;

	return "var mapSize = $MAP_SIZE; var tileSize = $TILE_SIZE;";
}

$nicks 		= getAllNicks();
$baseHTML	= file_get_html("base.html");

$gridHTML 	= "";

foreach ( $nicks as $nick ) {

	list($outHTML, $x, $y) 	= getMapHTML($nick);

	$gridHTML .= $outHTML;
}

renderHTML($baseHTML, $gridHTML, 0, 0);
