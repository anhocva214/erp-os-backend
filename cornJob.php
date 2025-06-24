<?php
// cron.php

// Set the path to the Laravel project
$laravelPath = "/home/u606539132/domains/demo.omega.ac/public_html/api";

// Build the full command
$command = "cd $laravelPath && php artisan migrate:fresh --seed";

// Execute the command using shell_exec
$output = shell_exec($command);



//echo output with message
echo  $output . " Cron job executed successfully";

?>
