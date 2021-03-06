<?php

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

function array_items($tab) {
    $items = array();
    foreach($tab as $key=>$value) {
        $items[] = array($key, $value);
    }
    return $items;
}

function save_url($url, $time) {
    $_SESSION["url"] = $url;
    $_SESSION["time"] = $time;
}

function retrieve_url() {
    if(!isset($_SESSION["url"]) || !isset($_SESSION["time"]))
        return array(NULL, NULL);
    return array($_SESSION["url"], $_SESSION["time"]);
}

function delete_url() {
    session_destroy();
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
    if($cookiestr === "") return array();

    $cookies = array();

    foreach(explode(';', $cookiestr) as $cookie) {
        $cookies[] = get_key_value($cookie);
    }

    return $cookies;
}

function save_cookies($data, $cookies) {
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

    return $data;
}

function post_url() {
    $time = time();
    $data = json_decode(get_request_body(), true);

    if($data === NULL) {
        set_response_code(400, "Bad Request");
        echo "Incorrect data";
        return;
    }

    $data = save_cookies($data, array_items($_COOKIE));

    $encoded = json_encode($data);

    save_url($encoded, $time);

    $url = '?text='.session_id();
    echo json_encode($url);
}

function get_url() {
    list($json_data, $time) = retrieve_url();
    delete_url();

    if($json_data === NULL) {
        set_response_code(403, "Forbidden");
        echo "Incorrect key";
        return;
    }

    if(!$time || (time() - $time > 15)) {
        set_response_code(403, "Forbidden");
        echo "URL expired.";
        return;
    }

    $data = json_decode($json_data, true);

    if($data === NULL) {
        set_response_code(403, "Forbidden");
        echo "Incorrect key";
        return;
    }

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

ini_set("session.use_cookies",0);
ini_set("session.use_trans_sid",1);
if(array_key_exists("text", $_GET))
    session_id($_GET['text']);
session_start();

if(array_key_exists("text", $_GET)) {
    get_url($_GET["text"]);
} else {
    post_url();
}

?>
