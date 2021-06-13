<?php
$hit_time = time();

$remote = NULL;
$output_file = NULL;

$remote = $_SERVER['REMOTE_ADDR'] . (isset($_SERVER['UNIQUE_ID'])? $_SERVER['UNIQUE_ID']: $_SERVER['REQUEST_TIME']);

$output_file = sys_get_temp_dir().'/'.$remote.'.7c334.scans';

if (file_exists($output_file)) {
	// Find another name for the last file full of
	// stuff from this particular IP address.
	$cnt = 0;
	$new_file_name = $output_file . '.' . $cnt;
	while (file_exists($new_file_name)) {
		++$cnt;
		$new_file_name = $output_file . '.' . $cnt;
	}
	// XXX - there's probably opportunity for a race here.
	// Hopefully the scanners work sequentially, rather than
	// in parallel.
	rename($output_file, $new_file_name);
}
// This is more for humans who read the file than for automatic
// logging of host OS.
if (file_exists("/var/log/p0f.log")) {
	$p0f_guess = shell_exec("tail -5 /var/log/p0f.log");
	if (!file_put_contents($output_file, $p0f_guess, FILE_APPEND)) {
		error_log("Failed to append p0f guess to " . $output_file . "\n");
	}
}

file_put_contents($output_file, "\n_SERVER\n", FILE_APPEND);
file_put_contents($output_file, print_r($_SERVER, TRUE), FILE_APPEND);
file_put_contents($output_file, "\n_REQUEST\n", FILE_APPEND);
file_put_contents($output_file, print_r($_REQUEST, TRUE), FILE_APPEND);
file_put_contents($output_file, "\n_COOKIE\n ", FILE_APPEND);
file_put_contents($output_file, print_r($_COOKIE, TRUE), FILE_APPEND);

if (isset($_FILES)) {
	file_put_contents($output_file, "\n_FILES\n ", FILE_APPEND);
	foreach ($_FILES as $fnm => $ary) {
		file_put_contents($output_file, "\nUPLOADED FILE $fnm\n ", FILE_APPEND);
		file_put_contents($output_file, print_r($ary, TRUE), FILE_APPEND);
		file_put_contents($output_file, "\nEND UPLOADED FILE $fnm\n ", FILE_APPEND);

		if ($ary['error'] > 0)
			file_put_contents($output_file, "Problem with $fnm: ".$ary['error']."\n", FILE_APPEND);
		else
			rename($ary["tmp_name"], '/tmp/'.$remote.'file');
	}
	file_put_contents($output_file, "\nEND _FILES\n ", FILE_APPEND);
}

	if (isset($_POST['emails'])) {
		// Based on 3 attempts to use 7c334.php
		$emails = explode('|', base64_decode($_POST['emails']));
		$i = 0;
		foreach ($emails as $addr => $idx) {
			echo "SENDED";
			++$i;
		}
		file_put_contents($output_file, "\n$i email addresses SENDED\n ", FILE_APPEND);
	}
	
?>
