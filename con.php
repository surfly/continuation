<?php

define('SECRET_KEY', 's');
define('SECRET_16_CHARS', 'deepheemae5eeGh5');
define('ENCRYPTION_METHOD', "AES-256-CBC");

# http://www.php.net/manual/en/function.http-get-request-body.php#77305
function get_request_body() {
    $body = @file_get_contents('php://input');
    return $body;
}

# http://www.php.net/manual/en/function.http-response-code.php#107261
function set_response_code($code, $text) {
    $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
    header($protocol . ' ' . $code . ' ' . $text);
}

# http://www.php.net/manual/en/function.http-parse-cookie.php#74810
function cookie_parse( $str ) {
    $cookies = array();

    $csplit = explode( ';', $str );
    $cdata = array();
    foreach( $csplit as $data ) {
        $cinfo = explode( '=', $data );
        $cinfo[0] = trim( $cinfo[0] );
        if( $cinfo[0] == 'expires' ) $cinfo[1] = strtotime( $cinfo[1] );
        if( $cinfo[0] == 'secure' ) $cinfo[1] = "true";
        if( in_array( $cinfo[0], array( 'domain', 'expires', 'path', 'secure', 'comment' ) ) ) {
            $cdata[trim( $cinfo[0] )] = $cinfo[1];
        }
        else {
            $cdata['value']['key'] = $cinfo[0];
            $cdata['value']['value'] = $cinfo[1];
        }
    }
    $cookies[] = $cdata;

    return $cookies;
}

function array_items($tab) {
    $items = array();
    foreach($tab as $key=>$value) {
        $items[] = array($key, $value);
    }
    return $items;
}

function get_key_value($cookiestr) {
    $tab = explode('=', $cookiestr, 2);
    if(count($tab) == 2) {
        $tab[0] = trim($tab[0]);
        return $tab;
    } else {
        return array('', trim($cookiestr));
    }
}

function get_cookies($cookiestr) {
    $cookies = array();

    foreach(explode(';', $cookiestr) as $cookie) {
        $cookies[] = get_key_value($cookie);
    }

    return $cookies;
}

function encrypt($key, $str) {
    $encrypted = openssl_encrypt($str, ENCRYPTION_METHOD,
        $key, 0, SECRET_16_CHARS);

    return $encrypted;
}

function decrypt($key, $str) {
    $decrypted = openssl_decrypt($str, ENCRYPTION_METHOD,
        $key, 0, SECRET_16_CHARS);

    return $decrypted;
}

function post_url() {
    $cookies = array_items($_COOKIE);
    $time = time();
    $data = json_decode(get_request_body(), true);

    if($data === NULL) {
        set_response_code(400, "Bad Request");
        return "Incorrect data";
    }

    if(array_key_exists("client", $data)) 
        $client_str = $data["client"];
    else
        $client_str = "";

    $data["client"] = get_cookies($client_str);
    $data["httponly"] = array();

    foreach($cookies as $cookie) {
        if(in_array($cookie, $data["client"]))
            continue;

        $data["httponly"][] = $cookie;
    }

    $encrypted = encrypt(SECRET_KEY.$time,
        json_encode($data));

    return json_encode('?text='.urlencode($encrypted).'&t='.$time);
}

function get_url($encrypted_data, $time) {
    if(!$time || (time() - $time > 15)) {
        set_response_code(403, "Bad Request");
        return "URL expired.";
    }

    $data = json_decode(decrypt(SECRET_KEY.$time,
            $encrypted_data), true);

    if($data === NULL) {
        set_response_code(403, "Bad Request");
        return "Incorrect key";
    }

    print_r($data["client"]);
    print_r($data["httponly"]);

    header("Location: ".$data["url"]);
    foreach($data["client"] as $cookie) {
        list($key, $value) = $cookie;
        setcookie($key, $value);
    }

    foreach($data["httponly"] as $cookie) {
        list($key, $value) = $cookie;
        setcookie($key, $value, 0, "", "", "", true);
    }
}

# http://stackoverflow.com/questions/2413991/php-equivalent-of-pythons-name-main
if(!count(debug_backtrace())) {
    if(array_key_exists("text", $_GET)) {
        if(array_key_exists("t", $_GET)) {
            $time = $_GET["t"];
        } else {
            $time = NULL;
        }
        echo(get_url($_GET["text"], $time));
    } else {
        echo(post_url());
    }
}

?>
