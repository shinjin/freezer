<?php
/**
 * Freezer
 *
 * Copyright (c) 2008-2010, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Freezer
 * @subpackage Tests
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2010 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @since      File available since Release 1.0.0
 */
namespace Freezer\Tests\Storage\CouchDB;

use Freezer\Storage\CouchDB;

/**
 * Tests for the Object\Freezer\Storage\CouchDB class without lazy loading.
 *
 * @package    Freezer
 * @subpackage Tests
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2008-2010 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-object-freezer/
 * @since      Class available since Release 1.0.0
 */
class WithoutLazyLoadTest extends TestCase
{
    protected $useLazyLoad = false;

    /**
     * @covers Freezer\Storage::store
     * @covers Freezer\Storage\CouchDB::doStore
     * @covers Freezer\Storage\CouchDB::send
     */
    public function testStoringAnObjectWorks()
    {
        $this->storage->store(new \A(1, 2, 3));

        $this->assertEquals(
          array(
            '_id'   => 'a',
            'class' => 'A',
            'state' => array(
              'a'                         => 1,
              'b'                         => 2,
              'c'                         => 3,
              '__freezer' => '{"hash":"3c0bd64e7f7143b457b51423b7f172f7172ef424"}'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage\CouchDB::doStore
     * @covers  Freezer\Storage\CouchDB::send
     * @depends testStoringAnObjectWorks
     */
    public function testStoringAnObjectThatAggregatesOtherObjectsWorks()
    {
        $this->storage->store(new \C);

        $this->assertEquals(
          array(
            '_id'   => 'a',
            'class' => 'C',
            'state' => array(
              'b'                         => '__freezer_b',
              '__freezer' => '{"hash":"9a7b11d8709331ee16304d3c2c7c72fc4730f7c4"}'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );

        $this->assertEquals(
          array(
            '_id'   => 'b',
            'class' => 'B',
            'state' => array(
              'a'                         => '__freezer_c',
              '__freezer' => '{"hash":"1404f057855305a1f5734b8c31f417d460285c42"}'
            )
          ),
          $this->getFrozenObjectFromStorage('b')
        );

        $this->assertEquals(
          array(
            '_id'   => 'c',
            'class' => 'A',
            'state' => array(
              'a'                         => 1,
              'b'                         => 2,
              'c'                         => 3,
              '__freezer' => '{"hash":"6f4ea6504fb30823218623e66cb47fff64373926"}'
            )
          ),
          $this->getFrozenObjectFromStorage('c')
        );
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage\CouchDB::doStore
     * @covers  Freezer\Storage\CouchDB::send
     * @depends testStoringAnObjectThatAggregatesOtherObjectsWorks
     */
    public function testStoringAnObjectThatAggregatesOtherObjectsInAnArrayWorks()
    {
        $this->storage->store(new \D);

        $this->assertEquals(
          array(
            '_id'   => 'a',
            'class' => 'D',
            'state' => array(
              'array' => array(
                0     => '__freezer_b'
              ),
              '__freezer' => '{"hash":"94d21ff37706a2c2095a95262f73d45c2f0a32f4"}'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );

        $this->assertEquals(
          array(
            '_id'   => 'b',
            'class' => 'A',
            'state' => array(
              'a'                         => 1,
              'b'                         => 2,
              'c'                         => 3,
              '__freezer' => '{"hash":"767101a9414bac28c076e39e1dc3eb5403cf0534"}'
            )
          ),
          $this->getFrozenObjectFromStorage('b')
        );
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage\CouchDB::doStore
     * @covers  Freezer\Storage\CouchDB::send
     * @depends testStoringAnObjectThatAggregatesOtherObjectsInAnArrayWorks
     */
    public function testStoringAnObjectThatAggregatesOtherObjectsInANestedArrayWorks()
    {
        $this->storage->store(new \E);

        $this->assertEquals(
          array(
            '_id'   => 'a',
            'class' => 'E',
            'state' => array(
              'array' => array(
                'array' => array(
                  0 => '__freezer_b'
                )
              ),
              '__freezer' => '{"hash":"fc93dde8215b082590100d32e7b26dc188ce0815"}'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );

        $this->assertEquals(
          array(
            '_id'   => 'b',
            'class' => 'A',
            'state' => array(
              'a'                         => 1,
              'b'                         => 2,
              'c'                         => 3,
              '__freezer' => '{"hash":"767101a9414bac28c076e39e1dc3eb5403cf0534"}'
            )
          ),
          $this->getFrozenObjectFromStorage('b')
        );
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage\CouchDB::doStore
     * @covers  Freezer\Storage\CouchDB::send
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
            '_id'   => 'a',
            'class' => 'Node',
            'state' => array(
              'parent'                    => null,
              'left'                      => '__freezer_b',
              'right'                     => '__freezer_c',
              'payload'                   => null,
              '__freezer' => '{"hash":"0b78e0ce8a31baa6174474e2e84256eb06acafca"}'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );

        $this->assertEquals(
          array(
            '_id'   => 'b',
            'class' => 'Node',
            'state' => array(
              'parent'                    => '__freezer_a',
              'left'                      => null,
              'right'                     => null,
              'payload'                   => null,
              '__freezer' => '{"hash":"4c138823f68eaeada0d122ed08354cb776022703"}'
            )
          ),
          $this->getFrozenObjectFromStorage('b')
        );

        $this->assertEquals(
          array(
            '_id'   => 'c',
            'class' => 'Node',
            'state' => array(
              'parent'                    => '__freezer_a',
              'left'                      => null,
              'right'                     => null,
              'payload'                   => null,
              '__freezer' => '{"hash":"e168d40c488fd27ecadfb3a5efa34ca2a10c6400"}'
            )
          ),
          $this->getFrozenObjectFromStorage('c')
        );
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage\CouchDB::doStore
     * @covers  Freezer\Storage\CouchDB::send
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
            '_id'   => 'a',
            'class' => 'Node2',
            'state' => array(
              'parent'   => null,
              'children' => array(
                0 => '__freezer_b',
                1 => '__freezer_c'
              ),
              'payload'                   => 'a',
              '__freezer' => '{"hash":"e72fff28068b932cc1cbf7cd3ee19438145a2db2"}'
            )
          ),
          $this->getFrozenObjectFromStorage('a')
        );

        $this->assertEquals(
          array(
            '_id'   => 'b',
            'class' => 'Node2',
            'state' => array(
              'parent'                    => '__freezer_a',
              'children'                  => array(),
              'payload'                   => 'b',
              '__freezer' => '{"hash":"7d784d361c301e8f9ea58e75d2288d2c8563ce24"}'
            )
          ),
          $this->getFrozenObjectFromStorage('b')
        );

        $this->assertEquals(
          array(
            '_id'   => 'c',
            'class' => 'Node2',
            'state' => array (
              'parent'                    => '__freezer_a',
              'children'                  => array(),
              'payload'                   => 'c',
              '__freezer' => '{"hash":"6763b776a62bebae3da18961bb42b22dba7ce441"}'
            )
          ),
          $this->getFrozenObjectFromStorage('c')
        );
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage::fetch
     * @covers  Freezer\Storage\CouchDB::doStore
     * @covers  Freezer\Storage\CouchDB::doFetch
     * @covers  Freezer\Storage\CouchDB::send
     * @depends testStoringAnObjectWorks
     */
    public function testStoringAndFetchingAnObjectWorks()
    {
        $object = new \A(1, 2, 3);
        $this->storage->store($object);

        $this->assertEquals($object, $this->removeRev($this->storage->fetch('a')));
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage::fetch
     * @covers  Freezer\Storage\CouchDB::doStore
     * @covers  Freezer\Storage\CouchDB::doFetch
     * @covers  Freezer\Storage\CouchDB::send
     * @depends testStoringAnObjectWorks
     */
    public function testStoringAndFetchingAndUpdatingAnObjectWorks()
    {
        $object = new \A(1, 2, 3);
        $this->storage->store($object);

        $expected = $this->storage->fetch('a');
        $expected->a = null;
        $this->storage->store($expected);

        $actual = $this->storage->fetch('a');

        $this->assertEquals($expected->a, $actual->a);
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage::fetch
     * @covers  Freezer\Storage\CouchDB::doStore
     * @covers  Freezer\Storage\CouchDB::doFetch
     * @depends testStoringAnObjectThatAggregatesOtherObjectsWorks
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsWorks()
    {
        $object = new \C;
        $this->storage->store($object);

        $this->assertEquals($object, $this->removeRev($this->storage->fetch('a')));
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage::fetch
     * @covers  Freezer\Storage::fetchArray
     * @covers  Freezer\Storage\CouchDB::doStore
     * @covers  Freezer\Storage\CouchDB::doFetch
     * @depends testStoringAnObjectThatAggregatesOtherObjectsInAnArrayWorks
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsInAnArrayWorks()
    {
        $object = new \D;
        $this->storage->store($object);

        $this->assertEquals($object, $this->removeRev($this->storage->fetch('a')));
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage::fetch
     * @covers  Freezer\Storage::fetchArray
     * @covers  Freezer\Storage\CouchDB::doStore
     * @covers  Freezer\Storage\CouchDB::doFetch
     * @depends testStoringAnObjectThatAggregatesOtherObjectsInANestedArrayWorks
     */
    public function testStoringAndFetchingAnObjectThatAggregatesOtherObjectsInANestedArrayWorks()
    {
        $object = new \E;
        $this->storage->store($object);

        $this->assertEquals($object, $this->removeRev($this->storage->fetch('a')));
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage::fetch
     * @covers  Freezer\Storage\CouchDB::doStore
     * @covers  Freezer\Storage\CouchDB::doFetch
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

        $this->assertEquals($root, $this->removeRev($this->storage->fetch('a')));
    }

    /**
     * @covers  Freezer\Storage::store
     * @covers  Freezer\Storage::fetch
     * @covers  Freezer\Storage::fetchArray
     * @covers  Freezer\Storage\CouchDB::doStore
     * @covers  Freezer\Storage\CouchDB::doFetch
     * @depends testStoringAndFetchingAnObjectGraphThatContainsCyclesWorks
     */
    public function testStoringAndFetchingAnObjectGraphThatContainsCyclesWorks2()
    {
        $root   = new \Node2('a');
        $left   = new \Node2('b', $root);
        $parent = new \Node2('c', $root);

        $this->storage->store($root);

        $this->assertEquals($root, $this->removeRev($this->storage->fetch('a')));
    }

    /**
     * @covers            Freezer\Storage\CouchDB::__construct
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfFirstConstructorArgumentIsNotAString()
    {
        $storage = new CouchDB(null);
    }

    /**
     * @covers            Freezer\Storage\CouchDB::__construct
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfThirdConstructorArgumentIsNotABoolean()
    {
        $storage = new CouchDB('test', null, null);
    }

    /**
     * @covers            Freezer\Storage\CouchDB::__construct
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfFourthConstructorArgumentIsNotAString()
    {
        $storage = new CouchDB('test', null, false, null);
    }

    /**
     * @covers            Freezer\Storage\CouchDB::__construct
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfFifthConstructorArgumentIsNotAnInteger()
    {
        $storage = new CouchDB('test', null, false, 'localhost', null);
    }

    /**
     * @covers            Freezer\Storage::fetch
     * @covers            Freezer\Storage\CouchDB::doFetch
     * @expectedException RuntimeException
     */
    public function testExceptionIsThrownIfObjectCouldNotBeFetched()
    {
        $this->storage->fetch('a');
    }

    /**
     * @covers            Freezer\Storage\CouchDB::send
     * @errorHandler      disabled
     * @expectedException RuntimeException
     */
    public function testExceptionIsThrownIfConnectionFails()
    {
        $storage = new CouchDB(
          'test',
          $this->freezer,
          false,
          'not.existing.host',
          5984
        );

        $storage->send('PUT', '/test');
    }

    private function removeRev($object) {
        $object->__freezer = strstr($object->__freezer, '","_rev"', true) . '"}';

        foreach(get_object_vars($object) as $prop) {
            if (is_object($prop) && strpos($prop->__freezer, '"_rev') !== false) {
                $this->removeRev($prop);
            } elseif(is_array($prop)) {
                $array = function($prop) use (&$array) {
                    foreach($prop as $val) {
                        if (is_object($val)) {
                            $this->removeRev($val);
                        } elseif (is_array($val)) {
                            $array($val);
                        }
                    }
                };
                $array($prop);
            }
        }

        return $object;
    }
}
