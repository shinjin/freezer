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
 * Abstract base class for Object_Freezer_Storage_CouchDB test case classes.
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
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected $freezer;
    protected $storage;

    /**
     * @covers Freezer\Storage\CouchDB::__construct
     * @covers Freezer\Storage\CouchDB::setUseLazyLoad
     */
    protected function setUp()
    {
        if (!@fsockopen(FREEZER_COUCHDB_HOST, FREEZER_COUCHDB_PORT, $errno, $errstr)) {
            $this->markTestSkipped(
              sprintf(
                'CouchDB not running on %s:%d.',
                FREEZER_COUCHDB_HOST,
                FREEZER_COUCHDB_PORT
              )
            );
        }

        $this->freezer = $this->getMockBuilder('Freezer\\Freezer')
            ->setMethods(array('generateId'))
            ->getMock();

        $this->freezer->expects($this->any())
                      ->method('generateId')
                      ->will($this->onConsecutiveCalls('a', 'b', 'c'));

        $this->storage = new CouchDB(
          'test',
          $this->freezer,
          $this->useLazyLoad,
          FREEZER_COUCHDB_HOST,
          (int)FREEZER_COUCHDB_PORT
        );

        $this->storage->send('PUT', '/test');
    }

    protected function tearDown()
    {
        if ($this->storage !== null) {
            $this->storage->send('DELETE', '/test/');
        }

        $this->freezer = null;
        $this->storage = null;
    }

    protected function getFrozenObjectFromStorage($id)
    {
        $buffer = $this->storage->send('GET', '/test/' . $id);
        $buffer = $buffer['body'];

        $frozenObject = json_decode($buffer, true);
        unset($frozenObject['_rev']);

        return $frozenObject;
    }
}
