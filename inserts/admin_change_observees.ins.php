<?php

// GET the user being edited
if ((isset($_GET['v'])) && (filter_var($_GET['v'], FILTER_VALIDATE_INT, array('min_range' => 1)))) {
	$u_id = preg_replace("/[^0-9]/","", $_GET['v']);
} else {
	return;
}

// Just arrived?
if ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['opened_by'])) && ($_POST['opened_by'] == $userid) ) {
	// $where_was_i ?
	if (isset($_SERVER['HTTP_REFERER'])) {
		$where_was_i = filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL);
	}
}

// Process a form POST
if ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['submit_button'])) && (isset($_POST['v'])) && ($_POST['v'] == $u_id) ) {
	// Check for $where_was_i
	if ((isset($_POST['where_was_i'])) && (filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL))) {
		$where_was_i = filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL);
		unset($_POST['where_was_i']);
	}

	// Clear the POST so we can iterate through it
	unset($_POST['submit_button']);
	unset($_POST['opened_by']);
	unset($_POST['v']);
	// Don't create a false JSON array if it is null, that can trip false entries in forms, and we need to json_decode() a null value correctly
	if (count($_POST) === 0) {
		$u_observing_json = 'null';
	} else {
		$string_blocks = implode(', ', $_POST); // Remove keys
		$u_observing_json = json_encode(explode(', ', $string_blocks)); // Send to JSON
	}

	// SQL
	$q = "UPDATE users SET observing='$u_observing_json' WHERE id='$u_id'";
	$r = mysqli_query($dbc, $q);
	if ($r) {
		echo '<p class="noticegreen sans">Observees saved.</p>';

		// Check for $where_was_i
		if (isset($where_was_i)) {
			set_button("&larr; Go back", "Return to the page that brought you here", $where_was_i, "newNoteButton");
		}

	} else {
		echo '<p class="noticered sans">Impossible error using SQL!</p>';
	}
}

// Sorting options
$sort_get = (strstr($where_am_i, '?')) ? '&' : '?' ;

// Sort GET setting
$creation_cl = 'act_ltgray';
$name_cl = 'act_ltgray';
if ((isset($_GET['s'])) && (preg_match("/[a-z]/", $_GET['s']))) {
	$sort = preg_replace("/[^a-z]/","", $_GET['s']);
	switch ($sort) {
		case "creation":
				$order_by = "id DESC";
				$creation_cl = 'act_dkgray';
				$sort_suffix = 's=creation';
				break;
		case "name":
				$order_by = "name ASC";
				$name_cl = 'act_dkgray';
				$sort_suffix = 's=name';
				break;
		default:
				$order_by = "id DESC";
				$creation_cl = 'act_dkgray';
				$sort_suffix = 's=creation';
				break;
	}
} else {
	$order_by = "name ASC";
	$name_cl = 'act_dkgray';
	$sort_suffix = '';
}

// Get the observing user info
$q = "SELECT name, username FROM users WHERE id='$u_id' ";
$r = mysqli_query($dbc, $q);
$row = mysqli_fetch_array($r);
	$u_name = "$row[0]";
	$u_username = "$row[1]";

// Heading
echo '<h2 class="lt">Change User Observees</h2>';

// User info
echo '<p class="sans lt">Observees for: <b>'.$u_name.'</b> <small>('.$u_username.')</small><br>';

// Start the form
echo '<form action="'.$rformaction.'?v='.$u_id.'" method="post" accept-charset="utf-8">
<input type="hidden" name="v" value="'.$u_id.'">';

// $where_was_i ?
echo (isset($where_was_i)) ? '<input type="hidden" name="where_was_i" value=".$where_was_i.">' : false ;

// SQL concatenate match
$sql_cols = 'id, editor, name, username';
$concat_where_statement = "users o WHERE EXISTS (SELECT 1 FROM users u WHERE JSON_CONTAINS(u.observing, CONCAT('\"', o.id, '\"')) AND u.id = '$u_id') AND type='writer'";
$q = "SELECT $sql_cols FROM $concat_where_statement ORDER BY $order_by";
$r = mysqli_query ($dbc, $q);

// Empty?
if (mysqli_num_rows($r) == 0) {
	echo '<p class="lt sans">No observed writers</p>';
} else {
	// Start the Observing table
	echo '
	<table class="list sans lt"><tbody>
	<tr><th><big>Observing</big></th><th></th><th></th></tr>
	<tr><th>Writer</th><th>Editor</th><th>Observing?</th></tr>';

	// Start our row color class
	$cc = 'lr';

	// Iterate the matches
	while ($row = mysqli_fetch_array($r)) {
		$writer_id = "$row[0]";
		$writer_editor = "$row[1]";
		$writer_name = "$row[2]";
		$writer_username = "$row[3]";
		// Get the Editor name
		$qe = "SELECT name FROM users WHERE id='$writer_editor'";
		$re = mysqli_query ($dbc, $qe);
		$rowe = mysqli_fetch_array($re);
		$editor_name = "$rowe[0]";

		// Checked?
		$checked = ' checked';

		echo '<tr class="'.$cc.'">
			<td><b>'.$writer_name.'</b> <small>('.$writer_username.')</small></td>';

			echo '<td>'.$editor_name.'</td>';

			echo '
			<td><input type="checkbox" name="enrolled_'.$writer_id.'" value="'.$writer_id.'" id="enrolled_'.$writer_id.'"'.$checked.'></td>
			</tr>';

		// Rotate our row color class
		$cc = ($cc == 'lr') ? 'dr' : 'lr';

	}

	echo '</tbody></table>';
}

// Valid the Pagination
if ((isset($_GET['p'])) && (filter_var($_GET['p'], FILTER_VALIDATE_INT, array('min_range' => 1)))) {
 $paged = preg_replace("/[^0-9]/","", $_GET['p']);
 unset($_GET['p']);
} else {
 $paged = 1;
}
if (isset($_GET['p'])) { unset($_GET['p']); }

// Search $clean_where_am_i from $where_am_i
$clean_where_am_i = (strstr($where_am_i, '?')) ? strstr( $where_am_i, '?', true) : $where_am_i;
// Search GET setting
if (isset($_GET['r'])) {
	$search_query = preg_replace("/[^A-Za-z0-9 \'\/&,:%-.!$?;]/"," ", $_GET['r']);
	$search_query = trim($search_query);
	$search_suffix = "&r=$search_query";
	$original_search_get = strip_tags($_GET['r']);
	$getsuffix = '';
	unset($_GET['r']); // We don't want to re-iterate it in our hidden inputs
	foreach ($_GET as $name => $value) {
		$getsuffix .= "$name=$value&";
	}
	if ($search_query == '') {
		echo '<script type="text/javascript"> window.location = "' . "${clean_where_am_i}?${getsuffix}" . '" </script>';
		exit(); // Quit the script
	} elseif ($search_query != $original_search_get) {
		echo '<script type="text/javascript"> window.location = "' . "${clean_where_am_i}?${getsuffix}r=${search_query}" . '" </script>';
		exit(); // Quit the script
	}
	// Search SQL query string
	$SQLcolumnSearch = "AND "; // For our syntax logic, this is the end of the WHERE statement and may be empty
	$SQLcolumnSearch .= "( id LIKE '0'";
	// Add each search word
	if(strpos($search_query, " ") !== false) {
			$searchwordS = array();
			$searchwordS = explode(" ", $search_query);

			foreach($searchwordS as $searchword){
					$searchword = mysqli_real_escape_string($dbc, $searchword);
					$SQLcolumnSearch = $SQLcolumnSearch." OR LOWER(name) LIKE LOWER('%$searchword%')";
			}
	} else {
		$searchword = $search_query;
		$searchword = mysqli_real_escape_string($dbc, $searchword);
		$SQLcolumnSearch = $SQLcolumnSearch." OR LOWER(name) LIKE LOWER('%$searchword%')";
	}
	// Finish the SQL serch query with order or operations
	$SQLcolumnSearch = $SQLcolumnSearch." )";
} else {
	$search_suffix = '';
	$SQLcolumnSearch = '';
}

// Pagination
// Set pagination variables:
$pageitems = ($search_suffix == '') ? 250 : 1000; // Search results list a lot
$itemskip = $pageitems * ($paged - 1);
// Prepare our SQL query, but only IDs for pagination
$sql_cols = 'id';
$concat_where_statement = "users o WHERE EXISTS (SELECT 1 FROM users u WHERE NOT JSON_CONTAINS(u.observing, CONCAT('\"', o.id, '\"')) AND u.id = '$u_id') AND type='writer'";
$qp = "SELECT $sql_cols FROM $concat_where_statement $SQLcolumnSearch";
$rp = mysqli_query($dbc, $qp);
$totalrows = mysqli_num_rows($rp);
$totalpages = floor($totalrows / $pageitems);
$remainder = $totalrows % $pageitems;
if ($remainder > 0) {
	$totalpages = $totalpages + 1;
}
if ($paged > $totalpages) {
	$totalpages = 1;
}
$nextpaged = $paged + 1;
$prevpaged = $paged - 1;

// Pagination row
if ($totalpages > 1) {
	echo "
	<div class=\"paginate_nav_container\">
		<div class=\"paginate_nav\">
			<table>
				<tr>
					<td>
						<a class=\"paginate";
						if ($paged == 1) {echo " disabled";}
						echo "\" title=\"Page 1\" href=\"${where_am_i}${sort_get}${sort_suffix}${search_suffix}&p=1\">&laquo;</a>
					</td>
					<td>
						<a class=\"paginate";
						if ($paged == 1) {echo " disabled";}
					 echo "\" title=\"Previous\" href=\"${where_am_i}${sort_get}${sort_suffix}${search_suffix}&p=$prevpaged\">&lsaquo;&nbsp;</a>
					</td>
					<td>
						<a class=\"paginate current\" title=\"Next\" href=\"${where_am_i}${sort_get}${sort_suffix}${search_suffix}&p=$paged\">Page $paged ($totalpages)</a>
					</td>
					<td>
						<a class=\"paginate";
						if ($paged == $totalpages) {echo " disabled";}
					 echo "\" title=\"Next\" href=\"${where_am_i}${sort_get}${sort_suffix}${search_suffix}&p=$nextpaged\">&nbsp;&rsaquo;</a>
					</td>
					 <td>
						 <a class=\"paginate";
						 if ($paged == $totalpages) {echo " disabled";}
						echo "\" title=\"Last Page\" href=\"${where_am_i}${sort_get}${sort_suffix}${search_suffix}&p=$totalpages\">&raquo;</a>
					 </td>
				</tr>
			</table>
		</div>
	</div>";
} else {
	echo '<br><br>';
}

// Sorting table
echo '
<div style="display: inline; float: right;">
	<table style="float: right;" class="plain">
	<tbody><tr>
		<td>
		<span class="lo sans">&uarr;&darr;</span>
		</td><td>';
set_button("Creation", "Sort by order of creation", "${where_am_i}${sort_get}s=creation${search_suffix}", $creation_cl);
echo '</td><td>';
set_button("Name", "Sort by name", "${where_am_i}${sort_get}s=name${search_suffix}", $name_cl);
echo '</td>';
// Search form inputs
echo '<td>
		<div class="search-input">
		<input type="text" name="r" placeholder="Search" form="searchform" id="searchbox"';
		echo (isset($search_query)) ? ' value="'.$search_query.'"' : false; // Here from searching?
		echo '>
		<span data-clear-input onclick="searchClearReset(\'searchbox\', \'searchform\');" id="searchclear">&times;</span>
		</div>
		</td><td>
		<label style="cursor:pointer;">
			<svg width="28" height="28" xmlns="http://www.w3.org/2000/svg">
				<ellipse stroke="#bbb" stroke-width="3" ry="10" rx="10" id="svg_1" cy="12" cx="12" fill="none"/>
				<line stroke="#bbb" stroke-width="3" id="svg_3" y2="27" x2="27" y1="18" x1="18" fill="none"/>
			</svg>
			<input type="submit" form="searchform" value="Search" hidden>
		</label>
		</td>';
// Finish sorting table
echo '
	</tr></tbody>
	</table>
</div><br><br>';
// Searchbox clear event listener
?>
<script>
if (document.getElementById('searchbox').value == '') {
	document.getElementById('searchclear').style.display='none';
} else {
	document.getElementById('searchclear').style.display='block';
}
var input = document.getElementById('searchbox');
input.addEventListener('keyup',function(){
	if (document.getElementById('searchbox').value == '') {
		document.getElementById('searchclear').style.display='none';
	} else {
		document.getElementById('searchclear').style.display='block';
	}
});
</script>
<?php

// SQL concatenate NO match
$sql_cols = 'id, editor, name, username';
$concat_where_statement = "users o WHERE EXISTS (SELECT 1 FROM users u WHERE NOT JSON_CONTAINS(u.observing, CONCAT('\"', o.id, '\"')) AND u.id = '$u_id') AND type='writer'";
$q = "SELECT $sql_cols FROM $concat_where_statement $SQLcolumnSearch ORDER BY $order_by LIMIT $itemskip,$pageitems";
$r = mysqli_query ($dbc, $q);

// Empty?
if (mysqli_num_rows($r) == 0) {
	echo '<p class="lt sans">No unobserved writers available</p>';
} else {
	// Start the NOT Observing section
	echo '
	<p><input type="submit" name="submit_button" value="Save" id="submit_button" class="formbutton"></p>
	<table class="list sans lt"><tbody>
	<tr><th><big>Available</big></th><th></th><th></th></tr>
	<tr><th>Editor</th><th>Observing?</th><th>Writer</th></tr>';

	// Start our row color class
	$cc = 'lr';

	// Iterate NO matches
	while ($row = mysqli_fetch_array($r)) {
		$writer_id = "$row[0]";
		$writer_editor = "$row[1]";
		$writer_name = "$row[2]";
		$writer_username = "$row[3]";
		// Get the Editor name
		$qe = "SELECT name FROM users WHERE id='$writer_editor'";
		$re = mysqli_query ($dbc, $qe);
		$rowe = mysqli_fetch_array($re);
		$editor_name = "$rowe[0]";

		// Checked?
		$checked = '';

		echo '<tr class="'.$cc.'">
			<td>'.$editor_name.'</td>
			<td><input type="checkbox" name="enrolled_'.$writer_id.'" value="'.$writer_id.'" id="enrolled_'.$writer_id.'"'.$checked.'></td>
			<td><b>'.$writer_name.'</b> <small>('.$writer_username.')</small></td>
			</tr>';

		// Rotate our row color class
		$cc = ($cc == 'lr') ? 'dr' : 'lr';

	}

	echo '</tbody></table>';
}

echo '<p><input type="submit" name="submit_button" value="Save" id="submit_button" class="formbutton"></p>
</form>';

// Search form
echo '<br>
<form id="searchform" action="'.$clean_where_am_i.'" method="get">';
// All GET arguments
foreach ($_GET as $name => $value) {
	echo '<input type="hidden" name="'.$name.'" value="'.$value.'">';
}

echo "
</form>
<script>
function searchClearReset(clearid, formid) {
	document.getElementById(clearid).value = '';
	document.getElementById(formid).submit();
}
</script>
";

// Pagination row
if ($totalpages > 1) {
	echo "
	<div class=\"paginate_nav_container\">
		<div class=\"paginate_nav\">
			<table>
				<tr>
					<td>
						<a class=\"paginate";
						if ($paged == 1) {echo " disabled";}
						echo "\" title=\"Page 1\" href=\"${where_am_i}${sort_get}${sort_suffix}${search_suffix}&p=1\">&laquo;</a>
					</td>
					<td>
						<a class=\"paginate";
						if ($paged == 1) {echo " disabled";}
					 echo "\" title=\"Previous\" href=\"${where_am_i}${sort_get}${sort_suffix}${search_suffix}&p=$prevpaged\">&lsaquo;&nbsp;</a>
					</td>
					<td>
						<a class=\"paginate current\" title=\"Next\" href=\"${where_am_i}${sort_get}${sort_suffix}${search_suffix}&p=$paged\">Page $paged ($totalpages)</a>
					</td>
					<td>
						<a class=\"paginate";
						if ($paged == $totalpages) {echo " disabled";}
					 echo "\" title=\"Next\" href=\"${where_am_i}${sort_get}${sort_suffix}${search_suffix}&p=$nextpaged\">&nbsp;&rsaquo;</a>
					</td>
					 <td>
						 <a class=\"paginate";
						 if ($paged == $totalpages) {echo " disabled";}
						echo "\" title=\"Last Page\" href=\"${where_am_i}${sort_get}${sort_suffix}${search_suffix}&p=$totalpages\">&raquo;</a>
					 </td>
				</tr>
			</table>
		</div>
	</div>";
}
