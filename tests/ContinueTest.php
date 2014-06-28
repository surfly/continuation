<?php

require_once("con.php");

class ContinueTest extends PHPUnit_Framework_TestCase {
    public function testGetKeyValue()
    {
        $this->assertEquals(get_key_value("asdf"), array("", "asdf"));
        $this->assertEquals(get_key_value("basic=example"), array("basic", "example"));
        $this->assertEquals(get_key_value("key=value=value"), array("key", "value=value"));
        $this->assertEquals(get_key_value("=value=value"), array("", "value=value"));
    }

    public function testGetCookies()
    {
        $this->assertEquals(get_cookies("key1=value; key2=value2;key3=value3"), 
            array(
                array("key1", "value"),
                array("key2", "value2"),
                array("key3", "value3")
            ));
        $this->assertEquals(get_cookies(""), array());
        $this->assertEquals(get_cookies("0"), array(array("", "0")));
    }

    public function testSaveCookies()
    {
        $payload = array('client' => 
            'key1=value1; key2=value_from_payload; key3=some_key',
            'other_key' => 'value');
        $cookies = array(array('key2', 'value_from_header'), array('key3', 'some_key'));

        $data = save_cookies($payload, $cookies);

        $this->assertFalse(array_key_exists('key3', $data["httponly"]));
        $this->assertContains(array("key3", "some_key"), $data["client"]);
        $this->assertEquals($data["other_key"], "value");
        $this->assertContains(array("key2", "value_from_header"), $data["httponly"]);
        $this->assertContains(array("key1", "value1"), $data["client"]);
    }

    public function testSaveUrl()
    {
        $time = time();
        save_url("apple", $time);
        $this->assertSame($_SESSION["url"], "apple");
        $this->assertSame($_SESSION["time"], $time);
    }

    public function testRetrieveUrl()
    {
        $time = time();
        $_SESSION["url"] = "apple";
        $_SESSION["time"] = time();

        list($rurl, $rtime) = retrieve_url();

        $this->assertSame($rurl, "apple");
        $this->assertSame($time, $time);
    }
};

?>
