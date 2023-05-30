<?php

// Because this has sorting and pagination, this must be set: $where_am_i

// Check writers
$q = "SELECT id FROM users WHERE editor='$userid' AND status='active' ORDER BY name";
$r = mysqli_query ($dbc, $q);

// Check for archived writs by writer
$review_status = (isset($review_status)) ? $review_status : 'current'; // Must be set

// Sorting options
$sort_get = (strstr($where_am_i, '?')) ? '&' : '?' ;

// Sort GET setting
$activity_cl = 'act_ltgray';
$creation_cl = 'act_ltgray';
$work_cl = 'act_ltgray';
$title_cl = 'act_ltgray';
$status_cl = 'act_ltgray';
$coalesce_greatest_dates = "COALESCE ( GREATEST(draft_open_date,draft_save_date,draft_submit_date,edits_date,edits_viewed_date,corrected_save_date,corrected_submit_date,scoring_date), draft_open_date,draft_save_date,draft_submit_date,edits_date,edits_viewed_date,corrected_save_date,corrected_submit_date,scoring_date )";
//$coalesce_greatest_dates = "GREATEST(draft_open_date,draft_save_date,draft_submit_date,edits_date,edits_viewed_date,corrected_save_date,corrected_submit_date,scoring_date)";
if ((isset($_GET['s'])) && (preg_match("/[a-z]/", $_GET['s']))) {
	$sort = preg_replace("/[^a-z]/","", $_GET['s']);
	switch ($sort) {
		case "activity":
				$order_by = "$coalesce_greatest_dates DESC";
				$activity_cl = 'act_dkgray';
				$sort_suffix = 's=activity';
				break;
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
				$order_by = "$coalesce_greatest_dates DESC";
				$activity_cl = 'act_dkgray';
				$sort_suffix = 's=creation';
				break;
	}
} else {
	$order_by = "$coalesce_greatest_dates DESC";
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
		echo '<script type="text/javascript"> window.location = "' . "{$clean_where_am_i}?{$getsuffix}" . '" </script>';
		exit(); // Quit the script
	} elseif ($search_query != $original_search_get) {
	  echo '<script type="text/javascript"> window.location = "' . "{$clean_where_am_i}?{$getsuffix}r=$search_query" . '" </script>';
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

// Writers
if (isset($_GET['u'])) {
	if (filter_var($_GET['u'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$u = preg_replace("/[^0-9]/","", $_GET['u']);
	} else {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
}
// Blocks
if (isset($_GET['v'])) {
	if (filter_var($_GET['v'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$v = preg_replace("/[^0-9]/","", $_GET['v']);
	} else {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
}

// Pagination
// Set pagination variables:
$pageitems = ($search_suffix == '') ? 250 : 1000; // Search results list a lot
$itemskip = $pageitems * ($paged - 1);
// Prepare our SQL query, but only IDs for pagination
// List all writers or one writer?
$listing_who = (isset($writer_id)) ? "writer_id='$writer_id' AND" : "";
// Viewing only one writer or clear the one-writer SESSION?
if (isset($writer_id)) {
	$_SESSION['list_writer'] = $writer_id;
} elseif (isset($_SESSION['list_writer'])) {
	unset($_SESSION['list_writer']);
}
$sql_cols = 'id';
$sql_blocks_where = (isset($v)) ? "AND block='$v'" : "" ; // This is used to filter specific blocks, by block_editor.php --> block_editor.ins.php
$sql_writer_where = (isset($u)) ? "AND writer_id='$u'" : "" ; // This is used to filter specific writers, by writer_editor.php --> writer_editor.ins.php
$sql_where = (isset($list_block_id)) ?
"$SQLcolumnSearch $listing_who block=$list_block_id AND review_status='$review_status' $sql_writer_where $sql_blocks_where ORDER BY $order_by" :
"$SQLcolumnSearch $listing_who review_status='$review_status' $sql_writer_where $sql_blocks_where ORDER BY $order_by, block DESC" ;
$qp = "SELECT $sql_cols FROM writs WHERE $sql_where";
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
<form id="searchformeditorwrits" action="'.$clean_where_am_i.'" method="get">';
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
set_button("Work", "Sort by work", "{$where_am_i}{$sort_get}s=work{$search_suffix}", $work_cl);
echo '</td><td>';
set_button("Title", "Sort by title", "{$where_am_i}{$sort_get}s=title{$search_suffix}", $title_cl);
echo '</td><td>';
set_button("Status", "Sort by status", "{$where_am_i}{$sort_get}s=status{$search_suffix}", $status_cl);
echo '</td>';
// Search form inputs
echo '<td>
		<div class="search-input">
		<input type="text" name="r" placeholder="Search" form="searchformeditorwrits" id="searchbox"';
		echo (isset($search_query)) ? ' value="'.$search_query.'"' : false; // Here from searching?
		echo '>
		<span data-clear-input onclick="searchClearReset(\'searchbox\', \'searchformeditorwrits\');" id="searchclear">&times;</span>
		</div>
		</td><td>
		<label style="cursor:pointer;">
			<svg width="28" height="28" xmlns="http://www.w3.org/2000/svg">
				<ellipse stroke="#bbb" stroke-width="3" ry="10" rx="10" id="svg_1" cy="12" cx="12" fill="none"/>
				<line stroke="#bbb" stroke-width="3" id="svg_3" y2="27" x2="27" y1="18" x1="18" fill="none"/>
			</svg>
			<input type="submit" form="searchformeditorwrits" value="Search" hidden>
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

// Archive actions
echo '<table><tbody><tr><td><div onclick="showBulkActions()" style="cursor: pointer; display: inline; float: right;"><button class="act_ltgray small">Archive actions &#9660;</button></div></td></tr></tbody></table>
<div id="bulk_actions_div" style="display: none;">
<form id="bulk_actions" method="post" action="archive.act.php">
	<input type="hidden" name="editor_archive" value="'.$userid.'"><br>
	<table style="float: right; width:auto;">
		<tr>';
		if ($review_status == 'archived') {
			echo '
			<td><input type="checkbox" name="checksubmit" value="delete" id="checksubmit">&nbsp;<b><input type="submit" class="act_red small" name="bluksubmit" value="delete" style="display: inline; float: right;"></b></td>
			<td><b><input type="submit" class="act_green small" name="bluksubmit" value="restore" style="display: inline; float: right;"></b></td>
			';
		} elseif ($review_status == 'current') {
			echo '
			<td><input type="checkbox" name="checksubmit" value="archive_selected" id="checksubmit"> <b><input type="submit" class="act_blue small" name="bluksubmit" value="archive all scored" style="display: inline; float: right;"></b></td>
			<td><b><input type="submit" class="act_dkgray small" name="bluksubmit" value="archive" style="display: inline; float: right;"></b></td>
			';
		}
echo '
			<td><label style="display: inline; float: right;"><small class="sans lt">Select all</small>&nbsp;<input type="checkbox" onclick="toggle(this);" /></label></td>
		</tr>
	</table>
</form>
</div>';

// JavaScript to show/hide Bulk Actions
?>
<script>
function showBulkActions() {
	var x = document.getElementById("bulk_actions_div");
	var cbc = document.getElementsByClassName("bulk_check");
	if (x.style.display === "block") {
		x.style.display = "none";
	} else {
		x.style.display = "block";
	}
	for (var i = 0; i < cbc.length; i++) { // Must iterate each key, loop and increment
		if (cbc[i].style.display === "inline") {
			cbc[i].style.display = "none";
		} else {
			cbc[i].style.display = "inline";
		}
	}
}
</script>
<?php
// JavaScript to "Select all"
?>
<script>
function toggle(source) {
		var cb = document.querySelectorAll('input[type="checkbox"]');
		for (var i = 0; i < cb.length; i++) {
				if (cb[i] != source)
						cb[i].checked = source.checked;
		}
		document.getElementById("checksubmit").checked = source.unchecked;
}
</script>
<?php

// Run the SQL query for all the info we need
$sql_cols = 'id, writer_id, block, work, title, draft_status, edits_status, score, outof';
$qw = "SELECT $sql_cols FROM writs WHERE $sql_where LIMIT $itemskip,$pageitems";
$rw = mysqli_query($dbc, $qw);
// Empty?
if (mysqli_num_rows($rw) == 0) {
	echo '<p class="lt sans">No writs</p>';
		} else {

	// Start the table
	echo '
	<table class="list writ lt sans"><tbody>';

	// Start our row color class
	$cc = 'lr';

	// Start the row
	echo '
		<tr>
			<th></th><th>Work</th><th>Title</th><th>Status</th><th>Edits</th><th>Score</th><th>Writer</th><th>Block</th><th class="bulk_check" style="display: none;"></th>
		</tr>';

		// Iterate each entry
		while ($wrow = mysqli_fetch_array($rw)) {
			$writ_id = "$wrow[0]";
			$writer_user_id = "$wrow[1]";
			$block_id = "$wrow[2]";
			$work = "$wrow[3]";
			$title = "$wrow[4]";
			$draft_status = "$wrow[5]";
			$edits_status = "$wrow[6]";
			$score = "$wrow[7]";
			$outof = "$wrow[8]";
			$scoreclassed = ($score > $outof) ? '<span class="noticegreen">'.$score.'</span>' : $score;

			if ($block_id != 0) {
				$qb = "SELECT name, code FROM blocks WHERE id='$block_id'";
				$rb = mysqli_query ($dbc, $qb);
				$rowb = mysqli_fetch_array($rb);
				$block_name = "$rowb[0]";
				$block_code = "$rowb[1]";
				$block_listing = '<small title="'.$block_name.'">'.$block_code.'</small>';
			} else {
				$block_listing = 'Main';
			}

			// Writer info
			$q = "SELECT name FROM users WHERE editor='$userid' AND id='$writer_user_id' AND status='active'";
			$r = mysqli_query($dbc, $q);
			while ($row = mysqli_fetch_array($r)) {
				$name = "$row[0]";
			}

			echo '
				<tr class="'.$cc.'">
					<td>';
					if ($draft_status == 'submitted') {
						get_switch("Review", "Open for review", "review.php", "w", $writ_id, "set_writ_orange");
				} elseif (($draft_status == 'reviewed') && ($edits_status == 'submitted')) {
						get_switch("Finish", "Open for review", "review.php", "w", $writ_id, "set_writ_green");
				} elseif (($draft_status == 'saved') || ($edits_status == 'saved')) {
						get_switch("Peek", "Preview current progress", "review.php", "w", $writ_id, "set_writ_gray");
				} elseif (($draft_status == 'reviewed') && ($edits_status == 'drafting')) {
						get_switch("Edited", "Recheck draft review", "review.php", "w", $writ_id, "set_writ_blue");
				} elseif (($draft_status == 'reviewed') && ($edits_status == 'viewed')) {
						get_switch("View", "Review current progress", "review.php", "w", $writ_id, "set_writ_gray");
				} elseif (($draft_status == 'reviewed') || ($edits_status == 'scored')) {
						get_switch("Scored", "Recheck scoring", "review.php", "w", $writ_id, "set_writ_blue");
				}
			echo "
					</td><td>$work</td><td>$title</td><td>$draft_status</td><td>$edits_status</td><td>$scoreclassed<small class=\"dk\">/$outof</small></td><td>$name</td><td>$block_listing</td>";
			// Checkbox
			echo '
				<td class="bulk_check" style="display: none;">
					<input form="bulk_actions" type="checkbox" id="bulk_'.$writ_id.'" name="bulk_'.$writ_id.'" value="'.$writ_id.'">
				</td>';
			echo '
				</tr>';
			// Rotate our row color class
			$cc = ($cc == 'lr') ? 'dr' : 'lr';
		} // End loop
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
