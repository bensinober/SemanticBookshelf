<html>
<meta http-equiv="Content-type" value="text/html; charset=UTF-8" />
<head>
<title>ISBN oppslagsverktøy</title>

<style type="text/css">
<!--
form, textarea, table {
font: normal 11px verdana;
}
-->
</style>
</head>

<?php
header ('Content-type: text/html; charset=utf-8');

// Access keys (obtain one by creating a free account at: https://isbndb.com/account/create.html, and enter it here. It will not work without the access key.)
$isbndbKey = "INSERT ISBNDB KEY";
$librarythingKey = "INSERT LIBRARYTHING KEY";

// Get Post variables

$isbnlookup = $_POST['isbnlookup'];
$save = $_POST['save'];
$save_local_coverArt = $_POST['save_local_coverArt'];
$content = $_POST['content'];
$xisbn = $_POST['xisbn'];
$librarything = $_POST['librarything'];
$isbndb = $_POST['isbndb'];
$bibsys = $_POST['bibsys'];
$openlibrary = $_POST['openlibrary'];


// constant variables
$basegraph = 'http://bensinober.sleepingwolf.net/biblioteket/';

/* SAVE QUERY */
if ($save):

/* BEGIN INSERT INTO RDF STORE */

include_once("config.php");

/*include_once(dirname(__FILE__).'/arc/ARC2.php'); // path to the file ARC2.php */

/* instantiation */
$store = ARC2::getStore($arc_config);

/* if store is not set up */
if (!$store->isSetUp()) {
     $store->setUp();
   }

//$data = getText($_POST['content']);
$data = $_POST['content'];

//$parser = ARC2::getTurtleParser();
$parser = ARC2::getRDFParser();
$parser->parse($basegraph, $data);
$triples = $parser->getTriples();
$turtle_doc = $parser->toTurtle($triples);
$rdfxml = $parser->toRDFXML($triples);

/* INSERT */

/* old query, not working */
//$store->query('INSERT INTO <http://bensinober.sleepingwolf.net/biblioteket/> {' . $content . ' } ', 'raw', '', true);

/* empty table first */
// $store->reset();

/* insert either $content from posted form $_POST['content'] or parsed $triples */
//$store->insert($triples, $basegraph, $keep_bnode_ids = 0); 
$rs = $store->insert($content, $basegraph);

$added_triples = $rs['t_count'];
$load_time = $rs['load_time'];
$index_update_time = $rs['index_update_time'];

/* END INSERT TRIPLES */

	/**
	* SAVE COVER IMAGE LOCALLY WITH ISBN AS NAME
	*/
	if ($save_local_coverArt):

file_put_contents("images/isbn_" . $_POST['saveisbn'] . ".jpg", file_get_contents($save_local_coverArt));


	endif; /* END SAVE LOCAL COVER */

endif; /* END SAVE */

/* START XML PARSING */

/* define arrays */
	$isbns = array();
	$titles = array();
	$years = array();
	$creators = array();
	$publishers = array();
	$languages = array();
	$forms = array();
	$cities = array();
	$oclcnumbers = array();
	$urls =	array();
	$descriptions =	array();
	$subjects =	array();
	$urns =	array();
	$covers = array();
	$pages = array();
	$lccnnumbers = array();
	
/* end define arrays */

if ($isbnlookup):
	
/* 		WORLDCAT 	*/
/********************/
	
	if ($xisbn):
	// Url xisbn
	$url_details = "http://xisbn.worldcat.org/webservices/xid/isbn/$isbnlookup?method=getMetadata&format=xml&fl=*";
	
	// API lookup ISBN value at xisbn
	$xml_details = @simplexml_load_file($url_details) or die ("no file loaded") ;
	
	// Parse Data
	$isbns[] = 	$xml_details->isbn ;
	$titles[] = 	$xml_details->isbn['title'] ;
	$years[] =		$xml_details->isbn['year'] ;
	$creators[] =	$xml_details->isbn['author'] ;
	$publishers[] =	$xml_details->isbn['publisher'] ;
	$languages[] =		$xml_details->isbn['lang'] ;
	$forms[] =		$xml_details->isbn['form'] ;
	$cities[] =		$xml_details->isbn['city'] ;
	$oclcnumbers[] =	$xml_details->isbn['oclcnum'] ;
	$urls[] =		$xml_details->isbn['url'] ;
	endif; //xisbn
	
/* 		LIBRARYTHING 	*/
/************************/
	
	if ($librarything):
	// Url librarything
	$url_details = "http://www.librarything.com/services/rest/1.0/?method=librarything.ck.getwork&isbn=$isbnlookup&apikey=$librarythingKey";
	$xml_details = @simplexml_load_file($url_details) ;

	$isbn = $isbnlookup ;
	
	foreach ($xml_details->ltml[0]->item[0]->commonknowledge[0]->fieldList[0]->field as $field) {
		$fieldname = $field->attributes()->name;

		if ((string) $fieldname == "canonicaltitle") {
		$titles[] = $field->versionList[0]->version[0]->factList[0]->fact ;
		}
		if ((string) $fieldname == "originalpublicationdate") {
		$years[] = $field->versionList[0]->version[0]->factList[0]->fact ;
		}
	}
	
	if ($xml_details->ltml[0]->item[0]->author) {
	$creators[] =	$xml_details->ltml[0]->item[0]->author ;
	}
	
//	$languages[] =		"eng" ;

	$forms[] =		$xml_details->isbn['form'] ;
	
	if ($xml_details->isbn['city']) {
		$cities[] =		$xml_details->isbn['city'] ;
	}
	
	$oclcnumbers[] =	$xml_details->isbn['oclcnum'] ;
	
	if ($xml_details->isbn['url']) { 
	$urls[] =		$xml_details->isbn['url'] ;
	}
	
	$covers[] = "http://covers.librarything.com/devkey/" . $librarythingKey . "/medium/isbn/" . $isbnlookup . "";
	
	endif; //librarything
	
/* 		ISBNDB 		*/
/********************/
	
	if ($isbndb):
	$url_details = "http://isbndb.com/api/books.xml?access_key=$isbndbKey&results=details&index1=isbn&value1=$isbnlookup";
	$xml_details = @simplexml_load_file($url_details) or die ("no file loaded") ;
	
	// Parse Data
	$isbns[] = 		$xml_details->BookList[0]->BookData[0]['isbn'] ;
	$titles[] = 		$xml_details->BookList[0]->BookData[0]->Title ;
	$creators[] =		$xml_details->BookList[0]->BookData[0]->AuthorsText ;
	$publishers[] =	$xml_details->BookList[0]->BookData[0]->PublisherText ;
	if ($xml_details->BookList[0]->BookData[0]->Details[0]['edition_info'] != '') {
		$years[] =		$xml_details->BookList[0]->BookData[0]->Details[0]['edition_info'] ;
	}
	$descriptions[] =			$xml_details->BookList[0]->BookData[0]->Details[0]['physical_description_text'] ;
	endif; //isbndb

/* 		BIBSYS 		*/
/********************/

	if ($bibsys):
	$c = curl_init();
	$headers = array("Accept:application/rdf+xml");
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_URL, "http://sru.bibsys.no/services/sru?operation=searchRetrieve&version=1.1&query=isbn=%22" . $isbnlookup . "%22&recordSchema=dc");
	curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($c, CURLOPT_POST, true);
	$query = curl_exec($c);
	echo curl_error($c);
	curl_close($c);

/* 
 * must use simple DOM here, as neither simpleXML nor XMLReader seems to validate the bibsys xml */
$dom = new DOMDocument;
$dom->loadXML($query);
if (!$dom) {
 echo 'Error while parsing the document';
 exit;
}

$bibsystitles = $dom->getElementsByTagName("title");
foreach ($bibsystitles as $bibsystitle) {
  $titles[] = $bibsystitle->firstChild->data ;
		}

$bibsyscreators = $dom->getElementsByTagName("creator");
foreach ($bibsyscreators as $bibsyscreator) {
  $creators[] = $bibsyscreator->firstChild->data ;
		}

$bibsyspublishers = $dom->getElementsByTagName("publisher");
foreach ($bibsyspublishers as $bibsyspublisher) {
  $publishers[] = $bibsyspublisher->firstChild->data ;
		}

$bibsysyears = $dom->getElementsByTagName("date");
foreach ($bibsysyears as $bibsysyear) {
  $years[] = $bibsysyear->firstChild->data ;
		}

$bibsyslanguages = $dom->getElementsByTagName("language");
foreach ($bibsyslanguages as $bibsyslanguage) {
  $languages[] = $bibsyslanguage->firstChild->data ;
		}

$bibsyssubjects = $dom->getElementsByTagName("subject");
foreach ($bibsyssubjects as $bibsyssubject) {
  $subjects[] = $bibsyssubject->firstChild->data ;
		}
		
$bibsyscities = $dom->getElementsByTagName("coverage");
foreach ($bibsyscities as $bibsyscity) {
  $cities[] = $bibsyscity->firstChild->data ;
		}
		
$bibsysurns = $dom->getElementsByTagName("identifier");
foreach ($bibsysurns as $bibsysurn) {
  $urns[] = $bibsysurn->firstChild->data ;
		}

$bibsysdescriptions = $dom->getElementsByTagName("description");
foreach ($bibsysdescriptions as $bibsysdescription) {
  $descriptions[] = $bibsysdescription->firstChild->data ;
		}

	endif; //bibsys
	
/* 		OPENLIBRARY 		*/
/****************************/
	
	if ($openlibrary):
	
	$c = curl_init();
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	//curl_setopt($c, CURLOPT_URL, "http://openlibrary.org/api/books?bibkeys=ISBN:" . $isbnlookup . "&format=json&jscmd=data");
	curl_setopt($c, CURLOPT_URL, "http://openlibrary.org/api/search?q={%22query%22:%22(isbn_10:(" . $isbnlookup . ")%20OR%20%20isbn_13:(" .  $isbnlookup . "))%22}");
	$query = curl_exec($c);
	echo curl_error($c);
	curl_close($c);


//$json = json_decode($query,true); true gives back arrays, if not -> objects
$book=json_decode($query,true) ;
$OL_key = $book["result"];
//var_dump ($results);

// creates OpenLibrary ID from first result
$openlibrary_url = "http://openlibrary.org/api/get?key=" . $OL_key[0];
$OLResult=file_get_contents($openlibrary_url) ;
$OL_data=json_decode($OLResult,true) ;
//var_dump($OpenLibrary_data);
if ($OL_data["status"] == "ok") {
	
	$titles[] = 	$OL_data['result']['title'] ;
	$years[] =		$OL_data['result']['publish_date'] ;
	$creators[] =	$OL_data['result']['by_statement'] ;
	
	foreach ($OL_data['result']['publishers'] as $publisher) {
	$publishers[] =	$publisher ;
	}
	foreach ($OL_data['result']['languages'] as $language) {
		/* NEED TO REMOVE PATH TO LANGUAGE HERE */
	$languages[] =	str_replace("/languages/", "", $language['key']) ;
	}
	
	foreach ($OL_data['result']['publish_places'] as $city) {
	$cities[] =		$city ;
	}
	foreach ($OL_data['result']['subjects'] as $subject) {
	$subjects[] =		$subject ;
	}
	$pages[] = 		$OL_data['result']['number_of_pages'] ;

	foreach ($OL_data['result']['lccn'] as $lccnnumber) {
	$lccnnumbers[] =$lccnnumber ;
	}
	
	// create url for medium sized cover, cut first three characters of id
	$covers[] =		"http://covers.openlibrary.org/b/olid/" . substr($OL_key[0], 3) . "-M.jpg" ;

	}
	//var_dump($covers);
	//var_dump(json_decode($query, true));
	
	endif; /* END OPENLIBRARY */


/* 
 * compose query 
 * 				*/
$q = '
@PREFIX xsd: <http://www.w3.org/2001/XMLSchema#> .
@PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@PREFIX owl: <http://www.w3.org/2002/07/owl#> .
@PREFIX foaf: <http://xmlns.com/foaf/0.1/> .
@PREFIX dct: <http://purl.org/dc/terms/> .
@PREFIX skos: <http://www.w3.org/2004/02/skos/core#> .
@PREFIX lccn:	<http://lccn.loc.gov/> .
@PREFIX bok: <http://bensinober.sleepingwolf.net/biblioteket/> .
@PREFIX instance: <http://bensinober.sleepingwolf.net/biblioteket/instance/> .
@PREFIX bibo: <http://purl.org/ontology/bibo/> .
@PREFIX geo: <http://www.geonames.org/ontology#> .
';
/*
 * MAIN TRIPLES */

$q .= "\nbok:isbn_" . $isbnlookup . "\n";
$q .= "  a bibo:Document ";

foreach ($titles as $title) {
	if (!$title == "") {
	$q .= ";\n   dct:title		\"" . $title . "\"^^xsd:string " ;
	}
}

foreach ($isbns as $isbn) {
	if (!$isbn == "") {
	$q .= ";\n   bibo:isbn		\"" . $isbn . "\"^^xsd:string " ;
	}
}

foreach ($years as $year) {
	if (!$year == "") {
		$q .= ";\n   dct:issued	\"" . $year . "\"^^xsd:int " ;
	}
}

foreach ($pages as $page) {
	if (!$page == "") {
		$q .= ";\n   bibo:numPages	\"" . $page . "\"^^xsd:int " ;
	}
}

/* remove non-ascii characters in instances */
foreach ($publishers as $publisher) {
	if (!$publisher == "") {
		$q .= ";\n   dct:publisher		instance:" . ereg_replace("[^A-ZÆØÅa-zæøå0-9]", "", $publisher) . " " ;
	}
}
	
foreach ($creators as $creator) {
	if (!$creator == "") {
		$q .= ";\n   dct:creator		instance:" . ereg_replace("[^A-ZÆØÅa-zæøå0-9]", "", $creator) . " " ;
	}
}

foreach ($subjects as $subject) {
	foreach (split("\ ", $subject) as $split_subject) {
	if (!$split_subject == "") {
		$q .= ";\n   bibo:subject		instance:" . ereg_replace("[^A-ZÆØÅa-zæøå0-9]", "", $split_subject) . " " ;
		}
	}
}
	
foreach ($languages as $language) {
	if (!$language == "") {
		$q .= ";\n   dct:language		\"" . $language . "\"^^xsd:string " ;
	}
}
	
foreach ($urls as $url) {
	if (!$url == "") {
		$q .= ";\n   bibo:url		<" . $url . "> " ;
	}
}
	
foreach ($cities as $city) {
	if (!$city == "") {
		$q .= ";\n   geo:name	\"" . $city . "\"^^xsd:string " ;
	}
}

foreach ($descriptions as $description) {
	if (!$description == "") {
		$q .= ";\n   dct:description	\"" . $description . "\"^^xsd:string " ;
	}
}
	
foreach ($urns as $urn) {
	if (!$urn == "") {
		$q .= ";\n   bibo:uri		<" . $urn . "> " ;
	}
}

foreach ($covers as $cover) {
	if (!$cover == "") {
		$q .= ";\n   bok:coverArt		<" . $cover . "> " ;
	}
}

foreach ($oclcnumbers as $oclcnumber) {
	foreach (split("\ ", $oclcnumber) as $split_oclcnumber) {
	if (!$split_oclcnumber == "") {
		$q .= ";\n   bibo:oclcnumber		bibo:" . $split_oclcnumber . " " ;
		}
	}
}

foreach ($lccnnumbers as $lccnnumber) {
	if (!$lccnnumber == "") {
		$q .= ";\n   owl:sameAs		lccn:" . $lccnnumber . " " ;
	}
}

	$q .= ".\n";

/* INSTANCES */
/*************/

	foreach ($publishers as $publisher) {
	if (!$publisher == "") {
		$q .= "\n\ninstance:" . ereg_replace("[^A-ZÆØÅa-zæøå0-9]", "", $publisher) . " \n" ;
		$q .= "\t a foaf:Organization ;\n" ;
		$q .= "\t foaf:name \t\"" . $publisher . "\"^^xsd:string .\n" ;
		}
	}
	
	foreach ($creators as $creator) {
	if (!$creator == "") {
		$q .= "\n\ninstance:" . ereg_replace("[^A-ZÆØÅa-zæøøå0-9]", "", $creator) . " \n" ;
		$q .= "\t a foaf:Person ;\n" ;
		$q .= "\t foaf:name \t\"" . $creator . "\"^^xsd:string .\n" ;
		}
	}
	
	
	foreach ($subjects as $subject) {
		foreach (split("\ ", $subject) as $split_subject) {
		if (!$split_subject == "") {
			$q .= "\n\ninstance:" . ereg_replace("[^A-ZÆØÅa-zæøå0-9]", "", $split_subject) . " \n" ;
			$q .= "\t a  skos:Concept ;\n" ;
			$q .= "\t skos:prefLabel \t\"" . $split_subject . "\"^^xsd:string .\n" ;
			}
		}
	}		

/* end query */

endif;  /* END PARSING ISBNLOOKUP */

?>

<!DOCTYPE html "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

<title>ISBN oppslagsverktøy</title>

<style type="text/css">
<!--
form, textarea, table {
font: normal 11px verdana
}
-->
</style>
</head>

<body onLoad="document.the_form.isbnlookup.focus();" bgcolor="#eaeaea">
<div align="center"><h1>Bokhylla ~ oppslag i bokbaser ~ overslag til RDF</h1></div>
<table width="90%" border="1" cellspacing="0" cellpadding="2">

<tr>
<td>
 <table width="500" border="1" cellspacing="0" cellpadding="2">
  <tr>
    <td><b>Spørring</b></td>
    <td><? print $isbnlookup; ?></td>
  </tr>
  <tr>
    <td><b>ISBN</b></td>
    <td><? print $isbn; ?>&nbsp;</td>
  </tr>
  <tr>
    <td><b>Tittel</b></td>
    <td><? print $title; ?>&nbsp;</td>
  </tr>
  <tr>
    <td><b>Utgiverår</b></td>
    <td><? print $year; ?>&nbsp;</td>
  </tr>
  <tr>
    <td><b>Forfatter(e)</b></td>
    <td><? print $creators[0]; ?>&nbsp;</td>
  </tr>
  <tr>
    <td><b>Utgiver</b></td>
    <td><? print $publisher; ?>&nbsp;</td>
  </tr>
  <tr>
    <td><b>Språk</b></td>
    <td><? print $language; ?>&nbsp;</td>
  </tr>
  <tr>
    <td><b>Beskrivelse</b></td>
    <td><? print $description; ?>&nbsp;</td>
  </tr>
   <tr>
    <td><b>Emner</b></td>
    <td><? print $subject; ?>&nbsp;</td>
  </tr>
  <tr>
    <td><b>By</b></td>
    <td><? print $city; ?>&nbsp;</td>
  </tr>
  <tr>
    <td><b>OCLC-nummer</b></td>
    <td><? print $split_oclcnumber; ?>&nbsp;</td>
  </tr>
  <tr>
    <td><b>URL</b></td>
    <td><? print $url; ?>&nbsp;</td>
  </tr>
 </table>

<form method="post" action="isbnlookup.php" name="the_form">
<b>[ISBN]:</b><br>
<input type="text" size="18" value="" name="isbnlookup">
<input type="submit" value="send inn" name="send"><br/>
<input type="checkbox" value="xisbn" name="xisbn" checked>worldcat</input>
<input type="checkbox" value="librarything" name="librarything" checked>librarything</input>
<input type="checkbox" value="isbndb" name="isbndb" checked>isbndb</input>
<input type="checkbox" value="bibsys" name="bibsys" checked>bibsys</input>
<input type="checkbox" value="openlibrary" name="openlibrary" checked>openlibrary</input>
</form>
</td>

<td>
<form method="post" action="bokhylla.php">
<textarea name="content" cols="80" rows="20" ><?php echo $q; ?></textarea>
<input type="submit" value="lagre tripler i ARC" name="save">

<?php
/* DISPLAY PICTURES */
if ($isbnlookup): 
print '
<table border="1" cellspacing="0" cellpadding="2">
	<tr><td>Save coverart (if available): </tr></td>';
foreach ($covers as $cover) {
		if (!$cover == "") {
		print '<tr>';
		print '<td><input type="checkbox" value="' . $cover . '" name="save_local_coverArt">save coverArt: <img src="' . $cover . '" alt="' . $cover . '"></td>';
		print '</tr>';
	}
}
	print '</table>';
endif;
?>
<input type="hidden" name="saveisbn" value="<?php echo $isbnlookup;?>">
</form>

</td>
</tr>
</table>

<?php
/* PRINT OUT INSERTED TRIPLES */
if ($save):
print '	
	<table>
	<tr><td>Successfully inserted triples: ' . $added_triples . '<br>
	Insert time: ' . $load_time . '</tr></td>
	';
	foreach ($triples as $triple) {
		print '<tr>';
		print '<td>' . $triple['s'] . '&nbsp;&nbsp;</td><td>' . $triple['p'] . '&nbsp;&nbsp;</td><td>' . $triple['o'] . '</td>' ;
		print '</tr>';
	}
	print '</table><br><br>';
	
	print 'saved coverArt: ' . $save_local_coverArt . '';
endif;
?>

</body>
</html>
