<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * PHP Version 4
 *
 * Copyright (c) 2002-2005, Sebastian Bergmann <sb@sebastian-bergmann.de>.
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
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRIC
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Testing
 * @package    PHPUnit
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2005 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    CVS: $Id: RepeatedTest.php,v 1.13 2005/11/10 09:47:14 sebastian Exp $
 * @link       http://pear.php.net/package/PHPUnit
 * @since      File available since Release 1.0.0
 */

require_once 'PHPUnit/TestDecorator.php';

/**
 * A Decorator that runs a test repeatedly.
 *
 * Here is an example:
 *
 * <code>
 * <?php
 * require_once 'PHPUnit.php';
 * require_once 'PHPUnit/RepeatedTest.php';
 *
 * class MathTest extends PHPUnit_TestCase {
 *     var $fValue1;
 *     var $fValue2;
 *
 *     function MathTest($name) {
 *         $this->PHPUnit_TestCase($name);
 *     }
 *
 *     function setUp() {
 *         $this->fValue1 = 2;
 *         $this->fValue2 = 3;
 *     }
 *
 *     function testAdd() {
 *         $this->assertTrue($this->fValue1 + $this->fValue2 == 5);
 *     }
 * }
 *
 * $suite = new PHPUnit_TestSuite;
 *
 * $suite->addTest(
 *   new PHPUnit_RepeatedTest(
 *     new MathTest('testAdd'),
 *     10
 *   )
 * );
 *
 * $result = PHPUnit::run($suite);
 * print $result->toString();
 * ?>
 * </code>
 *
 * @category   Testing
 * @package    PHPUnit
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2005 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/PHPUnit
 * @since      Class available since Release 1.0.0
 */
class PHPUnit_RepeatedTest extends PHPUnit_TestDecorator {
    /**
     * @var    integer
     * @access private
     */
    var $_timesRepeat = 1;

    /**
     * Constructor.
     *
     * @param  object
     * @param  integer
     * @access public
     */
    function PHPUnit_RepeatedTest(&$test, $timesRepeat = 1) {
        $this->PHPUnit_TestDecorator($test);
        $this->_timesRepeat = $timesRepeat;
    }

    /**
     * Counts the number of test cases that
     * will be run by this test.
     *
     * @return integer
     * @access public
     */
    function countTestCases() {
        return $this->_timesRepeat * $this->_test->countTestCases();
    }

    /**
     * Runs the decorated test and collects the
     * result in a TestResult.
     *
     * @param  object
     * @access public
     * @abstract
     */
    function run(&$result) {
        for ($i = 0; $i < $this->_timesRepeat; $i++) {
            $this->_test->run($result);
        }
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>
