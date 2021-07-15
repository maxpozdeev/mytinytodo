<?php

require_once(MTTINC. 'parsedown/Parsedown.php');


function noteMarkup($note)
{
	if (Config::get('markup') == 'v1') {
		return mttMarkup_v1($note);
	}
	return markdownToHtml($note);
}

// Markdown converter (Parsedown)
function markdownToHtml($s)
{
	$parser = Parsedown::instance();
	$parser->setSafeMode(true);
	//$parser->setBreaksEnabled(true);
	return $parser->text($s);
}


// Convert note's raw text to html with allowed elements (b,i,u,s and raw urls)
function mttMarkup_v1($s)
{
	//hide allowed elements from escaping
	$c1 = chr(1);
	$c2 = chr(2);
	$s = preg_replace("~<b>([\s\S]*?)</b>~i", "${c1}b${c2}\$1${c1}/b${c2}", $s);
	$s = preg_replace("~<i>([\s\S]*?)</i>~i", "${c1}i${c2}\$1${c1}/i${c2}", $s);
	$s = preg_replace("~<u>([\s\S]*?)</u>~i", "${c1}u${c2}\$1${c1}/u${c2}", $s);
	$s = preg_replace("~<s>([\s\S]*?)</s>~i", "${c1}s${c2}\$1${c1}/s${c2}", $s);
	$s = htmlspecialchars($s, ENT_QUOTES); //escape all elements, except above
	$s = str_replace( [$c1, $c2], ['<','>'], $s ); //unhide
	$s = nl2br($s);

	// make links from text starting with 'www.'
	$s = preg_replace(
		"/(^|\s|>)(www\.([\w\#$%&~\/.\-\+;:=,\?\[\]@]+?))(,|\.|:|)?(?=\s|&quot;|&lt;|&gt;|\"|<|>|$)/iu" ,
		'$1<a href="http://$2" target="_blank">$2</a>$4' ,
		$s
	);

	// make link from text starting with protocol like 'http://'
	$s = preg_replace(
		"/(^|\s|>)([a-z]+:\/\/([\w\#$%&~\/.\-\+;:=,\?\[\]@]+?))(,|\.|:|)?(?=\s|&quot;|&lt;|&gt;|\"|<|>|$)/iu" ,
		'$1<a href="$2" target="_blank">$2</a>$4' ,
		$s
	);

	return $s;
}

// Convert raw title to html with allowed urls
function titleMarkup($title)
{
	//escape all unsafe
	$title = htmlspecialchars($title, ENT_QUOTES);

	// make links from text starting with 'www.'
	$title = preg_replace(
		"/(^|\s|>)(www\.([\w\#$%&~\/.\-\+;:=,\?\[\]@]+?))(,|\.|:|)?(?=\s|&quot;|&lt;|&gt;|\"|<|>|$)/iu" ,
		'$1<a href="http://$2" target="_blank">$2</a>$4' ,
		 $title
		);

	// make link from text starting with protocol like 'http://'
	$title = preg_replace(
		"/(^|\s|>)([a-z]+:\/\/([\w\#$%&~\/.\-\+;:=,\?\[\]@]+?))(,|\.|:|)?(?=\s|&quot;|&lt;|&gt;|\"|<|>|$)/iu" ,
		'$1<a href="$2" target="_blank">$2</a>$4' ,
		$title
	);
	return $title;
}

?>