<?php


	echo '
	<div class="dash_menu_nav">
		<div class="dashnav">
			<ul class="dashnav">
				<li class="lt sans">'.$dashgreeting.'</li>
				<li class="user">';
					set_button("Locker", "Open your locker", "locker_observer.php", "navDarkButton user $active_locker");
				echo '
				</li>
				<li class="user">';
					set_button("Observees", "View all observed writers", "enrollment_observer.php", "navDarkButton user $active_observees");
				echo '
				</li>
				<li class="user">';
					set_button("Writs", "View all observed writs", "writs_observer.php", "navDarkButton user $active_obsvwrits");
				echo '
				</li>
				<li class="user lt sans">'.$u_name.' (Observer)</li>
			</ul>
		</div>
	</div>';
