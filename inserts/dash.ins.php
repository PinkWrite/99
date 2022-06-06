<?php


	echo '
	<div class="dash_menu_nav">
		<div class="dashnav">
			<ul class="dashnav">
				<li class="lt sans">'.$dashgreeting.'</li>
				<li class="user">';
					set_button("Locker", "Open your locker", "locker_dash.php", "navDarkButton user $active_locker");
				echo '
				</li>
				<li class="user">';
					set_button("Binder", "View editor notes &amp; tasks", "binder.php", "navDarkButton user $active_binder");
				echo '
				</li>
				<li class="user">';
					set_button("Notes", "View notes", "notes.php", "navDarkButton user $active_notes");
				echo '
				</li>
				<li class="user">';
					set_button("Blocks", "View blocks", "blocks.php", "navDarkButton user $active_blocks");
				echo '
				</li>
				<li class="user">';
					set_button("Writs", "View writs", "writs.php", "navDarkButton user $active_writs");
				echo '
				</li>
				<li class="user lt sans">'.$u_name.' (Dash)</li>
			</ul>
		</div>
	</div>';
