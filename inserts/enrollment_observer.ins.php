<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

// Okay to view this page
$userid = $_SESSION['user_id'];

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
		echo '<script type="text/javascript"> window.location = "' . "${clean_where_am_i}?${getsuffix}r=$search_query" . '" </script>';
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
$concat_where_statement = "users o WHERE EXISTS (SELECT 1 FROM users u WHERE JSON_CONTAINS(u.observing, CONCAT('\"', o.id, '\"')) AND u.id = '$userid')";
$qp = "SELECT $sql_cols FROM $concat_where_statement $SQLcolumnSearch ORDER BY $order_by";
$rp = mysqli_query($dbc, $qp);
$totalrows = mysqli_num_rows($rp);
if (($totalrows == 0) && ((!isset($SQLcolumnSearch)) || ($SQLcolumnSearch == ''))) {echo '<p class="lt sans"><b>Nothing yet</b></p>'; if (isset($_SERVER['HTTP_REFERER'])) {$where_was_i = filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL); set_button("&larr; Go back", "Return to the page that brought you here", $where_was_i, "newNoteButton");} return;}
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
}
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

// Get the observer info
$q = "SELECT observing, name, email FROM users WHERE id='$userid' ";
$r = mysqli_query($dbc, $q);
$row = mysqli_fetch_array($r);
$observing_array = json_decode($row[0]);
$u_name = "$row[1]";
$u_email = "$row[2]";

// Empty?
if (empty($observing_array)) {
	echo '<p class="lt sans">No observed writers</p>';
} else {

	// User info
	echo '<p class="sans lt">Observer: <b>'.$u_name.'</b> <small>('.$u_email.')</small><br>';

	// Start the table
	echo '
	<table class="list sans lt"><tbody>';

	// Start our row color class
	$cc = 'lr';

	// List Observees
	$sql_cols = 'id, name, username, email';
	$qu = "SELECT $sql_cols FROM $concat_where_statement $SQLcolumnSearch ORDER BY $order_by LIMIT $itemskip,$pageitems";
	$ru = mysqli_query($dbc, $qu);
	while ($rowu = mysqli_fetch_array($ru)) {

		// Iterate each Writer name
		$w_id = "$rowu[0]";
		$writer_name = "$rowu[1]";
		$writer_username = "$rowu[2]";
		$writer_email = "$rowu[3]";

		echo '<tr class="'.$cc.'">
			<td><b>'.$writer_name.'</b></td>
			<td><a title="List work from this writer" href="observer.php?o='.$w_id.'"><button type="button" class="navDarkButton">Observe writs</button></a></td>
			<td><a title="List work from this writer" href="binder_observer.php?w='.$w_id.'"><button type="button" class="navDarkButton">Observe memos</button></a></td>
			<td><small>('.$writer_username.')</small></td>
			<td><small>'.$writer_email.'</small></td>';
		/* No button at this time
		echo '
			<td><div style="display: inline; float:right;">';
		set_switch("Blocks", "Edit this user's blocks", "change_user_blocks.php?v=$u_id", "opened_by", $userid, "editNoteButton");
		echo '</div>
			</td>';
		*/
		echo '
			</tr>';

		// Rotate our row color class
		$cc = ($cc == 'lr') ? 'dr' : 'lr';
	} // End of Observees
	echo '</tbody></table>';
}

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
