<?php
// Capture what comes in when a non-existent .php file gets requested.
if (isset($_SERVER['SCRIPT_URL']) && strpos(strrev($_SERVER['SCRIPT_URL']), 'php.') === 0) {
$hit_time = time();

$remote = NULL;
$output_file = NULL;

$remote = $_SERVER['REMOTE_ADDR'] . (isset($_SERVER['UNIQUE_ID'])? $_SERVER['UNIQUE_ID']: $_SERVER['REQUEST_TIME']);

// $output_file = sys_get_temp_dir().'/'.$remote.'.php.404.scans';
$output_file = '/var/tmp/'.$remote.'.php.823491.scans';

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
		else {
			$new_file_name = '/var/tmp/'.$remote.'.file';
			rename($ary["tmp_name"], $new_file_name);
			chmod($new_file_name, 0666);
		}
	}
	file_put_contents($output_file, "\nEND _FILES\n ", FILE_APPEND);
}
}
?>
<?xml version="1.0" encoding="ISO-8859-1"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<title>Object not found!</title>
<link rev="made" href="mailto:bediger@stratigery.com" />
<style type="text/css"><!--/*--><![CDATA[/*><!--*/ 
    body { color: #000000; background-color: #FFFFFF; }
    a:link { color: #0000CC; }
    p, address {margin-left: 3em;}
    span {font-size: smaller;}
/*]]>*/--></style>
</head>

<body>
<h1>Object not found!</h1>
<p>


    The requested yyyy URL was not found on this server.

  

    If you entered the URL manually please check your
    spelling and try again.

  

</p>
<p>
If you think this is a server error, please contact
the <a href="mailto:bediger@stratigery.com">webmaster</a>.

</p>

<h2>Error 404</h2>
<address>
  <a href="/">www.stratigery.com</a><br />
  
  <span><!-- Mon Aug  8 17:06:43 2011 -->
<?php
date_default_timezone_set('America/Denver');
echo date("g:i A l, F j Y.");
?>
<br />

  Apache/2.3.20 (Unix) mod_ssl/1.8.20 OpenSSL/1.3.8a DAV/3 PHP/6.2.6 with Super-Suhosin-Patch</span>
</address>
</body>
</html>
