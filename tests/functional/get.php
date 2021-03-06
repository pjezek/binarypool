<?php
require_once(dirname(__FILE__) . "/../base/functional.php");

/**
 * Tests the GET method of the Binary Pool.
 */
class test_func_get extends test_base_functional {
    /**
     * Root URL returns a list of all defined buckets.
     */
    function testListBuckets() {
        $this->get('/');
        $this->assertEqual(api_response::getInstance()->getCode(), 200);
        $this->assertText('/buckets/bucket[@id="test"]/@id', 'test');
    }
    
    /**
     * Tests retrieval of a non-existing URL.
     */
    function testInvalidGet() {
        $this->get('/something/def');
        $this->assertEqual(api_response::getInstance()->getCode(), 404);
        $this->assertText('/error/code', '100');
        $this->assertText('/error/msg', 'Bucket not defined: something');
    }
    
    /**
     * Search for the client side XSL processing instruction
     */
    function testClientXSL() {
        $this->get('/');
        $this->assertPattern('#<\?xml-stylesheet#', $this->responseDom->saveXML());
        $this->get('/test');
        $this->assertPattern('#<\?xml-stylesheet#', $this->responseDom->saveXML());
        
        $this->get('/?NOXSL');
        $this->assertNoPattern('#<\?xml-stylesheet#', $this->responseDom->saveXML());
        $this->get('/test?NOXSL');
        $this->assertNoPattern('#<\?xml-stylesheet#', $this->responseDom->saveXML());
    }
}
