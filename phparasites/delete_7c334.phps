<?php
function check_dir($path, $delfile, $maxlevel, $level = 0)
{
    if($path[strlen($path)-1] != '/')
         $path = $path.DIRECTORY_SEPARATOR;
    $res = scandir($path);
    if($res === FALSE)
        return;
    foreach($res as $elem)
    {
        if(is_file($path.$elem) && $elem == $delfile)
        {
            @unlink($path.$elem); 
        }
        elseif(is_dir($path.$elem) && $elem != '..' && $elem != '.')
        {
            if($maxlevel > -1 && $level >= $maxlevel)
                continue;
            check_dir($path.$elem, $delfile, $maxlevel, $level+1);
        }
    }
}
check_dir('/', '7c334.php', -1);
echo 'eval_ok';
