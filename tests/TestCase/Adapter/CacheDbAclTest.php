<?php
/**
 * Project: hesa-mbit.
 * User: walther
 * Date: 2014/11/25
 * Time: 8:59 AM
 */

namespace Acl\Test\TestCase\Adapter;

use Acl\Adapter\CachedDbAcl;
use Acl\Controller\Component\AclComponent;
use Cake\Cache\Cache;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * CachedDbAclTwoTest class
 *
 */
class CachedDbAclTwoTest extends CachedDbAcl {

/**
 * construct method
 *
 */
	public function __construct() {
	}

/**
 * Pass through for cache keys
 *
 * @param string|array|Entity $aro The requesting object identifier.
 * @param string|array|Entity $aco The controlled object identifier.
 * @param string $action Action
 *
 * @return string
 */
	public function getCacheKey($aro, $aco, $action) {
		return $this->_getCacheKey($aro, $aco, $action);
	}

}

class CacheDbAclTest extends TestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		Configure::write('Acl.classname', __NAMESPACE__ . '\CachedDbAclTwoTest');

		$this->CachedDb = new CachedDbAclTwoTest();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Acl);
	}

/**
 * Test check
 *
 * @return void
 */
	public function testCacheKeys() {
		$this->assertSame('samir_print_read', $this->CachedDb->getCacheKey('Samir', 'print', 'read'));
		$this->assertSame('samir_root_tpsreports_update', $this->CachedDb->getCacheKey('Samir', 'ROOT/tpsReports/update', '*'));
		$this->assertSame('user_1_print', $this->CachedDb->getCacheKey(['User' => ['id' => 1]], 'print', '*'));
		$this->assertSame('user_1_print', $this->CachedDb->getCacheKey(['model' => 'User', 'foreign_key' => 1], 'print', '*'));

		$entity = new Entity([
			'id' => '1'
		], ['source' => 'User']);
		$this->assertSame('user_1_print', $this->CachedDb->getCacheKey($entity, 'print', '*'));
	}
}
