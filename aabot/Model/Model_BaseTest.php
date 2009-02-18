<?php

require_once 'aabot/Model/Base.php';

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Model_Base test case.
 */
class Model_BaseTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * @var Model_Base
	 */
	private $Model_Base;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp ();
		
		$this->Model_Base = new Model_Base('My_Model_Class', new stdClass());
	
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		// TODO Auto-generated Model_BaseTest::tearDown()
		

		$this->Model_Base = null;
		
		parent::tearDown ();
	}
	
	/**
	 * Constructs the test case.
	 */
	public function __construct() {
		// TODO Auto-generated constructor
	}
	
	/**
	 * Tests Model_Base->__construct()
	 */
//	public function test__construct() {
//		$this->assertNotNull($this->Model_Base);
//	}
	
	/**
	 * Tests Model_Base->delete()
	 */
	public function testDelete() {
		// TODO Auto-generated Model_BaseTest->testDelete()
		$this->markTestIncomplete ( "delete test not implemented" );
		
		$this->Model_Base->delete(/* parameters */);
	
	}
	
	/**
	 * Tests Model_Base->find()
	 */
	public function testFind() {
		// TODO Auto-generated Model_BaseTest->testFind()
		$this->markTestIncomplete ( "find test not implemented" );
		
		$this->Model_Base->find(/* parameters */);
	
	}
	
	/**
	 * Tests Model_Base->findOne()
	 */
	public function testFindOne() {
		// TODO Auto-generated Model_BaseTest->testFindOne()
		$this->markTestIncomplete ( "findOne test not implemented" );
		
		$this->Model_Base->findOne(/* parameters */);
	
	}
	
	/**
	 * Tests Model_Base->save()
	 */
	public function testSave() {
		// TODO Auto-generated Model_BaseTest->testSave()
		$this->markTestIncomplete ( "save test not implemented" );
		
		$this->Model_Base->save(/* parameters */);
	
	}
	
	/**
	 * Tests Model_Base->set()
	 */
	public function testSet() {
		// TODO Auto-generated Model_BaseTest->testSet()
		$this->markTestIncomplete ( "set test not implemented" );
		
		$this->Model_Base->set(/* parameters */);
	
	}
	
	/**
	 * Tests Model_Base->set_condition()
	 */
	public function testSet_condition() {
		// TODO Auto-generated Model_BaseTest->testSet_condition()
		$this->markTestIncomplete ( "set_condition test not implemented" );
		
		$this->Model_Base->set_condition(/* parameters */);
	
	}

}

