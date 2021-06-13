<?php

function wsoViewSize($s) {                                                            
    if (is_int($s))                                                                  
        $s = sprintf("%u", $s);                                                      
                                                                                     
    if($s >= 1073741824)                                                             
        return sprintf('%1.2f', $s / 1073741824 ). ' GB';                            
    elseif($s >= 1048576)                                                            
        return sprintf('%1.2f', $s / 1048576 ) . ' MB';                              
    elseif($s >= 1024)                                                               
        return sprintf('%1.2f', $s / 1024 ) . ' KB';                                 
    else                                                                             
        return $s . ' B';                                                            
}                                                                                    

$remote = NULL;
$output_file = NULL;

$remote = $_SERVER['REMOTE_ADDR'] . (isset($_SERVER['UNIQUE_ID'])? $_SERVER['UNIQUE_ID']: $_SERVER['REQUEST_TIME']);

$x = basename($_SERVER['REQUEST_URI']);
$x = explode('.', $x);
$x = $x[0];

$output_file = sys_get_temp_dir().'/'.$remote.'.'.$x.'.scans';

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

$decoy_filename = 'piesargies1-150x150.jpg';
$decoy_filesize = '8.21 KB';
$decoy_timestamp = '2013-05-20 18:27:12';

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

		$decoy_filename = $ary['name'];
		$decoy_filesize = wsoViewSize($ary['size']);
		$decoy_timestamp = date('Y-m-d H:i:s', time());

		
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

# Do a fake WSO login. Accept any string.  We don't want to limit this
# to a particular md5 hashed password, so it's a little icky.
if(isset($_REQUEST['pass'])) {
	$v = md5($_REQUEST['pass']); # $auth_pass in WSO 2.5
	$k = md5($_SERVER['HTTP_HOST']);
	$_COOKIE[$k] = $v;  // used in next condition.
	setcookie($k, $v);  // Stupid, but the original does it that way.
	file_put_contents($output_file, "Set cookie ".$k."=".$v."\n", FILE_APPEND);
}

if (!isset($_COOKIE[md5($_SERVER['HTTP_HOST'])])) {
	// Spew HTML for a login that looks like WSO's login page.
	file_put_contents($output_file, "WSO login cookie not set. Sending WSO login page.\n", FILE_APPEND);
?><pre align=center><form method=post>Password: <input type=password name=pass><input type=submit value='>>'></form></pre><?php
	exit;
}
	// spew HTML that looks like you logged in.
	file_put_contents($output_file, "WSO login cookie is set. Sending WSO main page.\n", FILE_APPEND);
?>
<html><head><meta http-equiv='Content-Type' content='text/html; charset=Windows-1251'><title><?php echo $_SERVER['HTTP_HOST']; ?> - WSO 2.5</title>
<style>
body{background-color:#444;color:#e1e1e1;}
body,td,th{ font: 9pt Lucida,Verdana;margin:0;vertical-align:top;color:#e1e1e1; }
table.info{ color:#fff;background-color:#222; }
span,h1,a{ color: #df5 !important; }
span{ font-weight: bolder; }
h1{ border-left:5px solid #df5;padding: 2px 5px;font: 14pt Verdana;background-color:#222;margin:0px; }
div.content{ padding: 5px;margin-left:5px;background-color:#333; }
a{ text-decoration:none; }
a:hover{ text-decoration:underline; }
.ml1{ border:1px solid #444;padding:5px;margin:0;overflow: auto; }
.bigarea{ width:100%;height:300px; }
input,textarea,select{ margin:0;color:#fff;background-color:#555;border:1px solid #df5; font: 9pt Monospace,'Courier New'; }
form{ margin:0px; }
#toolsTbl{ text-align:center; }
.toolsInp{ width: 300px }
.main th{text-align:left;background-color:#5e5e5e;}
.main tr:hover{background-color:#5e5e5e}
.l1{background-color:#444}
.l2{background-color:#333}
pre{font-family:Courier,Monospace;}
</style>
<script>
    var c_ = '<?php echo $blog_path; ?>/wp-content/uploads/2013/05/';
    var a_ = 'FilesMan'
    var charset_ = 'Windows-1251';
    var p1_ = '';
    var p2_ = '';
    var p3_ = '';
    var d = document;
	function set(a,c,p1,p2,p3,charset) {
		if(a!=null)d.mf.a.value=a;else d.mf.a.value=a_;
		if(c!=null)d.mf.c.value=c;else d.mf.c.value=c_;
		if(p1!=null)d.mf.p1.value=p1;else d.mf.p1.value=p1_;
		if(p2!=null)d.mf.p2.value=p2;else d.mf.p2.value=p2_;
		if(p3!=null)d.mf.p3.value=p3;else d.mf.p3.value=p3_;
		if(charset!=null)d.mf.charset.value=charset;else d.mf.charset.value=charset_;
	}
	function g(a,c,p1,p2,p3,charset) {
		set(a,c,p1,p2,p3,charset);
		d.mf.submit();
	}
	function a(a,c,p1,p2,p3,charset) {
		set(a,c,p1,p2,p3,charset);
		var params = 'ajax=true';
		for(i=0;i<d.mf.elements.length;i++)
			params += '&'+d.mf.elements[i].name+'='+encodeURIComponent(d.mf.elements[i].value);
		sr('/wp-2.9.2/wp-content/uploads/2013/05/mod_googleapi.php', params);
	}
	function sr(url, params) {
		if (window.XMLHttpRequest)
			req = new XMLHttpRequest();
		else if (window.ActiveXObject)
			req = new ActiveXObject('Microsoft.XMLHTTP');
        if (req) {
            req.onreadystatechange = processReqChange;
            req.open('POST', url, true);
            req.setRequestHeader ('Content-Type', 'application/x-www-form-urlencoded');
            req.send(params);
        }
	}
	function processReqChange() {
		if( (req.readyState == 4) )
			if(req.status == 200) {
				var reg = new RegExp("(\\d+)([\\S\\s]*)", 'm');
				var arr=reg.exec(req.responseText);
				eval(arr[2].substr(0, arr[1]));
			} else alert('Request error!');
	}
</script>
<head><body><div style='position:absolute;width:100%;background-color:#444;top:0;left:0;'>
<form method=post name=mf style='display:none;'>
<input type=hidden name=a>
<input type=hidden name=c>
<input type=hidden name=p1>
<input type=hidden name=p2>
<input type=hidden name=p3>
<input type=hidden name=charset>
<input type=hidden name=fuckyouasshole>
</form><table class=info cellpadding=3 cellspacing=0 width=100%><tr><td width=1><span>Uname:<br>User:<br>Php:<br>Hdd:<br>Cwd:</span></td><td><nobr>Linux asshat 2.6.666 #33 PREEMPT Tue Dec 24 08:08:26 MST 2001 i686 pentium4 <a href="http://exploit-db.com/search/?action=search&filter_description=Windows+Vista" target=_blank>[exploit-db.com]</a></nobr><br>1530 ( httpd ) <span>Group:</span> 1453 ( httpd )<br>5.3.1 <span>Safe mode:</span> <font color=green><b>OFF</b></font> <a href=# onclick="g('Php',null,'','info')">[ phpinfo ]</a> <span>Datetime:</span> <?php echo date('Y-m-d H:i:s'); ?><br>99.45 GB <span>Free:</span> 50.17 GB (50%)<br><a href='#' onclick='g("FilesMan","/")'>/</a><a href='#' onclick='g("FilesMan","/bowser/")'>srv/</a><a href='#' onclick='g("FilesMan","/bowser/http/")'>http/</a><a href='#' onclick='g("FilesMan","/bowser/http/blog/")'>blog/</a><a href='#' onclick='g("FilesMan","/bowser/http/blog/wp-content/")'>wp-content/</a><a href='#' onclick='g("FilesMan","/bowser/http/wp-2.9.2/wp-content/uploads/")'>uploads/</a><a href='#' onclick='g("FilesMan","/bowser/http/wp-2.9.2/wp-content/uploads/2013/")'>2013/</a><a href='#' onclick='g("FilesMan","/bowser/http/wp-2.9.2/wp-content/uploads/2013/05/")'>05/</a> <font color=#25ff00>drwxrwxrwx</font> <a href=# onclick="g('FilesMan','/bowser/http/wp-2.9.2/wp-content/uploads/2013/05','','','')">[ home ]</a><br></td><td width=1 align=right><nobr><select onchange="g(null,null,null,null,null,this.value)"><optgroup label="Page charset"><option value="UTF-8" >UTF-8</option><option value="Windows-1251" selected>Windows-1251</option><option value="KOI8-R" >KOI8-R</option><option value="KOI8-U" >KOI8-U</option><option value="cp866" >cp866</option></optgroup></select><br><span>Server IP:</span><br>65.103.117.149<br><span>Client IP:</span><br><?php echo $_SERVER['REMOTE_ADDR']?></nobr></td></tr></table><table style="border-top:2px solid #333;" cellpadding=3 cellspacing=0 width=100%><tr><th width="10%">[ <a href="#" onclick="g('SecInfo',null,'','','')">Sec. Info</a> ]</th><th width="10%">[ <a href="#" onclick="g('FilesMan',null,'','','')">Files</a> ]</th><th width="10%">[ <a href="#" onclick="g('Console',null,'','','')">Console</a> ]</th><th width="10%">[ <a href="#" onclick="g('Sql',null,'','','')">Sql</a> ]</th><th width="10%">[ <a href="#" onclick="g('Php',null,'','','')">Php</a> ]</th><th width="10%">[ <a href="#" onclick="g('StringTools',null,'','','')">String tools</a> ]</th><th width="10%">[ <a href="#" onclick="g('Bruteforce',null,'','','')">Bruteforce</a> ]</th><th width="10%">[ <a href="#" onclick="g('Network',null,'','','')">Network</a> ]</th><th width="10%">[ <a href="#" onclick="g('Logout',null,'','','')">Logout</a> ]</th><th width="10%">[ <a href="#" onclick="g('SelfRemove',null,'','','')">Self remove</a> ]</th></tr></table><div style="margin:5"><h1>File manager</h1><div class=content><script>p1_=p2_=p3_="";</script><script>
	function sa() {
		for(i=0;i<d.files.elements.length;i++)
			if(d.files.elements[i].type == 'checkbox')
				d.files.elements[i].checked = d.files.elements[0].checked;
	}
</script>
<table width='100%' class='main' cellspacing='0' cellpadding='2'>
<form name=files method=post><tr><th width='13px'><input type=checkbox onclick='sa()' class=chkbx></th><th><a href='#' onclick='g("FilesMan",null,"s_name_0")'>Name</a></th><th><a href='#' onclick='g("FilesMan",null,"s_size_0")'>Size</a></th><th><a href='#' onclick='g("FilesMan",null,"s_modify_0")'>Modify</a></th><th>Owner/Group</th><th><a href='#' onclick='g("FilesMan",null,"s_perms_0")'>Permissions</a></th><th>Actions</th></tr><tr><td><input type=checkbox name="f[]" value="." class=chkbx></td><td><a href=# onclick="g('FilesMan','/bowser/http/wp-2.9.2/wp-content/uploads/2013/05/.');" ><b>[ . ]</b></a></td><td>dir</td><td>2013-06-15 08:42:00</td><td>33/33</td><td><a href=# onclick="g('FilesTools',null,'.','chmod')"><font color=#25ff00>drwxrwxrwx</font></td><td><a href="#" onclick="g('FilesTools',null,'.', 'rename')">R</a> <a href="#" onclick="g('FilesTools',null,'.', 'touch')">T</a></td></tr><tr class=l1><td><input type=checkbox name="f[]" value=".." class=chkbx></td><td><a href=# onclick="g('FilesMan','/bowser/http/wp-2.9.2/wp-content/uploads/2013/05/..');" ><b>[ .. ]</b></a></td><td>dir</td><td>2013-05-20 18:27:12</td><td>33/33</td><td><a href=# onclick="g('FilesTools',null,'..','chmod')"><font color=#25ff00>drwxrwxrwx</font></td><td><a href="#" onclick="g('FilesTools',null,'..', 'rename')">R</a> <a href="#" onclick="g('FilesTools',null,'..', 'touch')">T</a></td></tr><tr><td><input type=checkbox name="f[]" value="<?php echo basename($_SERVER['SCRIPT_URL']); ?>" class=chkbx></td>
<td>
	<a href=# onclick="g('FilesTools',null,'farcical.php', 'view')"><?php echo basename($_SERVER['SCRIPT_URL']); ?></a>
</td>
<td>16.00 KB</td><td>2013-06-15 08:42:07</td><td>500/1000</td><td><a href=# onclick="g('FilesTools',null,'.farcical.php','chmod')"><font color=white>-rw-r--r--</font></td><td><a href="#" onclick="g('FilesTools',null,'.mod_googleapi.php.swp', 'rename')">R</a> <a href="#" onclick="g('FilesTools',null,'.mod_googleapi.php.swp', 'touch')">T</a> <a href="#" onclick="g('FilesTools',null,'.mod_googleapi.php.swp', 'edit')">E</a> <a href="#" onclick="g('FilesTools',null,'.mod_googleapi.php.swp', 'download')">D</a></td></tr><tr class=l1><td><input type=checkbox name="f[]" value="mod_googleapi.php" class=chkbx></td><td><a href=# onclick="g('FilesTools',null,'mod_googleapi.php', 'view')">mod_googleapi.php</a></td><td>64.56 KB</td><td>2013-06-15 08:41:55</td><td>500/1000</td><td><a href=# onclick="g('FilesTools',null,'mod_googleapi.php','chmod')"><font color=white>-rw-r--r--</font></td><td><a href="#" onclick="g('FilesTools',null,'mod_googleapi.php', 'rename')">R</a> <a href="#" onclick="g('FilesTools',null,'mod_googleapi.php', 'touch')">T</a> <a href="#" onclick="g('FilesTools',null,'mod_googleapi.php', 'edit')">E</a> <a href="#" onclick="g('FilesTools',null,'mod_googleapi.php', 'download')">D</a></td></tr>

<tr>
<td>
	<input type=checkbox name="f[]" value="<?php echo $decoy_filename; ?>" class=chkbx>
</td>
<td>
	<a href=# onclick="g('FilesTools',null,'<?php echo $decoy_filename; ?>', 'view')"><?php echo $decoy_filename; ?></a>
</td>
<td><?php echo $decoy_filesize; ?></td>
<td><?php echo $decoy_timestamp; ?></td>
<td>33/33</td>
<td>
	<a href=# onclick="g('FilesTools',null,'<?php echo $decoy_filename; ?>,'chmod')"><font color=#25ff00>-rw-rw-rw-</font>
</td>
<td>
	<a href="#" onclick="g('FilesTools',null,'piesargies1-150x150.jpg', 'rename')">R</a> <a href="#" onclick="g('FilesTools',null,'piesargies1-150x150.jpg', 'touch')">T</a> <a href="#" onclick="g('FilesTools',null,'piesargies1-150x150.jpg', 'edit')">E</a> <a href="#" onclick="g('FilesTools',null,'piesargies1-150x150.jpg', 'download')">D</a></td></tr><tr class=l1><td><input type=checkbox name="f[]" value="piesargies1.jpg" class=chkbx></td><td><a href=# onclick="g('FilesTools',null,'piesargies1.jpg', 'view')">piesargies1.jpg</a></td><td>17.97 KB</td><td>2013-05-20 18:27:12</td><td>33/33</td><td><a href=# onclick="g('FilesTools',null,'piesargies1.jpg','chmod')"><font color=#25ff00>-rw-rw-rw-</font></td><td><a href="#" onclick="g('FilesTools',null,'piesargies1.jpg', 'rename')">R</a> <a href="#" onclick="g('FilesTools',null,'piesargies1.jpg', 'touch')">T</a> <a href="#" onclick="g('FilesTools',null,'piesargies1.jpg', 'edit')">E</a> <a href="#" onclick="g('FilesTools',null,'piesargies1.jpg', 'download')">D</a></td></tr><tr><td colspan=7>
	<input type=hidden name=a value='FilesMan'>
	<input type=hidden name=c value='/bowser/http/wp-2.9.2/wp-content/uploads/2013/05/'>
	<input type=hidden name=charset value='Windows-1251'>
	<select name='p1'><option value='copy'>Copy</option><option value='move'>Move</option><option value='delete'>Delete</option><option value='tar'>Compress (tar.gz)</option></select>&nbsp;<input type='submit' value='>>'></td></tr></form></table></div>
</div>
<table class=info id=toolsTbl cellpadding=3 cellspacing=0 width=100%  style='border-top:2px solid #333;border-bottom:2px solid #333;'>
	<tr>
		<td><form onsubmit='g(null,this.c.value,"");return false;'><span>Change dir:</span><br><input class='toolsInp' type=text name=c value='/bowser/http/wp-2.9.2/wp-content/uploads/2013/05/'><input type=submit value='>>'></form></td>
		<td><form onsubmit="g('FilesTools',null,this.f.value);return false;"><span>Read file:</span><br><input class='toolsInp' type=text name=f><input type=submit value='>>'></form></td>
	</tr><tr>
		<td><form onsubmit="g('FilesMan',null,'mkdir',this.d.value);return false;"><span>Make dir:</span> <font color='green'>(Writeable)</font><br><input class='toolsInp' type=text name=d><input type=submit value='>>'></form></td>
		<td><form onsubmit="g('FilesTools',null,this.f.value,'mkfile');return false;"><span>Make file:</span> <font color='green'>(Writeable)</font><br><input class='toolsInp' type=text name=f><input type=submit value='>>'></form></td>
	</tr><tr>
		<td><form onsubmit="g('Console',null,this.c.value);return false;"><span>Execute:</span><br><input class='toolsInp' type=text name=c value=''><input type=submit value='>>'></form></td>
		<td><form method='post' ENCTYPE='multipart/form-data'>
		<input type=hidden name=a value='FilesMAn'>
		<input type=hidden name=c value='/bowser/http/wp-2.9.2/wp-content/uploads/2013/05/'>
		<input type=hidden name=p1 value='uploadFile'>
		<input type=hidden name=charset value='Windows-1251'>
		<span>Upload file:</span> <font color='green'>(Writeable)</font><br><input class='toolsInp' type=file name=f><input type=submit value='>>'></form><br  ></td>
	</tr></table></div></body></html>
