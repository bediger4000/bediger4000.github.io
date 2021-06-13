<?php
        error_reporting(0);
        echo 'init::f6mnZP0cwKNo9q::'.phpversion().'::';
        if(!function_exists('mail')) {
                echo 'error::function mail not exist';
        } else {
                for($i=0; $i<3; $i++) {
                        if(mail('noreply@google.com', 'Noreply', '') != false) {
                                break;
                        }
                }
                if($i == 3) {
                        echo 'error::failed call mail';
                } else {
                        echo 'ok';
                }
        }
?>
