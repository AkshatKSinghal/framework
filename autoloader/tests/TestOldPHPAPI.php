<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Defines the test cases for TestOldPHPAPI
 *
 * PHP version 5
 *
 * LICENSE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.
 * If not, see <http://php-autoloader.malkusch.de/en/license/>.
 *
 * @category   PHP
 * @package    Autoloader
 * @subpackage Test
 * @author     Markus Malkusch <markus@malkusch.de>
 * @copyright  2009 - 2010 Markus Malkusch
 * @license    http://php-autoloader.malkusch.de/en/license/ GPL 3
 * @version    SVN: $Id$
 * @link       http://php-autoloader.malkusch.de/en/
 */

/**
 * The Autoloader is used for class loading.
 */
require_once __DIR__ . "/../autoloader.php";

/**
 * Tests OldPHPAPI
 *
 * @category   PHP
 * @package    Autoloader
 * @subpackage Test
 * @author     Markus Malkusch <markus@malkusch.de>
 * @license    http://php-autoloader.malkusch.de/en/license/ GPL 3
 * @version    Release: 1.12
 * @link       http://php-autoloader.malkusch.de/en/
 * @see        OldPHPAPI
 */
class TestOldPHPAPI extends PHPUnit_Framework_TestCase
{

    /**
     * Asserts that a function is implemented and returns the expected result
     *
     * @param String $function The function name
     * @param Array  $params   The parameters
     * @param Mixed  $result   The expected result
     *
     * @dataProvider provideTestCheckAPI
     * @return void
     */
    public function testCheckAPI($function, Array $params, $result)
    {
        $api = new OldPHPAPI_Test();
        $api->checkAPI();

        $this->assertTrue(
            function_exists($function),
            "Function $function is not defined."
        );

        $reflection = new ReflectionFunction($function);
        $this->assertEquals($result, $reflection->invokeArgs($params));
    }

    /**
     * Provides test cases for testCheckAPI()
     *
     * A test case is a function name, a list of parameters and the expected result.
     *
     * @see testCheckAPI()
     * @return Array
     */
    public function provideTestCheckAPI()
    {
        return array(
            array('test_function_no_parameters',   array(),     true),
            array('test_function_with_parameters', array(1, 2), 3)
        );
    }

}