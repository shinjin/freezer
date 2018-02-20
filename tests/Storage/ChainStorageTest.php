<?php
namespace Freezer\Tests\Storage;

use Doctrine\Common\Cache\ArrayCache;
use Freezer\Storage\ChainStorage;
use Freezer\Storage\DoctrineCache;
use Freezer\Storage\Pdo;

class ChainStorageTest extends \PHPUnit\Framework\TestCase
{
    private $freezer;
    private $storageChain;

    private $cache;
    private $pdo;

    /**
     * @covers Freezer\Storage\ChainStorage::__construct
     * @covers Freezer\Storage\ChainStorage::setUseLazyLoad
     */
    protected function setUp()
    {
        $this->freezer = $this->getMockBuilder('Freezer\\Freezer')
            ->setMethods(array('generateId'))
            ->getMock();

        $this->freezer->expects($this->any())
                      ->method('generateId')
                      ->will($this->onConsecutiveCalls('a', 'b', 'c'));

        $this->cache = new ArrayCache;
        $this->pdo   = new \PDO('sqlite::memory:');

        $this->storageChain = array(
            new DoctrineCache($this->cache, $this->freezer),
            new Pdo($this->pdo, $this->freezer)
        );

        $this->pdo->exec('CREATE TABLE freezer (id char(40), body text)');
    }

    protected function getFrozenObjectFromPdoStorage($id)
    {
        $statement = sprintf('SELECT * FROM freezer WHERE id = "%s"', $id);
        $buffer = $this->pdo->query($statement)->fetch();

        return json_decode($buffer['body'], true);
    }

    /**
     * @covers Freezer\Storage\ChainStorage::__construct
     */
    public function testInstantiationWorks()
    {
        $storage = new ChainStorage(array());

        $this->assertInstanceOf('\\Freezer\\Storage\\ChainStorage', $storage);
    }

    /**
     * @covers Freezer\Storage::store
     * @covers Freezer\Storage\ChainStorage::doStore
     */
    public function testStoringAnObjectWithOneStorageWorks()
    {
        $storageChain = array($this->storageChain[0]);
        $storage = new ChainStorage($storageChain, $this->freezer);

        $storage->store(new \A(1, 2, 3));

        $this->assertSame(
            array(
                'class' => 'A',
                'state' => array(
                    'a'              => 1,
                    'b'              => 2,
                    'c'              => 3,
                    '__freezer' => 'hash=3c0bd64e7f7143b457b51423b7f172f7172ef424'
                )
            ),
            $this->cache->fetch('a')
        );
    }

    /**
     * @covers Freezer\Storage::store
     * @covers Freezer\Storage\ChainStorage::doStore
     */
    public function testStoringAnObjectWithMultipleStoragesWorks()
    {
        $storage = new ChainStorage($this->storageChain, $this->freezer);

        $storage->store(new \A(1, 2, 3));

        $expected = array(
            'class' => 'A',
            'state' => array(
                'a'              => 1,
                'b'              => 2,
                'c'              => 3,
                '__freezer' => 'hash=3c0bd64e7f7143b457b51423b7f172f7172ef424'
            )
        );

        $this->assertSame(
            $expected,
            $this->cache->fetch('a')
        );

        $this->assertSame(
            $expected,
            $this->getFrozenObjectFromPdoStorage('a')
        );
    }

    /**
     * @covers Freezer\Storage::store
     * @covers Freezer\Storage\ChainStorage::doStore
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionIsThrownIfStorageChainContainsInvalidStorage()
    {
        $storage = new ChainStorage(array(null), $this->freezer);
        $storage->store(new \stdClass);
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage::fetch
     * @covers  Freezer\Storage\ChainStorage::doStore
     * @covers  Freezer\Storage\ChainStorage::doFetch
     * @depends testStoringAnObjectWithOneStorageWorks
     */
    public function testStoringAndFetchingAnObjectWithOneStorageWorks()
    {
        $object = new \A(1, 2, 3);

        $storageChain = array($this->storageChain[0]);
        $storage = new ChainStorage($storageChain, $this->freezer);

        $storage->store($object);

        $this->assertEquals($object, $storage->fetch('a'));
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage::fetch
     * @covers  Freezer\Storage\ChainStorage::doStore
     * @covers  Freezer\Storage\ChainStorage::doFetch
     * @depends testStoringAnObjectWithMultipleStoragesWorks
     */
    public function testStoringAndFetchingAnObjectWithMultipleStoragesWorks()
    {
        $object = new \A(1, 2, 3);

        // make sure that object is fetched from primary, not secondary, storage
        $this->storageChain[0]->store($object);
        $this->storageChain[1] = null;

        $storage = new ChainStorage($this->storageChain, $this->freezer);

        $this->assertEquals($object, $storage->fetch('a'));
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage::fetch
     * @covers  Freezer\Storage\ChainStorage::doStore
     * @covers  Freezer\Storage\ChainStorage::doFetch
     * @depends testStoringAnObjectWithMultipleStoragesWorks
     */
    public function testFetchingAnObjectWithMultipleStoragesCachesObjectInPrimaryStorage()
    {
        $object = new \A(1, 2, 3);

        $this->storageChain[1]->store($object);

        $storage = new ChainStorage($this->storageChain, $this->freezer);

        $this->assertEquals($object, $storage->fetch('a'));
        $this->assertEquals($object, $this->storageChain[0]->fetch('a'));
    }

    /**
     * @covers            Freezer\Storage::fetch
     * @covers            Freezer\Storage\ChainStorage::doFetch
     * @expectedException Freezer\Exception\ObjectNotFoundException
     */
    public function testExceptionIsThrownIfObjectCouldNotBeFetched()
    {
        $storage = new ChainStorage($this->storageChain, $this->freezer);
        $storage->fetch('a');
    }
}
