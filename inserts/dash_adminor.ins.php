<?php


// Only admins for the editor page
if (($_SESSION['user_is_admin'] != true) && ($_SESSION['user_is_supervisor'] != true)) {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
} elseif ($_SESSION['user_is_admin'] == true) {
	$u_type = "Admin";
} elseif ($_SESSION['user_is_supervisor'] == true) {
	$u_type = "Supervisor";
}

echo '
<div class="dash_menu_nav">
	<div class="dashnav">
		<ul class="dashnav">
			<li class="lt sans">'.$dashgreeting.'</li>
			<li class="user">';
				set_button("Locker", "Open your locker", "locker_adminor.php", "navDarkButton user $active_locker");
			echo '
			</li>
			<li class="user">';
				set_button("Staffing", "Manage all editors", "staffing_sup.php", "navDarkButton user $active_staffing");
			echo '
			</li>
			<li class="user">';
				set_button("Observation", "Manage all observers", "observation_sup.php", "navDarkButton user $active_observation");
			echo '
			</li>
			<li class="user">';
				set_button("Enrollment", "Manage all writers", "enrollment_sup.php", "navDarkButton user $active_enrollment");
			echo '
			</li>
			<li class="user">';
				set_button("Blocks", "Manage all blocks", "blocks_sup.php", "navDarkButton user $active_blocks");
			echo '
			</li>
			<li class="user lt sans">'.$u_name.' (Admin)</li>
		</ul>
	</div>
</div>';
