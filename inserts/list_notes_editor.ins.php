<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

// Okay to view this page
$userid = $_SESSION['user_id'];

// What are we looking at?
if (isset($_GET['w'])) {
	if (filter_var($_GET['w'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$editor_set_writer_id = preg_replace("/[^0-9]/","", $_GET['w']);

		// Heading
		$q = "SELECT name, email FROM users WHERE id='$editor_set_writer_id'";
		$r = mysqli_query ($dbc, $q);
		$row = mysqli_fetch_array($r, MYSQLI_NUM);
		$w_name = "$row[0]";
		$w_email = "$row[1]";
		echo '<h2 class="sans dk">Memos for writer: '.$w_name.' <small>'.$w_email.'</small></h2>';

	}
} elseif (isset($_GET['b'])) {
	if (filter_var($_GET['b'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$editor_set_block = preg_replace("/[^0-9]/","", $_GET['b']);

		// Heading
		$q = "SELECT name, code FROM blocks WHERE id='$editor_set_block'";
		$r = mysqli_query ($dbc, $q);
		$row = mysqli_fetch_array($r, MYSQLI_NUM);
		$b_name = "$row[0]";
		$b_code = "$row[1]";
		echo '<h2 class="sans dk">Memos for block: '.$b_name.' <small>'.$b_code.'</small></h2>';

	}
} elseif (isset($_GET['m'])) {
	if (filter_var($_GET['m'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		// Editors can only view their own Main block
		if (($usr_type == "Admin") || ($usr_type == "Supervisor")) {
			$editor_main_block = preg_replace("/[^0-9]/","", $_GET['m']);

			// Heading
			$q = "SELECT name, email FROM users WHERE id='$editor_main_block'";
			$r = mysqli_query ($dbc, $q);
			$row = mysqli_fetch_array($r, MYSQLI_NUM);
			$e_name = "$row[0]";
			$e_email = "$row[1]";
			echo '<h2 class="sans dk">Memos for editor Main block: '.$e_name.' <small>'.$e_email.'</small></h2>';

		} elseif ($usr_type == "Editor") {
			$editor_main_block = ($userid == preg_replace("/[^0-9]/","", $_GET['m'])) ? preg_replace("/[^0-9]/","", $_GET['m']) : false;
			if ($editor_main_block == false) { unset($editor_main_block); }
			echo '<h2 class="sans dk">My Main block memos</h2>';
		}
	}

// Special for Supervisors to view All memos
} elseif (isset($_GET['v'])) {
	if (filter_var($_GET['v'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		// Editors can only view their own Main block
		if (($usr_type == "Admin") || ($usr_type == "Supervisor")) {
			$editor_all_notes = preg_replace("/[^0-9]/","", $_GET['v']);
			// Heading
			$q = "SELECT name, email FROM users WHERE id='$editor_all_notes'";
			$r = mysqli_query ($dbc, $q);
			$row = mysqli_fetch_array($r, MYSQLI_NUM);
			$e_name = "$row[0]";
			$e_email = "$row[1]";
			echo '<h2 class="sans dk">All memos for editor: '.$e_name.' <small>'.$e_email.'</small></h2>';

		} else {
			$editor_all_notes = $userid;
			echo '<h2 class="sans dk">All my memos</h2>';
		}
	}

// Default: all memos
} elseif (($usr_type == "Editor") || ($usr_type == "Admin") || ($usr_type == "Supervisor")) {
	$editor_all_notes = $userid;
	echo '<h2 class="sans dk">All my memos</h2>';
}

// Editor limit
if (($usr_type == "Admin") || ($usr_type == "Supervisor")) {
	$editor_limit = '';
} elseif ($usr_type == "Editor") {
	$editor_limit = "AND n.editor_id='$userid'";
} else {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
}

// New
set_switch("New memo +", "Start a new note", "note_editor.php", "new_note", $userid, "newNoteButton");
echo '<br>';

// Sorting options
$sort_get = (strstr($where_am_i, '?')) ? '&' : '?' ;

// Sort GET setting
$activity_cl = 'act_ltgray';
$creation_cl = 'act_ltgray';
$heading_cl = 'act_ltgray';
$block_cl = 'act_ltgray';
$writer_cl = 'act_ltgray';
$editor_cl = 'act_ltgray';
if ((isset($_GET['s'])) && (preg_match("/[a-z]/", $_GET['s']))) {
	$sort = preg_replace("/[^a-z]/","", $_GET['s']);
	switch ($sort) {
		case "activity":
				$order_by = "n.save_date DESC";
				$activity_cl = 'act_dkgray';
				$sort_suffix = 's=activity';
				break;
		case "creation":
				$order_by = "n.id DESC";
				$creation_cl = 'act_dkgray';
				$sort_suffix = 's=creation';
				break;
		case "heading":
				$order_by = "n.body ASC, n.save_date DESC";
				$heading_cl = 'act_dkgray';
				$sort_suffix = 's=heading';
				break;
		case "block":
				$sql_order_formula = "editor_set_block";
				$order_by = "b.name, n.editor_set_block=0 DESC, n.save_date DESC";
				$block_cl = 'act_dkgray';
				$sort_suffix = 's=block';
				break;
		case "writer":
				$sql_order_formula = "editor_set_writer_id";
				$order_by = "u.name ASC, n.save_date DESC";
				$writer_cl = 'act_dkgray';
				$sort_suffix = 's=writer';
				break;
		case "editor":
				$sql_order_formula = "editor_id";
				$order_by = "u.name ASC, n.save_date DESC";
				$editor_cl = 'act_dkgray';
				$sort_suffix = 's=editor';
				break;
		default:
				$order_by = "n.save_date DESC";
				$creation_cl = 'act_dkgray';
				$sort_suffix = 's=creation';
				break;
	}
} else {
	$order_by = "n.save_date DESC";
	$activity_cl = 'act_dkgray';
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
if (isset($editor_set_block)) {
	$sql_who = "n.editor_set_block='$editor_set_block' $editor_limit";
} elseif (isset($editor_set_writer_id)) {
  $sql_who = "n.editor_set_writer_id='$editor_set_writer_id' $editor_limit";
} elseif (isset($editor_main_block)) { // Editor's Main block
  $sql_who = "n.editor_id='$editor_main_block' AND editor_set_writer_id='0' AND editor_set_block='0' $editor_limit";
} elseif (isset($editor_all_notes)) {
	$sql_who = "n.editor_id='$editor_all_notes' $editor_limit";
}
if ((isset($sql_order_formula)) && ($sql_order_formula == 'editor_set_writer_id')) {
	$sql_where = "$SQLcolumnSearch $sql_who ORDER BY $order_by" ;
	$qp = "SELECT $sql_cols FROM notes n JOIN users u ON n.editor_set_writer_id = u.id WHERE $sql_where";

} elseif ((isset($sql_order_formula)) && ($sql_order_formula == 'editor_set_block')) {
	$sql_where = "$SQLcolumnSearch $sql_who ORDER BY $order_by" ;
	$qp = "SELECT $sql_cols FROM notes n JOIN blocks b ON n.editor_set_block = b.id WHERE $sql_where";

} elseif ((isset($sql_order_formula)) && ($sql_order_formula == 'editor_id')) {
	$sql_where = "$SQLcolumnSearch $sql_who ORDER BY $order_by" ;
	$qp = "SELECT $sql_cols FROM notes n JOIN users u ON n.editor_id = u.id WHERE n.editor_set_writer_id='0' AND n.editor_set_block='0' AND $sql_where";

} else {
	$sql_where = "$SQLcolumnSearch $sql_who ORDER BY $order_by" ;
	$qp = "SELECT $sql_cols FROM notes n WHERE $sql_where";
}
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
set_button("Activity", "Sort by most recent activity", "${where_am_i}${sort_get}s=activity${search_suffix}", $activity_cl);
echo '</td><td>';
set_button("Creation", "Sort by order of creation", "${where_am_i}${sort_get}s=creation${search_suffix}", $creation_cl);
echo '</td><td>';
set_button("Heading", "Sort by heading", "${where_am_i}${sort_get}s=heading${search_suffix}", $heading_cl);
echo '</td><td><span class="lo sans">&#x15CA;</span></td><td>';
set_button("Blocks", "Sort by block", "${where_am_i}${sort_get}s=block${search_suffix}", $block_cl);
echo '</td><td>';
set_button("Writers", "Sort by writer", "${where_am_i}${sort_get}s=writer${search_suffix}", $writer_cl);
echo '</td><td>';
set_button("Editor Main", "Sort by editor Main blocks", "${where_am_i}${sort_get}s=editor${search_suffix}", $editor_cl);
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

// List notes
$sql_cols = 'n.id, n.body, n.save_date, n.editor_set_block, n.editor_set_writer_id';
if ((isset($sql_order_formula)) && ($sql_order_formula == 'editor_set_writer_id')) {
	$sql_where = "$SQLcolumnSearch $sql_who ORDER BY $order_by" ;
	$q = "SELECT $sql_cols FROM notes n JOIN users u ON n.editor_set_writer_id = u.id WHERE $sql_where";

} elseif ((isset($sql_order_formula)) && ($sql_order_formula == 'editor_set_block')) {
	$sql_where = "$SQLcolumnSearch $sql_who ORDER BY $order_by" ;
	$q = "SELECT $sql_cols FROM notes n JOIN blocks b ON n.editor_set_block = b.id WHERE $sql_where";

} elseif ((isset($sql_order_formula)) && ($sql_order_formula == 'editor_id')) {
	$sql_where = "$SQLcolumnSearch $sql_who ORDER BY $order_by" ;
	$q = "SELECT $sql_cols FROM notes n JOIN users u ON n.editor_id = u.id WHERE n.editor_set_writer_id='0' AND n.editor_set_block='0' AND $sql_where";

} else {
	$sql_where = "$SQLcolumnSearch $sql_who ORDER BY $order_by" ;
	$q = "SELECT $sql_cols FROM notes n WHERE $sql_where";
}
//$q .= " LIMIT $itemskip,$pageitems";
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
		$editor_set_block = "$row[3]";
		$editor_set_writer_id = "$row[4]";
		$title = strtok($body, "\n"); // Get just the first line

		echo '<tr class="'.$cc.'">';
		echo "<td><a class=\"listed_note\" href=\"note_editor.php?v=$note_id\">$title</a><br /><i class=\"listed_note\">$save_date</i></td>";

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
			if ((($usr_type == "Admin") || ($usr_type == "Supervisor")) && (isset($editor_main_block))) {
				$qmb = "SELECT name, email FROM users WHERE id='$editor_main_block'";
				$rmb = mysqli_query ($dbc, $qmb);
				$rowmb = mysqli_fetch_array($rmb, MYSQLI_NUM);
				$e_name = "$rowmb[0]";
				$e_email = "$rowmb[1]";
				echo "<td>Block: $e_name <small>$e_email</small></td>";

			} elseif (($usr_type == "Editor") || ($usr_type == "Admin") || ($usr_type == "Supervisor")) {
				echo "<td>Block: Main</td>";
			}
		}

		echo '<td><div style="display: inline; float:right;">';
		set_switch("Read", "Read this note", "note_editor.php?v=$note_id", "no_post_value", "no_post_value", "act_blue editNoteButton");
		echo '</div>
			</td>
			<td><div style="display: inline; float:right;">';
		set_switch("Edit", "Edit this note", "note_editor.php?v=$note_id", "opened_by", $userid, "editNoteButton");
		echo '</div>
			</td>
			<td><div style="display: inline; float:right;">';
		set_switch("Delete", "Delete this note", "delete_note.php", "deleted_note", $note_id, "editNoteButton");
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
