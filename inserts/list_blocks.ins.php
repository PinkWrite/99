<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

// Okay to view this page
$userid = $_SESSION['user_id'];

// Original query
// $qb = "SELECT id, editor_id, name, code FROM blocks WHERE status='open' AND id='$b_id'";
// Full concatenate SQL query
// $qb = "SELECT id, editor_id, name, code
// FROM blocks b
// WHERE EXISTS (SELECT 1 FROM users u WHERE JSON_CONTAINS(u.blocks, CONCAT('\"', b.id, '\"')) AND u.id = '$userid')
// AND status='open'";

// Sorting options
$sort_get = (strstr($where_am_i, '?')) ? '&' : '?' ;

// Sort GET setting
$creation_cl = 'act_ltgray';
$name_cl = 'act_ltgray';
$code_cl = 'act_ltgray';
$status_cl = 'act_ltgray';
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
		case "code":
				$order_by = "code ASC";
				$code_cl = 'act_dkgray';
				$sort_suffix = 's=code';
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
					$SQLcolumnSearch = $SQLcolumnSearch." OR LOWER(name) LIKE LOWER('%$searchword%') OR LOWER(code) LIKE LOWER('%$searchword%')";
			}
	} else {
		$searchword = $search_query;
		$searchword = mysqli_real_escape_string($dbc, $searchword);
		$SQLcolumnSearch = $SQLcolumnSearch." OR LOWER(name) LIKE LOWER('%$searchword%') OR LOWER(code) LIKE LOWER('%$searchword%')";
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
$sql_cols = 'id';
$concat_where_statement = "blocks b WHERE EXISTS (SELECT 1 FROM users u WHERE JSON_CONTAINS(u.blocks, CONCAT('\"', b.id, '\"')) AND u.id = '$userid')";
$sql_where = "status='open' ORDER BY $order_by";
$qp = "SELECT $sql_cols FROM $concat_where_statement AND $SQLcolumnSearch $sql_where";
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
<form id="searchformblocks" action="'.$clean_where_am_i.'" method="get">';
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
set_button("Creation", "Sort by order of creation", "{$where_am_i}{$sort_get}s=creation{$search_suffix}", $creation_cl);
echo '</td><td>';
set_button("Name", "Sort by name", "{$where_am_i}{$sort_get}s=name{$search_suffix}", $name_cl);
echo '</td><td>';
set_button("Code", "Sort by code", "{$where_am_i}{$sort_get}s=code{$search_suffix}", $code_cl);
echo '</td>';
// Search form inputs
echo '<td>
		<div class="search-input">
		<input type="text" name="r" placeholder="Search" form="searchformblocks" id="searchbox"';
		echo (isset($search_query)) ? ' value="'.$search_query.'"' : false; // Here from searching?
		echo '>
		<span data-clear-input onclick="searchClearReset(\'searchbox\', \'searchformblocks\');" id="searchclear">&times;</span>
		</div>
		</td><td>
		<label style="cursor:pointer;">
			<svg width="28" height="28" xmlns="http://www.w3.org/2000/svg">
				<ellipse stroke="#bbb" stroke-width="3" ry="10" rx="10" id="svg_1" cy="12" cx="12" fill="none"/>
				<line stroke="#bbb" stroke-width="3" id="svg_3" y2="27" x2="27" y1="18" x1="18" fill="none"/>
			</svg>
			<input type="submit" form="searchformblocks" value="Search" hidden>
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

// Because there is always a Main block, we don't need an "Empty?" check for (mysqli_num_rows($r) == 0)

// Start the table
echo '
<table class="list sans lt"><tbody>';

// Don't show the Main block in search results
if (($SQLcolumnSearch == '') || (!isset($SQLcolumnSearch))) {
	// Main Block
	echo '<tr>
		<td><a class="listed_note" href="block_main.php?v=0"><b>Main</b></a></td>
		<td></td>
		<td><span class="sans bt">'.$main_editor_name.'</span></td>
		<td><div style="display: inline; float:right;">';
	set_switch("Writs &rarr;", "List my writs for this block", "block.php?v=0", "opened_by", "no_value", "editNoteButton");
	echo '</div></td>
		<td><div style="display: inline; float:right;">';
	set_button("Memos &rarr;", "List memos for Main block", "notes_view.php", "editNoteButton");
	echo '</div>
		</td>
		<td><div style="display: inline; float:right;">';
	set_switch("New writ +", "Start a general task for this block", "writ.php?v=0", "opened_by", "no_value", "editNoteButton");
	echo '</div>
		</td>
		</tr>';
}

// Start our row color class
$cc = 'lr';
// List enrolled blocks
$has_blocks = false;
//foreach ($u_blocks_array as $b_id) {
//	$qb = "SELECT id, editor_id, name, code FROM blocks WHERE status='open' AND id='$b_id'";
$sql_cols = 'id, editor_id, name, code';
$qb = "SELECT $sql_cols FROM $concat_where_statement AND $SQLcolumnSearch $sql_where LIMIT $itemskip,$pageitems";
$rb = mysqli_query ($dbc, $qb);

while ($rowb = mysqli_fetch_array($rb)) {
	$block_id = "$rowb[0]";
	$editor_id = "$rowb[1]";
	$block_name = "$rowb[2]";
	$block_code = "$rowb[3]";

	// Get the Editor name
	$qe = "SELECT name FROM users WHERE id='$editor_id'";
	$re = mysqli_query ($dbc, $qe);
	$rowe = mysqli_fetch_array($re);
	$editor_name = "$rowe[0]";

	echo '<tr class="'.$cc.'">
		<td><a class="listed_note" href="block.php?v='.$block_id.'"><b>'.$block_name.'</b></a></td>
		<td><a class="listed_note" href="block.php?v='.$block_id.'">'.$block_code.'</a></td>
		<td><span class="sans bt">'.$editor_name.'</span></td>
		<td><div style="display: inline; float:right;">';
	get_switch("Writs &rarr;", "List my writs for this block", "block.php", "v", $block_id, "editNoteButton");
	echo '</div></td>
		<td><div style="display: inline; float:right;">';
	get_switch("Memos &rarr;", "List memos for this block", "notes_view.php", "b", "$block_id", "editNoteButton");
	echo '</div></td>
		<td><div style="display: inline; float:right;">';
	set_switch("New writ +", "Start a general writ for this block", "writ.php?v=$block_id", "opened_by", $userid, "editNoteButton");
	echo '</div>
		</td>
		</tr>';

	// Rotate our row color class
	$cc = ($cc == 'lr') ? 'dr' : 'lr';
	// We have at least one row
	$has_blocks = true;
} // End loop
if (mysqli_num_rows($rb) == 0) {
	echo '<tr><td colspan="4"><span class="lt sans">No other enrolled blocks</sans></td></tr>';
}

echo '</tbody></table>';


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
