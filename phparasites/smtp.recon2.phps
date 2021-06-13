if (version_compare(PHP_VERSION, '5.0.0', '<') ) {
  exit("Sorry, PHPMailer will only run on PHP version 5 or greater!\n");
}



class html2text
{
    public $html;
    public $text;
    public $width = 70;

    public $search = array(
        "/\r/",                                  // Non-legal carriage return
        "/[\n\t]+/",                             // Newlines and tabs
        '/<head[^>]*>.*?<\/head>/i',             // <head>
        '/<script[^>]*>.*?<\/script>/i',         // <script>s -- which strip_tags supposedly has problems with
        '/<style[^>]*>.*?<\/style>/i',           // <style>s -- which strip_tags supposedly has problems with
        '/<p[^>]*>/i',                           // <P>
        '/<br[^>]*>/i',                          // <br>
        '/<i[^>]*>(.*?)<\/i>/i',                 // <i>
        '/<em[^>]*>(.*?)<\/em>/i',               // <em>
        '/(<ul[^>]*>|<\/ul>)/i',                 // <ul> and </ul>
        '/(<ol[^>]*>|<\/ol>)/i',                 // <ol> and </ol>
        '/<li[^>]*>(.*?)<\/li>/i',               // <li> and </li>
        '/<li[^>]*>/i',                          // <li>
        '/<hr[^>]*>/i',                          // <hr>
        '/<div[^>]*>/i',                         // <div>
        '/(<table[^>]*>|<\/table>)/i',           // <table> and </table>
        '/(<tr[^>]*>|<\/tr>)/i',                 // <tr> and </tr>
        '/<td[^>]*>(.*?)<\/td>/i',               // <td> and </td>
        '/<span class="_html2text_ignore">.+?<\/span>/i'  // <span class="_html2text_ignore">...</span>
    );

    public $replace = array(
        '',                                     // Non-legal carriage return
        ' ',                                    // Newlines and tabs
        '',                                     // <head>
        '',                                     // <script>s -- which strip_tags supposedly has problems with
        '',                                     // <style>s -- which strip_tags supposedly has problems with
        "\n\n",                                 // <P>
        "\n",                                   // <br>
        '_\\1_',                                // <i>
        '_\\1_',                                // <em>
        "\n\n",                                 // <ul> and </ul>
        "\n\n",                                 // <ol> and </ol>
        "\t* \\1\n",                            // <li> and </li>
        "\n\t* ",                               // <li>
        "\n-------------------------\n",        // <hr>
        "<div>\n",                              // <div>
        "\n\n",                                 // <table> and </table>
        "\n",                                   // <tr> and </tr>
        "\t\t\\1\n",                            // <td> and </td>
        ""                                      // <span class="_html2text_ignore">...</span>
    );

    public $ent_search = array(
        '/&(nbsp|#160);/i',                      // Non-breaking space
        '/&(quot|rdquo|ldquo|#8220|#8221|#147|#148);/i',
        // Double quotes
        '/&(apos|rsquo|lsquo|#8216|#8217);/i',   // Single quotes
        '/&gt;/i',                               // Greater-than
        '/&lt;/i',                               // Less-than
        '/&(copy|#169);/i',                      // Copyright
        '/&(trade|#8482|#153);/i',               // Trademark
        '/&(reg|#174);/i',                       // Registered
        '/&(mdash|#151|#8212);/i',               // mdash
        '/&(ndash|minus|#8211|#8722);/i',        // ndash
        '/&(bull|#149|#8226);/i',                // Bullet
        '/&(pound|#163);/i',                     // Pound sign
        '/&(euro|#8364);/i',                     // Euro sign
        '/&(amp|#38);/i',                        // Ampersand: see _converter()
        '/[ ]{2,}/',                             // Runs of spaces, post-handling
    );

    public $ent_replace = array(
        ' ',                                    // Non-breaking space
        '"',                                    // Double quotes
        "'",                                    // Single quotes
        '>',
        '<',
        '(c)',
        '(tm)',
        '(R)',
        '--',
        '-',
        '*',
        'Р“вЂљР’Р€',
        'EUR',                                  // Euro sign. РІвЂљВ¬ ?
        '|+|amp|+|',                            // Ampersand: see _converter()
        ' ',                                    // Runs of spaces, post-handling
    );

    public $callback_search = array(
        '/<(a) [^>]*href=("|\')([^"\']+)\2([^>]*)>(.*?)<\/a>/i', // <a href="">
        '/<(h)[123456]( [^>]*)?>(.*?)<\/h[123456]>/i',         // h1 - h6
        '/<(b)( [^>]*)?>(.*?)<\/b>/i',                         // <b>
        '/<(strong)( [^>]*)?>(.*?)<\/strong>/i',               // <strong>
        '/<(th)( [^>]*)?>(.*?)<\/th>/i',                       // <th> and </th>
    );

    public $pre_search = array(
        "/\n/",
        "/\t/",
        '/ /',
        '/<pre[^>]*>/',
        '/<\/pre>/'
    );

    public $pre_replace = array(
        '<br>',
        '&nbsp;&nbsp;&nbsp;&nbsp;',
        '&nbsp;',
        '',
        ''
    );

    public $allowed_tags = '';
    public $url;
    private $_converted = false;
    private $_link_list = array();

    private $_options = array(
        'do_links' => 'inline',
        'width' => 70,
    );

    public function __construct( $source = '', $from_file = false, $options = array() )
    {
        $this->_options = array_merge($this->_options, $options);

        if ( !empty($source) ) {
            $this->set_html($source, $from_file);
        }

        $this->set_base_url();
    }

    public function set_html( $source, $from_file = false )
    {
        if ( $from_file && file_exists($source) ) {
            $this->html = file_get_contents($source);
        }
        else
            $this->html = $source;

        $this->_converted = false;
    }

    public function get_text()
    {
        if ( !$this->_converted ) {
            $this->_convert();
        }

        return $this->text;
    }

    public function print_text()
    {
        print $this->get_text();
    }

    public function p()
    {
        print $this->get_text();
    }

    public function set_allowed_tags( $allowed_tags = '' )
    {
        if ( !empty($allowed_tags) ) {
            $this->allowed_tags = $allowed_tags;
        }
    }

    public function set_base_url( $url = '' )
    {
        if ( empty($url) ) {
            if ( !empty($_SERVER['HTTP_HOST']) ) {
                $this->url = 'http://' . $_SERVER['HTTP_HOST'];
            } else {
                $this->url = '';
            }
        } else {
            if ( substr($url, -1) == '/' ) {
                $url = substr($url, 0, -1);
            }
            $this->url = $url;
        }
    }

    private function _convert()
    {
        $this->_link_list = array();
        $text = trim(stripslashes($this->html));
        $this->_converter($text);
        if (!empty($this->_link_list)) {
            $text .= "\n\nLinks:\n------\n";
            foreach ($this->_link_list as $idx => $url) {
                $text .= '[' . ($idx+1) . '] ' . $url . "\n";
            }
        }
        $this->text = $text;
        $this->_converted = true;
    }

    private function _converter(&$text)
    {
        $this->_convert_blockquotes($text);
        $this->_convert_pre($text);
        $text = preg_replace($this->search, $this->replace, $text);
        $text = preg_replace_callback($this->callback_search, array($this, '_preg_callback'), $text);
        $text = strip_tags($text, $this->allowed_tags);
        $text = preg_replace($this->ent_search, $this->ent_replace, $text);
        $text = html_entity_decode($text, ENT_QUOTES);
        $text = preg_replace('/&([a-zA-Z0-9]{2,6}|#[0-9]{2,4});/', '', $text);
        $text = str_replace('|+|amp|+|', '&', $text);
        $text = preg_replace("/\n\s+\n/", "\n\n", $text);
        $text = preg_replace("/[\n]{3,}/", "\n\n", $text);

        $text = ltrim($text, "\n");

        if ( $this->_options['width'] > 0 ) {
            $text = wordwrap($text, $this->_options['width']);
        }
    }

    private function _build_link_list( $link, $display, $link_override = null)
    {
        $link_method = ($link_override) ? $link_override : $this->_options['do_links'];
        if ($link_method == 'none')
            return $display;

        if (preg_match('!^(javascript:|mailto:|#)!i', $link)) {
            return $display;
        }
        if (preg_match('!^([a-z][a-z0-9.+-]+:)!i', $link)) {
            $url = $link;
        }
        else {
            $url = $this->url;
            if (substr($link, 0, 1) != '/') {
                $url .= '/';
            }
            $url .= "$link";
        }

        if ($link_method == 'table')
        {
            if (($index = array_search($url, $this->_link_list)) === false) {
                $index = count($this->_link_list);
                $this->_link_list[] = $url;
            }

            return $display . ' [' . ($index+1) . ']';
        }
        elseif ($link_method == 'nextline')
        {
            return $display . "\n[" . $url . ']';
        }
        else // link_method defaults to inline
        {
            return $display . ' [' . $url . ']';
        }
    }

    private function _convert_pre(&$text)
    {
        while (preg_match('/<pre[^>]*>(.*)<\/pre>/ismU', $text, $matches)) {
            $this->pre_content = $matches[1];

            $this->pre_content = preg_replace_callback($this->callback_search,
                array($this, '_preg_callback'), $this->pre_content);

            $this->pre_content = sprintf('<div><br>%s<br></div>',
                preg_replace($this->pre_search, $this->pre_replace, $this->pre_content));
            $text = preg_replace_callback('/<pre[^>]*>.*<\/pre>/ismU',
                array($this, '_preg_pre_callback'), $text, 1);

            $this->pre_content = '';
        }
    }

    private function _convert_blockquotes(&$text)
    {
        if (preg_match_all('/<\/*blockquote[^>]*>/i', $text, $matches, PREG_OFFSET_CAPTURE)) {
            $level = 0;
            $diff = 0;
            $start = 0;
            $taglen = 0;
            foreach ($matches[0] as $m) {
                if ($m[0][0] == '<' && $m[0][1] == '/') {
                    $level--;
                    if ($level < 0) {
                        $level = 0; // malformed HTML: go to next blockquote
                    }
                    else if ($level > 0) {
                    }
                    else {
                        $end  = $m[1];
                        $len  = $end - $taglen - $start;
                        $body = substr($text, $start + $taglen - $diff, $len);
                        $p_width = $this->_options['width'];
                        if ($this->_options['width'] > 0) $this->_options['width'] -= 2;
                        $body = trim($body);
                        $this->_converter($body);
                        $body = preg_replace('/((^|\n)>*)/', '\\1> ', trim($body));
                        $body = '<pre>' . htmlspecialchars($body) . '</pre>';
                        $this->_options['width'] = $p_width;
                        $text = substr($text, 0, $start - $diff)
                            . $body . substr($text, $end + strlen($m[0]) - $diff);

                        $diff = $len + $taglen + strlen($m[0]) - strlen($body);
                        unset($body);
                    }
                }
                else {
                    if ($level == 0) {
                        $start = $m[1];
                        $taglen = strlen($m[0]);
                    }
                    $level ++;
                }
            }
        }
    }

    private function _preg_callback($matches)
    {
        switch (strtolower($matches[1])) {
            case 'b':
            case 'strong':
                return $this->_toupper($matches[3]);
            case 'th':
                return $this->_toupper("\t\t". $matches[3] ."\n");
            case 'h':
                return $this->_toupper("\n\n". $matches[3] ."\n\n");
            case 'a':
                $link_override = null;
                if (preg_match("/_html2text_link_(\w+)/", $matches[4], $link_override_match))
                {
                    $link_override = $link_override_match[1];
                }
                $url = str_replace(' ', '', $matches[3]);
                return $this->_build_link_list($url, $matches[5], $link_override);
        }
    }

    private function _preg_pre_callback($matches)
    {
        return $this->pre_content;
    }

    private function _toupper($str)
    {
        $chunks = preg_split('/(<[^>]*>)/', $str, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        foreach ($chunks as $idx => $chunk) {
            if ($chunk[0] != '<') {
                $chunks[$idx] = $this->_strtoupper($chunk);
            }
        }
        return implode($chunks);
    }

    private function _strtoupper($str)
    {
        $str = html_entity_decode($str, ENT_COMPAT);
        if (function_exists('mb_strtoupper'))
            $str = mb_strtoupper($str);
        else
            $str = strtoupper($str);
        $str = htmlspecialchars($str, ENT_COMPAT);
        return $str;
    }
}


class SMTP {
  public $SMTP_PORT = 25;
  public $CRLF = "\r\n";
  public $do_debug = 0;
  public $Debugoutput     = 'echo';
  public $do_verp = false;
  public $Timeout         = 15;
  public $Timelimit       = 30;
  public $Version         = '5.2.6';
  protected $smtp_conn;
  protected $error;
  protected $helo_rply;
  protected function edebug($str) {
    switch ($this->Debugoutput) {
      case 'error_log':
        error_log($str);
        break;
      case 'html':
        echo htmlentities(preg_replace('/[\r\n]+/', '', $str), ENT_QUOTES, 'UTF-8')."<br>\n";
        break;
      case 'echo':
      default:
        echo $str;
    }
  }
  public function __construct() {
    $this->smtp_conn = 0;
    $this->error = null;
    $this->helo_rply = null;

    $this->do_debug = 0;
  }
  public function Connect($host, $port = 0, $timeout = 30, $options = array()) {
    $this->error = null;
    if($this->connected()) {
      $this->error = array('error' => 'Already connected to a server');
      return false;
    }

	if(empty($port)) {
      $port = $this->SMTP_PORT;
    }

    $errno = 0;
    $errstr = '';
    $socket_context = stream_context_create($options);
    $this->smtp_conn = @stream_socket_client($host.":".$port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $socket_context);

    if(empty($this->smtp_conn)) {
      $this->error = array('error' => 'Failed to connect to server',
                           'errno' => $errno,
                           'errstr' => $errstr);
      if($this->do_debug >= 1) {
        $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ": $errstr ($errno)");
      }
      return false;
    }

    if(substr(PHP_OS, 0, 3) != 'WIN') {
      $max = ini_get('max_execution_time');
      if ($max != 0 && $timeout > $max) { // Don't bother if unlimited
        @set_time_limit($timeout);
      }
      stream_set_timeout($this->smtp_conn, $timeout, 0);
    }

    $announce = $this->get_lines();

    if($this->do_debug >= 2) {
      $this->edebug('SMTP -> FROM SERVER:' . $announce);
    }

    return true;
  }

  public function StartTLS() {
    $this->error = null; # to avoid confusion

    if(!$this->connected()) {
      $this->error = array('error' => 'Called StartTLS() without being connected');
      return false;
    }

    $this->client_send('STARTTLS' . $this->CRLF);

    $rply = $this->get_lines();
    $code = substr($rply, 0, 3);

    if($this->do_debug >= 2) {
      $this->edebug('SMTP -> FROM SERVER:' . $rply);
    }

    if($code != 220) {
      $this->error =
         array('error'     => 'STARTTLS not accepted from server',
               'smtp_code' => $code,
               'smtp_msg'  => substr($rply, 4));
      if($this->do_debug >= 1) {
        $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply);
      }
      return false;
    }

    if(!stream_socket_enable_crypto($this->smtp_conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
      return false;
    }

    return true;
  }

  public function Authenticate($username, $password, $authtype='LOGIN', $realm='', $workstation='') {
    if (empty($authtype)) {
      $authtype = 'LOGIN';
    }

    switch ($authtype) {
      case 'PLAIN':
        $this->client_send('AUTH PLAIN' . $this->CRLF);

        $rply = $this->get_lines();
        $code = substr($rply, 0, 3);

        if($code != 334) {
          $this->error =
            array('error' => 'AUTH not accepted from server',
                  'smtp_code' => $code,
                  'smtp_msg' => substr($rply, 4));
          if($this->do_debug >= 1) {
            $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply);
          }
          return false;
        }
          $this->client_send(base64_encode("\0".$username."\0".$password) . $this->CRLF);

        $rply = $this->get_lines();
        $code = substr($rply, 0, 3);

        if($code != 235) {
          $this->error =
            array('error' => 'Authentication not accepted from server',
                  'smtp_code' => $code,
                  'smtp_msg' => substr($rply, 4));
          if($this->do_debug >= 1) {
            $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply);
          }
          return false;
        }
        break;
      case 'LOGIN':
        $this->client_send('AUTH LOGIN' . $this->CRLF);

        $rply = $this->get_lines();
        $code = substr($rply, 0, 3);

        if($code != 334) {
          $this->error =
            array('error' => 'AUTH not accepted from server',
                  'smtp_code' => $code,
                  'smtp_msg' => substr($rply, 4));
          if($this->do_debug >= 1) {
            $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply);
          }
          return false;
        }

        $this->client_send(base64_encode($username) . $this->CRLF);

        $rply = $this->get_lines();
        $code = substr($rply, 0, 3);

        if($code != 334) {
          $this->error =
            array('error' => 'Username not accepted from server',
                  'smtp_code' => $code,
                  'smtp_msg' => substr($rply, 4));
          if($this->do_debug >= 1) {
            $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply);
          }
          return false;
        }

        $this->client_send(base64_encode($password) . $this->CRLF);

        $rply = $this->get_lines();
        $code = substr($rply, 0, 3);

        if($code != 235) {
          $this->error =
            array('error' => 'Password not accepted from server',
                  'smtp_code' => $code,
                  'smtp_msg' => substr($rply, 4));
          if($this->do_debug >= 1) {
            $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply);
          }
          return false;
        }
        break;
      case 'NTLM':
        require_once 'extras/ntlm_sasl_client.php';
        $temp = new stdClass();
        $ntlm_client = new ntlm_sasl_client_class;
        if(! $ntlm_client->Initialize($temp)){//let's test if every function its available
            $this->error = array('error' => $temp->error);
            if($this->do_debug >= 1) {
                $this->edebug('You need to enable some modules in your php.ini file: ' . $this->error['error']);
            }
            return false;
        }
        $msg1 = $ntlm_client->TypeMsg1($realm, $workstation);//msg1

        $this->client_send('AUTH NTLM ' . base64_encode($msg1) . $this->CRLF);

        $rply = $this->get_lines();
        $code = substr($rply, 0, 3);

        if($code != 334) {
            $this->error =
                array('error' => 'AUTH not accepted from server',
                      'smtp_code' => $code,
                      'smtp_msg' => substr($rply, 4));
            if($this->do_debug >= 1) {
                $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply);
            }
            return false;
        }

        $challenge = substr($rply, 3);//though 0 based, there is a white space after the 3 digit number....//msg2
        $challenge = base64_decode($challenge);
        $ntlm_res = $ntlm_client->NTLMResponse(substr($challenge, 24, 8), $password);
        $msg3 = $ntlm_client->TypeMsg3($ntlm_res, $username, $realm, $workstation);//msg3
        $this->client_send(base64_encode($msg3) . $this->CRLF);

        $rply = $this->get_lines();
        $code = substr($rply, 0, 3);

        if($code != 235) {
            $this->error =
                array('error' => 'Could not authenticate',
                      'smtp_code' => $code,
                      'smtp_msg' => substr($rply, 4));
            if($this->do_debug >= 1) {
                $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply);
            }
            return false;
        }
        break;
      case 'CRAM-MD5':
        $this->client_send('AUTH CRAM-MD5' . $this->CRLF);

        $rply = $this->get_lines();
        $code = substr($rply, 0, 3);

        if($code != 334) {
          $this->error =
            array('error' => 'AUTH not accepted from server',
                  'smtp_code' => $code,
                  'smtp_msg' => substr($rply, 4));
          if($this->do_debug >= 1) {
            $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply);
          }
          return false;
        }
        $challenge = base64_decode(substr($rply, 4));
        $response = $username . ' ' . $this->hmac($challenge, $password);
        $this->client_send(base64_encode($response) . $this->CRLF);
        $rply = $this->get_lines();
        $code = substr($rply, 0, 3);

        if($code != 235) {
          $this->error =
            array('error' => 'Credentials not accepted from server',
                  'smtp_code' => $code,
                  'smtp_msg' => substr($rply, 4));
          if($this->do_debug >= 1) {
            $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply);
          }
          return false;
        }
        break;
    }
    return true;
  }

  protected function hmac($data, $key) {
      if (function_exists('hash_hmac')) {
          return hash_hmac('md5', $data, $key);
      }
      $b = 64; // byte length for md5
      if (strlen($key) > $b) {
          $key = pack('H*', md5($key));
      }
      $key  = str_pad($key, $b, chr(0x00));
      $ipad = str_pad('', $b, chr(0x36));
      $opad = str_pad('', $b, chr(0x5c));
      $k_ipad = $key ^ $ipad ;
      $k_opad = $key ^ $opad;

      return md5($k_opad  . pack('H*', md5($k_ipad . $data)));
  }

  public function Connected() {
    if(!empty($this->smtp_conn)) {
      $sock_status = stream_get_meta_data($this->smtp_conn);
      if($sock_status['eof']) {
        if($this->do_debug >= 1) {
            $this->edebug('SMTP -> NOTICE: EOF caught while checking if connected');
        }
        $this->Close();
        return false;
      }
      return true; // everything looks good
    }
    return false;
  }

  public function Close() {
    $this->error = null; // so there is no confusion
    $this->helo_rply = null;
    if(!empty($this->smtp_conn)) {
      // close the connection and cleanup
      fclose($this->smtp_conn);
      $this->smtp_conn = 0;
    }
  }

  public function Data($msg_data) {
    $this->error = null; // so no confusion is caused

    if(!$this->connected()) {
      $this->error = array(
              'error' => 'Called Data() without being connected');
      return false;
    }
    $this->client_send('DATA' . $this->CRLF);
    $rply = $this->get_lines();
    $code = substr($rply, 0, 3);

    if($this->do_debug >= 2) {
      $this->edebug('SMTP -> FROM SERVER:' . $rply);
    }

    if($code != 354) {
      $this->error =
        array('error' => 'DATA command not accepted from server',
              'smtp_code' => $code,
              'smtp_msg' => substr($rply, 4));
      if($this->do_debug >= 1) {
        $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply);
      }
      return false;
    }

    $msg_data = str_replace("\r\n", "\n", $msg_data);
    $msg_data = str_replace("\r", "\n", $msg_data);
    $lines = explode("\n", $msg_data);
    $field = substr($lines[0], 0, strpos($lines[0], ':'));
    $in_headers = false;
    if(!empty($field) && !strstr($field, ' ')) {
      $in_headers = true;
    }

    $max_line_length = 998; // used below; set here for ease in change

    while(list(, $line) = @each($lines)) {
      $lines_out = null;
      if($line == '' && $in_headers) {
        $in_headers = false;
      }
      while(strlen($line) > $max_line_length) {
        $pos = strrpos(substr($line, 0, $max_line_length), ' ');

        if(!$pos) {
          $pos = $max_line_length - 1;
          $lines_out[] = substr($line, 0, $pos);
          $line = substr($line, $pos);
        } else {
          $lines_out[] = substr($line, 0, $pos);
          $line = substr($line, $pos + 1);
        }

        if($in_headers) {
          $line = "\t" . $line;
        }
      }
      $lines_out[] = $line;

      while(list(, $line_out) = @each($lines_out)) {
        if(strlen($line_out) > 0)
        {
          if(substr($line_out, 0, 1) == '.') {
            $line_out = '.' . $line_out;
          }
        }
        $this->client_send($line_out . $this->CRLF);
      }
    }

    $this->client_send($this->CRLF . '.' . $this->CRLF);

    $rply = $this->get_lines();
    $code = substr($rply, 0, 3);

    if($this->do_debug >= 2) {
      $this->edebug('SMTP -> FROM SERVER:' . $rply);
    }

    if($code != 250) {
      $this->error =
        array('error' => 'DATA not accepted from server',
              'smtp_code' => $code,
              'smtp_msg' => substr($rply, 4));
      if($this->do_debug >= 1) {
        $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply);
      }
      return false;
    }
    return true;
  }

  public function Hello($host = '') {
    $this->error = null; // so no confusion is caused

    if(!$this->connected()) {
      $this->error = array(
            'error' => 'Called Hello() without being connected');
      return false;
    }
    if(empty($host)) {
      $host = 'localhost';
    }
    if(!$this->SendHello('EHLO', $host)) {
      if(!$this->SendHello('HELO', $host)) {
        return false;
      }
    }

    return true;
  }

  protected function SendHello($hello, $host) {
    $this->client_send($hello . ' ' . $host . $this->CRLF);

    $rply = $this->get_lines();
    $code = substr($rply, 0, 3);

    if($this->do_debug >= 2) {
      $this->edebug('SMTP -> FROM SERVER: ' . $rply);
    }

    if($code != 250) {
      $this->error =
        array('error' => $hello . ' not accepted from server',
              'smtp_code' => $code,
              'smtp_msg' => substr($rply, 4));
      if($this->do_debug >= 1) {
        $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply);
      }
      return false;
    }

    $this->helo_rply = $rply;

    return true;
  }

  public function Mail($from) {
    $this->error = null; // so no confusion is caused

    if(!$this->connected()) {
      $this->error = array(
              'error' => 'Called Mail() without being connected');
      return false;
    }

    $useVerp = ($this->do_verp ? ' XVERP' : '');
    $this->client_send('MAIL FROM:<' . $from . '>' . $useVerp . $this->CRLF);

    $rply = $this->get_lines();
    $code = substr($rply, 0, 3);

    if($this->do_debug >= 2) {
      $this->edebug('SMTP -> FROM SERVER:' . $rply);
    }

    if($code != 250) {
      $this->error =
        array('error' => 'MAIL not accepted from server',
              'smtp_code' => $code,
              'smtp_msg' => substr($rply, 4));
      if($this->do_debug >= 1) {
        $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply);
      }
      return false;
    }
    return true;
  }

  public function Quit($close_on_error = true) {
    $this->error = null; // so there is no confusion

    if(!$this->connected()) {
      $this->error = array(
              'error' => 'Called Quit() without being connected');
      return false;
    }

    $this->client_send('quit' . $this->CRLF);
    $byemsg = $this->get_lines();

    if($this->do_debug >= 2) {
      $this->edebug('SMTP -> FROM SERVER:' . $byemsg);
    }

    $rval = true;
    $e = null;

    $code = substr($byemsg, 0, 3);
    if($code != 221) {
      $e = array('error' => 'SMTP server rejected quit command',
                 'smtp_code' => $code,
                 'smtp_rply' => substr($byemsg, 4));
      $rval = false;
      if($this->do_debug >= 1) {
        $this->edebug('SMTP -> ERROR: ' . $e['error'] . ': ' . $byemsg);
      }
    }

    if(empty($e) || $close_on_error) {
      $this->Close();
    }

    return $rval;
  }

  public function Recipient($to) {
    $this->error = null; // so no confusion is caused

    if(!$this->connected()) {
      $this->error = array(
              'error' => 'Called Recipient() without being connected');
      return false;
    }

    $this->client_send('RCPT TO:<' . $to . '>' . $this->CRLF);

    $rply = $this->get_lines();
    $code = substr($rply, 0, 3);

    if($this->do_debug >= 2) {
      $this->edebug('SMTP -> FROM SERVER:' . $rply);
    }

    if($code != 250 && $code != 251) {
      $this->error =
        array('error' => 'RCPT not accepted from server',
              'smtp_code' => $code,
              'smtp_msg' => substr($rply, 4));
      if($this->do_debug >= 1) {
        $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply);
      }
      return false;
    }
    return true;
  }

  public function Reset() {
    $this->error = null; // so no confusion is caused

    if(!$this->connected()) {
      $this->error = array('error' => 'Called Reset() without being connected');
      return false;
    }

    $this->client_send('RSET' . $this->CRLF);

    $rply = $this->get_lines();
    $code = substr($rply, 0, 3);

    if($this->do_debug >= 2) {
      $this->edebug('SMTP -> FROM SERVER:' . $rply);
    }

    if($code != 250) {
      $this->error =
        array('error' => 'RSET failed',
              'smtp_code' => $code,
              'smtp_msg' => substr($rply, 4));
      if($this->do_debug >= 1) {
        $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply);
      }
      return false;
    }

    return true;
  }

  public function SendAndMail($from) {
    $this->error = null; // so no confusion is caused

    if(!$this->connected()) {
      $this->error = array(
          'error' => 'Called SendAndMail() without being connected');
      return false;
    }

    $this->client_send('SAML FROM:' . $from . $this->CRLF);

    $rply = $this->get_lines();
    $code = substr($rply, 0, 3);

    if($this->do_debug >= 2) {
      $this->edebug('SMTP -> FROM SERVER:' . $rply);
    }

    if($code != 250) {
      $this->error =
        array('error' => 'SAML not accepted from server',
              'smtp_code' => $code,
              'smtp_msg' => substr($rply, 4));
      if($this->do_debug >= 1) {
        $this->edebug('SMTP -> ERROR: ' . $this->error['error'] . ': ' . $rply);
      }
      return false;
    }
    return true;
  }

  public function Turn() {
    $this->error = array('error' => 'This method, TURN, of the SMTP '.
                                    'is not implemented');
    if($this->do_debug >= 1) {
      $this->edebug('SMTP -> NOTICE: ' . $this->error['error']);
    }
    return false;
  }

  public function client_send($data) {
      if ($this->do_debug >= 1) {
          $this->edebug("CLIENT -> SMTP: $data");
      }
      return fwrite($this->smtp_conn, $data);
  }

  public function getError() {
    return $this->error;
  }

  protected function get_lines() {
    $data = '';
    $endtime = 0;
    if (!is_resource($this->smtp_conn)) {
      return $data;
    }
    stream_set_timeout($this->smtp_conn, $this->Timeout);
    if ($this->Timelimit > 0) {
      $endtime = time() + $this->Timelimit;
    }
    while(is_resource($this->smtp_conn) && !feof($this->smtp_conn)) {
      $str = @fgets($this->smtp_conn, 515);
      if($this->do_debug >= 4) {
        $this->edebug("SMTP -> get_lines(): \$data was \"$data\"");
        $this->edebug("SMTP -> get_lines(): \$str is \"$str\"");
      }
      $data .= $str;
      if($this->do_debug >= 4) {
        $this->edebug("SMTP -> get_lines(): \$data is \"$data\"");
      }
      if(substr($str, 3, 1) == ' ') { break; }
      $info = stream_get_meta_data($this->smtp_conn);
      if ($info['timed_out']) {
        if($this->do_debug >= 4) {
          $this->edebug('SMTP -> get_lines(): timed-out (' . $this->Timeout . ' seconds)');
        }
        break;
      }
      // Now check if reads took too long
      if ($endtime) {
        if (time() > $endtime) {
          if($this->do_debug >= 4) {
            $this->edebug('SMTP -> get_lines(): timelimit reached (' . $this->Timelimit . ' seconds)');
          }
          break;
        }
      }
    }
    return $data;
  }

}


class PHPMailer {
  public $Priority          = 3;
  public $CharSet           = 'iso-8859-1';
  public $ContentType       = 'text/plain';
  public $Encoding          = '8bit';
  public $ErrorInfo         = '';
  public $From              = 'root@localhost';
  public $FromName          = 'Root User';
  public $Sender            = '';
  public $ReturnPath        = '';
  public $Subject           = '';
  public $Body              = '';
  public $AltBody           = '';
  public $Ical              = '';
  protected $MIMEBody       = '';
  protected $MIMEHeader     = '';
  protected $mailHeader     = '';
  public $WordWrap          = 0;
  public $Mailer            = 'mail';
  public $Sendmail          = '/usr/sbin/sendmail';
  public $UseSendmailOptions	= true;
  public $PluginDir         = '';
  public $ConfirmReadingTo  = '';
  public $Hostname          = '';
  public $MessageID         = '';
  public $MessageDate       = '';

  public $Host          = 'localhost';
  public $Port          = 25;
  public $Helo          = '';
  public $SMTPSecure    = '';
  public $SMTPAuth      = false;
  public $Username      = '';
  public $Password      = '';
  public $AuthType      = '';
  public $Realm         = '';
  public $Workstation   = '';
  public $Timeout       = 10;
  public $SMTPDebug     = false;
  public $Debugoutput     = "echo";
  public $SMTPKeepAlive = false;
  public $SingleTo      = false;
  public $do_verp      = false;
  public $SingleToArray = array();
  public $AllowEmpty = false;
  public $LE              = "\n";
  public $DKIM_selector   = '';
  public $DKIM_identity   = '';
  public $DKIM_passphrase   = '';
  public $DKIM_domain     = '';
  public $DKIM_private    = '';
  public $action_function = ''; //'callbackAction';
  public $Version         = '5.2.6';
  public $XMailer         = '';

  protected   $smtp           = null;
  protected   $to             = array();
  protected   $cc             = array();
  protected   $bcc            = array();
  protected   $ReplyTo        = array();
  protected   $all_recipients = array();
  protected   $attachment     = array();
  protected   $CustomHeader   = array();
  protected   $message_type   = '';
  protected   $boundary       = array();
  protected   $language       = array();
  protected   $error_count    = 0;
  protected   $sign_cert_file = '';
  protected   $sign_key_file  = '';
  protected   $sign_key_pass  = '';
  protected   $exceptions     = false;

  const STOP_MESSAGE  = 0; // message only, continue processing
  const STOP_CONTINUE = 1; // message?, likely ok to continue processing
  const STOP_CRITICAL = 2; // message, plus full stop, critical error reached
  const CRLF = "\r\n";     // SMTP RFC specified EOL

  private function mail_passthru($to, $subject, $body, $header, $params) {
    if ( ini_get('safe_mode') || !($this->UseSendmailOptions) ) {
        $rt = @mail($to, $this->EncodeHeader($this->SecureHeader($subject)), $body, $header);
    } else {
        $rt = @mail($to, $this->EncodeHeader($this->SecureHeader($subject)), $body, $header, $params);
    }
    return $rt;
  }

  protected function edebug($str) {
    switch ($this->Debugoutput) {
      case 'error_log':
        error_log($str);
        break;
      case 'html':
        //Cleans up output a bit for a better looking display that's HTML-safe
        echo htmlentities(preg_replace('/[\r\n]+/', '', $str), ENT_QUOTES, $this->CharSet)."<br>\n";
        break;
      case 'echo':
      default:
        //Just echoes exactly what was received
        echo $str;
    }
  }

  public function __construct($exceptions = false) {
    $this->exceptions = ($exceptions == true);
  }

  public function __destruct() {
      if ($this->Mailer == 'smtp') { //Close any open SMTP connection nicely
          $this->SmtpClose();
      }
  }

  public function IsHTML($ishtml = true) {
    if ($ishtml) {
      $this->ContentType = 'text/html';
    } else {
      $this->ContentType = 'text/plain';
    }
  }

  public function IsSMTP() {
    $this->Mailer = 'smtp';
  }

  public function IsMail() {
    $this->Mailer = 'mail';
  }

  public function IsSendmail() {
    if (!stristr(ini_get('sendmail_path'), 'sendmail')) {
      $this->Sendmail = '/var/qmail/bin/sendmail';
    }
    $this->Mailer = 'sendmail';
  }

  public function IsQmail() {
    if (stristr(ini_get('sendmail_path'), 'qmail')) {
      $this->Sendmail = '/var/qmail/bin/sendmail';
    }
    $this->Mailer = 'sendmail';
  }

  public function AddAddress($address, $name = '') {
    return $this->AddAnAddress('to', $address, $name);
  }

  public function AddCC($address, $name = '') {
    return $this->AddAnAddress('cc', $address, $name);
  }

  public function AddBCC($address, $name = '') {
    return $this->AddAnAddress('bcc', $address, $name);
  }

  public function AddReplyTo($address, $name = '') {
    return $this->AddAnAddress('Reply-To', $address, $name);
  }

  protected function AddAnAddress($kind, $address, $name = '') {
    if (!preg_match('/^(to|cc|bcc|Reply-To)$/', $kind)) {
      $this->SetError($this->Lang('Invalid recipient array').': '.$kind);
      if ($this->exceptions) {
        throw new phpmailerException('Invalid recipient array: ' . $kind);
      }
      if ($this->SMTPDebug) {
        $this->edebug($this->Lang('Invalid recipient array').': '.$kind);
      }
      return false;
    }
    $address = trim($address);
    $name = trim(preg_replace('/[\r\n]+/', '', $name)); //Strip breaks and trim
    if (!$this->ValidateAddress($address)) {
      $this->SetError($this->Lang('invalid_address').': '. $address);
      if ($this->exceptions) {
        throw new phpmailerException($this->Lang('invalid_address').': '.$address);
      }
      if ($this->SMTPDebug) {
        $this->edebug($this->Lang('invalid_address').': '.$address);
      }
      return false;
    }
    if ($kind != 'Reply-To') {
      if (!isset($this->all_recipients[strtolower($address)])) {
        array_push($this->$kind, array($address, $name));
        $this->all_recipients[strtolower($address)] = true;
        return true;
      }
    } else {
      if (!array_key_exists(strtolower($address), $this->ReplyTo)) {
        $this->ReplyTo[strtolower($address)] = array($address, $name);
      return true;
    }
  }
  return false;
}

  public function SetFrom($address, $name = '', $auto = true) {
    $address = trim($address);
    $name = trim(preg_replace('/[\r\n]+/', '', $name)); //Strip breaks and trim
    if (!$this->ValidateAddress($address)) {
      $this->SetError($this->Lang('invalid_address').': '. $address);
      if ($this->exceptions) {
        throw new phpmailerException($this->Lang('invalid_address').': '.$address);
      }
      if ($this->SMTPDebug) {
        $this->edebug($this->Lang('invalid_address').': '.$address);
      }
      return false;
    }
    $this->From = $address;
    $this->FromName = $name;
    if ($auto) {
      if (empty($this->Sender)) {
        $this->Sender = $address;
      }
    }
    return true;
  }

  public static function ValidateAddress($address) {
      if (defined('PCRE_VERSION')) { //Check this instead of extension_loaded so it works when that function is disabled
          if (version_compare(PCRE_VERSION, '8.0') >= 0) {
              return (boolean)preg_match('/^(?!(?>(?1)"?(?>\\\[ -~]|[^"])"?(?1)){255,})(?!(?>(?1)"?(?>\\\[ -~]|[^"])"?(?1)){65,}@)((?>(?>(?>((?>(?>(?>\x0D\x0A)?[\t ])+|(?>[\t ]*\x0D\x0A)?[\t ]+)?)(\((?>(?2)(?>[\x01-\x08\x0B\x0C\x0E-\'*-\[\]-\x7F]|\\\[\x00-\x7F]|(?3)))*(?2)\)))+(?2))|(?2))?)([!#-\'*+\/-9=?^-~-]+|"(?>(?2)(?>[\x01-\x08\x0B\x0C\x0E-!#-\[\]-\x7F]|\\\[\x00-\x7F]))*(?2)")(?>(?1)\.(?1)(?4))*(?1)@(?!(?1)[a-z0-9-]{64,})(?1)(?>([a-z0-9](?>[a-z0-9-]*[a-z0-9])?)(?>(?1)\.(?!(?1)[a-z0-9-]{64,})(?1)(?5)){0,126}|\[(?:(?>IPv6:(?>([a-f0-9]{1,4})(?>:(?6)){7}|(?!(?:.*[a-f0-9][:\]]){8,})((?6)(?>:(?6)){0,6})?::(?7)?))|(?>(?>IPv6:(?>(?6)(?>:(?6)){5}:|(?!(?:.*[a-f0-9]:){6,})(?8)?::(?>((?6)(?>:(?6)){0,4}):)?))?(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])(?>\.(?9)){3}))\])(?1)$/isD', $address);
          } else {
              //Fall back to an older regex that doesn't need a recent PCRE
              return (boolean)preg_match('/^(?!(?>"?(?>\\\[ -~]|[^"])"?){255,})(?!(?>"?(?>\\\[ -~]|[^"])"?){65,}@)(?>[!#-\'*+\/-9=?^-~-]+|"(?>(?>[\x01-\x08\x0B\x0C\x0E-!#-\[\]-\x7F]|\\\[\x00-\xFF]))*")(?>\.(?>[!#-\'*+\/-9=?^-~-]+|"(?>(?>[\x01-\x08\x0B\x0C\x0E-!#-\[\]-\x7F]|\\\[\x00-\xFF]))*"))*@(?>(?![a-z0-9-]{64,})(?>[a-z0-9](?>[a-z0-9-]*[a-z0-9])?)(?>\.(?![a-z0-9-]{64,})(?>[a-z0-9](?>[a-z0-9-]*[a-z0-9])?)){0,126}|\[(?:(?>IPv6:(?>(?>[a-f0-9]{1,4})(?>:[a-f0-9]{1,4}){7}|(?!(?:.*[a-f0-9][:\]]){8,})(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,6})?::(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,6})?))|(?>(?>IPv6:(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){5}:|(?!(?:.*[a-f0-9]:){6,})(?>[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,4})?::(?>(?:[a-f0-9]{1,4}(?>:[a-f0-9]{1,4}){0,4}):)?))?(?>25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])(?>\.(?>25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])){3}))\])$/isD', $address);
          }
      } else {
          return (strlen($address) >= 3 and strpos($address, '@') >= 1 and strpos($address, '@') != strlen($address) - 1);
      }
  }

  public function Send() {
    try {
      if(!$this->PreSend()) return false;
      return $this->PostSend();
    } catch (phpmailerException $e) {
      $this->mailHeader = '';
      $this->SetError($e->getMessage());
      if ($this->exceptions) {
        throw $e;
      }
      return false;
    }
  }

  public function PreSend() {
    try {
      $this->mailHeader = "";
      if ((count($this->to) + count($this->cc) + count($this->bcc)) < 1) {
        throw new phpmailerException($this->Lang('provide_address'), self::STOP_CRITICAL);
      }

      // Set whether the message is multipart/alternative
      if(!empty($this->AltBody)) {
        $this->ContentType = 'multipart/alternative';
      }

      $this->error_count = 0; // reset errors
      $this->SetMessageType();
      //Refuse to send an empty message unless we are specifically allowing it
      if (!$this->AllowEmpty and empty($this->Body)) {
        throw new phpmailerException($this->Lang('empty_message'), self::STOP_CRITICAL);
      }

      $this->MIMEHeader = $this->CreateHeader();
      $this->MIMEBody = $this->CreateBody();

      // To capture the complete message when using mail(), create
      // an extra header list which CreateHeader() doesn't fold in
      if ($this->Mailer == 'mail') {
        if (count($this->to) > 0) {
          $this->mailHeader .= $this->AddrAppend("To", $this->to);
        } else {
          $this->mailHeader .= $this->HeaderLine("To", "undisclosed-recipients:;");
        }
        $this->mailHeader .= $this->HeaderLine('Subject', $this->EncodeHeader($this->SecureHeader(trim($this->Subject))));
      }

      // digitally sign with DKIM if enabled
      if (!empty($this->DKIM_domain) && !empty($this->DKIM_private) && !empty($this->DKIM_selector) && !empty($this->DKIM_domain) && file_exists($this->DKIM_private)) {
        $header_dkim = $this->DKIM_Add($this->MIMEHeader . $this->mailHeader, $this->EncodeHeader($this->SecureHeader($this->Subject)), $this->MIMEBody);
        $this->MIMEHeader = str_replace("\r\n", "\n", $header_dkim) . $this->MIMEHeader;
      }

      return true;

    } catch (phpmailerException $e) {
      $this->SetError($e->getMessage());
      if ($this->exceptions) {
        throw $e;
      }
      return false;
    }
  }

  public function PostSend() {
    try {
      // Choose the mailer and send through it
      switch($this->Mailer) {
        case 'sendmail':
          return $this->SendmailSend($this->MIMEHeader, $this->MIMEBody);
        case 'smtp':
          return $this->SmtpSend($this->MIMEHeader, $this->MIMEBody);
        case 'mail':
          return $this->MailSend($this->MIMEHeader, $this->MIMEBody);
        default:
          return $this->MailSend($this->MIMEHeader, $this->MIMEBody);
      }
    } catch (phpmailerException $e) {
      $this->SetError($e->getMessage());
      if ($this->exceptions) {
        throw $e;
      }
      if ($this->SMTPDebug) {
        $this->edebug($e->getMessage()."\n");
      }
    }
    return false;
  }

  protected function SendmailSend($header, $body) {
    if ($this->Sender != '') {
      $sendmail = sprintf("%s -oi -f%s -t", escapeshellcmd($this->Sendmail), escapeshellarg($this->Sender));
    } else {
      $sendmail = sprintf("%s -oi -t", escapeshellcmd($this->Sendmail));
    }
    if ($this->SingleTo === true) {
      foreach ($this->SingleToArray as $val) {
        if(!@$mail = popen($sendmail, 'w')) {
          throw new phpmailerException($this->Lang('execute') . $this->Sendmail, self::STOP_CRITICAL);
        }
        fputs($mail, "To: " . $val . "\n");
        fputs($mail, $header);
        fputs($mail, $body);
        $result = pclose($mail);
        // implement call back function if it exists
        $isSent = ($result == 0) ? 1 : 0;
        $this->doCallback($isSent, $val, $this->cc, $this->bcc, $this->Subject, $body);
        if($result != 0) {
          throw new phpmailerException($this->Lang('execute') . $this->Sendmail, self::STOP_CRITICAL);
        }
      }
    } else {
      if(!@$mail = popen($sendmail, 'w')) {
        throw new phpmailerException($this->Lang('execute') . $this->Sendmail, self::STOP_CRITICAL);
      }
      fputs($mail, $header);
      fputs($mail, $body);
      $result = pclose($mail);
      // implement call back function if it exists
      $isSent = ($result == 0) ? 1 : 0;
      $this->doCallback($isSent, $this->to, $this->cc, $this->bcc, $this->Subject, $body);
      if($result != 0) {
        throw new phpmailerException($this->Lang('execute') . $this->Sendmail, self::STOP_CRITICAL);
      }
    }
    return true;
  }

  protected function MailSend($header, $body) {
    $toArr = array();
    foreach($this->to as $t) {
      $toArr[] = $this->AddrFormat($t);
    }
    $to = implode(', ', $toArr);

    if (empty($this->Sender)) {
      $params = " ";
    } else {
      $params = sprintf("-f%s", $this->Sender);
    }
    if ($this->Sender != '' and !ini_get('safe_mode')) {
      $old_from = ini_get('sendmail_from');
      ini_set('sendmail_from', $this->Sender);
    }
      $rt = false;
    if ($this->SingleTo === true && count($toArr) > 1) {
      foreach ($toArr as $val) {
        $rt = $this->mail_passthru($val, $this->Subject, $body, $header, $params);
        // implement call back function if it exists
        $isSent = ($rt == 1) ? 1 : 0;
        $this->doCallback($isSent, $val, $this->cc, $this->bcc, $this->Subject, $body);
      }
    } else {
      $rt = $this->mail_passthru($to, $this->Subject, $body, $header, $params);
      // implement call back function if it exists
      $isSent = ($rt == 1) ? 1 : 0;
      $this->doCallback($isSent, $to, $this->cc, $this->bcc, $this->Subject, $body);
    }
    if (isset($old_from)) {
      ini_set('sendmail_from', $old_from);
    }
    if(!$rt) {
      throw new phpmailerException($this->Lang('instantiate'), self::STOP_CRITICAL);
    }
    return true;
  }

  protected function SmtpSend($header, $body) {
    //require_once $this->PluginDir . 'class.smtp.php';
    $bad_rcpt = array();

    if(!$this->SmtpConnect()) {
      throw new phpmailerException($this->Lang('smtp_connect_failed'), self::STOP_CRITICAL);
    }
    $smtp_from = ($this->Sender == '') ? $this->From : $this->Sender;
    if(!$this->smtp->Mail($smtp_from)) {
      $this->SetError($this->Lang('from_failed') . $smtp_from . ' : ' .implode(',', $this->smtp->getError()));
      throw new phpmailerException($this->ErrorInfo, self::STOP_CRITICAL);
    }

    // Attempt to send attach all recipients
    foreach($this->to as $to) {
      if (!$this->smtp->Recipient($to[0])) {
        $bad_rcpt[] = $to[0];
        // implement call back function if it exists
        $isSent = 0;
        $this->doCallback($isSent, $to[0], '', '', $this->Subject, $body);
      } else {
        // implement call back function if it exists
        $isSent = 1;
        $this->doCallback($isSent, $to[0], '', '', $this->Subject, $body);
      }
    }
    foreach($this->cc as $cc) {
      if (!$this->smtp->Recipient($cc[0])) {
        $bad_rcpt[] = $cc[0];
        // implement call back function if it exists
        $isSent = 0;
        $this->doCallback($isSent, '', $cc[0], '', $this->Subject, $body);
      } else {
        // implement call back function if it exists
        $isSent = 1;
        $this->doCallback($isSent, '', $cc[0], '', $this->Subject, $body);
      }
    }
    foreach($this->bcc as $bcc) {
      if (!$this->smtp->Recipient($bcc[0])) {
        $bad_rcpt[] = $bcc[0];
        // implement call back function if it exists
        $isSent = 0;
        $this->doCallback($isSent, '', '', $bcc[0], $this->Subject, $body);
      } else {
        // implement call back function if it exists
        $isSent = 1;
        $this->doCallback($isSent, '', '', $bcc[0], $this->Subject, $body);
      }
    }


    if (count($bad_rcpt) > 0 ) { //Create error message for any bad addresses
      $badaddresses = implode(', ', $bad_rcpt);
      throw new phpmailerException($this->Lang('recipients_failed') . $badaddresses);
    }
    if(!$this->smtp->Data($header . $body)) {
      throw new phpmailerException($this->Lang('data_not_accepted'), self::STOP_CRITICAL);
    }
    if($this->SMTPKeepAlive == true) {
      $this->smtp->Reset();
    } else {
        $this->smtp->Quit();
        $this->smtp->Close();
    }
    return true;
  }

  public function TestSmtpConnect($options = array())
  {
	if(is_null($this->smtp)) {
      $this->smtp = new SMTP;
    }

    //Already connected?
    if ($this->smtp->Connected()) {
      return true;
    }

	$this->smtp->Timeout = $this->Timeout;
    $this->smtp->do_debug = $this->SMTPDebug;
    $this->smtp->Debugoutput = $this->Debugoutput;
    $this->smtp->do_verp = $this->do_verp;
    $index = 0;
    $tls = ($this->SMTPSecure == 'tls');
    $ssl = ($this->SMTPSecure == 'ssl');
    $hosts = explode(';', $this->Host);
    $lastexception = null;

    foreach ($hosts as $hostentry) {
      $hostinfo = array();
      $host = $hostentry;
      $port = $this->Port;
      if (preg_match('/^(.+):([0-9]+)$/', $hostentry, $hostinfo)) { //If $hostentry contains 'address:port', override default
        $host = $hostinfo[1];
        $port = $hostinfo[2];
      }
      if ($this->smtp->Connect(($ssl ? 'ssl://':'').$host, $port, $this->Timeout, $options)) {
        try {
          if ($this->Helo) {
            $hello = $this->Helo;
          } else {
            $hello = $this->ServerHostname();
          }
          $this->smtp->Hello($hello);

          if ($tls) {
            if (!$this->smtp->StartTLS()) {
              throw new phpmailerException($this->Lang('connect_host'));
            }
            //We must resend HELO after tls negotiation
            $this->smtp->Hello($hello);
          }
          /*if ($this->SMTPAuth) {
            if (!$this->smtp->Authenticate($this->Username, $this->Password, $this->AuthType, $this->Realm, $this->Workstation)) {
              throw new phpmailerException($this->Lang('authenticate'));
            }
          }*/
		  $this->smtp->Close();
          return true;
        } catch (phpmailerException $e) {
          $lastexception = $e;
          //We must have connected, but then failed TLS or Auth, so close connection nicely
          $this->smtp->Quit();
        }
      }
    }
    //If we get here, all connection attempts have failed, so close connection hard
    $this->smtp->Close();
    //As we've caught all exceptions, just report whatever the last one was
    if ($this->exceptions and !is_null($lastexception)) {
      throw $lastexception;
    }
    return false;
  }

  public function SmtpConnect($options = array()) {

    if(is_null($this->smtp)) {
      $this->smtp = new SMTP;
    }

    //Already connected?
    if ($this->smtp->Connected()) {
      return true;
    }

    $this->smtp->Timeout = $this->Timeout;
    $this->smtp->do_debug = $this->SMTPDebug;
    $this->smtp->Debugoutput = $this->Debugoutput;
    $this->smtp->do_verp = $this->do_verp;
    $index = 0;
    $tls = ($this->SMTPSecure == 'tls');
    $ssl = ($this->SMTPSecure == 'ssl');
    $hosts = explode(';', $this->Host);
    $lastexception = null;

    foreach ($hosts as $hostentry) {
      $hostinfo = array();
      $host = $hostentry;
      $port = $this->Port;
      if (preg_match('/^(.+):([0-9]+)$/', $hostentry, $hostinfo)) { //If $hostentry contains 'address:port', override default
        $host = $hostinfo[1];
        $port = $hostinfo[2];
      }
      if ($this->smtp->Connect(($ssl ? 'ssl://':'').$host, $port, $this->Timeout, $options)) {
        try {
          if ($this->Helo) {
            $hello = $this->Helo;
          } else {
            $hello = $this->ServerHostname();
          }
          $this->smtp->Hello($hello);

          if ($tls) {
            if (!$this->smtp->StartTLS()) {
              throw new phpmailerException($this->Lang('connect_host'));
            }
            //We must resend HELO after tls negotiation
            $this->smtp->Hello($hello);
          }
          if ($this->SMTPAuth) {
            if (!$this->smtp->Authenticate($this->Username, $this->Password, $this->AuthType, $this->Realm, $this->Workstation)) {
              throw new phpmailerException($this->Lang('authenticate'));
            }
          }
          return true;
        } catch (phpmailerException $e) {
          $lastexception = $e;
          //We must have connected, but then failed TLS or Auth, so close connection nicely
          $this->smtp->Quit();
        }
      }
    }
    //If we get here, all connection attempts have failed, so close connection hard
    $this->smtp->Close();
    //As we've caught all exceptions, just report whatever the last one was
    if ($this->exceptions and !is_null($lastexception)) {
      throw $lastexception;
    }
    return false;
  }

  public function SmtpClose() {
    if ($this->smtp !== null) {
      if($this->smtp->Connected()) {
        $this->smtp->Quit();
        $this->smtp->Close();
      }
    }
  }

  function SetLanguage($langcode = 'en', $lang_path = 'language/') {
    $PHPMAILER_LANG = array(
      'authenticate'         => 'SMTP Error: Could not authenticate.',
      'connect_host'         => 'SMTP Error: Could not connect to SMTP host.',
      'data_not_accepted'    => 'SMTP Error: Data not accepted.',
      'empty_message'        => 'Message body empty',
      'encoding'             => 'Unknown encoding: ',
      'execute'              => 'Could not execute: ',
      'file_access'          => 'Could not access file: ',
      'file_open'            => 'File Error: Could not open file: ',
      'from_failed'          => 'The following From address failed: ',
      'instantiate'          => 'Could not instantiate mail function.',
      'invalid_address'      => 'Invalid address',
      'mailer_not_supported' => ' mailer is not supported.',
      'provide_address'      => 'You must provide at least one recipient email address.',
      'recipients_failed'    => 'SMTP Error: The following recipients failed: ',
      'signing'              => 'Signing Error: ',
      'smtp_connect_failed'  => 'SMTP Connect() failed.',
      'smtp_error'           => 'SMTP server error: ',
      'variable_set'         => 'Cannot set or reset variable: '
    );
    $l = true;
    if ($langcode != 'en') { //There is no English translation file
      $l = @include $lang_path.'phpmailer.lang-'.$langcode.'.php';
    }
    $this->language = $PHPMAILER_LANG;
    return ($l == true); //Returns false if language not found
  }

  public function GetTranslations() {
    return $this->language;
  }

  public function AddrAppend($type, $addr) {
    $addr_str = $type . ': ';
    $addresses = array();
    foreach ($addr as $a) {
      $addresses[] = $this->AddrFormat($a);
    }
    $addr_str .= implode(', ', $addresses);
    $addr_str .= $this->LE;

    return $addr_str;
  }

  public function AddrFormat($addr) {
    if (empty($addr[1])) {
      return $this->SecureHeader($addr[0]);
    } else {
      return $this->EncodeHeader($this->SecureHeader($addr[1]), 'phrase') . " <" . $this->SecureHeader($addr[0]) . ">";
    }
  }

  public function WrapText($message, $length, $qp_mode = false) {
    $soft_break = ($qp_mode) ? sprintf(" =%s", $this->LE) : $this->LE;
    $is_utf8 = (strtolower($this->CharSet) == "utf-8");
    $lelen = strlen($this->LE);
    $crlflen = strlen(self::CRLF);

    $message = $this->FixEOL($message);
    if (substr($message, -$lelen) == $this->LE) {
      $message = substr($message, 0, -$lelen);
    }

    $line = explode($this->LE, $message);
    $message = '';
    for ($i = 0 ;$i < count($line); $i++) {
      $line_part = explode(' ', $line[$i]);
      $buf = '';
      for ($e = 0; $e<count($line_part); $e++) {
        $word = $line_part[$e];
        if ($qp_mode and (strlen($word) > $length)) {
          $space_left = $length - strlen($buf) - $crlflen;
          if ($e != 0) {
            if ($space_left > 20) {
              $len = $space_left;
              if ($is_utf8) {
                $len = $this->UTF8CharBoundary($word, $len);
              } elseif (substr($word, $len - 1, 1) == "=") {
                $len--;
              } elseif (substr($word, $len - 2, 1) == "=") {
                $len -= 2;
              }
              $part = substr($word, 0, $len);
              $word = substr($word, $len);
              $buf .= ' ' . $part;
              $message .= $buf . sprintf("=%s", self::CRLF);
            } else {
              $message .= $buf . $soft_break;
            }
            $buf = '';
          }
          while (strlen($word) > 0) {
            if ($length <= 0) {
                break;
            }
            $len = $length;
            if ($is_utf8) {
              $len = $this->UTF8CharBoundary($word, $len);
            } elseif (substr($word, $len - 1, 1) == "=") {
              $len--;
            } elseif (substr($word, $len - 2, 1) == "=") {
              $len -= 2;
            }
            $part = substr($word, 0, $len);
            $word = substr($word, $len);

            if (strlen($word) > 0) {
              $message .= $part . sprintf("=%s", self::CRLF);
            } else {
              $buf = $part;
            }
          }
        } else {
          $buf_o = $buf;
          $buf .= ($e == 0) ? $word : (' ' . $word);

          if (strlen($buf) > $length and $buf_o != '') {
            $message .= $buf_o . $soft_break;
            $buf = $word;
          }
        }
      }
      $message .= $buf . self::CRLF;
    }

    return $message;
  }

  public function UTF8CharBoundary($encodedText, $maxLength) {
    $foundSplitPos = false;
    $lookBack = 3;
    while (!$foundSplitPos) {
      $lastChunk = substr($encodedText, $maxLength - $lookBack, $lookBack);
      $encodedCharPos = strpos($lastChunk, "=");
      if ($encodedCharPos !== false) {
        // Found start of encoded character byte within $lookBack block.
        // Check the encoded byte value (the 2 chars after the '=')
        $hex = substr($encodedText, $maxLength - $lookBack + $encodedCharPos + 1, 2);
        $dec = hexdec($hex);
        if ($dec < 128) { // Single byte character.
          // If the encoded char was found at pos 0, it will fit
          // otherwise reduce maxLength to start of the encoded char
          $maxLength = ($encodedCharPos == 0) ? $maxLength :
          $maxLength - ($lookBack - $encodedCharPos);
          $foundSplitPos = true;
        } elseif ($dec >= 192) { // First byte of a multi byte character
          // Reduce maxLength to split at start of character
          $maxLength = $maxLength - ($lookBack - $encodedCharPos);
          $foundSplitPos = true;
        } elseif ($dec < 192) { // Middle byte of a multi byte character, look further back
          $lookBack += 3;
        }
      } else {
        $foundSplitPos = true;
      }
    }
    return $maxLength;
  }

  public function SetWordWrap() {
    if($this->WordWrap < 1) {
      return;
    }

    switch($this->message_type) {
      case 'alt':
      case 'alt_inline':
      case 'alt_attach':
      case 'alt_inline_attach':
        $this->AltBody = $this->WrapText($this->AltBody, $this->WordWrap);
        break;
      default:
        $this->Body = $this->WrapText($this->Body, $this->WordWrap);
        break;
    }
  }

  public function CreateHeader() {
    $result = '';

    $uniq_id = md5(uniqid(time()));
    $this->boundary[1] = 'b1_' . $uniq_id;
    $this->boundary[2] = 'b2_' . $uniq_id;
    $this->boundary[3] = 'b3_' . $uniq_id;

    if ($this->MessageDate == '') {
      $result .= $this->HeaderLine('Date', self::RFCDate());
    } else {
      $result .= $this->HeaderLine('Date', $this->MessageDate);
    }

    if ($this->ReturnPath) {
      $result .= $this->HeaderLine('Return-Path', '<'.trim($this->ReturnPath).'>');
    } elseif ($this->Sender == '') {
      $result .= $this->HeaderLine('Return-Path', '<'.trim($this->From).'>');
    } else {
      $result .= $this->HeaderLine('Return-Path', '<'.trim($this->Sender).'>');
    }

    if($this->Mailer != 'mail') {
      if ($this->SingleTo === true) {
        foreach($this->to as $t) {
          $this->SingleToArray[] = $this->AddrFormat($t);
        }
      } else {
        if(count($this->to) > 0) {
          $result .= $this->AddrAppend('To', $this->to);
        } elseif (count($this->cc) == 0) {
          $result .= $this->HeaderLine('To', 'undisclosed-recipients:;');
        }
      }
    }

    $from = array();
    $from[0][0] = trim($this->From);
    $from[0][1] = $this->FromName;
    $result .= $this->AddrAppend('From', $from);

    if(count($this->cc) > 0) {
      $result .= $this->AddrAppend('Cc', $this->cc);
    }

    if((($this->Mailer == 'sendmail') || ($this->Mailer == 'mail')) && (count($this->bcc) > 0)) {
      $result .= $this->AddrAppend('Bcc', $this->bcc);
    }

    if(count($this->ReplyTo) > 0) {
      $result .= $this->AddrAppend('Reply-To', $this->ReplyTo);
    }

    // mail() sets the subject itself
    if($this->Mailer != 'mail') {
      $result .= $this->HeaderLine('Subject', $this->EncodeHeader($this->SecureHeader($this->Subject)));
    }

    if($this->MessageID != '') {
      $result .= $this->HeaderLine('Message-ID', $this->MessageID);
    } else {
      $result .= sprintf("Message-ID: <%s@%s>%s", $uniq_id, $this->ServerHostname(), $this->LE);
    }
    $result .= $this->HeaderLine('X-Priority', $this->Priority);
    if ($this->XMailer == '') {
        $result .= $this->HeaderLine('X-Mailer', 'PHPMailer '.$this->Version.' (https://github.com/PHPMailer/PHPMailer/)');
    } else {
      $myXmailer = trim($this->XMailer);
      if ($myXmailer) {
        $result .= $this->HeaderLine('X-Mailer', $myXmailer);
      }
    }

    if($this->ConfirmReadingTo != '') {
      $result .= $this->HeaderLine('Disposition-Notification-To', '<' . trim($this->ConfirmReadingTo) . '>');
    }

    // Add custom headers
    for($index = 0; $index < count($this->CustomHeader); $index++) {
      $result .= $this->HeaderLine(trim($this->CustomHeader[$index][0]), $this->EncodeHeader(trim($this->CustomHeader[$index][1])));
    }
    if (!$this->sign_key_file) {
      $result .= $this->HeaderLine('MIME-Version', '1.0');
      $result .= $this->GetMailMIME();
    }

    return $result;
  }

  public function GetMailMIME() {
    $result = '';
    switch($this->message_type) {
      case 'inline':
        $result .= $this->HeaderLine('Content-Type', 'multipart/related;');
        $result .= $this->TextLine("\tboundary=\"" . $this->boundary[1].'"');
        break;
      case 'attach':
      case 'inline_attach':
      case 'alt_attach':
      case 'alt_inline_attach':
        $result .= $this->HeaderLine('Content-Type', 'multipart/mixed;');
        $result .= $this->TextLine("\tboundary=\"" . $this->boundary[1].'"');
        break;
      case 'alt':
      case 'alt_inline':
        $result .= $this->HeaderLine('Content-Type', 'multipart/alternative;');
        $result .= $this->TextLine("\tboundary=\"" . $this->boundary[1].'"');
        break;
      default:
        // Catches case 'plain': and case '':
        $result .= $this->TextLine('Content-Type: '.$this->ContentType.'; charset='.$this->CharSet);
        break;
    }
    //RFC1341 part 5 says 7bit is assumed if not specified
    if ($this->Encoding != '7bit') {
      $result .= $this->HeaderLine('Content-Transfer-Encoding', $this->Encoding);
    }

    if($this->Mailer != 'mail') {
      $result .= $this->LE;
    }

    return $result;
  }

  public function GetSentMIMEMessage() {
    return $this->MIMEHeader . $this->mailHeader . self::CRLF . $this->MIMEBody;
  }

  public function CreateBody() {
    $body = '';

    if ($this->sign_key_file) {
      $body .= $this->GetMailMIME().$this->LE;
    }

    $this->SetWordWrap();

    switch($this->message_type) {
      case 'inline':
        $body .= $this->GetBoundary($this->boundary[1], '', '', '');
        $body .= $this->EncodeString($this->Body, $this->Encoding);
        $body .= $this->LE.$this->LE;
        $body .= $this->AttachAll('inline', $this->boundary[1]);
        break;
      case 'attach':
        $body .= $this->GetBoundary($this->boundary[1], '', '', '');
        $body .= $this->EncodeString($this->Body, $this->Encoding);
        $body .= $this->LE.$this->LE;
        $body .= $this->AttachAll('attachment', $this->boundary[1]);
        break;
      case 'inline_attach':
        $body .= $this->TextLine('--' . $this->boundary[1]);
        $body .= $this->HeaderLine('Content-Type', 'multipart/related;');
        $body .= $this->TextLine("\tboundary=\"" . $this->boundary[2].'"');
        $body .= $this->LE;
        $body .= $this->GetBoundary($this->boundary[2], '', '', '');
        $body .= $this->EncodeString($this->Body, $this->Encoding);
        $body .= $this->LE.$this->LE;
        $body .= $this->AttachAll('inline', $this->boundary[2]);
        $body .= $this->LE;
        $body .= $this->AttachAll('attachment', $this->boundary[1]);
        break;
      case 'alt':
        $body .= $this->GetBoundary($this->boundary[1], '', 'text/plain', '');
        $body .= $this->EncodeString($this->AltBody, $this->Encoding);
        $body .= $this->LE.$this->LE;
        $body .= $this->GetBoundary($this->boundary[1], '', 'text/html', '');
        $body .= $this->EncodeString($this->Body, $this->Encoding);
        $body .= $this->LE.$this->LE;
        if(!empty($this->Ical)) {
          $body .= $this->GetBoundary($this->boundary[1], '', 'text/calendar; method=REQUEST', '');
          $body .= $this->EncodeString($this->Ical, $this->Encoding);
          $body .= $this->LE.$this->LE;
        }
        $body .= $this->EndBoundary($this->boundary[1]);
        break;
      case 'alt_inline':
        $body .= $this->GetBoundary($this->boundary[1], '', 'text/plain', '');
        $body .= $this->EncodeString($this->AltBody, $this->Encoding);
        $body .= $this->LE.$this->LE;
        $body .= $this->TextLine('--' . $this->boundary[1]);
        $body .= $this->HeaderLine('Content-Type', 'multipart/related;');
        $body .= $this->TextLine("\tboundary=\"" . $this->boundary[2].'"');
        $body .= $this->LE;
        $body .= $this->GetBoundary($this->boundary[2], '', 'text/html', '');
        $body .= $this->EncodeString($this->Body, $this->Encoding);
        $body .= $this->LE.$this->LE;
        $body .= $this->AttachAll('inline', $this->boundary[2]);
        $body .= $this->LE;
        $body .= $this->EndBoundary($this->boundary[1]);
        break;
      case 'alt_attach':
        $body .= $this->TextLine('--' . $this->boundary[1]);
        $body .= $this->HeaderLine('Content-Type', 'multipart/alternative;');
        $body .= $this->TextLine("\tboundary=\"" . $this->boundary[2].'"');
        $body .= $this->LE;
        $body .= $this->GetBoundary($this->boundary[2], '', 'text/plain', '');
        $body .= $this->EncodeString($this->AltBody, $this->Encoding);
        $body .= $this->LE.$this->LE;
        $body .= $this->GetBoundary($this->boundary[2], '', 'text/html', '');
        $body .= $this->EncodeString($this->Body, $this->Encoding);
        $body .= $this->LE.$this->LE;
        $body .= $this->EndBoundary($this->boundary[2]);
        $body .= $this->LE;
        $body .= $this->AttachAll('attachment', $this->boundary[1]);
        break;
      case 'alt_inline_attach':
        $body .= $this->TextLine('--' . $this->boundary[1]);
        $body .= $this->HeaderLine('Content-Type', 'multipart/alternative;');
        $body .= $this->TextLine("\tboundary=\"" . $this->boundary[2].'"');
        $body .= $this->LE;
        $body .= $this->GetBoundary($this->boundary[2], '', 'text/plain', '');
        $body .= $this->EncodeString($this->AltBody, $this->Encoding);
        $body .= $this->LE.$this->LE;
        $body .= $this->TextLine('--' . $this->boundary[2]);
        $body .= $this->HeaderLine('Content-Type', 'multipart/related;');
        $body .= $this->TextLine("\tboundary=\"" . $this->boundary[3].'"');
        $body .= $this->LE;
        $body .= $this->GetBoundary($this->boundary[3], '', 'text/html', '');
        $body .= $this->EncodeString($this->Body, $this->Encoding);
        $body .= $this->LE.$this->LE;
        $body .= $this->AttachAll('inline', $this->boundary[3]);
        $body .= $this->LE;
        $body .= $this->EndBoundary($this->boundary[2]);
        $body .= $this->LE;
        $body .= $this->AttachAll('attachment', $this->boundary[1]);
        break;
      default:
        // catch case 'plain' and case ''
        $body .= $this->EncodeString($this->Body, $this->Encoding);
        break;
    }

    if ($this->IsError()) {
      $body = '';
    } elseif ($this->sign_key_file) {
      try {
        if (!defined('PKCS7_TEXT')) {
            throw new phpmailerException($this->Lang('signing').' OpenSSL extension missing.');
        }
        $file = tempnam(sys_get_temp_dir(), 'mail');
        file_put_contents($file, $body); //TODO check this worked
        $signed = tempnam(sys_get_temp_dir(), 'signed');
        if (@openssl_pkcs7_sign($file, $signed, 'file://'.realpath($this->sign_cert_file), array('file://'.realpath($this->sign_key_file), $this->sign_key_pass), null)) {
          @unlink($file);
          $body = file_get_contents($signed);
          @unlink($signed);
        } else {
          @unlink($file);
          @unlink($signed);
          throw new phpmailerException($this->Lang('signing').openssl_error_string());
        }
      } catch (phpmailerException $e) {
        $body = '';
        if ($this->exceptions) {
          throw $e;
        }
      }
    }
    return $body;
  }

  protected function GetBoundary($boundary, $charSet, $contentType, $encoding) {
    $result = '';
    if($charSet == '') {
      $charSet = $this->CharSet;
    }
    if($contentType == '') {
      $contentType = $this->ContentType;
    }
    if($encoding == '') {
      $encoding = $this->Encoding;
    }
    $result .= $this->TextLine('--' . $boundary);
    $result .= sprintf("Content-Type: %s; charset=%s", $contentType, $charSet);
    $result .= $this->LE;
    $result .= $this->HeaderLine('Content-Transfer-Encoding', $encoding);
    $result .= $this->LE;

    return $result;
  }

  protected function EndBoundary($boundary) {
    return $this->LE . '--' . $boundary . '--' . $this->LE;
  }

  protected function SetMessageType() {
    $this->message_type = array();
    if($this->AlternativeExists()) $this->message_type[] = "alt";
    if($this->InlineImageExists()) $this->message_type[] = "inline";
    if($this->AttachmentExists()) $this->message_type[] = "attach";
    $this->message_type = implode("_", $this->message_type);
    if($this->message_type == "") $this->message_type = "plain";
  }

  public function HeaderLine($name, $value) {
    return $name . ': ' . $value . $this->LE;
  }

  public function TextLine($value) {
    return $value . $this->LE;
  }

  public function AddAttachment($path, $name = '', $encoding = 'base64', $type = '') {
    try {
      if ( !@is_file($path) ) {
        throw new phpmailerException($this->Lang('file_access') . $path, self::STOP_CONTINUE);
      }

      //If a MIME type is not specified, try to work it out from the file name
      if ($type == '') {
        $type = self::filenameToType($path);
      }

      $filename = basename($path);
      if ( $name == '' ) {
        $name = $filename;
      }

      $this->attachment[] = array(
        0 => $path,
        1 => $filename,
        2 => $name,
        3 => $encoding,
        4 => $type,
        5 => false,  // isStringAttachment
        6 => 'attachment',
        7 => 0
      );

    } catch (phpmailerException $e) {
      $this->SetError($e->getMessage());
      if ($this->exceptions) {
        throw $e;
      }
      if ($this->SMTPDebug) {
        $this->edebug($e->getMessage()."\n");
      }
      return false;
    }
    return true;
  }

  public function GetAttachments() {
    return $this->attachment;
  }

  protected function AttachAll($disposition_type, $boundary) {
    $mime = array();
    $cidUniq = array();
    $incl = array();

    foreach ($this->attachment as $attachment) {
      if($attachment[6] == $disposition_type) {
        $string = '';
        $path = '';
        $bString = $attachment[5];
        if ($bString) {
          $string = $attachment[0];
        } else {
          $path = $attachment[0];
        }

        $inclhash = md5(serialize($attachment));
        if (in_array($inclhash, $incl)) { continue; }
        $incl[]      = $inclhash;
        $filename    = $attachment[1];
        $name        = $attachment[2];
        $encoding    = $attachment[3];
        $type        = $attachment[4];
        $disposition = $attachment[6];
        $cid         = $attachment[7];
        if ( $disposition == 'inline' && isset($cidUniq[$cid]) ) { continue; }
        $cidUniq[$cid] = true;

        $mime[] = sprintf("--%s%s", $boundary, $this->LE);
        $mime[] = sprintf("Content-Type: %s; name=\"%s\"%s", $type, $this->EncodeHeader($this->SecureHeader($name)), $this->LE);
        $mime[] = sprintf("Content-Transfer-Encoding: %s%s", $encoding, $this->LE);

        if($disposition == 'inline') {
          $mime[] = sprintf("Content-ID: <%s>%s", $cid, $this->LE);
        }

        if (preg_match('/[ \(\)<>@,;:\\"\/\[\]\?=]/', $name)) {
          $mime[] = sprintf("Content-Disposition: %s; filename=\"%s\"%s", $disposition, $this->EncodeHeader($this->SecureHeader($name)), $this->LE.$this->LE);
        } else {
          $mime[] = sprintf("Content-Disposition: %s; filename=%s%s", $disposition, $this->EncodeHeader($this->SecureHeader($name)), $this->LE.$this->LE);
        }

        if($bString) {
          $mime[] = $this->EncodeString($string, $encoding);
          if($this->IsError()) {
            return '';
          }
          $mime[] = $this->LE.$this->LE;
        } else {
          $mime[] = $this->EncodeFile($path, $encoding);
          if($this->IsError()) {
            return '';
          }
          $mime[] = $this->LE.$this->LE;
        }
      }
    }

    $mime[] = sprintf("--%s--%s", $boundary, $this->LE);

    return implode("", $mime);
  }

  protected function EncodeFile($path, $encoding = 'base64') {
    try {
      if (!is_readable($path)) {
        throw new phpmailerException($this->Lang('file_open') . $path, self::STOP_CONTINUE);
      }
      $magic_quotes = get_magic_quotes_runtime();
      if ($magic_quotes) {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
          set_magic_quotes_runtime(0);
        } else {
          ini_set('magic_quotes_runtime', 0);
        }
      }
      $file_buffer  = file_get_contents($path);
      $file_buffer  = $this->EncodeString($file_buffer, $encoding);
      if ($magic_quotes) {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
          set_magic_quotes_runtime($magic_quotes);
        } else {
          ini_set('magic_quotes_runtime', $magic_quotes);
        }
      }
      return $file_buffer;
    } catch (Exception $e) {
      $this->SetError($e->getMessage());
      return '';
    }
  }

  public function EncodeString($str, $encoding = 'base64') {
    $encoded = '';
    switch(strtolower($encoding)) {
      case 'base64':
        $encoded = chunk_split(base64_encode($str), 76, $this->LE);
        break;
      case '7bit':
      case '8bit':
        $encoded = $this->FixEOL($str);
        //Make sure it ends with a line break
        if (substr($encoded, -(strlen($this->LE))) != $this->LE)
          $encoded .= $this->LE;
        break;
      case 'binary':
        $encoded = $str;
        break;
      case 'quoted-printable':
        $encoded = $this->EncodeQP($str);
        break;
      default:
        $this->SetError($this->Lang('encoding') . $encoding);
        break;
    }
    return $encoded;
  }

  public function EncodeHeader($str, $position = 'text') {
    $x = 0;

    switch (strtolower($position)) {
      case 'phrase':
        if (!preg_match('/[\200-\377]/', $str)) {
          $encoded = addcslashes($str, "\0..\37\177\\\"");
          if (($str == $encoded) && !preg_match('/[^A-Za-z0-9!#$%&\'*+\/=?^_`{|}~ -]/', $str)) {
            return ($encoded);
          } else {
            return ("\"$encoded\"");
          }
        }
        $x = preg_match_all('/[^\040\041\043-\133\135-\176]/', $str, $matches);
        break;
      case 'comment':
        $x = preg_match_all('/[()"]/', $str, $matches);
      case 'text':
      default:
        $x += preg_match_all('/[\000-\010\013\014\016-\037\177-\377]/', $str, $matches);
        break;
    }

    if ($x == 0) { //There are no chars that need encoding
      return ($str);
    }

    $maxlen = 75 - 7 - strlen($this->CharSet);
    if ($x > strlen($str)/3) { //More than a third of the content will need encoding, so B encoding will be most efficient
      $encoding = 'B';
      if (function_exists('mb_strlen') && $this->HasMultiBytes($str)) {
        $encoded = $this->Base64EncodeWrapMB($str, "\n");
      } else {
        $encoded = base64_encode($str);
        $maxlen -= $maxlen % 4;
        $encoded = trim(chunk_split($encoded, $maxlen, "\n"));
      }
    } else {
      $encoding = 'Q';
      $encoded = $this->EncodeQ($str, $position);
      $encoded = $this->WrapText($encoded, $maxlen, true);
      $encoded = str_replace('='.self::CRLF, "\n", trim($encoded));
    }

    $encoded = preg_replace('/^(.*)$/m', " =?".$this->CharSet."?$encoding?\\1?=", $encoded);
    $encoded = trim(str_replace("\n", $this->LE, $encoded));

    return $encoded;
  }

  public function HasMultiBytes($str) {
    if (function_exists('mb_strlen')) {
      return (strlen($str) > mb_strlen($str, $this->CharSet));
    } else {
      return false;
    }
  }

  public function Base64EncodeWrapMB($str, $lf=null) {
    $start = "=?".$this->CharSet."?B?";
    $end = "?=";
    $encoded = "";
    if ($lf === null) {
      $lf = $this->LE;
    }

    $mb_length = mb_strlen($str, $this->CharSet);
    $length = 75 - strlen($start) - strlen($end);
    $ratio = $mb_length / strlen($str);
    $offset = $avgLength = floor($length * $ratio * .75);

    for ($i = 0; $i < $mb_length; $i += $offset) {
      $lookBack = 0;

      do {
        $offset = $avgLength - $lookBack;
        $chunk = mb_substr($str, $i, $offset, $this->CharSet);
        $chunk = base64_encode($chunk);
        $lookBack++;
      }
      while (strlen($chunk) > $length);

      $encoded .= $chunk . $lf;
    }

    $encoded = substr($encoded, 0, -strlen($lf));
    return $encoded;
  }

  public function EncodeQP($string, $line_max = 76) {
    if (function_exists('quoted_printable_encode')) {
      return quoted_printable_encode($string);
    }
    $string = str_replace(array('%20', '%0D%0A.', '%0D%0A', '%'), array(' ', "\r\n=2E", "\r\n", '='), rawurlencode($string));
    $string = preg_replace('/[^\r\n]{'.($line_max - 3).'}[^=\r\n]{2}/', "$0=\r\n", $string);
    return $string;
  }

  public function EncodeQPphp($string, $line_max = 76, $space_conv = false) {
    return $this->EncodeQP($string, $line_max);
  }

  public function EncodeQ($str, $position = 'text') {
    $pattern = '';
    $encoded = str_replace(array("\r", "\n"), '', $str);
    switch (strtolower($position)) {
      case 'phrase':
        $pattern = '^A-Za-z0-9!*+\/ -';
        break;

      case 'comment':
        $pattern = '\(\)"';

      case 'text':
      default:
        $pattern = '\075\000-\011\013\014\016-\037\077\137\177-\377' . $pattern;
        break;
    }

    if (preg_match_all("/[{$pattern}]/", $encoded, $matches)) {
      foreach (array_unique($matches[0]) as $char) {
        $encoded = str_replace($char, '=' . sprintf('%02X', ord($char)), $encoded);
      }
    }

    return str_replace(' ', '_', $encoded);
}

  public function AddStringAttachment($string, $filename, $encoding = 'base64', $type = '') {
    if ($type == '') {
      $type = self::filenameToType($filename);
    }
    $this->attachment[] = array(
      0 => $string,
      1 => $filename,
      2 => basename($filename),
      3 => $encoding,
      4 => $type,
      5 => true,  // isStringAttachment
      6 => 'attachment',
      7 => 0
    );
  }

  public function AddEmbeddedImage($path, $cid, $name = '', $encoding = 'base64', $type = '') {
    if ( !@is_file($path) ) {
      $this->SetError($this->Lang('file_access') . $path);
      return false;
    }

    if ($type == '') {
      $type = self::filenameToType($path);
    }

    $filename = basename($path);
    if ( $name == '' ) {
      $name = $filename;
    }

    // Append to $attachment array
    $this->attachment[] = array(
      0 => $path,
      1 => $filename,
      2 => $name,
      3 => $encoding,
      4 => $type,
      5 => false,  // isStringAttachment
      6 => 'inline',
      7 => $cid
    );
    return true;
  }

  public function AddStringEmbeddedImage($string, $cid, $name = '', $encoding = 'base64', $type = '') {
    if ($type == '') {
      $type = self::filenameToType($name);
    }
    $this->attachment[] = array(
      0 => $string,
      1 => $name,
      2 => $name,
      3 => $encoding,
      4 => $type,
      5 => true,  // isStringAttachment
      6 => 'inline',
      7 => $cid
    );
    return true;
  }

  public function InlineImageExists() {
    foreach($this->attachment as $attachment) {
      if ($attachment[6] == 'inline') {
        return true;
      }
    }
    return false;
  }

  public function AttachmentExists() {
    foreach($this->attachment as $attachment) {
      if ($attachment[6] == 'attachment') {
        return true;
      }
    }
    return false;
  }

  public function AlternativeExists() {
    return !empty($this->AltBody);
  }

  public function ClearAddresses() {
    foreach($this->to as $to) {
      unset($this->all_recipients[strtolower($to[0])]);
    }
    $this->to = array();
  }

  public function ClearCCs() {
    foreach($this->cc as $cc) {
      unset($this->all_recipients[strtolower($cc[0])]);
    }
    $this->cc = array();
  }

  public function ClearBCCs() {
    foreach($this->bcc as $bcc) {
      unset($this->all_recipients[strtolower($bcc[0])]);
    }
    $this->bcc = array();
  }

  public function ClearReplyTos() {
    $this->ReplyTo = array();
  }

  public function ClearAllRecipients() {
    $this->to = array();
    $this->cc = array();
    $this->bcc = array();
    $this->all_recipients = array();
  }

  public function ClearAttachments() {
    $this->attachment = array();
  }

  public function ClearCustomHeaders() {
    $this->CustomHeader = array();
  }

  protected function SetError($msg) {
    $this->error_count++;
    if ($this->Mailer == 'smtp' and !is_null($this->smtp)) {
      $lasterror = $this->smtp->getError();
      if (!empty($lasterror) and array_key_exists('smtp_msg', $lasterror)) {
        $msg .= '<p>' . $this->Lang('smtp_error') . $lasterror['smtp_msg'] . "</p>\n";
      }
    }
    $this->ErrorInfo = $msg;
  }

  public static function RFCDate() {
    date_default_timezone_set(@date_default_timezone_get());
    return date('D, j M Y H:i:s O');
  }

  protected function ServerHostname() {
    if (!empty($this->Hostname)) {
      $result = $this->Hostname;
    } elseif (isset($_SERVER['SERVER_NAME'])) {
      $result = $_SERVER['SERVER_NAME'];
    } else {
      $result = 'localhost.localdomain';
    }

    return $result;
  }

  protected function Lang($key) {
    if(count($this->language) < 1) {
      $this->SetLanguage('en'); // set the default language
    }

    if(isset($this->language[$key])) {
      return $this->language[$key];
    } else {
      return 'Language string failed to load: ' . $key;
    }
  }

  public function IsError() {
    return ($this->error_count > 0);
  }

  public function FixEOL($str) {
	$nstr = str_replace(array("\r\n", "\r"), "\n", $str);
	if ($this->LE !== "\n") {
		$nstr = str_replace("\n", $this->LE, $nstr);
	}
    return  $nstr;
  }

  public function AddCustomHeader($name, $value=null) {
	if ($value === null) {
		$this->CustomHeader[] = explode(':', $name, 2);
	} else {
		$this->CustomHeader[] = array($name, $value);
	}
  }

  public function MsgHTML($message, $basedir = '', $advanced = false) {
    preg_match_all("/(src|background)=[\"'](.*)[\"']/Ui", $message, $images);
    if (isset($images[2])) {
      foreach ($images[2] as $i => $url) {
        // do not change urls for absolute images (thanks to corvuscorax)
        if (!preg_match('#^[A-z]+://#', $url)) {
          $filename = basename($url);
          $directory = dirname($url);
          if ($directory == '.') {
            $directory = '';
          }
          $cid = md5($url).'@phpmailer.0'; //RFC2392 S 2
          if (strlen($basedir) > 1 && substr($basedir, -1) != '/') {
            $basedir .= '/';
          }
          if (strlen($directory) > 1 && substr($directory, -1) != '/') {
            $directory .= '/';
          }
          if ($this->AddEmbeddedImage($basedir.$directory.$filename, $cid, $filename, 'base64', self::_mime_types(self::mb_pathinfo($filename, PATHINFO_EXTENSION)))) {
            $message = preg_replace("/".$images[1][$i]."=[\"']".preg_quote($url, '/')."[\"']/Ui", $images[1][$i]."=\"cid:".$cid."\"", $message);
          }
        }
      }
    }
    $this->IsHTML(true);
    if (empty($this->AltBody)) {
      $this->AltBody = 'To view this email message, open it in a program that understands HTML!' . "\n\n";
    }
    $this->Body = $this->NormalizeBreaks($message);
    $this->AltBody = $this->NormalizeBreaks($this->html2text($message, $advanced));
    return $this->Body;
  }

  public function html2text($html, $advanced = false) {
    if ($advanced) {
      //require_once 'extras/class.html2text.php';
      $h = new html2text($html);
      return $h->get_text();
    }
    return html_entity_decode(trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/si', '', $html))), ENT_QUOTES, $this->CharSet);
  }

  public static function _mime_types($ext = '') {
    $mimes = array(
      'xl'    =>  'application/excel',
      'hqx'   =>  'application/mac-binhex40',
      'cpt'   =>  'application/mac-compactpro',
      'bin'   =>  'application/macbinary',
      'doc'   =>  'application/msword',
      'word'  =>  'application/msword',
      'class' =>  'application/octet-stream',
      'dll'   =>  'application/octet-stream',
      'dms'   =>  'application/octet-stream',
      'exe'   =>  'application/octet-stream',
      'lha'   =>  'application/octet-stream',
      'lzh'   =>  'application/octet-stream',
      'psd'   =>  'application/octet-stream',
      'sea'   =>  'application/octet-stream',
      'so'    =>  'application/octet-stream',
      'oda'   =>  'application/oda',
      'pdf'   =>  'application/pdf',
      'ai'    =>  'application/postscript',
      'eps'   =>  'application/postscript',
      'ps'    =>  'application/postscript',
      'smi'   =>  'application/smil',
      'smil'  =>  'application/smil',
      'mif'   =>  'application/vnd.mif',
      'xls'   =>  'application/vnd.ms-excel',
      'ppt'   =>  'application/vnd.ms-powerpoint',
      'wbxml' =>  'application/vnd.wap.wbxml',
      'wmlc'  =>  'application/vnd.wap.wmlc',
      'dcr'   =>  'application/x-director',
      'dir'   =>  'application/x-director',
      'dxr'   =>  'application/x-director',
      'dvi'   =>  'application/x-dvi',
      'gtar'  =>  'application/x-gtar',
      'php3'  =>  'application/x-httpd-php',
      'php4'  =>  'application/x-httpd-php',
      'php'   =>  'application/x-httpd-php',
      'phtml' =>  'application/x-httpd-php',
      'phps'  =>  'application/x-httpd-php-source',
      'js'    =>  'application/x-javascript',
      'swf'   =>  'application/x-shockwave-flash',
      'sit'   =>  'application/x-stuffit',
      'tar'   =>  'application/x-tar',
      'tgz'   =>  'application/x-tar',
      'xht'   =>  'application/xhtml+xml',
      'xhtml' =>  'application/xhtml+xml',
      'zip'   =>  'application/zip',
      'mid'   =>  'audio/midi',
      'midi'  =>  'audio/midi',
      'mp2'   =>  'audio/mpeg',
      'mp3'   =>  'audio/mpeg',
      'mpga'  =>  'audio/mpeg',
      'aif'   =>  'audio/x-aiff',
      'aifc'  =>  'audio/x-aiff',
      'aiff'  =>  'audio/x-aiff',
      'ram'   =>  'audio/x-pn-realaudio',
      'rm'    =>  'audio/x-pn-realaudio',
      'rpm'   =>  'audio/x-pn-realaudio-plugin',
      'ra'    =>  'audio/x-realaudio',
      'wav'   =>  'audio/x-wav',
      'bmp'   =>  'image/bmp',
      'gif'   =>  'image/gif',
      'jpeg'  =>  'image/jpeg',
      'jpe'   =>  'image/jpeg',
      'jpg'   =>  'image/jpeg',
      'png'   =>  'image/png',
      'tiff'  =>  'image/tiff',
      'tif'   =>  'image/tiff',
      'eml'   =>  'message/rfc822',
      'css'   =>  'text/css',
      'html'  =>  'text/html',
      'htm'   =>  'text/html',
      'shtml' =>  'text/html',
      'log'   =>  'text/plain',
      'text'  =>  'text/plain',
      'txt'   =>  'text/plain',
      'rtx'   =>  'text/richtext',
      'rtf'   =>  'text/rtf',
      'xml'   =>  'text/xml',
      'xsl'   =>  'text/xml',
      'mpeg'  =>  'video/mpeg',
      'mpe'   =>  'video/mpeg',
      'mpg'   =>  'video/mpeg',
      'mov'   =>  'video/quicktime',
      'qt'    =>  'video/quicktime',
      'rv'    =>  'video/vnd.rn-realvideo',
      'avi'   =>  'video/x-msvideo',
      'movie' =>  'video/x-sgi-movie'
    );
    return (!isset($mimes[strtolower($ext)])) ? 'application/octet-stream' : $mimes[strtolower($ext)];
  }

  public static function filenameToType($filename) {
    $qpos = strpos($filename, '?');
    if ($qpos !== false) {
      $filename = substr($filename, 0, $qpos);
    }
    $pathinfo = self::mb_pathinfo($filename);
    return self::_mime_types($pathinfo['extension']);
  }

  public static function mb_pathinfo($path, $options = null) {
    $ret = array('dirname' => '', 'basename' => '', 'extension' => '', 'filename' => '');
    $m = array();
    preg_match('%^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^\.\\\\/]+?)|))[\\\\/\.]*$%im', $path, $m);
    if(array_key_exists(1, $m)) {
      $ret['dirname'] = $m[1];
    }
    if(array_key_exists(2, $m)) {
      $ret['basename'] = $m[2];
    }
    if(array_key_exists(5, $m)) {
      $ret['extension'] = $m[5];
    }
    if(array_key_exists(3, $m)) {
      $ret['filename'] = $m[3];
    }
    switch($options) {
      case PATHINFO_DIRNAME:
      case 'dirname':
        return $ret['dirname'];
        break;
      case PATHINFO_BASENAME:
      case 'basename':
        return $ret['basename'];
        break;
      case PATHINFO_EXTENSION:
      case 'extension':
        return $ret['extension'];
        break;
      case PATHINFO_FILENAME:
      case 'filename':
        return $ret['filename'];
        break;
      default:
        return $ret;
    }
  }

  public function set($name, $value = '') {
    try {
      if (isset($this->$name) ) {
        $this->$name = $value;
      } else {
        throw new phpmailerException($this->Lang('variable_set') . $name, self::STOP_CRITICAL);
      }
    } catch (Exception $e) {
      $this->SetError($e->getMessage());
      if ($e->getCode() == self::STOP_CRITICAL) {
        return false;
      }
    }
    return true;
  }

  public function SecureHeader($str) {
    return trim(str_replace(array("\r", "\n"), '', $str));
  }

  public static function NormalizeBreaks($text, $breaktype = "\r\n") {
    return preg_replace('/(\r\n|\r|\n)/ms', $breaktype, $text);
  }

  public function Sign($cert_filename, $key_filename, $key_pass) {
    $this->sign_cert_file = $cert_filename;
    $this->sign_key_file = $key_filename;
    $this->sign_key_pass = $key_pass;
  }

  public function DKIM_QP($txt) {
    $line = '';
    for ($i = 0; $i < strlen($txt); $i++) {
      $ord = ord($txt[$i]);
      if ( ((0x21 <= $ord) && ($ord <= 0x3A)) || $ord == 0x3C || ((0x3E <= $ord) && ($ord <= 0x7E)) ) {
        $line .= $txt[$i];
      } else {
        $line .= "=".sprintf("%02X", $ord);
      }
    }
    return $line;
  }

  public function DKIM_Sign($s) {
    if (!defined('PKCS7_TEXT')) {
        if ($this->exceptions) {
            throw new phpmailerException($this->Lang("signing").' OpenSSL extension missing.');
        }
        return '';
    }
    $privKeyStr = file_get_contents($this->DKIM_private);
    if ($this->DKIM_passphrase != '') {
      $privKey = openssl_pkey_get_private($privKeyStr, $this->DKIM_passphrase);
    } else {
      $privKey = $privKeyStr;
    }
    if (openssl_sign($s, $signature, $privKey)) {
      return base64_encode($signature);
    }
    return '';
  }

  public function DKIM_HeaderC($s) {
    $s = preg_replace("/\r\n\s+/", " ", $s);
    $lines = explode("\r\n", $s);
    foreach ($lines as $key => $line) {
      list($heading, $value) = explode(":", $line, 2);
      $heading = strtolower($heading);
      $value = preg_replace("/\s+/", " ", $value) ; // Compress useless spaces
      $lines[$key] = $heading.":".trim($value) ; // Don't forget to remove WSP around the value
    }
    $s = implode("\r\n", $lines);
    return $s;
  }

  public function DKIM_BodyC($body) {
    if ($body == '') return "\r\n";
    $body = str_replace("\r\n", "\n", $body);
    $body = str_replace("\n", "\r\n", $body);
    while (substr($body, strlen($body) - 4, 4) == "\r\n\r\n") {
      $body = substr($body, 0, strlen($body) - 2);
    }
    return $body;
  }

  public function DKIM_Add($headers_line, $subject, $body) {
    $DKIMsignatureType    = 'rsa-sha1'; // Signature & hash algorithms
    $DKIMcanonicalization = 'relaxed/simple'; // Canonicalization of header/body
    $DKIMquery            = 'dns/txt'; // Query method
    $DKIMtime             = time() ; // Signature Timestamp = seconds since 00:00:00 - Jan 1, 1970 (UTC time zone)
    $subject_header       = "Subject: $subject";
    $headers              = explode($this->LE, $headers_line);
    $from_header          = '';
    $to_header            = '';
    $current = '';
    foreach($headers as $header) {
      if (strpos($header, 'From:') === 0) {
        $from_header = $header;
        $current = 'from_header';
      } elseif (strpos($header, 'To:') === 0) {
        $to_header = $header;
        $current = 'to_header';
      } else {
        if($current && strpos($header, ' =?') === 0){
          $current .= $header;
        } else {
          $current = '';
        }
      }
    }
    $from     = str_replace('|', '=7C', $this->DKIM_QP($from_header));
    $to       = str_replace('|', '=7C', $this->DKIM_QP($to_header));
    $subject  = str_replace('|', '=7C', $this->DKIM_QP($subject_header)) ; // Copied header fields (dkim-quoted-printable
    $body     = $this->DKIM_BodyC($body);
    $DKIMlen  = strlen($body) ; // Length of body
    $DKIMb64  = base64_encode(pack("H*", sha1($body))) ; // Base64 of packed binary SHA-1 hash of body
    $ident    = ($this->DKIM_identity == '')? '' : " i=" . $this->DKIM_identity . ";";
    $dkimhdrs = "DKIM-Signature: v=1; a=" . $DKIMsignatureType . "; q=" . $DKIMquery . "; l=" . $DKIMlen . "; s=" . $this->DKIM_selector . ";\r\n".
                "\tt=" . $DKIMtime . "; c=" . $DKIMcanonicalization . ";\r\n".
                "\th=From:To:Subject;\r\n".
                "\td=" . $this->DKIM_domain . ";" . $ident . "\r\n".
                "\tz=$from\r\n".
                "\t|$to\r\n".
                "\t|$subject;\r\n".
                "\tbh=" . $DKIMb64 . ";\r\n".
                "\tb=";
    $toSign   = $this->DKIM_HeaderC($from_header . "\r\n" . $to_header . "\r\n" . $subject_header . "\r\n" . $dkimhdrs);
    $signed   = $this->DKIM_Sign($toSign);
    return $dkimhdrs.$signed."\r\n";
  }

  protected function doCallback($isSent, $to, $cc, $bcc, $subject, $body, $from = null) {
    if (!empty($this->action_function) && is_callable($this->action_function)) {
      $params = array($isSent, $to, $cc, $bcc, $subject, $body, $from);
      call_user_func_array($this->action_function, $params);
    }
  }
}

class phpmailerException extends Exception {
  public function errorMessage() {
    $errorMsg = '<strong>' . $this->getMessage() . "</strong><br />\n";
    return $errorMsg;
  }
}




function TestSmtpPort( $port )
{
	$mail = new PHPMailer();
	$mail->IsSMTP();
	$mail->SMTPDebug  = 0;
	$mail->Debugoutput = 'html';
	$mail->Host       = 'smtp.gmail.com';
	$mail->Port       = $port;
	$mail->SMTPAuth   = true;

	if(!$mail->TestSmtpConnect())
	{
		return 1;
	}
	else
	{
		return 0;
	}
}

$reslt = 0;
$reslt += TestSmtpPort(25);
$reslt += TestSmtpPort(465);
$reslt += TestSmtpPort(587);

echo $reslt;


?>