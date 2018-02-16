<?php
namespace Freezer\Tests\Storage;

use Freezer\Storage\Pdo;

class PdoTest extends \PHPUnit\Framework\TestCase
{
    private $freezer;
    private $storage;

    private $pdo;

    /**
     * @covers Freezer\Storage\Pdo::__construct
     * @covers Freezer\Storage\Pdo::setUseLazyLoad
     */
    protected function setUp()
    {
        $this->freezer = $this->getMockBuilder('Freezer\\Freezer')
            ->setMethods(array('generateId'))
            ->getMock();

        $this->freezer->expects($this->any())
                      ->method('generateId')
                      ->will($this->onConsecutiveCalls('a', 'b', 'c'));

        $this->pdo     = new \PDO('sqlite::memory:');
        $this->storage = new Pdo($this->pdo, $this->freezer);

        $this->pdo->exec('CREATE TABLE freezer (id char(40), body text)');
    }

    protected function getFrozenObjectFromStorage($id)
    {
        $statement = sprintf('SELECT * FROM freezer WHERE id = "%s"', $id);
        $buffer = $this->pdo->query($statement)->fetch();

        return json_decode($buffer['body'], true);
    }

    /**
     * @covers Freezer\Storage\Pdo::__construct
     */
    public function testInstantiationWorks()
    {
        $storage = new Pdo(array('driver' => 'sqlite'));
        $this->assertInstanceOf('\\Freezer\\Storage\\Pdo', $storage);
    }

    /**
     * @covers Freezer\Storage\Pdo::__construct
     * @expectedException Freezer\Exception\InvalidArgumentException
     */
    public function testExceptionIsThrownIfPdoArgumentIsInvalid()
    {
        new Pdo(null);
    }

    /**
     * @covers Freezer\Storage::store
     * @covers Freezer\Storage\Pdo::doStore
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
     * @covers  Freezer\Storage\Pdo::doStore
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
     * @covers  Freezer\Storage\Pdo::doStore
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
     * @covers  Freezer\Storage\Pdo::doStore
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
     * @covers  Freezer\Storage\Pdo::doStore
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
     * @covers  Freezer\Storage\Pdo::doStore
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
     * @covers  Freezer\Storage\Pdo::doStore
     * @covers  Freezer\Storage\Pdo::doFetch
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
     * @covers  Freezer\Storage\Pdo::doStore
     * @covers  Freezer\Storage\Pdo::doFetch
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
     * @covers  Freezer\Storage\Pdo::doStore
     * @covers  Freezer\Storage\Pdo::doFetch
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
     * @covers  Freezer\Storage\Pdo::doStore
     * @covers  Freezer\Storage\Pdo::doFetch
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
     * @covers  Freezer\Storage\Pdo::doStore
     * @covers  Freezer\Storage\Pdo::doFetch
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
     * @covers  Freezer\Storage\Pdo::doStore
     * @covers  Freezer\Storage\Pdo::doFetch
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
     * @covers            Freezer\Storage\Pdo::doFetch
     * @expectedException RuntimeException
     */
    public function testExceptionIsThrownIfObjectCouldNotBeFetched()
    {
        $this->storage->fetch('a');
    }

}
