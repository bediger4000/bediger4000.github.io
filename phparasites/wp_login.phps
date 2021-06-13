<?php
$hit_time = time();

$remote = NULL;
$output_file = NULL;

$remote = $_SERVER['REMOTE_ADDR'] . (isset($_SERVER['UNIQUE_ID'])? $_SERVER['UNIQUE_ID']: $_SERVER['REQUEST_TIME']);

$output_file = sys_get_temp_dir().'/'.$remote.'.wp-login.scans';

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

# Figure out "where" wp-login.php is in the URL name space.
$my_uri = $_SERVER['REQUEST_URI'];
file_put_contents($output_file, "my_uri = ".$my_uri."\n", FILE_APPEND);
$blog_path = dirname($my_uri);
file_put_contents($output_file, "blog_path = ".$blog_path."\n", FILE_APPEND);

if ($blog_path == '/') $blog_path = '';
$my_blog = 'http://'.$_SERVER['HTTP_HOST'].$blog_path;
file_put_contents($output_file, "my_blog = ".$my_blog."\n", FILE_APPEND);

if (isset($_REQUEST["log"]) && isset($_REQUEST["pwd"])) {
	file_put_contents($output_file, "Trying to redirect\n", FILE_APPEND);

	$log = $_REQUEST['log'];
	$pwd = $_REQUEST['pwd'];

	if (isset($_REQUEST['redirect_to'])) {
		$wp_admin = $_REQUEST['redirect_to'];
	} else {
		$wp_admin = $my_blog.'/wp-admin/';
	}
	file_put_contents($output_file, "wp_admin = ".$wp_admin."\n", FILE_APPEND);

	# Set reasonably life-like cookies
	# bool setcookie ( string $name [, string $value [, int $expire = 0 [, string $path [, string $domain [, bool $secure = false [, bool $httponly = false ]]]]]] )

	$gibberish = 'd'.$_SERVER['REQUEST_TIME'].'fuck';

	#Set-Cookie: wordpress_test_cookie=WP+Cookie+check; path=/blog/
	setcookie("wordpress_test_cookie", "WP Cookie check", 0, $blog_path);

	#Set-Cookie: wordpress_d23cdc2cc5dd18709e8feb86452d865b=flawmaster%7C1367772732%7Cb3db15ed23e99ec0c828a3f37c93717d; expires=Sun, 05-May-2013 16:52:12 GMT; path=/blog/wp-content/plugins; httponly
	$cookie_name1 = "wordpress_".$gibberish;
	$cookie_value1 = $log.$gibberish;
	file_put_contents($output_file, "set cookie ".$cookie_name1.'='.$cookie_value1."\n", FILE_APPEND);
	setcookie($cookie_name1, $cookie_value1, 0, $blog_path."/wp-content/plugins", NULL, false, true);
	setcookie($cookie_name1, $cookie_value1, 0, $blog_path."/wp-admin", NULL, false, true);

	#Set-Cookie: wordpress_logged_in_d23cdc2cc5dd18709e8feb86452d865b=flawmaster%7C1367772732%7Cb0efc8f116e443168ef77d2154648582; expires=Sun, 05-May-2013 16:52:12 GMT; path=/blog/; httponly
	$cookie_name2 = "wordpress_logged_in_".$gibberish;
	setcookie($cookie_name2, $cookie_value1, 0, $blog_path.'/', NULL, false, true);
	file_put_contents($output_file, "set cookie ".$cookie_name2.'='.$cookie_value1."\n", FILE_APPEND);

	header("Content-Length: 0");

	# Redirect.
	header("Location: ".$wp_admin, true, 302);

	exit;
} else {
	#Set-Cookie: wordpress_test_cookie=WP+Cookie+check; path=/blog/
	setcookie("wordpress_test_cookie", "WP Cookie check", 0, $blog_path);
}
if (isset($_REQUEST['action'])) {
	file_put_contents($output_file, "action set.\n", FILE_APPEND);
	$action = $_REQUEST['action'];
	if ($action == 'register') {
		file_put_contents($output_file, "action set to register.\n", FILE_APPEND);
		if (isset($_REQUEST['user_login']) && isset($_REQUEST['user_email'])) {
			file_put_contents($output_file, "action set to register, user_login, user_email set, redirecting.\n", FILE_APPEND);
			header('Location: '.$my_blog.'/wp-login.php?checkemail=registered', TRUE, 302);
		} else {
			file_put_contents($output_file, "action set to register, user_login, user_email NOT set.\n", FILE_APPEND);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head>
	<title>Totally Baked &rsaquo; Registration Form</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel='stylesheet' id='login-css'  href='<?php echo $my_blog; ?>/wp-admin/css/login.css?ver=20091010' type='text/css' media='all' />
<link rel='stylesheet' id='colors-fresh-css'  href='<?php echo $my_blog; ?>/wp-admin/css/colors-fresh.css?ver=20091217' type='text/css' media='all' />
<meta name='robots' content='noindex,nofollow' />
</head>
<body class="login">

<div id="login"><h1><a href="http://wordpress.org/" title="Powered by WordPress">Totally Baked</a></h1>
<p class="message register">Register For This Site</p>

<form name="registerform" id="registerform" action="<?php echo $my_blog; ?>/wp-login.php?action=register" method="post">
	<p>
		<label>Username<br />
		<input type="text" name="user_login" id="user_login" class="input" value="" size="20" tabindex="10" /></label>
	</p>
	<p>
		<label>E-mail<br />
		<input type="text" name="user_email" id="user_email" class="input" value="" size="25" tabindex="20" /></label>
	</p>
	<p id="reg_passmail">A password will be e-mailed to you.</p>
	<br class="clear" />
	<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="Register" tabindex="100" /></p>
</form>

<p id="nav">
<a href="<?php echo $my_blog; ?>/wp-login.php">Log in</a> |
<a href="<?php echo $my_blog; ?>/wp-login.php?action=lostpassword" title="Password Lost and Found">Lost your password?</a>
</p>

</div>

<p id="backtoblog"><a href="<?php echo $my_blog; ?>/" title="Are you lost?">&larr; Back to Totally Baked</a></p>

<script type="text/javascript">
try{document.getElementById('user_login').focus();}catch(e){}
</script>
</body>
</html>
<?php
		}
		exit;
	}
} else if (isset($_REQUEST['checkemail'])) {
	file_put_contents($output_file, "checkemail set.\n", FILE_APPEND);
       if ($_REQUEST['checkemail'] == 'registered') {
		file_put_contents($output_file, "checkemail set to registered.\n", FILE_APPEND);
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head>
	<title>Totally Baked &rsaquo; Log In</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel='stylesheet' id='login-css'  href='<?php echo $my_blog; ?>/wp-admin/css/login.css?ver=20091010' type='text/css' media='all' />
<link rel='stylesheet' id='colors-fresh-css'  href='<?php echo $my_blog; ?>/wp-admin/css/colors-fresh.css?ver=20091217' type='text/css' media='all' />
<meta name='robots' content='noindex,nofollow' />
</head>
<body class="login">

<div id="login"><h1><a href="http://wordpress.org/" title="Powered by WordPress">Totally Baked</a></h1>
<p class="message">	Registration complete. Please check your e-mail.<br />
</p>

<form name="loginform" id="loginform" action="<?php echo $my_blog; ?>/wp-login.php" method="post">
	<p>
		<label>Username<br />
		<input type="text" name="log" id="user_login" class="input" value="" size="20" tabindex="10" /></label>
	</p>
	<p>
		<label>Password<br />
		<input type="password" name="pwd" id="user_pass" class="input" value="" size="20" tabindex="20" /></label>
	</p>
	<p class="forgetmenot"><label><input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="90" /> Remember Me</label></p>
	<p class="submit">
		<input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="Log In" tabindex="100" />
		<input type="hidden" name="redirect_to" value="<?php echo $my_blog; ?>/wp-admin/" />
		<input type="hidden" name="testcookie" value="1" />
	</p>
</form>

<p id="nav">
<a href="<?php echo $my_blog; ?>/wp-login.php?action=register">Register</a> |
<a href="<?php echo $my_blog; ?>/wp-login.php?action=lostpassword" title="Password Lost and Found">Lost your password?</a>
</p>

<p id="backtoblog"><a href="<?php echo $my_blog; ?>/" title="Are you lost?">&larr; Back to Totally Baked</a></p>
</div>

<script type="text/javascript">
setTimeout( function(){ try{
d = document.getElementById('user_pass');
d.value = '';
d.focus();
} catch(e){}
}, 200);
</script>
</body>
</html>
<?php
		exit;
	}
	file_put_contents($output_file, "checkemail set, but not to registered.\n", FILE_APPEND);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head>
	<title>Totally Baked &rsaquo; Log In</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel='stylesheet' id='login-css'  href='<?php echo $my_blog; ?>/wp-admin/css/login.css?ver=20091010' type='text/css' media='all' />
<link rel='stylesheet' id='colors-fresh-css'  href='<?php echo $my_blog; ?>/wp-admin/css/colors-fresh.css?ver=20091217' type='text/css' media='all' />
<meta name='robots' content='noindex,nofollow' />
</head>
<body class="login">

<div id="login"><h1><a href="http://wordpress.org/" title="Powered by WordPress">Totally Baked</a></h1>

<form name="loginform" id="loginform" action="<?php echo $my_blog; ?>/wp-login.php" method="post">
	<p>
		<label>Username<br />
		<input type="text" name="log" id="user_login" class="input" value="" size="20" tabindex="10" /></label>
	</p>
	<p>
		<label>Password<br />
		<input type="password" name="pwd" id="user_pass" class="input" value="" size="20" tabindex="20" /></label>
	</p>
	<p class="forgetmenot"><label><input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="90" /> Remember Me</label></p>
	<p class="submit">
		<input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="Log In" tabindex="100" />
		<input type="hidden" name="redirect_to" value="<?php echo $my_blog; ?>/wp-admin/" />
		<input type="hidden" name="testcookie" value="1" />
	</p>
</form>

<p id="nav">
<a href="<?php echo $my_blog; ?>/wp-login.php?action=lostpassword" title="Password Lost and Found">Lost your password?</a>
</p>

<p id="backtoblog"><a href="<?php echo $my_blog; ?>/" title="Are you lost?">&larr; Back to Totally Baked</a></p>
</div>

<script type="text/javascript">
try{document.getElementById('user_login').focus();}catch(e){}
</script>
</body>
</html>
