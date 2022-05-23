<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

// Okay to view this page
$userid = $_SESSION['user_id'];

// Term status
$term_status = (isset($term_status)) ? $term_status : 'current'; // Must be set
// Observer page type
$observer_page_type = (strstr($where_am_i, '_')) ? 'limited' : 'any' ; // Won't show shortlist at top for 'limited' pages

// Full concatenate SQL query
// $qb = "SELECT id
// FROM writs w
// WHERE EXISTS (SELECT 1 FROM users u WHERE JSON_CONTAINS(u.observing, CONCAT('\"', w.id, '\"')) AND u.id = '$userid')
// AND term_status='$term_status'";

// Single or all observees?
if ((isset($_GET['o'])) && (filter_var($_GET['o'], FILTER_VALIDATE_INT, array('min_range' => 1)))) {
	$w_id = preg_replace("/[^0-9]/","", $_GET['o']);
	$observee_suffix = "&o=$w_id";

	// Observee info
	$q = "SELECT name, username, email FROM users WHERE id='$w_id'";
	$r = mysqli_query ($dbc, $q);
	$row = mysqli_fetch_array($r);
	$writer_name = "$row[0]";
	$writer_username = "$row[1]";
	$writer_email = "$row[2]";
	$observation = 'single';
	echo '<h3 class="lt">Observing: '.$writer_name.'<small> ('.$writer_username.') '.$writer_email.'</small></h3>
	<p><a title="List work from all writers" href="observer.php"><button type="button" class="navButton">Observe from all</button></a></p>';

} else {
	$qo = "SELECT observing FROM users WHERE id='$userid'";
	$ro = mysqli_query($dbc, $qo);
	$rowo = mysqli_fetch_array($ro);
	//$observing_array = json_decode($rowo[0], true);
	$observee_suffix = "";
	$observation = 'all';
}

// List first few Observees
if (($observation == 'all') && ($observer_page_type == 'any')) {

	// How many observees to link at the top of the Observer Dashboard?
	$limit_observees = 20; // This can be changed

	// SQL
	$sql_limiter = ($limit_observees + 1);
	$sql_cols = 'id, name, username, email';
	$concat_where_statement = "users o WHERE EXISTS (SELECT 1 FROM users u WHERE JSON_CONTAINS(u.observing, CONCAT('\"', o.id, '\"')) AND u.id = '$userid')";
	$q = "SELECT $sql_cols FROM $concat_where_statement ORDER BY name LIMIT $sql_limiter";
	$r = mysqli_query ($dbc, $q);

	// Start the table
	echo '<table class="list lt sans"><tbody>';
	// Start our row color class
	$cc = 'lr';
	// Limit counter
	$current_observee = 1;

	// SQL loop through observed users
	while ($row = mysqli_fetch_array($r)) {
		$w_id = "$row[0]";
		$writer_name = "$row[1]";
		$writer_username = "$row[2]";
		$writer_email = "$row[3]";

		// echo the row
		echo '<tr class="'.$cc.'">
			<td><b>'.$writer_name.'</b></td>
			<td><a title="List work from this writer" href="observer.php?o='.$w_id.'"><button type="button" class="navDarkButton">Observe writs</button></a></td>
			<td><small>('.$writer_username.')</small></td>
			<td><small>'.$writer_email.'</small></td>
		</tr>';

		// Rotate our row color class
		$cc = ($cc == 'lr') ? 'dr' : 'lr';
		// Increment for our limit
		$current_observee ++;
		if ($current_observee > $limit_observees) { break; }
	}
	echo '</tbody></table>';
	echo ($current_observee > $limit_observees) ? '<p><a href="enrollment_observer.php"><button type="button" class="navButton">View all observed writers</button></a></p>' : false ;
}

// Sorting options
$sort_get = (strstr($where_am_i, '?')) ? '&' : '?' ;

// Sort GET setting
$creation_cl = 'act_ltgray';
$work_cl = 'act_ltgray';
$title_cl = 'act_ltgray';
$status_cl = 'act_ltgray';
if ((isset($_GET['s'])) && (preg_match("/[a-z]/", $_GET['s']))) {
	$sort = preg_replace("/[^a-z]/","", $_GET['s']);
	switch ($sort) {
		case "creation":
				$order_by = "id DESC";
				$creation_cl = 'act_dkgray';
				$sort_suffix = 's=creation';
				break;
		case "work":
				$order_by = "work ASC";
				$work_cl = 'act_dkgray';
				$sort_suffix = 's=work';
				break;
		case "title":
				$order_by = "title ASC";
				$title_cl = 'act_dkgray';
				$sort_suffix = 's=title';
				break;
		case "status":
				$order_by = "draft_status='submitted' DESC, edits_status='submitted' DESC, draft_status='reviewed' DESC, edits_status='drafting' DESC, edits_status='scored' DESC, draft_status='saved' DESC, id DESC";
				$status_cl = 'act_dkgray';
				$sort_suffix = 's=status';
				break;
		default:
				$order_by = "id DESC";
				$creation_cl = 'act_dkgray';
				$sort_suffix = 's=creation';
				break;
	}
} else {
	$order_by = "id DESC";
	$creation_cl = 'act_dkgray';
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
	$SQLcolumnSearch = "( id LIKE '0'";
	// Add each search word
	if(strpos($search_query, " ") !== false) {
			$searchwordS = array();
			$searchwordS = explode(" ", $search_query);

			foreach($searchwordS as $searchword){
					$searchword = mysqli_real_escape_string($dbc, $searchword);
					$SQLcolumnSearch = $SQLcolumnSearch." OR LOWER(work) LIKE LOWER('%$searchword%') OR LOWER(title) LIKE LOWER('%$searchword%') OR LOWER(draft) LIKE LOWER('%$searchword%') OR LOWER(edits) LIKE LOWER('%$searchword%') OR LOWER(correction) LIKE LOWER('%$searchword%') OR LOWER(notes) LIKE LOWER('%$searchword%')";
			}
	} else {
		$searchword = $search_query;
		$searchword = mysqli_real_escape_string($dbc, $searchword);
		$SQLcolumnSearch = $SQLcolumnSearch." OR LOWER(work) LIKE LOWER('%$searchword%') OR LOWER(title) LIKE LOWER('%$searchword%') OR LOWER(draft) LIKE LOWER('%$searchword%') OR LOWER(edits) LIKE LOWER('%$searchword%') OR LOWER(correction) LIKE LOWER('%$searchword%') OR LOWER(notes) LIKE LOWER('%$searchword%')";
	}
	// Finish the SQL serch query with order or operations
	$SQLcolumnSearch = $SQLcolumnSearch." ) AND";
} else {
	$search_suffix = '';
	$SQLcolumnSearch = '';
}

// Pagination
// Set pagination variables:
$pageitems = ($search_suffix == '') ? 250 : 1000; // Search results list a lot
$itemskip = $pageitems * ($paged - 1);
// Prepare our SQL query, but only IDs for pagination
$term_status = (isset($term_status)) ? $term_status : 'current'; // Must be set
$sql_cols = 'id';
if ($observation == 'single') {
	$concat_where_statement = "writs WHERE";
	$sql_where = "writer_id=$w_id AND term_status='$term_status' ORDER BY $order_by";
} elseif ($observation == 'all') {
	$concat_where_statement = "writs w WHERE EXISTS (SELECT 1 FROM users u WHERE JSON_CONTAINS(u.observing, CONCAT('\"', w.writer_id, '\"')) AND u.id = '$userid') AND";
	$sql_where = "term_status='$term_status' ORDER BY $order_by";
}
$qp = "SELECT $sql_cols FROM $concat_where_statement $SQLcolumnSearch $sql_where";
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
						echo "\" title=\"Page 1\" href=\"${where_am_i}${sort_get}${sort_suffix}${observee_suffix}${search_suffix}&p=1\">&laquo;</a>
					</td>
					<td>
						<a class=\"paginate";
						if ($paged == 1) {echo " disabled";}
					 echo "\" title=\"Previous\" href=\"${where_am_i}${sort_get}${sort_suffix}${observee_suffix}${search_suffix}&p=$prevpaged\">&lsaquo;&nbsp;</a>
					</td>
					<td>
						<a class=\"paginate current\" title=\"Next\" href=\"${where_am_i}${sort_get}${sort_suffix}${observee_suffix}${search_suffix}&p=$paged\">Page $paged ($totalpages)</a>
					</td>
					<td>
						<a class=\"paginate";
						if ($paged == $totalpages) {echo " disabled";}
					 echo "\" title=\"Next\" href=\"${where_am_i}${sort_get}${sort_suffix}${observee_suffix}${search_suffix}&p=$nextpaged\">&nbsp;&rsaquo;</a>
					</td>
					 <td>
						 <a class=\"paginate";
						 if ($paged == $totalpages) {echo " disabled";}
						echo "\" title=\"Last Page\" href=\"${where_am_i}${sort_get}${sort_suffix}${observee_suffix}${search_suffix}&p=$totalpages\">&raquo;</a>
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
set_button("Creation", "Sort by order of creation", "${where_am_i}${sort_get}s=creation${observee_suffix}${search_suffix}", $creation_cl);
echo '</td><td>';
set_button("Work", "Sort by work", "${where_am_i}${sort_get}s=work${observee_suffix}${search_suffix}", $work_cl);
echo '</td><td>';
set_button("Title", "Sort by title", "${where_am_i}${sort_get}s=title${observee_suffix}${search_suffix}", $title_cl);
echo '</td><td>';
set_button("Status", "Sort by status", "${where_am_i}${sort_get}s=status${observee_suffix}${search_suffix}", $status_cl);
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

$sql_cols = 'id, writer_id, block, work, draft_status, edits_status, score';
if ($observation == 'single') {
	$concat_where_statement = "writs WHERE";
	$sql_where = "writer_id=$w_id AND term_status='$term_status' ORDER BY $order_by";
} elseif ($observation == 'all') {
	$concat_where_statement = "writs w WHERE EXISTS (SELECT 1 FROM users u WHERE JSON_CONTAINS(u.observing, CONCAT('\"', w.writer_id, '\"')) AND u.id = '$userid') AND";
	$sql_where = "term_status='$term_status' ORDER BY $order_by";
}
$qw = "SELECT $sql_cols FROM $concat_where_statement $SQLcolumnSearch $sql_where LIMIT $itemskip,$pageitems";
$rw = mysqli_query ($dbc, $qw);

// Empty?
if (mysqli_num_rows($r) == 0) {
	echo '<p class="lt sans">No observed writers</p>';
} else {

	// Start the table
	echo '
	<table class="list writ lt sans"><tbody>';

	// Start our row color class
	$cc = 'lr';

	echo '
		<tr>
			<th></th><th>Work</th>';
			echo ($observation == 'all') ? '<th>Writer</th>' : false;
			echo '<th>Block</th><th>Status</th><th>Edits</th><th>Score</th>
		</tr>';

	// List writs
	while ($roww = mysqli_fetch_array($rw)) {
		$writ_id = "$roww[0]";
		$w_id = "$roww[1]";
		$block_id = "$roww[2]";
		$work = "$roww[3]";
		$draft_status = "$roww[4]";
		$edits_status = "$roww[5]";
		$score = "$roww[6]";

		// Get the Block name
		if ($block_id == 0) {
			// Main block
			$qe = "SELECT editor FROM users WHERE id='$userid'";
			$re = mysqli_query ($dbc, $qe);
			$rowe = mysqli_fetch_array($re);
			$editor_id = "$rowe[0]";
			// Get the Editor name
			$qe = "SELECT name FROM users WHERE id='$editor_id'";
			$re = mysqli_query ($dbc, $qe);
			$rowe = mysqli_fetch_array($re);
			$main_editor_name = "$rowe[0]";
			$block_listing = 'Main <small>('.$main_editor_name.')</small>';
		} else {
			$qb = "SELECT name, code FROM blocks WHERE id='$block_id'";
			$rb = mysqli_query ($dbc, $qb);
			$rowb = mysqli_fetch_array($rb);
			$block_name = "$rowb[0]";
			$block_code = "$rowb[1]";
			$block_listing = $block_name.' <small>('.$block_code.')</small>';
		}
		echo '<tr class="'.$cc.'">
			<td>';
		get_switch("View", "View current progress", "observe.php", "w", $writ_id, "set_writ_gray");

		echo "</td><td>$work</td>";
		if ($observation == 'all') {
			// Get the Writer info
			$qu = "SELECT name, username, email FROM users WHERE id='$w_id'";
			$ru = mysqli_query ($dbc, $qu);
			$rowu = mysqli_fetch_array($ru);
			$writer_name = "$rowu[0]";
			$writer_username = "$rowu[1]";
			$writer_email = "$rowu[2]";
			echo '<td><a class="dklink" title="List work from this writer" href="observer.php?o='.$w_id.'"><b>'.$writer_name.'</b> <small>('.$writer_username.')<br>'.$writer_email.'</small></a></td>';
		}

		echo "<td>$block_listing</td><td>$draft_status</td><td>$edits_status</td><td>$score</td>";
		echo '
			</tr>';

		// Rotate our row color class
		$cc = ($cc == 'lr') ? 'dr' : 'lr';
	} // Finish the rows
	if (mysqli_num_rows($rw) == 0) {
		echo '<tr><td colspan="4">No results</td></tr>';
	}

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
						echo "\" title=\"Page 1\" href=\"${where_am_i}${sort_get}${sort_suffix}${observee_suffix}${search_suffix}&p=1\">&laquo;</a>
					</td>
					<td>
						<a class=\"paginate";
						if ($paged == 1) {echo " disabled";}
					 echo "\" title=\"Previous\" href=\"${where_am_i}${sort_get}${sort_suffix}${observee_suffix}${search_suffix}&p=$prevpaged\">&lsaquo;&nbsp;</a>
					</td>
					<td>
						<a class=\"paginate current\" title=\"Next\" href=\"${where_am_i}${sort_get}${sort_suffix}${observee_suffix}${search_suffix}&p=$paged\">Page $paged ($totalpages)</a>
					</td>
					<td>
						<a class=\"paginate";
						if ($paged == $totalpages) {echo " disabled";}
					 echo "\" title=\"Next\" href=\"${where_am_i}${sort_get}${sort_suffix}${observee_suffix}${search_suffix}&p=$nextpaged\">&nbsp;&rsaquo;</a>
					</td>
					 <td>
						 <a class=\"paginate";
						 if ($paged == $totalpages) {echo " disabled";}
						echo "\" title=\"Last Page\" href=\"${where_am_i}${sort_get}${sort_suffix}${observee_suffix}${search_suffix}&p=$totalpages\">&raquo;</a>
					 </td>
				</tr>
			</table>
		</div>
	</div>";
}
