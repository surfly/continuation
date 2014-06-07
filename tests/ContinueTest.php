<?php

require_once("con.php");

class ContinueTest extends PHPUnit_Framework_TestCase {
    public function testGetKeyValue()
    {
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
    }
};

?>