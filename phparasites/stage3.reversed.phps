<?php

// From testm.php:
$OOO0O0O00=__FILE__;

// From stage1: read past the plaintext code, and the encyphered
// code of stage1.
$hdl=fopen('188.27.97.163UiDb4goAAAMAAAzk1DwAAAAIfile','rb');
fread($hdl,0x4fb+0x1a8);

$more_text=str_replace(
	'__FILE__',
	"'".$OOO0O0O00."'",
	base64_decode(
		strtr(
			fread($hdl,0x12C),
			'QYqgd7emER8otKG5P+FcaU1nZsV/lJH0xwIOkS43vANLjryuiWzXhCfpTDMBb269=',
			'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/'
		)
	)
);
fclose($hdl);
echo($more_text);
?>
