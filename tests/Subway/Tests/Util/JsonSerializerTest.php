<?php

/*
 * This file is part of the Subway package.
 *
 * (c) Eymen Gunay <eymen@egunay.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Subway\Tests\Util;

use Subway\Util\JsonSerializer;

/**
 * Json serializer test
 */
class JsonSerializerTest extends \PHPUnit_Framework_TestCase 
{

    /**
     * Serializer instance
     *
     * @var Subway\Util\JsonSerializer
     */
    protected $serializer;

    /**
     * Test case setup
     *
     * @return void
     */
    public function setUp() {
        parent::setUp();
        $this->serializer = new JsonSerializer();
    }

    /**
     * Test serialization of scalar values
     *
     * @dataProvider scalarData
     * @param mixed $scalar
     * @param strign $jsoned
     * @return void
     */
    public function testSerializeScalar($scalar, $jsoned) {
        $this->assertSame($jsoned, $this->serializer->serialize($scalar));
    }

    /**
     * Test unserialization of scalar values
     *
     * @dataProvider scalarData
     * @param mixed $scalar
     * @param strign $jsoned
     * @return void
     */
    public function testUnserializeScalar($scalar, $jsoned) {
        $this->assertSame($scalar, $this->serializer->unserialize($jsoned));
    }

    /**
     * List of scalar data
     *
     * @return array
     */
    public function scalarData() {
        return array(
            array('testing', '"testing"'),
            array(123, '123'),
            array(0, '0'),
            array(0.0, '0.0'),
            array(17.0, '17.0'),
            array(17e1, '170.0'),
            array(17.2, '17.2'),
            array(true, 'true'),
            array(false, 'false'),
            array(null, 'null')
        );
    }

    /**
     * Test the serialization of resources
     *
     * @return void
     */
    public function testSerializeResource() {
        $this->setExpectedException('Subway\Exception\SubwayException');
        $this->serializer->serialize(fopen(__FILE__, 'r'));
    }

    /**
     * Test the serialization of closures
     *
     * @return void
     */
    public function testSerializeClosure() {
        $this->setExpectedException('Subway\Exception\SubwayException');
        $this->serializer->serialize(array('func' => function() {
            echo 'whoops';
        }));
    }

    /**
     * Test serialization of array without objects
     *
     * @dataProvider arrayNoObjectData
     * @param array $array
     * @param strign $jsoned
     * @return void
     */
    public function testSerializeArrayNoObject($array, $jsoned) {
        $this->assertSame($jsoned, $this->serializer->serialize($array));
    }

    /**
     * Test unserialization of array without objects
     *
     * @dataProvider arrayNoObjectData
     * @param array $array
     * @param strign $jsoned
     * @return void
     */
    public function testUnserializeArrayNoObject($array, $jsoned) {
        $this->assertSame($array, $this->serializer->unserialize($jsoned));
    }

    /**
     * List of array data
     *
     * @return array
     */
    public function arrayNoObjectData() {
        return array(
            array(array(1, 2, 3), '[1,2,3]'),
            array(array(1, 'abc', false), '[1,"abc",false]'),
            array(array('a' => 1, 'b' => 2, 'c' => 3), '{"a":1,"b":2,"c":3}'),
            array(array('integer' => 1, 'string' => 'abc', 'bool' => false), '{"integer":1,"string":"abc","bool":false}'),
            array(array(1, array('nested')), '[1,["nested"]]'),
            array(array('integer' => 1, 'array' => array('nested')), '{"integer":1,"array":["nested"]}'),
            array(array('integer' => 1, 'array' => array('nested' => 'object')), '{"integer":1,"array":{"nested":"object"}}'),
            array(array(1.0, 2, 3e1), '[1.0,2,30.0]'),
        );
    }

    /**
     * Test serialization of objects
     *
     * @return void
     */
    public function testSerializeObject() {
        $obj = new \stdClass();
        $this->assertSame('{"@type":"stdClass"}', $this->serializer->serialize($obj));

        $obj = $empty = new EmptyClass();
        $this->assertSame('{"@type":"Subway\\\\Tests\\\\Util\\\\EmptyClass"}', $this->serializer->serialize($obj));

        $obj = new AllVisibilities();
        $expected = '{"@type":"Subway\\\\Tests\\\\Util\\\\AllVisibilities","pub":"this is public","prot":"protected","priv":"dont tell anyone"}';
        $this->assertSame($expected, $this->serializer->serialize($obj));

        $obj->pub = 'new value';
        $expected = '{"@type":"Subway\\\\Tests\\\\Util\\\\AllVisibilities","pub":"new value","prot":"protected","priv":"dont tell anyone"}';
        $this->assertSame($expected, $this->serializer->serialize($obj));

        $obj->pub = $empty;
        $expected = '{"@type":"Subway\\\\Tests\\\\Util\\\\AllVisibilities","pub":{"@type":"Subway\\\\Tests\\\\Util\\\\EmptyClass"},"prot":"protected","priv":"dont tell anyone"}';
        $this->assertSame($expected, $this->serializer->serialize($obj));

        $array = array('instance' => $empty);
        $expected = '{"instance":{"@type":"Subway\\\\Tests\\\\Util\\\\EmptyClass"}}';
        $this->assertSame($expected, $this->serializer->serialize($array));

        $obj = new \stdClass();
        $obj->total = 10.0;
        $obj->discount = 0.0;
        $expected = '{"@type":"stdClass","total":10.0,"discount":0.0}';
        $this->assertSame($expected, $this->serializer->serialize($obj));
    }

    /**
     * Test unserialization of objects
     *
     * @return void
     */
    public function testUnserializeObjects() {
        $serialized = '{"@type":"stdClass"}';
        $obj = $this->serializer->unserialize($serialized);
        $this->assertInstanceOf('stdClass', $obj);

        $serialized = '{"@type":"Subway\\\\Tests\\\\Util\\\\EmptyClass"}';
        $obj = $this->serializer->unserialize($serialized);
        $this->assertInstanceOf('Subway\Tests\Util\EmptyClass', $obj);

        $serialized = '{"@type":"Subway\\\\Tests\\\\Util\\\\AllVisibilities","pub":{"@type":"Subway\\\\Tests\\\\Util\\\\EmptyClass"},"prot":"protected","priv":"dont tell anyone"}';
        $obj = $this->serializer->unserialize($serialized);
        $this->assertInstanceOf('Subway\Tests\Util\AllVisibilities', $obj);
        $this->assertInstanceOf('Subway\Tests\Util\EmptyClass', $obj->pub);
        $this->assertAttributeSame('protected', 'prot', $obj);
        $this->assertAttributeSame('dont tell anyone', 'priv', $obj);

        $serialized = '{"instance":{"@type":"Subway\\\\Tests\\\\Util\\\\EmptyClass"}}';
        $array = $this->serializer->unserialize($serialized);
        $this->assertTrue(is_array($array));
        $this->assertInstanceOf('Subway\Tests\Util\EmptyClass', $array['instance']);
    }

    /**
     * Test magic serialization methods
     *
     * @return void
     */
    public function testSerializationMagicMethods() {
        $obj = new MagicClass();
        $serialized = '{"@type":"Subway\\\\Tests\\\\Util\\\\MagicClass","show":true}';
        $this->assertSame($serialized, $this->serializer->serialize($obj));
        $this->assertFalse($obj->woke);

        $obj = $this->serializer->unserialize($serialized);
        $this->assertTrue($obj->woke);
    }

    /**
     * Test serialization of DateTime classes
     *
     * Some interal classes, such as DateTime, cannot be initialized with
     * ReflectionClass::newInstanceWithoutConstructor()
     *
     * @return void
     */
    public function testSerializationOfDateTime() {
        $date = new \DateTime('2014-06-15 12:00:00', new \DateTimeZone('UTC'));
        $obj = $this->serializer->unserialize($this->serializer->serialize($date));
        $this->assertSame($date->getTimestamp(), $obj->getTimestamp());
    }

    /**
     * Test unserialize of unknown class
     *
     * @return void
     */
    public function testUnserializeUnknownClass() {
        $this->setExpectedException('Subway\Exception\SubwayException');
        $serialized = '{"@type":"UnknownClass"}';
        $this->serializer->unserialize($serialized);
    }

    /**
     * Test serialization of undeclared properties
     *
     * @return void
     */
    public function testSerializationUndeclaredProperties() {
        $obj = new \stdClass();
        $obj->param1 = true;
        $obj->param2 = 'store me, please';
        $serialized = '{"@type":"stdClass","param1":true,"param2":"store me, please"}';
        $this->assertSame($serialized, $this->serializer->serialize($obj));

        $obj2 = $this->serializer->unserialize($serialized);
        $this->assertInstanceOf('stdClass', $obj2);
        $this->assertTrue($obj2->param1);
        $this->assertSame('store me, please', $obj2->param2);

        $serialized = '{"@type":"stdClass","sub":{"@type":"stdClass","key":"value"}}';
        $obj = $this->serializer->unserialize($serialized);
        $this->assertInstanceOf('stdClass', $obj->sub);
        $this->assertSame('value', $obj->sub->key);
    }

    /**
     * Test serialize with recursion
     *
     * @return void
     */
    public function testSerializeRecursion() {
        $c1 = new \stdClass();
        $c1->c2 = new \stdClass();
        $c1->c2->c3 = new \stdClass();
        $c1->c2->c3->c1 = $c1;
        $c1->something = 'ok';
        $c1->c2->c3->ok = true;

        $expected = '{"@type":"stdClass","c2":{"@type":"stdClass","c3":{"@type":"stdClass","c1":{"@type":"@0"},"ok":true}},"something":"ok"}';
        $this->assertSame($expected, $this->serializer->serialize($c1));

        $c1 = new \stdClass();
        $c1->mirror = $c1;
        $expected = '{"@type":"stdClass","mirror":{"@type":"@0"}}';
        $this->assertSame($expected, $this->serializer->serialize($c1));
    }

    /**
     * Test unserialize with recursion
     *
     * @return void
     */
    public function testUnserializeRecursion() {
        $serialized = '{"@type":"stdClass","c2":{"@type":"stdClass","c3":{"@type":"stdClass","c1":{"@type":"@0"},"ok":true}},"something":"ok"}';
        $obj = $this->serializer->unserialize($serialized);
        $this->assertTrue($obj->c2->c3->ok);
        $this->assertSame($obj, $obj->c2->c3->c1);
        $this->assertNotSame($obj, $obj->c2);

        $serialized = '{"@type":"stdClass","c2":{"@type":"stdClass","c3":{"@type":"stdClass","c1":{"@type":"@0"},"c2":{"@type":"@1"},"c3":{"@type":"@2"}},"c3_copy":{"@type":"@2"}}}';
        $obj = $this->serializer->unserialize($serialized);
        $this->assertSame($obj, $obj->c2->c3->c1);
        $this->assertSame($obj->c2, $obj->c2->c3->c2);
        $this->assertSame($obj->c2->c3, $obj->c2->c3->c3);
        $this->assertSame($obj->c2->c3_copy, $obj->c2->c3);
    }

}

/**
 * All visibilities class
 */
class AllVisibilities 
{
    public $pub = 'this is public';
    protected $prot = 'protected';
    private $priv = 'dont tell anyone';

}

/**
 * Empty class
 */
class EmptyClass
{
}

/**
 * Magic class
 */
class MagicClass
{

    public $show = true;
    public $hide = true;
    public $woke = false;

    public function __sleep() {
        return array('show');
    }

    public function __wakeup() {
        $this->woke = true;
    }

}