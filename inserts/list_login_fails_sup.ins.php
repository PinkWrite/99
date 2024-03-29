<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

// Okay to view this page
$userid = $_SESSION['user_id'];

// Submit Unlock?
if ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['lockout_id'])) && (preg_match("/[0-9]/", $_POST['lockout_id'])) && (isset($_POST['submit'])) && ($_POST['submit'] == "Unlock") ) {
	$lockout_id = $_POST['lockout_id'];
	$q = "UPDATE clickathon SET unlocked=NOW() WHERE lockout_id='$lockout_id' AND unlocked=NULL";
	$r = mysqli_query ($dbc, $q);
	// We aren't checking because we will reload the page without POST either way and render the results either way
	// if ($r) {
	// 	// Success
	// 	echo "Success!";
	// } else {
	// 	// Fail
	// 	echo "Fail";
	// }
	header("Location: $where_am_i");
	exit();
}

// Sorting options
$sort_get = (strstr($where_am_i, '?')) ? '&' : '?' ;

// Sort GET setting
$occurance_cl = 'act_ltgray';
$beginning_cl = 'act_ltgray';
$username_cl = 'act_ltgray';
$ipaddress_cl = 'act_ltgray';
if ((isset($_GET['s'])) && (preg_match("/[a-z]/", $_GET['s']))) {
	$sort = preg_replace("/[^a-z]/","", $_GET['s']);
	switch ($sort) {
		case "occurance":
				$order_by = "id DESC";
				$occurance_cl = 'act_dkgray';
				$sort_suffix = 's=occurance';
				break;
		case "beginning":
				$order_by = "id ASC";
				$beginning_cl = 'act_dkgray';
				$sort_suffix = 's=beginning';
				break;
		case "username":
				$order_by = "username_list ASC";
				$username_cl = 'act_dkgray';
				$sort_suffix = 's=username';
				break;
		case "ipaddress":
				$order_by = "ip ASC";
				$ipaddress_cl = 'act_dkgray';
				$sort_suffix = 's=ipaddress';
				break;
		default:
				$order_by = "id DESC";
				$occurance_cl = 'act_dkgray';
				$sort_suffix = 's=occurance';
				break;
	}
} else {
	$order_by = "id DESC";
	$occurance_cl = 'act_dkgray';
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
	$SQLcolumnSearch = "WHERE ( id LIKE '0'";
	// Add each search word
	if(strpos($search_query, " ") !== false) {
			$searchwordS = array();
			$searchwordS = explode(" ", $search_query);

			foreach($searchwordS as $searchword){
					$searchword = mysqli_real_escape_string($dbc, $searchword);
					$SQLcolumnSearch = $SQLcolumnSearch." OR LOWER(username_list) LIKE LOWER('%$searchword%') OR LOWER(ip) LIKE LOWER('%$searchword%') OR LOWER(time) LIKE LOWER('%$searchword%')";
			}
	} else {
		$searchword = $search_query;
		$searchword = mysqli_real_escape_string($dbc, $searchword);
		$SQLcolumnSearch = $SQLcolumnSearch." OR LOWER(username_list) LIKE LOWER('%$searchword%') OR LOWER(ip) LIKE LOWER('%$searchword%') OR LOWER(time) LIKE LOWER('%$searchword%')";
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
$sql_where = "ORDER BY $order_by";
$qp = "SELECT $sql_cols FROM clickathon $SQLcolumnSearch $sql_where";
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
set_button("Last occurance", "Sort by last occurance", "{$where_am_i}{$sort_get}s=occurance{$search_suffix}", $occurance_cl);
echo '</td><td>';
set_button("Beginning", "Sort from beginning", "{$where_am_i}{$sort_get}s=beginning{$search_suffix}", $beginning_cl);
echo '</td><td>';
set_button("Username", "Sort by username", "{$where_am_i}{$sort_get}s=username{$search_suffix}", $username_cl);
echo '</td><td>';
set_button("IP Address", "Sort by IP address", "{$where_am_i}{$sort_get}s=ipaddress{$search_suffix}", $ipaddress_cl);
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

// List blocks
$sql_cols = 'id, username_list, ip, time_stamp, time_epoch, unlocked';
$q = "SELECT $sql_cols FROM clickathon $SQLcolumnSearch $sql_where LIMIT $itemskip,$pageitems";
$r = mysqli_query ($dbc, $q);

// Empty?
if (mysqli_num_rows($r) == 0) {
	echo '<p class="lt sans">No repeat login fails recorded</p>';
		} else {

	// Start the table
	echo '
	<table class="list sans lt"><tbody>
	<tr><th>Usernames attempted</th><th>IP Address</th><th>IP Locked at</th><th>Status</th></tr>';

	// Start our row color class
	$cc = 'lr';

	// Iterate each entry
	while ($row = mysqli_fetch_array($r)) {
		$lockout_id = "$row[0]";
		$username_list = "$row[1]";
		$ip = "$row[2]";
		$time = "$row[3]";
		$time_epoch = "$row[4]";
		$unlocked = "$row[5]";
		$now_epoch = time();

		echo '<tr class="'.$cc.'">
			<td>'.$username_list.'</td>
			<td>'.$ip.'</td>
			<td>'.$time.'</td>
			<td>';
			if ( ($unlocked == '') && ( $time_epoch + (60 * 60) > ( $now_epoch ) ) ) {
			  echo '<form method="post" action="'.$where_am_i.'">
			  <input type="hidden" name="lockout_id" value="'.$lockout_id.'" />
			  <input type="submit" name="submit" value="Unlock" class="editNoteButton" />
			  </form> ';
			} elseif ( ($unlocked == '' ) && ( $time_epoch + (60 * 60) <= ( $now_epoch ) ) ) {
			  echo 'Expired';
			} else {
			  echo "1 $unlocked";
			}
		echo '</td>
			</tr>';

		// Rotate our row color class
		$cc = ($cc == 'lr') ? 'dr' : 'lr';
	} // End loop
	if (mysqli_num_rows($r) == 0) {
		echo '<tr><td colspan="4">No login fails</td></tr>';
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
