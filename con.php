<?php

define('SECRET_KEY', 's');
define('SECRET_16_CHARS', 'deepheemae5eeGh5');
define('ENCRYPTION_METHOD', "AES-256-CBC");
define('DB_FILENAME', '/tmp/path-to-db');
define('DB_TABLE', 'continue');

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

function create_table($db) {
    $db->exec('CREATE TABLE IF NOT EXISTS '.DB_TABLE.' ('
        .'shortcut VARCHAR(10), '
        .'url TEXT, '
        .'creation_time DATETIME)');
}

# http://stackoverflow.com/a/14043346/3576976
function random_string($length=10) {
    return substr(sha1(rand()), 0, $length);
}

function save_url($db, $shortcut, $url, $time) {
    $stmt = $db->prepare('INSERT INTO '.DB_TABLE
        .' VALUES (?, ?, ?)');
    $res = $stmt->execute(array($shortcut, $url, $time));
}

function retrieve_url($db, $shortcut) {
    $query = 'SELECT url, creation_time FROM '.DB_TABLE
        .' WHERE shortcut=?';
    $stmt = $db->prepare($query);
    $stmt->execute(array($shortcut));

    return $stmt->fetch(PDO::FETCH_NUM);
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

function post_url($db) {
    $time = time();
    $data = json_decode(get_request_body(), true);

    if($data === NULL) {
        set_response_code(400, "Bad Request");
        echo "Incorrect data";
        return;
    }

    $data = save_cookies($data, array_items($_COOKIE));

    $encoded = json_encode($data);

    $shortcut = random_string();
    save_url($db, $shortcut, $encoded, $time);

    echo json_encode('?text='.$shortcut);
}

function get_url($db, $shortcut) {
    list($json_data, $time) = retrieve_url($db, $shortcut);

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

# http://stackoverflow.com/questions/2413991/php-equivalent-of-pythons-name-main
if(!count(debug_backtrace())) {
    $db = new PDO("sqlite:".DB_FILENAME);
    create_table($db);
    if(array_key_exists("text", $_GET)) {
        if(array_key_exists("t", $_GET)) {
            $time = $_GET["t"];
        } else {
            $time = NULL;
        }
        get_url($db, $_GET["text"]);
    } else {
        post_url($db);
    }
}

?>
