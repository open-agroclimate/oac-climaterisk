<?php

// Are you a proper ajax call?
if( $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest" ) {
	
} else {
	die( "<img src=\"./kp.jpg\"><p>Bad kitty. No ponies for you!</p>" );
}

?>
