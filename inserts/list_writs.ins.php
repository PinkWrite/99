<?php

// List writs
$term_status = (isset($term_status)) ? $term_status : 'current'; // Must be set

// Sorting options
$sort_get = (strstr($where_am_i, '?')) ? '&' : '?' ;

// Sort GET setting
$activity_cl = 'act_ltgray';
$creation_cl = 'act_ltgray';
$work_cl = 'act_ltgray';
$title_cl = 'act_ltgray';
$status_cl = 'act_ltgray';
$coalesce_greatest_dates = "COALESCE ( GREATEST(draft_open_date,draft_save_date,draft_submit_date,edits_date,edits_viewed_date,corrected_save_date,corrected_submit_date,scoring_date), draft_open_date,draft_save_date,draft_submit_date,edits_date,edits_viewed_date,corrected_save_date,corrected_submit_date,scoring_date )";
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
		echo '<script type="text/javascript"> window.location = "' . "${clean_where_am_i}?${getsuffix}" . '"; </script>';
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
// List all writers or one writer?
$review_status = (isset($review_status)) ? $review_status : 'current'; // Must be set
$sql_where = "writer_id='$userid'";
$sql_cols = 'id';
$qp = "SELECT $sql_cols FROM writs WHERE $SQLcolumnSearch $sql_where";
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
set_button("Work", "Sort by work", "${where_am_i}${sort_get}s=work${search_suffix}", $work_cl);
echo '</td><td>';
set_button("Title", "Sort by title", "${where_am_i}${sort_get}s=title${search_suffix}", $title_cl);
echo '</td><td>';
set_button("Status", "Sort by status", "${where_am_i}${sort_get}s=status${search_suffix}", $status_cl);
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

		// Archive actions
		echo '<table><tbody><tr><td><div onclick="showBulkActions()" style="cursor: pointer; display: inline; float: right;"><button class="act_ltgray small">Archive actions &#9660;</button></div></td></tr></tbody></table>
		<div id="bulk_actions_div" style="display: none;">
		<form id="bulk_actions" method="post" action="archive.act.php">
			<input type="hidden" name="writer_archive" value="'.$userid.'"><br>
		  <table style="float: right; width:auto;">
		    <tr>';
				if ($term_status == 'archived') {
					echo '<td><input type="checkbox" name="checksubmit" value="delete" id="checksubmit"> <b><input type="submit" class="act_red small" name="bluksubmit" value="delete" style="display: inline; float: right;"></b></td>';
					echo '<td><b><input type="submit" class="act_green small" name="bluksubmit" value="restore" style="display: inline; float: right;"></b></td>';
				} elseif ($term_status == 'current') {
					echo '
					<td><input type="checkbox" name="checksubmit" value="archive_selected" id="checksubmit">&nbsp;<b><input type="submit" class="act_blue small" name="bluksubmit" value="archive all scored" style="display: inline; float: right;"></b></td>
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
$sql_cols = 'id, block, work, title, draft_status, edits_status, score, outof';
$qw = "SELECT $sql_cols FROM writs WHERE $SQLcolumnSearch $sql_where ORDER BY $order_by LIMIT $itemskip,$pageitems";
$rw = mysqli_query ($dbc, $qw);

// Empty?
if (mysqli_num_rows($rw) == 0) {
	echo '<p class="lt sans">No writs</p>';
} else {

	// Start the table
	echo '
	<table class="list writ lt sans"><tbody>';

	// Start our row color class
	$cc = 'lr';

	echo '
		<tr>
			<th></th><th>Work</th><th>Title</th><th>Block</th><th>Status</th><th>Edits</th><th>Score</th><th class="bulk_check" style="display: none;"></th>
		</tr>';

	// Iterate each entry

	while ($row = mysqli_fetch_array($rw)) {
		$writ_id = "$row[0]";
		$block_id = "$row[1]";
		$work = "$row[2]";
		$title = "$row[3]";
		$draft_status = "$row[4]";
		$edits_status = "$row[5]";
		$score = "$row[6]";
		$outof = "$row[7]";
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

		echo '<tr class="'.$cc.'">
				<td>';
				if (($draft_status == 'saved')) {
				get_switch("Open", "Open to make available changes and updates", "writ.php", "w", $writ_id, "set_writ_orange");
			} elseif (($draft_status == 'reviewed') && (($edits_status == 'drafting') || ($edits_status == 'viewed') || ($edits_status == 'saved'))) {
				get_switch("Correct", "Open to make final corrections", "writ.php", "w", $writ_id, "set_writ_green");
			} elseif (($draft_status == 'submitted') || ($edits_status == 'submitted')) {
				dead_switch("Submitted", "Can't open after submitted for review", "set_writ_disabled");
			} elseif (($draft_status == 'reviewed') || ($edits_status == 'scored')) {
				get_switch("View", "See the history and results", "writ.php", "w", $writ_id, "set_writ_blue");
			}
		echo "
				</td><td>$work</td><td>$title</td><td>$block_listing</td><td>$draft_status</td><td>$edits_status</td><td>$scoreclassed<small class=\"dk\">/$outof</small></td>";
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
