<?php
header ('Content-type: text/html; charset=utf-8');

include_once("config.php");

/* instantiation */
$store = ARC2::getStore($arc_config);

/* if store is not set up */
if (!$store->isSetUp()) {
     $store->setUp();
   }

/* VARIABLES */

// constant variables
$basegraph = 'http://bensinober.sleepingwolf.net/biblioteket/';

/* POSTS ---
 * FROM QUERY FORM */
if (isset($_POST['sorting'])) {
$sorting = $_POST['sorting'];
}
if (isset($_POST['sok'])) {
$sok = $_POST['sok'];
}

/* GETS --- 
 * FROM URI */
$offset = $_GET['offset'];

/* get sorting from from url if POST is not set */
if (!isset($_POST['sorting'])) {
$sorting = $_GET['sorting'];
}

/* get search term from url if POST is not set */ 
if (!isset($_POST['sok'])) {
$sok = $_GET['sok'];
}

/* get selected individual item from url */ 
if (isset($_GET['item'])) {
$item = $_GET['item'];
}

/* get selected individual object from url */ 
if (isset($_GET['object'])) {
$object = $_GET['object'];
}

/* get selected individual object from url */ 
if (isset($_GET['edit'])) {
$edit = $_GET['edit'];
}

/* save form */ 
if (isset($_POST['save'])) {
$save = $_POST['save'];
}

/* END VARIABLES */

/* SAVE EDITED QUERY */
if (isset($POST['save'])) {


/* BEGIN INSERT INTO RDF STORE */

//$parser = ARC2::getTurtleParser();
//$parser = ARC2::getRDFParser();
//$parser->parse($basegraph, $data);
//$triples = $parser->getTriples();
//$turtle_doc = $parser->toTurtle($triples);
//$rdfxml = $parser->toRDFXML($triples);


/* INSERT */

/* old query, not working */
//$store->query('INSERT INTO <http://bensinober.sleepingwolf.net/biblioteket/> {' . $content . ' } ', 'raw', '', true);

/* empty table first */
// $store->reset();

/* insert either $content from posted form or parsed $triples */
//$store->insert($triples, $basegraph, $keep_bnode_ids = 0); 
//$rs = $store->insert($content, $basegraph);

//$added_triples = $rs['t_count'];
//$load_time = $rs['load_time'];
//$index_update_time = $rs['index_update_time'];


} /* END SAVE */


/* 
 * SPARQL QUERIES 
 * 				*/
/* paging */
if (!$limit) {
	$limit=25;
}

/* if item is not selected, use listing query */
if (!isset($item)) {

$q = '
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#> 
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> 
PREFIX owl: <http://www.w3.org/2002/07/owl#> 
PREFIX foaf: <http://xmlns.com/foaf/0.1/> 
PREFIX dct: <http://purl.org/dc/terms/> 
PREFIX skos: <http://www.w3.org/2004/02/skos/core#> 
PREFIX bok: <http://bensinober.sleepingwolf.net/biblioteket/> 
PREFIX instance: <http://bensinober.sleepingwolf.net/biblioteket/instance/> 
PREFIX bibo: <http://purl.org/ontology/bibo/> 
PREFIX geo: <http://www.geonames.org/ontology#> 

SELECT ?book ?title ?person WHERE {
?book a bibo:Document ;
dct:title ?title .

optional {
{
?book dct:creator ?creator .
?creator foaf:name ?person .
} union {
?book bibo:translator ?translator .
?translator foaf:name ?person .
}
}
';

if (!$sok == "") { 
	$q .= "FILTER(regex(?title, \"" . $sok . "\", \"i\") || regex(?person, \"" . $sok . "\", \"i\")) ";
	}
$q .= "}";
if (!$sorting == "") { 
	if ($sorting == "title") {
		$q .= "ORDER BY ?title ";
		}
	if ($sorting == "person") {
		$q .= "ORDER BY ?person ";
		}
	}
	$q .= "LIMIT " . $limit . " ";
if (!$offset == "") { 
	$q .= "OFFSET " . $offset . " ";
}

}/* END LIST QUERY */


?>
<!DOCTYPE html "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link href="style.css" rel="stylesheet" type="text/css">
<title>Bokhylla</title>
</head>
<body>
<div class="my_wrapper">

   <div class="my_header">
       <h1><a href="index.php">Bokhylla i Markveien</a></h1>

    </div> 
	<div class="left_box">
<?php 

/* STANDARD LISTING 
 *************/
if (!isset($item) && !isset($object) && !isset($edit) && !isset($save)) {

$rs = $store->query($q);
if (!$store->getErrors()) {
$triples = $rs['result']['rows'];
$duration = $rs['query_time'];
$totalrows = count($triples);
		print '<table class="pretty"><tr>
		<th><a href="index.php?offset=' . $offset . '&sorting=uri&sok=' . $sok . '">uri</a></th>
		<th><a href="index.php?offset=' . $offset . '&sorting=title&sok=' . $sok . '">tittel</a></th>
		<th><a href="index.php?offset=' . $offset . '&sorting=person&sok=' . $sok . '">person</a></th>
		</tr>';
foreach ($triples as $triple) {
		print '<tr>
		<td><a href="index.php?item=' . $triple['book'] . '">' . $triple['book'] . '</a></td>
		<td>' . $triple['title'] . '</td><td>' . $triple['person'] . '</td></tr>' ;
	}
		print '</table><br/>';
}
/* pagination links */
echo '<div align="right">';
if ($offset > 25) {
echo '<a href="index.php?offset=' . $back = $offset - $limit . '&sorting=' . $sorting . '&sok=' . $sok . '"> << </a>';
}
echo '<a href="index.php?offset=' . $forward = $offset + $limit . '&sorting=' . $sorting . '&sok=' . $sok . '"> >> </a>';
echo '</div>';
echo "<br/>tid på spørring: " . $duration . "<br/>";
echo "antall rader: " . $totalrows . "<br/>";

} /* END LISTING */



/* ITEM VIEW 
 *************/
if (isset($item)) {
	
$itemquery = "SELECT * WHERE { <" . $item . "> ?p ?o }";	
$rs = $store->query($itemquery);
if (!$store->getErrors()) {
$triples = $rs['result']['rows'];
$duration = $rs['query_time'];
$totalrows = count($triples);
 //var_dump($triples);

//$triples = $parser->getTriples($serialized);

		echo '<table class="pretty">';
		echo $item;
		/* print edit link */
		print '&nbsp;&nbsp;&nbsp;<a href="index.php?edit=' . $item . '">rediger</a>'; 
		echo '<tr><th>propertier</th><th>objekter</th></tr>';
	
	foreach ($triples as $triple) {
		print '
		<tr><td>' . $triple['p'] . '</td>
		';
			if ($triple['o type'] == "uri") {
				// if type uri, make link for object view
				print '<td><a href="index.php?object=' . $triple['o'] . '">' . $triple['o'] . '</a></td>'; 
				} else { 
				echo '<td>' . $triple['o'] . '</td>';
				}
			} /* end foreach */ 
		print '</tr></table><br/>';
		} 
} /*  END ITEM VIEW */


/* OBJECT VIEW 
 *************/
if (isset($object)) {
	
$objectquery = '
PREFIX dct: <http://purl.org/dc/terms/> 
SELECT ?book ?title WHERE { 
	?book ?p <' . $object . '> ;
	   dct:title ?title }
';	
$rs = $store->query($objectquery);
if (!$store->getErrors()) {
$triples = $rs['result']['rows'];
$duration = $rs['query_time'];
$totalrows = count($triples);
 //var_dump($triples);

		echo '<table class="pretty">';
		echo $object;
		echo '<tr><th>book</th><th>title</th></tr>';
	
	foreach ($triples as $triple) {
		print '<tr>
		<td><a href="index.php?item=' . $triple['book'] . '">' . $triple['book'] . '</a></td>
		<td>' . $triple['title'] . '</td></tr>';
			} /* end foreach */ 
		print '</table><br/>';
		}
} /*  END OBJECT VIEW */

/* EDIT ITEM
*************/
if (isset($edit)) {
$itemquery = "SELECT * WHERE { <" . $edit . "> ?p ?o }";	
$rs = $store->query($itemquery);
if (!$store->getErrors()) {
$triples = $rs['result']['rows'];
$duration = $rs['query_time'];
$totalrows = count($triples);
 // var_dump($triples);
		print 'redigerer: ' . $edit . ' ';
		print '<form class="pretty" method="post" action="index.php" name="edit_form">';
		echo '<table class="pretty" >';
		echo '<tr><th>property</th><th>object</th><th>type</th><th>datatype</th><th>slett</th></tr>';
 
		/* empty edit and delete triples, and make counter for triples array */
		$edittriples = array();
		$deletetriples = array();
		$i = 0;
	
	foreach ($triples as $triple) {
		
		/* parse out editable triples, 
		 * NB! no quotes around array elements in form name */
		print "<input type=\"hidden\" name=\"edittriples[" . $i . "][s]\" value=\"" . $edit . "\" >"; 
		print "<td><input type=\"hidden\" name=\"edittriples[" . $i . "][p]\" value=\"" . $triple['p'] . "\">" . $triple['p'] . "</td>\n";
		print "<td><input type=\"text\" size=\"70\" name=\"edittriples[" . $i . "][o]\" value=\"" . $triple['o'] . "\"></td>\n";
		print "<td><input type=\"hidden\"  name=\"edittriples[" . $i . "][o type]\" value=\"" . $triple['o type'] . "\">". $triple['o type'] ."</td>\n";
		print "<td><input type=\"hidden\"  name=\"edittriples[" . $i . "][o datatype]\" value=\"" . $triple['o datatype'] . "\">". $triple['o datatype'] ."</td>\n";

		/* print out delete checkbox, with o type as hidden */
		print "<td><input type=\"checkbox\" name=\"deletetriples[" . $i . "][o]\" value=\"". $triple['o'] . "\"></td></tr>";
		print "<td><input type=\"hidden\" name=\"deletetriples[" . $i . "][o type]\" value=\"". $triple['o type'] . "\"></td></tr>";
		
		$i = $i +1;  /* increment counter for array */
		} /* end foreach */
		print '</table><br/>';
		
		} /* end getErrors */ 

		print '<input type="submit" value="oppdatér bok" name="save">
		</form>';

} /*  END EDIT */


if (isset($save)) {

$edittriples = $_POST['edittriples'];
$deletetriples = $_POST['deletetriples'];
$uri = $_POST['edittriples'][0]['s'];

//print_r($edittriples);
//print_r($deletetriples);

/* EDITED TRIPLES */

		/* COMPOSE QUERIES */
		/* first delete all triples from selected subject */
		$deletequery = "DELETE { <" . $uri . "> ?p ?o } ";
		
		/* INSERT EDITED TRIPLES */
		$insertquery = "INSERT INTO <http://bensinober.sleepingwolf.net/biblioteket/> { "; 
	
	/* parse through edited triples */
	foreach ($edittriples as $edittriple) {
		
		$insertquery .= " <". $edittriple['s'] . "> <" . $edittriple['p'] . "> ";

		/* is object uri or literal? */
		if ($edittriple['o type'] == "uri") {
		/* uri? then wrap object in angle brackets */
		$insertquery .= " <" . $edittriple['o'] . "> . \n";
		/* else check for string or integer */
		} else { 
			if ($edittriple['o datatype'] == "http://www.w3.org/2001/XMLSchema#int") {
			$insertquery .= " \"" . $edittriple['o'] . "\"^^xsd:int . \n";
			} else {
			$insertquery .= " \"" . $edittriple['o'] . "\"^^xsd:string . \n";
			}
		}
	} /* end foreach edittriples*/
	$insertquery .= " } ";
	//print '</table>';
	
/* UPDATE */
/* RUN DELETE QUERY */
	$rs = $store->query($deletequery);
	if (!$store->getErrors()) {
		print '<table class="pretty">
		sparql delete
				   <th>antall</th><th>slettetid</th><th>indeksoppdatering</th><th>spørretid</th>
					   <tr>';
		print '<td>' . $rs['result']['t_count'] . '</td>' ;
		print '<td>' . $rs['result']['delete_time'] . '</td>' ;
		print '<td>' . $rs['result']['index_update_time'] . '</td>' ;
		print '<td>' . $rs['query_time'] . '</td>' ;
		print '		</tr>
			   </table>';
		}
/* INSERT MODIFIED TRIPLES */
	$rs = $store->query($insertquery);

if (!$store->getErrors()) {
		print '<table class="pretty">
		sparql insert
				   <th>antall</th><th>lastetid</th><th>spørretid</th>
					   <tr>';
		print '<td>' . $rs['result']['t_count'] . '</td>' ;
		print '<td>' . $rs['result']['load_time'] . '</td>' ;
		print '<td>' . $rs['query_time'] . '</td>' ;
		print '		</tr>
			   </table>';
		}
		
//var_dump ($deletequery);	
//var_dump ($insertquery);
//var_dump ($rs);

/* DELETED TRIPLES */

	print 'slettede tripler:';
	print	'<table class="pretty">
	<th>antall</th><th>delete_time</th><th>index_update</th>
	<tr><td></td></tr>
	';
	$removequery = "DELETE FROM <http://bensinober.sleepingwolf.net/biblioteket/> { "; 
	
	foreach ($deletetriples as $deletetriple) {
		/* if checked box, then object != "" */
		if (!$deletetriple['o'] == "") {
		
		/* is object uri or literal? */
		if ($deletetriple['o type'] == "literal") {
		/* literal? then wrap object in quotes */
		$removequery .= " <" . $uri . "> ?p \"" . $deletetriple['o'] . "\" . \n";
		} else {
		/* else wrap in uri brackets */
		$removequery .= " <" . $uri . "> ?p <" . $deletetriple['o'] . ">  . \n";
		}
		
		} /* end check if delete box is checked */
	} /* end foreach */
$removequery .= " } ";
$rs = $store->query($removequery);

if (!$store->getErrors()) {
		print '<tr>';
		print '<td>' . $rs['result']['t_count'] . '</td>' ;
		print '<td>' . $rs['result']['delete_time'] . '</td>' ;
		print '<td>' . $rs['result']['index_update_time'] . '</td>' ;
		print '</tr>';
		}

	print '</table>';

/* PRINT OUT MODIFIED BOOK */	
$q = "SELECT * WHERE { <" . $uri . "> ?p ?o }";
$rs = $store->query($q);
$triples = $rs['result']['rows'];
	print '	
		<table class="pretty">
		redigerte tripler fra: ' . $uri . '<br>	';
		print '<tr><th>property</th><th>object</th><th>type</th><th>datatype</th></tr>';

		foreach ($triples as $triple) {

		print '<tr>';
		print '<td>' . $triple['p'] . '&nbsp;&nbsp;</td><td>' . $triple['o'] . '</td><td>' . $triple['o type'] . '</td><td>' . $triple['o datstype'] . '</td>' ;
		print '</tr>';
		
		} /* end foreach */
	print '</table>';

	
	
} /* END SAVE */



?>
</div> <!-- end leftbox -->

<div class="right_box">

<!-- START RIGHT COLUMN -->
<?php
/* IF ITEM VIEW, SHOW IMAGE */
if (isset($item)) {
$imagequery = "SELECT * WHERE { <" . $item . "> <http://bensinober.sleepingwolf.net/biblioteket/coverArt> ?o }";	
$rs = $store->query($imagequery);
if (!$store->getErrors()) {
$triples = $rs['result']['rows'];
	foreach ($triples as $triple) {
	echo '<img src="' . $triple['o'] . '"/>';
		}
	}
} /* END IMAGE VIEW */
?>
	<br/><br/><b>Alternativer</b><br>

	<form method="post" action="index.php" name="the_form">
	søk:<input type="text" size="18" name="sok"><br/>
	sortering: 
	<select name="sorting">
	<option value="person">person</option>
	<option value="title">tittel</option>
	</select><br/>
	Treff per side:
	<select name="limit">
	<option value="25">25</option>
	<option value="50">50</option>
	</select><br/>
	<input type="submit" value="sorter" name="send">
	</form>

	<br/><br/>
	<b>statistikk</b><br/>
	
	<?php 
/* count books */
$rs = $store->query("
PREFIX bok: <http://bensinober.sleepingwolf.net/biblioteket/> 
PREFIX bibo: <http://purl.org/ontology/bibo/> 
SELECT count(*) as ?count where {
	?s a bibo:Document
}"
);
if (!$store->getErrors()) {
$triples = $rs['result']['rows'];
echo "antall bøker: ";
echo $triples[0]['count'] , "<br/>";
}

/* count persons */
$rs = $store->query("
PREFIX foaf: <http://xmlns.com/foaf/0.1/> 
SELECT count(*) as ?count where {
	?s a foaf:Person
}"
);
if (!$store->getErrors()) {
$triples = $rs['result']['rows'];
echo "antall personer: ";
echo $triples[0]['count'], "<br/>";
}

/* count triples */
$rs = $store->query("
PREFIX bok: <http://bensinober.sleepingwolf.net/biblioteket/> 
PREFIX bibo: <http://purl.org/ontology/bibo/> 
SELECT count(*) as ?count where {
	?s ?p ?o
}"
);
if (!$store->getErrors()) {
$triples = $rs['result']['rows'];
echo "antall tripler: ";
echo $triples[0]['count'], "<br/>";
}

?>

<br/>
<a href="endpoint.php">sparql endpoint</a><br/><br/>
<a href="isbnlookup.php">isbn lookup tool</a>
</div><!-- end right_box-->

    <div class="footer">
       (c) 2010 ~ Benjamin og Kristin
    </div>

</div>
</body>
</html>
