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
namespace Freezer\Tests;

use Freezer\Freezer;

require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'A.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'B.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'Base.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'C.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'D.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'E.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'Extended.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'F.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'ConstructorCounter.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'Node.php'));
require_once join(DIRECTORY_SEPARATOR, array(__DIR__, '_files', 'Node2.php'));

/**
 * Tests for the Freezer\Freezer class.
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
class FreezerTest extends \PHPUnit\Framework\TestCase
{
    const LEVEL_OF_PARANOIA = 1;

    protected $freezer;

    /**
     * @covers Freezer\Freezer::__construct
     */
    protected function setUp()
    {
        $this->freezer = $this->getMockBuilder('Freezer\\Freezer')
            ->setMethods(array('generateId'))
            ->getMock();

        $this->freezer->expects($this->any())
                      ->method('generateId')
                      ->will($this->onConsecutiveCalls('a', 'b', 'c'));
    }

    protected function tearDown()
    {
        $this->freezer = null;
    }

    /**
     * @covers Freezer\Freezer::freeze
     * @covers Freezer\Freezer::generateId
     */
    public function testFreezingAnObjectWorks()
    {
        $this->assertEquals(
          array(
            'root'    => 'a',
            'objects' => array(
              'a' => array(
                'class' => 'A',
                'isDirty'   => true,
                'state'     => array(
                  'a'                         => 1,
                  'b'                         => 2,
                  'c'                         => 3,
                  '__freezer' => '{"hash":"3c0bd64e7f7143b457b51423b7f172f7172ef424"}'
                )
              )
            )
          ),
          $this->freezer->freeze(new \A(1, 2, 3))
        );
    }

    /**
     * @covers Freezer\Freezer::freeze
     */
    public function testFreezingAnObjectOfAnExtendedClassWorks()
    {
        $this->assertEquals(
          array(
            'root'    => 'a',
            'objects' => array(
              'a' => array(
                'class' => 'Extended',
                'isDirty'   => true,
                'state' => array(
                  'd' => 'd',
                  'e' => 'e',
                  'f' => 'f',
                  'a' => 'a',
                  'b' => 'b',
                  '__freezer' => '{"hash":"78e22e75eb22e8ca26127a89c156365b9a1e9a6e"}',
                )
              )
            )
          ),
          $this->freezer->freeze(new \Extended)
        );
    }

    /**
     * @covers Freezer\Freezer::freeze
     */
    public function testFreezingAnObjectThatAggregatesAResourceWorks()
    {
        $this->assertEquals(
          array(
            'root'    => 'a',
            'objects' => array(
              'a' => array(
                'class' => 'F',
                'isDirty'   => true,
                'state'     => array(
                  'file'                      => null,
                  '__freezer' => '{"hash":"e57d9e232b4f1691aceeea16e1099728b7c03830"}'
                )
              )
            )
          ),
          $this->freezer->freeze(new \F)
        );
    }

    /**
     * @covers  Freezer\Freezer::freeze
     * @depends testFreezingAnObjectWorks
     */
    public function testFreezingAnObjectThatAggregatesOtherObjectsWorks()
    {
        $this->assertEquals(
          array(
            'root'    => 'a',
            'objects' => array(
              'a' => array(
                'class' => 'C',
                'isDirty'   => true,
                'state' => array(
                  'b'                         => '__freezer_b',
                  '__freezer' => '{"hash":"9a7b11d8709331ee16304d3c2c7c72fc4730f7c4"}'
                )
              ),
              'b' => array(
                'class' => 'B',
                'isDirty'   => true,
                'state' => array(
                  'a'                         => '__freezer_c',
                  '__freezer' => '{"hash":"1404f057855305a1f5734b8c31f417d460285c42"}'
                )
              ),
              'c' => array(
                'class' => 'A',
                'isDirty'   => true,
                'state' => array(
                  'a'                         => 1,
                  'b'                         => 2,
                  'c'                         => 3,
                  '__freezer' => '{"hash":"6f4ea6504fb30823218623e66cb47fff64373926"}'
                )
              )
            )
          ),
          $this->freezer->freeze(new \C)
        );
    }

    /**
     * @covers  Freezer\Freezer::freeze
     * @covers  Freezer\Freezer::freezeArray
     * @depends testFreezingAnObjectThatAggregatesOtherObjectsWorks
     */
    public function testFreezingAnObjectThatAggregatesOtherObjectsInAnArrayWorks()
    {
        $this->assertEquals(
          array(
            'root'    => 'a',
            'objects' => array(
              'a' => array(
                'class' => 'D',
                'isDirty'   => true,
                'state'     => array(
                  'array' => array(
                    0 => '__freezer_b'
                  ),
                  '__freezer' => '{"hash":"94d21ff37706a2c2095a95262f73d45c2f0a32f4"}'
                )
              ),
              'b' => array(
                'class' => 'A',
                'isDirty'   => true,
                'state'     => array(
                  'a'                         => 1,
                  'b'                         => 2,
                  'c'                         => 3,
                  '__freezer' => '{"hash":"767101a9414bac28c076e39e1dc3eb5403cf0534"}'
                )
              )
            )
          ),
          $this->freezer->freeze(new \D)
        );
    }

    /**
     * @covers  Freezer\Freezer::freeze
     * @covers  Freezer\Freezer::freezeArray
     * @depends testFreezingAnObjectThatAggregatesOtherObjectsInAnArrayWorks
     */
    public function testFreezingAnObjectThatAggregatesOtherObjectsInANestedArrayWorks()
    {
        $this->assertEquals(
          array(
            'root'    => 'a',
            'objects' => array(
              'a' => array(
                'class' => 'E',
                'isDirty'   => true,
                'state'     => array(
                  'array' => array(
                    'array' => array(
                      0 => '__freezer_b'
                    )
                  ),
                  '__freezer' => '{"hash":"fc93dde8215b082590100d32e7b26dc188ce0815"}'
                ),
              ),
              'b' => array(
                'class' => 'A',
                'isDirty'   => true,
                'state'     => array(
                  'a' => 1,
                  'b' => 2,
                  'c' => 3,
                  '__freezer' => '{"hash":"767101a9414bac28c076e39e1dc3eb5403cf0534"}'
                )
              )
            )
          ),
          $this->freezer->freeze(new \E)
        );
    }

    /**
     * @covers  Freezer\Freezer::freeze
     * @depends testFreezingAnObjectThatAggregatesOtherObjectsWorks
     */
    public function testFreezingAnObjectGraphThatContainsCyclesWorks()
    {
        $root                = new \Node;
        $root->left          = new \Node;
        $root->right         = new \Node;
        $root->left->parent  = $root;
        $root->right->parent = $root;

        $this->assertEquals(
          array(
            'root'    => 'a',
            'objects' => array(
              'a' => array(
                'class' => 'Node',
                'isDirty'   => true,
                'state'     => array(
                  'parent'                    => null,
                  'left'                      => '__freezer_b',
                  'right'                     => '__freezer_c',
                  'payload'                   => null,
                  '__freezer' => '{"hash":"0b78e0ce8a31baa6174474e2e84256eb06acafca"}'
                )
              ),
              'b' => 
              array(
                'class' => 'Node',
                'isDirty'   => true,
                'state'     => array(
                  'parent'                    => '__freezer_a',
                  'left'                      => null,
                  'right'                     => null,
                  'payload'                   => null,
                  '__freezer' => '{"hash":"4c138823f68eaeada0d122ed08354cb776022703"}'
                )
              ),
              'c' => array(
                'class' => 'Node',
                'isDirty'   => true,
                'state'     => array(
                  'parent'                    => '__freezer_a',
                  'left'                      => null,
                  'right'                     => null,
                  'payload'                   => null,
                  '__freezer' => '{"hash":"e168d40c488fd27ecadfb3a5efa34ca2a10c6400"}'
                )
              )
            )
          ),
          $this->freezer->freeze($root)
        );
    }

    /**
     * @covers  Freezer\Freezer::freeze
     * @covers  Freezer\Freezer::freezeArray
     * @depends testFreezingAnObjectGraphThatContainsCyclesWorks
     */
    public function testFreezingAnObjectGraphThatContainsCyclesWorks2()
    {
        $a = new \Node2('a');
        $b = new \Node2('b', $a);
        $c = new \Node2('c', $a);

        $this->assertEquals(
          array(
            'root'    => 'a',
            'objects' => array(
              'a' => array(
                'class' => 'Node2',
                'isDirty'   => true,
                'state'     => array(
                  'parent'   => null,
                  'children' => array(
                    0 => '__freezer_b',
                    1 => '__freezer_c'
                  ),
                  'payload'                   => 'a',
                  '__freezer' => '{"hash":"e72fff28068b932cc1cbf7cd3ee19438145a2db2"}'
                )
              ),
              'b' => array(
                'class' => 'Node2',
                'isDirty'   => true,
                'state'     => array(
                  'parent'   => '__freezer_a',
                  'children' => array(),
                  'payload'                   => 'b',
                  '__freezer' => '{"hash":"7d784d361c301e8f9ea58e75d2288d2c8563ce24"}'
                )
              ),
              'c' => array(
                'class' => 'Node2',
                'isDirty'   => true,
                'state'     => array(
                  'parent'   => '__freezer_a',
                  'children' => array(),
                  'payload'                   => 'c',
                  '__freezer' => '{"hash":"6763b776a62bebae3da18961bb42b22dba7ce441"}'
                )
              )
            )
          ),
          $this->freezer->freeze($a)
        );
    }

    /**
     * @covers  Freezer\Freezer::freeze
     * @covers  Freezer\Freezer::thaw
     * @depends testFreezingAnObjectWorks
     */
    public function testFreezingAndThawingAnObjectWorks()
    {
        $object = new \A(1, 2, 3);

        $this->assertEquals(
          $object, $this->freezer->thaw($this->freezer->freeze($object))
        );
    }

    /**
     * @covers  Freezer\Freezer::freeze
     * @covers  Freezer\Freezer::thaw
     * @depends testFreezingAndThawingAnObjectWorks
     */
    public function testRepeatedlyFreezingAndThawingAnObjectWorks()
    {
        $object = new \A(1, 2, 3);

        $this->assertEquals(
          $object, $this->freezer->thaw(
            $this->freezer->freeze(
              $this->freezer->thaw($this->freezer->freeze($object))
            )
          )
        );
    }

    /**
     * @covers  Freezer\Freezer::freeze
     * @covers  Freezer\Freezer::thaw
     * @depends testFreezingAnObjectWorks
     */
    public function testFreezingAndThawingAnObjectOfAnExtendedClassWorks()
    {
        $object = new \Extended;

        $this->assertEquals(
          $object, $this->freezer->thaw($this->freezer->freeze($object))
        );
    }

    /**
     * @covers  Freezer\Freezer::freeze
     * @covers  Freezer\Freezer::thaw
     * @depends testFreezingAnObjectThatAggregatesOtherObjectsWorks
     */
    public function testFreezingAndThawingAnObjectThatAggregatesOtherObjectsWorks()
    {
        $object = new \C;

        $this->assertEquals(
          $object, $this->freezer->thaw($this->freezer->freeze($object))
        );
    }

    /**
     * @covers  Freezer\Freezer::freeze
     * @covers  Freezer\Freezer::thaw
     * @depends testFreezingAndThawingAnObjectThatAggregatesOtherObjectsWorks
     */
    public function testRepeatedlyFreezingAndThawingAnObjectThatAggregatesOtherObjectsWorks()
    {
        $object = new \C;

        $this->assertEquals(
          $object, $this->freezer->thaw(
            $this->freezer->freeze(
              $this->freezer->thaw(
                $this->freezer->freeze($object)
              )
            )
          )
        );
    }

    /**
     * @covers  Freezer\Freezer::freeze
     * @covers  Freezer\Freezer::freezeArray
     * @covers  Freezer\Freezer::thaw
     * @covers  Freezer\Freezer::thawArray
     * @depends testFreezingAnObjectThatAggregatesOtherObjectsInAnArrayWorks
     */
    public function testFreezingAndThawingAnObjectThatAggregatesOtherObjectsInAnArrayWorks()
    {
        $object = new \D;

        $this->assertEquals(
          $object, $this->freezer->thaw($this->freezer->freeze($object))
        );
    }

    /**
     * @covers  Freezer\Freezer::freeze
     * @covers  Freezer\Freezer::freezeArray
     * @covers  Freezer\Freezer::thaw
     * @covers  Freezer\Freezer::thawArray
     * @depends testFreezingAnObjectThatAggregatesOtherObjectsInANestedArrayWorks
     */
    public function testFreezingAndThawingAnObjectThatAggregatesOtherObjectsInANestedArrayWorks()
    {
        $object = new \E;

        $this->assertEquals(
          $object, $this->freezer->thaw($this->freezer->freeze($object))
        );
    }

    /**
     * @covers  Freezer\Freezer::freeze
     * @covers  Freezer\Freezer::freezeArray
     * @covers  Freezer\Freezer::thaw
     * @covers  Freezer\Freezer::thawArray
     * @depends testFreezingAnObjectGraphThatContainsCyclesWorks
     */
    public function testFreezingAndThawingAnObjectGraphThatContainsCyclesWorks()
    {
        $root                = new \Node;
        $root->left          = new \Node;
        $root->right         = new \Node;
        $root->left->parent  = $root;
        $root->right->parent = $root;

        $this->assertEquals(
          $root, $this->freezer->thaw($this->freezer->freeze($root))
        );
    }

    /**
     * @covers  Freezer\Freezer::freeze
     * @covers  Freezer\Freezer::freezeArray
     * @covers  Freezer\Freezer::thaw
     * @covers  Freezer\Freezer::thawArray
     * @depends testFreezingAndThawingAnObjectGraphThatContainsCyclesWorks
     */
    public function testFreezingAndThawingAnObjectGraphThatContainsCyclesWorks2()
    {
        $a = new \Node2('a');
        $b = new \Node2('b', $a);
        $c = new \Node2('c', $a);

        $this->assertEquals(
          $a, $this->freezer->thaw($this->freezer->freeze($a))
        );
    }

    /**
     * @covers  Freezer\Freezer::thaw
     * @depends testFreezingAndThawingAnObjectWorks
     */
    public function testConstructorIsNotCalledWhenAnObjectIsThawed()
    {
        $this->freezer->thaw($this->freezer->freeze(new \ConstructorCounter));
        $this->assertEquals(1, \ConstructorCounter::$numTimesConstructorCalled);
    }

    /**
     * @covers            Freezer\Freezer::freeze
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfNotAnObjectIsPassedAsFirstArgumentToFreeze()
    {
        $this->freezer->freeze(null);
    }

    /**
     * @covers            Freezer\Freezer::thaw
     * @expectedException RuntimeException
     */
    public function testExceptionIsThrownWhenClassCouldNotBeFound()
    {
        $this->freezer->thaw(
          array(
            'root'    => '173a01cc-3ca0-41a8-9a7e-d6db5657d20f',
            'objects' => array(
              '173a01cc-3ca0-41a8-9a7e-d6db5657d20f' => array(
                'class' => 'NotExistingClass',
                'state'     => array(
                  '__freezer_uuid' => '173a01cc-3ca0-41a8-9a7e-d6db5657d20f',
                )
              )
            )
          )
        );
    }

    /**
     * @covers Freezer\Freezer::__construct
     * @covers Freezer\Freezer::setIdAttribute
     * @covers Freezer\Freezer::getIdAttribute
     * @covers Freezer\Freezer::setAttributeFilter
     * @covers Freezer\Freezer::getAttributeFilter
     * @covers Freezer\Freezer::setUseAutoload
     * @covers Freezer\Freezer::getUseAutoload
     */
    public function testConstructorWithDefaultArguments()
    {
        $freezer = new Freezer;

        $this->assertSame('__freezer_uuid', $freezer->getIdAttribute());
        $this->assertNull($freezer->getAttributeFilter());
        $this->assertTrue($freezer->getUseAutoload());
    }

    /**
     * @covers            Freezer\Freezer::__construct
     * @covers            Freezer\Freezer::setIdAttribute
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsRaisedForInvalidConstructorArguments1()
    {
        $freezer = new Freezer(null);
    }

    /**
     * @covers            Freezer\Freezer::__construct
     * @covers            Freezer\Freezer::setUseAutoload
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsRaisedForInvalidConstructorArguments2()
    {
        $freezer = new Freezer('__freezer_uuid', array(), null);
    }

    /**
     * @covers Freezer\Freezer::generateHash
     */
    public function testObjectCanBeHashed()
    {
        $this->assertEquals(
          '1a66b04455fbcb456fca201730e8b9fb1336d2e7',
          $this->freezer->generateHash(new \A(1, 2, 3))
        );
    }

    /**
     * @covers Freezer\Freezer::generateHash
     */
    public function testHashedObjectCanBeHashed()
    {
        $object = new \A(1, 2, 3);
        $object->__freezer['hash'] = '1a66b04455fbcb456fca201730e8b9fb1336d2e7';

        $this->assertEquals(
          '1a66b04455fbcb456fca201730e8b9fb1336d2e7',
          $this->freezer->generateHash($object)
        );
    }

    /**
     * @covers  Freezer\Freezer::generateHash
     * @depends testObjectCanBeHashed
     */
    public function testObjectWithAggregatedResourceCanBeHashed()
    {
        $this->assertEquals(
          'e69f20e9f683920d3fb4329abd951e878b1f9372',
          $this->freezer->generateHash(new \F)
        );
    }

    /**
     * @covers  Freezer\Freezer::generateHash
     * @depends testObjectCanBeHashed
     */
    public function testObjectWithAggregatedObjectCanBeHashed()
    {
        $this->assertEquals(
          '2fd22ce656b849cb086889e5eacd1da49228eb0a',
          $this->freezer->generateHash(new \B)
        );
    }

    /**
     * @covers  Freezer\Freezer::generateHash
     * @depends testObjectWithAggregatedObjectCanBeHashed
     */
    public function testObjectThatAggregatesOtherObjectsInAnArrayCanBeHashed()
    {
        $this->assertEquals(
          '08fbe76f6e026529706b3f839bb89ef553f2244f',
          $this->freezer->generateHash(new \D)
        );
    }

    /**
     * @covers  Freezer\Freezer::generateHash
     * @depends testObjectThatAggregatesOtherObjectsInAnArrayCanBeHashed
     */
    public function testObjectThatAggregatesOtherObjectsInANestedArrayCanBeHashed()
    {
        $this->assertEquals(
          '4a90e5557becb306532cc9d68dea147d3ef1a3ae',
          $this->freezer->generateHash(new \E)
        );
    }

    /**
     * @covers  Freezer\Freezer::generateHash
     * @depends testObjectWithAggregatedObjectCanBeHashed
     */
    public function testObjectGraphThatContainsCyclesCanBeHashed()
    {
        $root                = new \Node;
        $root->left          = new \Node;
        $root->right         = new \Node;
        $root->left->parent  = $root;
        $root->right->parent = $root;

        $this->assertEquals(
          '830f3470aa75a83deab25ef5a7617a5967f07ed4',
          $this->freezer->generateHash($root)
        );
    }

    /**
     * @covers  Freezer\Freezer::generateHash
     * @depends testObjectGraphThatContainsCyclesCanBeHashed
     */
    public function testObjectGraphThatContainsCyclesCanBeHashed2()
    {
        $a = new \Node2('a');
        $b = new \Node2('b', $a);
        $c = new \Node2('c', $a);

        $this->assertEquals(
          '8e3d1f054f570f708241f0ee5519c0913b802465',
          $this->freezer->generateHash($a)
        );
    }

    /**
     * @covers            Freezer\Freezer::generateHash
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfNotAnObjectIsPassedToGetHash()
    {
        $this->freezer->generateHash(null);
    }

    /**
     * @covers Freezer\Freezer::generateId
     */
    public function testReturnValueOfUuidIsUnique()
    {
        $freezer = new Freezer;
        $fixedId = $freezer->generateId();

        for ($i = 1; $i <= self::LEVEL_OF_PARANOIA; $i++) {
            $this->assertNotEquals($fixedId, $this->freezer->generateId());
        }
    }

    /**
     * @covers Freezer\Freezer::isDirty
     */
    public function testNonDirtyObjectIsRecognizedAsNotBeingDirty()
    {
        $object = new \A(1, 2, 3);
        $object->__freezer['hash'] = '1a66b04455fbcb456fca201730e8b9fb1336d2e7';

        $this->assertFalse($this->freezer->isDirty($object));
    }

    /**
     * @covers Freezer\Freezer::isDirty
     */
    public function testDirtyObjectIsRecognizedAsBeingDirty()
    {
        $object = new \A(3, 2, 1);
        $object->__freezer['hash'] = 'a6efdb77cb879e26cf30635156cf045a7e7f9564';

        $this->assertTrue($this->freezer->isDirty($object));
    }

    /**
     * @covers Freezer\Freezer::isDirty
     */
    public function testDirtyObjectIsRecognizedAsBeingDirty2()
    {
        $object = new \A(1, 2, 3);

        $this->assertTrue($this->freezer->isDirty($object));
    }

    /**
     * @covers Freezer\Freezer::isDirty
     */
    public function testDirtyObjectIsRecognizedAsBeingDirty3()
    {
        $object = new \A(3, 2, 1);
        $object->__freezer['hash'] = 'a6efdb77cb879e26cf30635156cf045a7e7f9564';

        $this->assertTrue($this->freezer->isDirty($object, true));
        $this->assertFalse($this->freezer->isDirty($object));
    }

    /**
     * @covers            Freezer\Freezer::isDirty
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfNotAnObjectIsPassedAsFirstArgumentToIsDirty()
    {
        $this->freezer->isDirty(null);
    }

    /**
     * @covers            Freezer\Freezer::isDirty
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfNotABooleanIsPassedAsSecondArgumentToIsDirty()
    {
        $this->freezer->isDirty(new \StdClass, null);
    }

    /**
     * @covers Freezer\Freezer::readAttributes
     */
    public function testAttributesOfAnObjectCanBeRead()
    {
        $this->assertEquals(
          array('a' => 1, 'b' => 2, 'c' => 3),
          $this->freezer->readAttributes(new \A(1, 2, 3))
        );
    }

    /**
     * @covers  Freezer\Freezer::readAttributes
     * @depends testAttributesOfAnObjectCanBeRead
     */
    public function testAttributesOfAnObjectWithAggregatedObjectCanBeRead()
    {
        $this->assertEquals(
          array('a' => new \A(1, 2, 3)),
          $this->freezer->readAttributes(new \B)
        );
    }

    /**
     * @covers  Freezer\Freezer::readAttributes
     * @covers  Freezer\Freezer::setAttributeFilter
     * @depends testAttributesOfAnObjectCanBeRead
     */
    public function testAttributeFilterFiltersResults()
    {
        $this->freezer->setAttributeFilter(function($name, $value){
          if ($name === 'a') {
            return false;
          }
          return true;
        });

        $this->assertEquals(
          array('b' => 2, 'c' => 3),
          $this->freezer->readAttributes(new \A(1, 2, 3))
        );
    }

    /**
     * @covers            Freezer\Freezer::readAttributes
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfNotAnObjectIsPassedToReadAttributes()
    {
        $this->freezer->readAttributes(null);
    }
}
