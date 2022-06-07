<?php


// Only admins for the editor page
if ( ($_SESSION['user_is_editor'] != true) && ($_SESSION['user_is_supervisor'] != true) && ($_SESSION['user_is_admin'] != true) ) {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
} elseif ($_SESSION['user_is_admin'] == true) {
	$usr_type = "Admin";
} elseif ($_SESSION['user_is_supervisor'] == true) {
	$usr_type = "Supervisor";
} elseif ($_SESSION['user_is_editor'] == true) {
	$usr_type = "Editor";
}

echo '
<div class="dash_menu_nav">
	<div class="dashnav">
		<ul class="dashnav">
			<li class="lt sans">'.$dashgreeting.'</li>
			<li class="user">';
				set_button("Locker", "Open your locker", "locker_editor.php", "navDarkButton user $active_locker");
			echo '
			</li>
			<li class="user">';
				set_button("Binder", "List editor notes", "binder_editor.php", "navDarkButton user $active_binder");
			echo '
			</li>
			<li class="user">';
				set_button("Roll", "List writers", "enrollment_editor.php", "navDarkButton user $active_roll");
			echo '
			</li>
			<li class="user">';
				set_button("Blocks", "List blocks", "blocks_editor.php", "navDarkButton user $active_blocks");
			echo '
			</li>
			<li class="user">';
				set_button("Writs", "List writs", "writs_editor.php", "navDarkButton user $active_writs");
			echo '
			</li>
			<li class="user lt sans">'.$u_name.' (Editor)</li>
		</ul>
	</div>
</div>';
