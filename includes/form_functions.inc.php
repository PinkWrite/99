<?php

// This function generates a form INPUT or TEXTAREA tag
// It takes three arguments
// - The name to be given to the element
// - The type of element (text, password, textarea)
// - An array of errors
function create_form_input($name, $type, $errors, $value) {

	// Assume no value already exists
	if (!isset($value)) {
	$value = false;
	}

	// Check for a value in POST
	if (isset($_POST[$name])) $value = $_POST[$name];

	// Strip slashes if Magic Quotes is enabled
	if ($value && get_magic_quotes_gpc()) $value = stripslashes($value);

	// Conditional to determine what kind of element to create

	// text email password
	if ( ($type == 'text') || ($type == 'email') ||($type == 'password') ) {

		// Start creating the input
		echo '<input';

		// Don't require it for some forms, by name
		if ( ($name != 'project') && (!strstr($name, "__o")) ) { // project OR $name does not contain __o
			echo ' required';
		}
		// Correct "optional" names by finding, then removing the __o
		$name = (strstr($name, '__o')) ? str_replace('__o', '', $name) : $name;

		// Continue the input
		echo ' type="' . $type . '" name="' . $name . '" class="' . $name . '" id="' . $name . '"';

		// Add the value to the input
		if ($value) echo ' value="' . htmlspecialchars($value) . '"';

		// Check for an error
		if (array_key_exists($name, $errors)) {
			echo 'class="error noticered sans" /> <span class="error noticered sans">' . $errors[$name] . '</span>';
		} else {
			echo ' />';
		}

	// textarea
	} elseif ($type == 'textarea') {

		// Display the error first
		if (array_key_exists($name, $errors)) echo ' <span class="error noticered sans">' . $errors[$name] . '</span>';

		// Start creating the textarea
		echo '<textarea required name="' . $name . '" id="' . $name . '" rows="5" cols="75"';

		// Add the error class, if applicable
		if (array_key_exists($name, $errors)) {
			echo ' class="error noticered sans">';
		} else {
			echo '>';
		}

		// Add the value to the textarea
		if ($value) echo $value;

		// Complete the textarea
		echo '</textarea>';

	} // End of primary IF-ELSE

} // End of the create_form_input() function

// Like create_form_input(), but takes a $value argument
function update_form_input($name, $type, $errors, $value) {

	// Check for a value in POST
	if (isset($_POST[$name])) $value = $_POST[$name];

	// Strip slashes if Magic Quotes is enabled
	if ($value && get_magic_quotes_gpc()) $value = stripslashes($value);

	// Conditional to determine what kind of element to create

	// text email password
	if ( ($type == 'text') || ($type == 'email') ||($type == 'password') ) {

		// Start creating the input
		echo '<input';

		// Don't require it for some forms, by name
		if ($name != 'project') {
			echo ' required';
		}

		// Continue the input
		echo ' type="' . $type . '" name="' . $name . '" class="' . $name . '" id="' . $name . '"';

		// Add the value to the input
		if ($value) echo ' value="' . htmlspecialchars($value) . '"';

		// Check for an error
		if (array_key_exists($name, $errors)) {
			echo 'class="error noticered sans" /> <span class="error noticered sans">' . $errors[$name] . '</span>';
		} else {
			echo ' />';
		}

	// textarea
	} elseif ($type == 'textarea') {

		// Display the error first
		if (array_key_exists($name, $errors)) echo ' <span class="error noticered sans">' . $errors[$name] . '</span>';

		// Start creating the textarea
		echo '<textarea required name="' . $name . '" id="' . $name . '" rows="5" cols="75"';

		// Add the error class, if applicable
		if (array_key_exists($name, $errors)) {
			echo ' class="error noticered sans">';
		} else {
			echo '>';
		}

		// Add the value to the textarea
		if ($value) echo $value;

		// Complete the textarea
		echo '</textarea>';

	} // End of primary IF-ELSE

} // End of the create_form_input() function

function set_switch($text, $title, $action, $_post_name, $_post_value, $class) {
	echo "<form align=\"inherit\" action=\"$action\" method=\"post\">
  <input type=\"hidden\" name=\"$_post_name\" value=\"$_post_value\" />
  <input type=\"submit\" title=\"$title\" value=\"$text\" class=\"$class\" />
	</form>";
}

function get_switch($text, $title, $action, $_post_name, $_post_value, $class) {
	echo "<form align=\"inherit\" action=\"$action\" method=\"get\">
  <input type=\"hidden\" name=\"$_post_name\" value=\"$_post_value\" />
  <input type=\"submit\" title=\"$title\" value=\"$text\" class=\"$class\" />
	</form>";
}

function set_button($text, $title, $targeturl, $class) {
	echo "<a title=\"$title\" href=\"$targeturl\"><button type=\"button\" class=\"$class\">$text</button></a>";
}

function dead_switch($text, $title, $class, $action='#') {
	echo "<form align=\"inherit\" action=\"$action\" method=\"post\">
  <input type=\"submit\" title=\"$title\" value=\"$text\" class=\"$class\" disabled=\"disabled\" />
	</form>";
}
