<?php
namespace Freezer\Tests\Storage;

use Doctrine\Common\Cache\ArrayCache;
use Freezer\Storage\DoctrineCache;

class DoctrineCacheTest extends \PHPUnit\Framework\TestCase
{
    private $freezer;
    private $storage;

    private $cache;

    /**
     * @covers Freezer\Storage\DoctrineCache::__construct
     * @covers Freezer\Storage\DoctrineCache::setUseLazyLoad
     */
    protected function setUp()
    {
        $this->freezer = $this->getMockBuilder('Freezer\\Freezer')
            ->setMethods(array('generateId'))
            ->getMock();

        $this->freezer->expects($this->any())
                      ->method('generateId')
                      ->will($this->onConsecutiveCalls('a', 'b', 'c'));

        $this->cache   = new ArrayCache;
        $this->storage = new DoctrineCache($this->cache, $this->freezer);
    }

    protected function getFrozenObjectFromStorage($id)
    {
        return $this->cache->fetch($id);
    }

    /**
     * @covers Freezer\Storage\DoctrineCache::__construct
     */
    public function testInstantiationWorks()
    {
        $storage = new DoctrineCache(new ArrayCache);
        $this->assertInstanceOf('\\Freezer\\Storage\\DoctrineCache', $storage);
    }


    /**
     * @covers Freezer\Storage::store
     * @covers Freezer\Storage\DoctrineCache::doStore
     */
    public function testStoringAnObjectWorks()
    {
        $this->storage->store(new \A(1, 2, 3));

        $this->assertEquals(
          array(
            'class' => 'A',
            'state' => array(
              'a'              => 1,
              'b'              => 2,
              'c'              => 3,
              '__freezer_hash' => '3c0bd64e7f7143b457b51423b7f172f7172ef424'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage\DoctrineCache::doStore
     * @depends testStoringAnObjectWorks
     */
    public function testStoringAnObjectThatAggregatesOtherObjectsWorks()
    {
        $this->storage->store(new \C);

        $this->assertEquals(
          array(
            'class' => 'C',
            'state' => array(
              'b'              => '__freezer_b',
              '__freezer_hash' => '9a7b11d8709331ee16304d3c2c7c72fc4730f7c4'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );

        $this->assertEquals(
          array(
            'class' => 'B',
            'state' => array(
              'a'              => '__freezer_c',
              '__freezer_hash' => '1404f057855305a1f5734b8c31f417d460285c42'
            )
          ),
          $this->getFrozenObjectFromStorage('b')
        );

        $this->assertEquals(
          array(
            'class' => 'A',
            'state' => array(
              'a'              => 1,
              'b'              => 2,
              'c'              => 3,
              '__freezer_hash' => '6f4ea6504fb30823218623e66cb47fff64373926'
            )
          ),
          $this->getFrozenObjectFromStorage('c')
        );
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage\DoctrineCache::doStore
     * @depends testStoringAnObjectThatAggregatesOtherObjectsWorks
     */
    public function testStoringAnObjectThatAggregatesOtherObjectsInAnArrayWorks()
    {
        $this->storage->store(new \D);

        $this->assertEquals(
          array(
            'class' => 'D',
            'state' => array(
              'array' => array(
                0     => '__freezer_b'
              ),
              '__freezer_hash' => '94d21ff37706a2c2095a95262f73d45c2f0a32f4'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );

        $this->assertEquals(
          array(
            'class' => 'A',
            'state' => array(
              'a'              => 1,
              'b'              => 2,
              'c'              => 3,
              '__freezer_hash' => '767101a9414bac28c076e39e1dc3eb5403cf0534'
            )
          ),
          $this->getFrozenObjectFromStorage('b')
        );
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage\DoctrineCache::doStore
     * @depends testStoringAnObjectThatAggregatesOtherObjectsInAnArrayWorks
     */
    public function testStoringAnObjectThatAggregatesOtherObjectsInANestedArrayWorks()
    {
        $this->storage->store(new \E);

        $this->assertEquals(
          array(
            'class' => 'E',
            'state' => array(
              'array' => array(
                'array' => array(
                  0 => '__freezer_b'
                )
              ),
              '__freezer_hash' => 'fc93dde8215b082590100d32e7b26dc188ce0815'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );

        $this->assertEquals(
          array(
            'class' => 'A',
            'state' => array(
              'a'              => 1,
              'b'              => 2,
              'c'              => 3,
              '__freezer_hash' => '767101a9414bac28c076e39e1dc3eb5403cf0534'
            )
          ),
          $this->getFrozenObjectFromStorage('b')
        );
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage\DoctrineCache::doStore
     * @depends testStoringAnObjectThatAggregatesOtherObjectsWorks
     */
    public function testStoringAnObjectGraphThatContainsCyclesWorks()
    {
        $root                = new \Node;
        $root->left          = new \Node;
        $root->right         = new \Node;
        $root->left->parent  = $root;
        $root->right->parent = $root;

        $this->storage->store($root);

        $this->assertEquals(
          array(
            'class' => 'Node',
            'state' => array(
              'parent'         => null,
              'left'           => '__freezer_b',
              'right'          => '__freezer_c',
              'payload'        => null,
              '__freezer_hash' => '0b78e0ce8a31baa6174474e2e84256eb06acafca'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );

        $this->assertEquals(
          array(
            'class' => 'Node',
            'state' => array(
              'parent'         => '__freezer_a',
              'left'           => null,
              'right'          => null,
              'payload'        => null,
              '__freezer_hash' => '4c138823f68eaeada0d122ed08354cb776022703'
            )
          ),
          $this->getFrozenObjectFromStorage('b')
        );

        $this->assertEquals(
          array(
            'class' => 'Node',
            'state' => array(
              'parent'         => '__freezer_a',
              'left'           => null,
              'right'          => null,
              'payload'        => null,
              '__freezer_hash' => 'e168d40c488fd27ecadfb3a5efa34ca2a10c6400'
            )
          ),
          $this->getFrozenObjectFromStorage('c')
        );
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage\DoctrineCache::doStore
     * @depends testStoringAnObjectGraphThatContainsCyclesWorks
     */
    public function testStoringAnObjectGraphThatContainsCyclesWorks2()
    {
        $a = new \Node2('a');
        $b = new \Node2('b', $a);
        $c = new \Node2('c', $a);

        $this->storage->store($a);

        $this->assertEquals(
          array(
            'class' => 'Node2',
            'state' => array(
              'parent'   => null,
              'children' => array(
                0 => '__freezer_b',
                1 => '__freezer_c'
              ),
              'payload'                   => 'a',
              '__freezer_hash' => 'e72fff28068b932cc1cbf7cd3ee19438145a2db2'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );

        $this->assertEquals(
          array(
            'class' => 'Node2',
            'state' => array(
              'parent'         => '__freezer_a',
              'children'       => array(),
              'payload'        => 'b',
              '__freezer_hash' => '7d784d361c301e8f9ea58e75d2288d2c8563ce24'
            )
          ),
          $this->getFrozenObjectFromStorage('b')
        );

        $this->assertEquals(
          array(
            'class' => 'Node2',
            'state' => array (
              'parent'         => '__freezer_a',
              'children'       => array(),
              'payload'        => 'c',
              '__freezer_hash' => '6763b776a62bebae3da18961bb42b22dba7ce441'
            )
          ),
          $this->getFrozenObjectFromStorage('c')
        );
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage::fetch
     * @covers  Freezer\Storage\DoctrineCache::doStore
     * @covers  Freezer\Storage\DoctrineCache::doFetch
     * @depends testStoringAnObjectWorks
     */
    public function testStoringAndFetchingAnObjectWorks()
    {
        $object = new \A(1, 2, 3);
        $this->storage->store($object);

        $this->assertEquals($object, $this->storage->fetch('a'));
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage::fetch
     * @covers  Freezer\Storage\DoctrineCache::doStore
     * @covers  Freezer\Storage\DoctrineCache::doFetch
     * @depends testStoringAnObjectThatAggregatesOtherObjectsWorks
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsWorks()
    {
        $object = new \C;
        $this->storage->store($object);

        $this->assertEquals($object, $this->storage->fetch('a'));
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage::fetch
     * @covers  Freezer\Storage::fetchArray
     * @covers  Freezer\Storage\DoctrineCache::doStore
     * @covers  Freezer\Storage\DoctrineCache::doFetch
     * @depends testStoringAnObjectThatAggregatesOtherObjectsInAnArrayWorks
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsInAnArrayWorks()
    {
        $object = new \D;
        $this->storage->store($object);

        $this->assertEquals($object, $this->storage->fetch('a'));
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage::fetch
     * @covers  Freezer\Storage::fetchArray
     * @covers  Freezer\Storage\DoctrineCache::doStore
     * @covers  Freezer\Storage\DoctrineCache::doFetch
     * @depends testStoringAnObjectThatAggregatesOtherObjectsInANestedArrayWorks
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsInANestedArrayWorks()
    {
        $object = new \E;
        $this->storage->store($object);

        $this->assertEquals($object, $this->storage->fetch('a'));
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage::fetch
     * @covers  Freezer\Storage\DoctrineCache::doStore
     * @covers  Freezer\Storage\DoctrineCache::doFetch
     * @depends testStoringAnObjectGraphThatContainsCyclesWorks
     */
    public function testStoringAndFetchingAnObjectGraphThatContainsCyclesWorks()
    {
        $root                = new \Node;
        $root->left          = new \Node;
        $root->right         = new \Node;
        $root->left->parent  = $root;
        $root->right->parent = $root;

        $this->storage->store($root);

        $this->assertEquals($root, $this->storage->fetch('a'));
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage::fetch
     * @covers  Freezer\Storage::fetchArray
     * @covers  Freezer\Storage\DoctrineCache::doStore
     * @covers  Freezer\Storage\DoctrineCache::doFetch
     * @depends testStoringAndFetchingAnObjectGraphThatContainsCyclesWorks
     */
    public function testStoringAndFetchingAnObjectGraphThatContainsCyclesWorks2()
    {
        $root   = new \Node2('a');
        $left   = new \Node2('b', $root);
        $parent = new \Node2('c', $root);

        $this->storage->store($root);

        $this->assertEquals($root, $this->storage->fetch('a'));
    }

    /**
     * @covers            Freezer\Storage::fetch
     * @covers            Freezer\Storage\DoctrineCache::doFetch
     * @expectedException RuntimeException
     */
    public function testExceptionIsThrownIfObjectCouldNotBeFetched()
    {
        $this->storage->fetch('a');
    }

}
