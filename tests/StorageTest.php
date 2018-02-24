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

/**
 * Tests for the Freezer\Storage class.
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
class StorageTest extends \PHPUnit\Framework\TestCase
{
    protected $stub;

    protected function setUp()
    {
        $this->stub = $this->getMockForAbstractClass('Freezer\\Storage');
    }

    protected function tearDown()
    {
        $this->stub = null;
    }

    /**
     * @covers Freezer\Storage::__construct
     * @covers Freezer\Storage::__getFreezer
     * @covers Freezer\Storage::__setUseLazyLoad
     * @covers Freezer\Storage::__getUseLazyLoad
     */
    public function testConstructorWithDefaultArguments()
    {
        $this->assertInstanceOf(
          'Freezer\\Freezer', $this->stub->getFreezer()
        );
        $this->assertSame(false, $this->stub->getUseLazyLoad());
    }

    /**
     * @covers            Freezer\Storage::setUseLazyLoad
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfNonBooleanIsPassedAsArgumentToSetUseLazyLoad()
    {
        $this->stub->setUseLazyLoad(null);
    }

    /**
     * @covers            Freezer\Storage::store
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfNotAnObjectIsPassedAsArgumentToStore()
    {
        $this->stub->store(null);
    }

    /**
     * @covers            Freezer\Storage::fetch
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownIfNotAStringIsPassedAsArgumentToFetch()
    {
        $this->stub->fetch(null);
    }
}
