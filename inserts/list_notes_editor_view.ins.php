<?php

// When including, these can be set rather than using GET
// These must be set in cascade of the following order, those above will override those below
// $editor_set_writer_id = GET w
// $editor_set_block = GET b
// $by_main_block = GET m
// $by_user_all = GET u
// $observing = userid (for binder_observer.php)

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

// Okay to view this page
$userid = $_SESSION['user_id'];

// Which notes?
if (isset($_GET['w'])) {
	if (filter_var($_GET['w'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$editor_set_writer_id = preg_replace("/[^0-9]/","", $_GET['w']);

		// Make sure we should be here
		if ($usr_type == "Writer") {
			if ($editor_set_writer_id != $userid) {
				unset($editor_set_writer_id);
				echo '<h2 class="sans dk">Memos</h2>';
			}
		} elseif ($usr_type == "Observer") {
			$q = "SELECT id FROM users WHERE JSON_CONTAINS(observing, CONCAT('\"', $editor_set_writer_id, '\"')) AND id='$userid'";
			$r = mysqli_query ($dbc, $q);
			if (mysqli_num_rows($r) == 0) {
				unset($editor_set_writer_id);
				echo isset($observing) ? '<h2 class="sans dk">Memos <small>(observing)</small></h2>' : '<h2 class="sans dk">Memos</small></h2>';
			}
		}

		// Heading
		$q = "SELECT name, email FROM users WHERE id='$editor_set_writer_id'";
		$r = mysqli_query ($dbc, $q);
		$row = mysqli_fetch_array($r, MYSQLI_NUM);
		$w_name = "$row[0]";
		$w_email = "$row[1]";
		echo '<h2 class="sans dk">Memos for writer: '.$w_name.' <small>'.$w_email.'</small></h2>';

		// For filters
		$writer_only = true;

	}
} elseif (isset($_GET['b'])) {
	if (filter_var($_GET['b'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$editor_set_block = preg_replace("/[^0-9]/","", $_GET['b']);

		// Make sure we should be here
		if ($usr_type == "Writer") {
			$q = "SELECT id FROM users WHERE JSON_CONTAINS(blocks, CONCAT('\"', $editor_set_block, '\"')) AND id='$userid'";
			$r = mysqli_query ($dbc, $q);
			if (mysqli_num_rows($r) == 0) {
				unset($editor_set_block);
				echo '<h2 class="sans dk">Memos <small>(all blocks)</small></h2>';
			}
		} elseif ($usr_type == "Observer") {
			$qo = "SELECT observing FROM users WHERE id='$userid'";
			$ro = mysqli_query ($dbc, $qo);
			$rowo = mysqli_fetch_array($ro, MYSQLI_NUM);
			$observing_array = json_decode($rowo[0], true);
			$observes_block = false; // Preset for our test
			foreach ($observing_array as $u_id) {
				$q = "SELECT id FROM users WHERE JSON_CONTAINS(blocks, CONCAT('\"', $editor_set_block, '\"')) AND id='$u_id'";
				$r = mysqli_query ($dbc, $q);
				if (mysqli_num_rows($r) == 1) {
					$observes_block = true;
				}
			}
			if ($observes_block != true) {
				unset($editor_set_block);
				echo '<h2 class="sans dk">Memos <small>(all blocks)</small></h2>';
			}
		}

		// Heading
		$q = "SELECT name, code FROM blocks WHERE id='$editor_set_block'";
		$r = mysqli_query ($dbc, $q);
		$row = mysqli_fetch_array($r, MYSQLI_NUM);
		$b_name = "$row[0]";
		$b_code = "$row[1]";
		echo '<h2 class="sans dk">Memos for block: '.$b_name.' <small>'.$b_code.'</small></h2>';

	}

// $by_main_block = user id for the main block
} elseif (isset($_GET['m'])) {
	if (filter_var($_GET['m'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$by_main_block = preg_replace("/[^0-9]/","", $_GET['m']);

		// Make sure we should be here
		if ($usr_type == "Writer") {
			if ($by_main_block != $userid) {
				unset($by_main_block);
				echo '<h2 class="sans dk">Memos <small>(all blocks)</small></h2>';
			}
		} elseif ($usr_type == "Observer") {
			$q = "SELECT id FROM users WHERE JSON_CONTAINS(observing, CONCAT('\"', $by_main_block, '\"')) AND id='$userid'";
			$r = mysqli_query ($dbc, $q);
			if (mysqli_num_rows($r) == 0) {
				unset($by_main_block);
				echo '<h2 class="sans dk">Memos <small>(all blocks)</small></h2>';
			}
		}
  }

// $by_user_all = user id for all blocks
} elseif (isset($_GET['u'])) {
	if (filter_var($_GET['u'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$by_user_all = preg_replace("/[^0-9]/","", $_GET['u']);

		// Make sure we should be here
		if ($usr_type == "Writer") {
			if ($by_user_all != $userid) {
				unset($by_user_all);
				echo '<h2 class="sans dk">Memos <small>(all blocks)</small></h2>';
			}
		} elseif ($usr_type == "Observer") {
			$q = "SELECT id FROM users WHERE JSON_CONTAINS(observing, CONCAT('\"', $by_user_all, '\"')) AND id='$userid'";
			$r = mysqli_query ($dbc, $q);
			if (mysqli_num_rows($r) == 0) {
				unset($by_user_all);
				echo '<h2 class="sans dk">Memos <small>(all blocks)</small></h2>';
			}
		}
  }

// Observer
} elseif ( (isset($observing)) && ( (($usr_type == "Observer") && ($observing == $userid)) || ($usr_type == "Supervisor") || ($usr_type == "Admin") ) ) {
	echo '<h2 class="sans dk">Memos <small>(all blocks)</small></h2>';

// No GET & no settings
} else {
	$by_user = $userid;
	echo '<h2 class="sans dk">Memos <small>(all blocks)</small></h2>';
}

// Sorting options
$sort_get = (strstr($where_am_i, '?')) ? '&' : '?' ;
$where_am_i_base = substr($where_am_i, 0, strpos($where_am_i, '?'));

// Sort GET setting
$activity_cl = 'act_ltgray';
$creation_cl = 'act_ltgray';
$heading_cl = 'act_ltgray';
if ((isset($_GET['s'])) && (preg_match("/[a-z]/", $_GET['s']))) {
	$sort = preg_replace("/[^a-z]/","", $_GET['s']);
	switch ($sort) {
		case "activity":
				$order_by = "save_date DESC";
				$activity_cl = 'act_dkgray';
				$sort_suffix = 's=activity';
				break;
		case "creation":
				$order_by = "id DESC";
				$creation_cl = 'act_dkgray';
				$sort_suffix = 's=creation';
				break;
		case "heading":
				$order_by = "body ASC";
				$heading_cl = 'act_dkgray';
				$sort_suffix = 's=heading';
				break;
		default:
				$order_by = "save_date DESC";
				$activity_cl = 'act_dkgray';
				$sort_suffix = 's=activity';
				break;
	}
} else {
	$order_by = "save_date DESC";
	$activity_cl = 'act_dkgray';
	$sort_suffix = '';
	$sort = 'activity'; // For filters, but we won't rewrite the URL just over this not being set
}

// Filters
if (isset($writer_only)) {
	$writer_cl = 'act_dkgray';
	$blocks_cl = 'act_ltgray';
} else {
	$writer_cl = 'act_ltgray';
	$blocks_cl = 'act_dkgray';
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
		echo '<script type="text/javascript"> window.location = "' . "{$clean_where_am_i}?{$getsuffix}" . '" </script>';
		exit(); // Quit the script
	} elseif ($search_query != $original_search_get) {
		echo '<script type="text/javascript"> window.location = "' . "{$clean_where_am_i}?{$getsuffix}r=$search_query" . '" </script>';
		exit(); // Quit the script
	}
	// Search SQL query string
	$SQLcolumnSearch = "AND ";
	$SQLcolumnSearch .= "( id LIKE '0'";
	// Add each search word
	if(strpos($search_query, " ") !== false) {
			$searchwordS = array();
			$searchwordS = explode(" ", $search_query);

			foreach($searchwordS as $searchword){
					$searchword = mysqli_real_escape_string($dbc, $searchword);
					$SQLcolumnSearch = $SQLcolumnSearch." OR LOWER(body) LIKE LOWER('%$searchword%')";
			}
	} else {
		$searchword = $search_query;
		$searchword = mysqli_real_escape_string($dbc, $searchword);
		$SQLcolumnSearch = $SQLcolumnSearch." OR LOWER(body) LIKE LOWER('%$searchword%')";
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
$sql_cols = 'n.id';
$from = 'notes n';
if (isset($editor_set_writer_id)) {
  $sql_where = "WHERE n.editor_set_writer_id='$editor_set_writer_id'";
}	elseif (isset($editor_set_block)) {
	$sql_where = "WHERE n.editor_set_block='$editor_set_block'";
} elseif (isset($by_main_block)) { // Writer's Main block
 	$q = "SELECT editor FROM users WHERE id='$by_main_block'";
 	$r = mysqli_query ($dbc, $q);
 	$row = mysqli_fetch_array($r, MYSQLI_NUM);
 	$u_editor = "$row[0]";
 	$sql_where = "WHERE n.editor_id='$u_editor' AND n.editor_set_writer_id='0' AND n.editor_set_block='0'";
} elseif (isset($by_user_all)) { // Writer's all blocks (including Main and personal)
	$from = 'users u';
	$sql_where = "JOIN notes n ON JSON_CONTAINS(u.blocks, CONCAT('\"', n.editor_set_block, '\"')) WHERE u.id = '$by_user_all' AND n.editor_set_writer_id='0' OR (n.editor_set_writer_id='0' AND n.editor_set_block='0' AND n.writer_id='0') OR n.editor_set_writer_id='$by_user_all'";
	// Above improvement thanks https://stackoverflow.com/questions/72526684/sql-join-each-id-in-json-object
	//$sql_where = "WHERE EXISTS (SELECT 1 FROM users u WHERE JSON_CONTAINS(u.blocks, CONCAT('\"', n.editor_set_block, '\"')) AND u.id = '$by_user_all') AND n.editor_set_writer_id='0' OR (n.editor_set_writer_id='0' AND n.editor_set_block='0' AND n.writer_id='0') OR n.editor_set_writer_id='$by_user_all'";

} elseif (isset($observing)) { // Observer seeing all
	//// DEV following replace the defunct code, delete these when migrated to new MariaDB server ////
	$sql_where = "SELECT n.id, n.body, n.save_date, n.editor_set_writer_id, n.editor_set_block FROM notes n WHERE EXISTS (SELECT 1 FROM users u WHERE JSON_CONTAINS(u.observing, CONCAT('\"', n.editor_set_writer_id, '\"')) AND u.id = '$observing')";
	//// DEV delete above when below is on MariaDB and tested ////

	//// Defunct until updated to new server with MariaDB ////
	//// From https://stackoverflow.com/questions/72515789/sql-join-two-json-columns-by-two-related-ids
	// $sql_where = "WITH
	// RECURSIVE cte AS (
  //   SELECT 0 AS x
  //   UNION ALL
  //   SELECT x+1 FROM cte
	// ),
	// tbl_users AS (
	// 	SELECT
	// 	   id,
	// 	   name,
	// 	   JSON_VALUE(blocks,CONCAT('$[',cte.x,']')) AS block
	// 	FROM users
	// 	CROSS JOIN cte
	// 	WHERE JSON_VALUE(blocks,CONCAT('$[',cte.x,']')) IS NOT NULL
	// ),
	// tbl_observers AS (
	// 	SELECT
	// 	   id,
	// 	   name,
	// 	   JSON_VALUE(observing,CONCAT('$[',cte.x,']')) AS writer
	// 	FROM users
	// 	CROSS JOIN cte
	// 	WHERE JSON_VALUE(observing,CONCAT('$[',cte.x,']')) IS NOT NULL
	// )
	// SELECT n.id, n.body, n.save_date, n.editor_set_writer_id, n.editor_set_block
	// FROM tbl_observers o
	// LEFT JOIN tbl_users u ON u.id = o.writer
	// INNER JOIN notes n ON n.editor_set_block = u.block
	// WHERE o.id = '$observing'
	// ORDER BY project";
	//// DEV above is defunct code to test and use once on MariaDB ////

} else { // Writer's all blocks (including Main)
	$q = "SELECT editor FROM users WHERE id='$by_user'";
	$r = mysqli_query ($dbc, $q);
	$row = mysqli_fetch_array($r, MYSQLI_NUM);
	$u_editor = "$row[0]";
	$from = "users u";
	$sql_where = "JOIN notes n ON JSON_CONTAINS(u.blocks, CONCAT('\"', n.editor_set_block, '\"')) WHERE u.id = '$by_user' AND n.editor_set_writer_id='0' OR (n.editor_set_writer_id='0' AND n.editor_set_block='0' AND n.writer_id='0')";
	// Above improvement thanks https://stackoverflow.com/questions/72526684/sql-join-each-id-in-json-object
	//$sql_where = "WHERE EXISTS (SELECT 1 FROM users u WHERE JSON_CONTAINS(u.blocks, CONCAT('\"', n.editor_set_block, '\"')) AND u.id = '$by_user') AND n.editor_set_writer_id='0' OR (n.editor_set_writer_id='0' AND n.editor_set_block='0' AND n.writer_id='0')";
}
$sql_where .= " $SQLcolumnSearch ORDER BY $order_by";
$qp = ((isset($observing)) && (!isset($editor_set_writer_id)) && (!isset($editor_set_block)) && (!isset($by_main_block)) && (!isset($by_user_all))) ? $sql_where : "SELECT $sql_cols FROM $from $sql_where"; // From our $observing option
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
						echo "\" title=\"Page 1\" href=\"{$where_am_i}{$sort_get}{$sort_suffix}{$search_suffix}&p=1\">&laquo;</a>
					</td>
					<td>
						<a class=\"paginate";
						if ($paged == 1) {echo " disabled";}
					 echo "\" title=\"Previous\" href=\"{$where_am_i}{$sort_get}{$sort_suffix}{$search_suffix}&p=$prevpaged\">&lsaquo;&nbsp;</a>
					</td>
					<td>
						<a class=\"paginate current\" title=\"Next\" href=\"{$where_am_i}{$sort_get}{$sort_suffix}{$search_suffix}&p=$paged\">Page $paged ($totalpages)</a>
					</td>
					<td>
						<a class=\"paginate";
						if ($paged == $totalpages) {echo " disabled";}
					 echo "\" title=\"Next\" href=\"{$where_am_i}{$sort_get}{$sort_suffix}{$search_suffix}&p=$nextpaged\">&nbsp;&rsaquo;</a>
					</td>
					 <td>
						 <a class=\"paginate";
						 if ($paged == $totalpages) {echo " disabled";}
						echo "\" title=\"Last Page\" href=\"{$where_am_i}{$sort_get}{$sort_suffix}{$search_suffix}&p=$totalpages\">&raquo;</a>
					 </td>
				</tr>
			</table>
		</div>
	</div>";
}
// Search form
echo '<br>
<form id="searchformeditorviewnotes" action="'.$clean_where_am_i.'" method="get">';
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
set_button("Activity", "Sort by most recent activity", "{$where_am_i}{$sort_get}s=activity{$search_suffix}", $activity_cl);
echo '</td><td>';
set_button("Creation", "Sort by order of creation", "{$where_am_i}{$sort_get}s=creation{$search_suffix}", $creation_cl);
echo '</td><td>';
set_button("Heading", "Sort by heading", "{$where_am_i}{$sort_get}s=heading{$search_suffix}", $heading_cl);
echo '</td>';
// Search form inputs
echo '<td>
		<div class="search-input">
		<input type="text" name="r" placeholder="Search" form="searchformeditorviewnotes" id="searchbox"';
		echo (isset($search_query)) ? ' value="'.$search_query.'"' : false; // Here from searching?
		echo '>
		<span data-clear-input onclick="searchClearReset(\'searchbox\', \'searchformeditorviewnotes\');" id="searchclear">&times;</span>
		</div>
		</td><td>
		<label style="cursor:pointer;">
			<svg width="28" height="28" xmlns="http://www.w3.org/2000/svg">
				<ellipse stroke="#bbb" stroke-width="3" ry="10" rx="10" id="svg_1" cy="12" cx="12" fill="none"/>
				<line stroke="#bbb" stroke-width="3" id="svg_3" y2="27" x2="27" y1="18" x1="18" fill="none"/>
			</svg>
			<input type="submit" form="searchformeditorviewnotes" value="Search" hidden>
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

// List notes
$sql_cols = 'n.id, n.body, n.save_date, n.editor_set_writer_id, n.editor_set_block';
//$q = "SELECT $sql_cols FROM notes n WHERE $sql_where LIMIT $itemskip,$pageitems"; // Delete if below works
$q = ((isset($observing)) && (!isset($editor_set_writer_id)) && (!isset($editor_set_block)) && (!isset($by_main_block)) && (!isset($by_user_all))) ? $sql_where : "SELECT $sql_cols FROM $from $sql_where LIMIT $itemskip,$pageitems"; // From our $observing option
$r = mysqli_query ($dbc, $q);

// Empty?
if (mysqli_num_rows($r) == 0) {
	echo '<p class="lt sans">No notes</p>';
} else {

	// Start our row color class
	$cc = 'lr';

	// Start the table
	echo '
	<table class="list lt notes sans"><tbody>';

	// Iterate each entry
	while ($row = mysqli_fetch_array($r)) {
		$note_id = "$row[0]";
		$body = "$row[1]";
		$save_date = "$row[2]";
		$editor_set_writer_id = "$row[3]";
		$editor_set_block = "$row[4]";
		$title = strtok($body, "\n"); // Get just the first line

		echo '<tr class="'.$cc.'">';
		echo "<td><a class=\"listed_note\" href=\"note_view.php?v=$note_id\">$title</a><br /><i class=\"listed_note\">$save_date</i></td>";

		// Writer note
		if ($editor_set_writer_id > 0) {
			$qwn = "SELECT name, email FROM users WHERE id='$editor_set_writer_id'";
			$rwn = mysqli_query ($dbc, $qwn);
			$rowwn = mysqli_fetch_array($rwn, MYSQLI_NUM);
			$w_name = "$rowwn[0]";
			$w_email = "$rowwn[1]";
			echo "<td>Writer: $w_name <small>$w_email</small></td>";
		// Block note
		} elseif ($editor_set_block > 0) {
			$qbn = "SELECT name, code FROM blocks WHERE id='$editor_set_block'";
			$rbn = mysqli_query ($dbc, $qbn);
			$rowbn = mysqli_fetch_array($rbn, MYSQLI_NUM);
			$b_name = "$rowbn[0]";
			$b_code = "$rowbn[1]";
			echo "<td>Block: $b_name <small>$b_code</small></td>";
		// Main block note
		} else {
			echo "<td>Block: Main</td>";
		}

		echo '<td><div style="display: inline; float:right;">';
		get_switch("Read", "Read this note", "note_view.php", "v", "$note_id", "act_blue editNoteButton");
		echo '</div>
			</td>
		</tr>';

		// Rotate our row color class
		$cc = ($cc == 'lr') ? 'dr' : 'lr';
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
						echo "\" title=\"Page 1\" href=\"{$where_am_i}{$sort_get}{$sort_suffix}{$search_suffix}&p=1\">&laquo;</a>
					</td>
					<td>
						<a class=\"paginate";
						if ($paged == 1) {echo " disabled";}
					 echo "\" title=\"Previous\" href=\"{$where_am_i}{$sort_get}{$sort_suffix}{$search_suffix}&p=$prevpaged\">&lsaquo;&nbsp;</a>
					</td>
					<td>
						<a class=\"paginate current\" title=\"Next\" href=\"{$where_am_i}{$sort_get}{$sort_suffix}{$search_suffix}&p=$paged\">Page $paged ($totalpages)</a>
					</td>
					<td>
						<a class=\"paginate";
						if ($paged == $totalpages) {echo " disabled";}
					 echo "\" title=\"Next\" href=\"{$where_am_i}{$sort_get}{$sort_suffix}{$search_suffix}&p=$nextpaged\">&nbsp;&rsaquo;</a>
					</td>
					 <td>
						 <a class=\"paginate";
						 if ($paged == $totalpages) {echo " disabled";}
						echo "\" title=\"Last Page\" href=\"{$where_am_i}{$sort_get}{$sort_suffix}{$search_suffix}&p=$totalpages\">&raquo;</a>
					 </td>
				</tr>
			</table>
		</div>
	</div>";
}

// We don't want this messing with other stuff
unset($editor_id);
unset($editor_set_writer_id);
unset($editor_set_block);
unset($by_main_block);
unset($by_user_all);
unset($observing);
