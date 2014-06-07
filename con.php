<?php

define('SECRET_KEY', 's');

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
    return $str;
}

function decrypt($key, $str) {
    return $str;
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

    return json_encode($encrypted.'?t='.$time);
}

function get_url() {

}

echo(post_url());

?>
