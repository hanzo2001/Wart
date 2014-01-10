<?php
/*
 * This file was shamelessly repurposed from
 * https://github.com/fabpot/Pimple/blob/master/tests/Pimple/Tests/PimpleTest.php
 * he has the license for this
 */
use Wart\Wart;
class WartTest extends \PHPUnit_Framework_TestCase {
	public function testWithString() {
		$wart = new Wart();
		$wart['param'] = 'value';
		$this->assertEquals('value',$wart['param']);
	}
	public function testWithClosure() {
		$wart = new Wart();
		$wart['service'] = function () {
			return new Service();
		};
		$this->assertInstanceOf('Service',$wart['service']);
	}
	public function testServicesShouldBeDifferent() {
		$wart = new Wart();
		$wart['service'] = $wart->factory(function () {
			return new Service();
		});
		$serviceOne = $wart['service'];
		$this->assertInstanceOf('Service',$serviceOne);
		$serviceTwo = $wart['service'];
		$this->assertInstanceOf('Service',$serviceTwo);
		$this->assertNotSame($serviceOne,$serviceTwo);
	}
	public function testShouldPassContainerAsParameter() {
		$wart = new Wart();
		$wart['service'] = function () {
			return new Service();
		};
		$wart['container'] = function ($container) {
			return $container;
		};
		$this->assertNotSame($wart,$wart['service']);
		$this->assertSame($wart,$wart['container']);
	}
	public function testIsset() {
		$wart = new Wart();
		$wart['param'] = 'value';
		$wart['service'] = function () {
			return new Service();
		};
		$wart['null'] = null;
		$this->assertTrue(isset($wart['param']));
		$this->assertTrue(isset($wart['service']));
		$this->assertTrue(isset($wart['null']));
		$this->assertFalse(isset($wart['non_existent']));
	}
	public function testConstructorInjection() {
		$params = array("param" => "value");
		$wart = new Wart($params);
		$this->assertSame($params['param'],$wart['param']);
	}
	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Identifier `foo` is not defined.
	 */
	public function testOffsetGetValidatesKeyIsPresent() {
		$wart = new Wart();
		echo $wart['foo'];
	}
	public function testOffsetGetHonorsNullValues() {
		$wart = new Wart();
		$wart['foo'] = null;
		$this->assertNull($wart['foo']);
	}
	public function testUnset() {
		$wart = new Wart();
		$wart['param'] = 'value';
		$wart['service'] = function () {
			return new Service();
		};
		unset($wart['param'],$wart['service']);
		$this->assertFalse(isset($wart['param']));
		$this->assertFalse(isset($wart['service']));
	}
	/**
	 * @dataProvider serviceDefinitionProvider
	 */
	public function testShare($service) {
		$wart = new Wart();
		$wart['shared_service'] = $service;
		$serviceOne = $wart['shared_service'];
		$this->assertInstanceOf('Service',$serviceOne);
		$serviceTwo = $wart['shared_service'];
		$this->assertInstanceOf('Service',$serviceTwo);
		$this->assertSame($serviceOne,$serviceTwo);
	}
	/**
	 * @dataProvider serviceDefinitionProvider
	 */
	public function testProtect($service) {
		$wart = new Wart();
		$wart['protected'] = $wart->protect($service);
		$this->assertSame($service,$wart['protected']);
	}
	public function testGlobalFunctionNameAsParameterValue() {
		$wart = new Wart();
		$wart['global_function'] = 'strlen';
		$this->assertSame('strlen',$wart['global_function']);
	}
	public function testRaw() {
		$wart = new Wart();
		$wart['service'] = $definition = $wart->factory(function () { return 'foo'; });
		$this->assertSame($definition,$wart->raw('service'));
	}
	public function testRawHonorsNullValues() {
		$wart = new Wart();
		$wart['foo'] = null;
		$this->assertNull($wart->raw('foo'));
	}
	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Identifier `foo` is not defined.
	 */
	public function testRawValidatesKeyIsPresent() {
		$wart = new Wart();
		$wart->raw('foo');
	}
	/**
	 * @dataProvider serviceDefinitionProvider
	 */
	public function testExtend($service) {
		$wart = new Wart();
		$wart['shared_service'] = function () {
			return new Service();
		};
		$wart['factory_service'] = $wart->factory(function () {
			return new Service();
		});
		$wart->extend('shared_service',$service);
		$serviceOne = $wart['shared_service'];
		$this->assertInstanceOf('Service',$serviceOne);
		$serviceTwo = $wart['shared_service'];
		$this->assertInstanceOf('Service',$serviceTwo);
		$this->assertSame($serviceOne,$serviceTwo);
		$this->assertSame($serviceOne->value,$serviceTwo->value);
		$wart->extend('factory_service',$service);
		$serviceOne = $wart['factory_service'];
		$this->assertInstanceOf('Service',$serviceOne);
		$serviceTwo = $wart['factory_service'];
		$this->assertInstanceOf('Service',$serviceTwo);
		$this->assertNotSame($serviceOne,$serviceTwo);
		$this->assertNotSame($serviceOne->value,$serviceTwo->value);
	}
	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Identifier `foo` is not defined.
	 */
	public function testExtendValidatesKeyIsPresent() {
		$wart = new Wart();
		$wart->extend('foo',function () {});
	}
	public function testKeys() {
		$wart = new Wart();
		$wart['foo'] = 123;
		$wart['bar'] = 123;
		$this->assertEquals(array('foo','bar'),$wart->keys());
	}
	/** @test */
	public function settingAnInvokableObjectShouldTreatItAsFactory() {
		$wart = new Wart();
		$wart['invokable'] = new Invokable();
		$this->assertInstanceOf('Service',$wart['invokable']);
	}
	/** @test */
	public function settingNonInvokableObjectShouldTreatItAsParameter() {
		$wart = new Wart();
		$wart['non_invokable'] = new NonInvokable();
		$this->assertInstanceOf('NonInvokable',$wart['non_invokable']);
	}
	/**
	 * @dataProvider badServiceDefinitionProvider
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Service definition is not a Closure or invokable object.
	 */
	public function testFactoryFailsForInvalidServiceDefinitions($service) {
		$wart = new Wart();
		$wart->factory($service);
	}
	/**
	 * @dataProvider badServiceDefinitionProvider
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Callable is not a Closure or invokable object.
	 */
	public function testProtectFailsForInvalidServiceDefinitions($service) {
		$wart = new Wart();
		$wart->protect($service);
	}
	/**
	 * @dataProvider badServiceDefinitionProvider
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Identifier `foo` is not a Closure or invokable object.
	 * Identifier `foo` does not contain an object definition.
	 */
	public function testExtendFailsForKeysNotContainingServiceDefinitions($service) {
		$wart = new Wart();
		$wart['foo'] = $service;
		$wart->extend('foo',function () {});
	}
	/**
	 * @dataProvider badServiceDefinitionProvider
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Extension service definition is not a Closure or invokable object.
	 */
	public function testExtendFailsForInvalidServiceDefinitions($service) {
		$wart = new Wart();
		$wart['foo'] = function () {};
		$wart->extend('foo',$service);
	}
	/**
	 * Provider for invalid service definitions
	 */
	public function badServiceDefinitionProvider() {
		return array(
		  array(123),
		  array(new NonInvokable())
		);
	}
	/**
	 * Provider for service definitions
	 */
	public function serviceDefinitionProvider() {
		return array(
			array(function ($value) {
				$service = new Service();
				$service->value = $value;
				return $service;
			}),
			array(new Invokable())
		);
	}
	public function testDefiningNewServiceAfterFreeze() {
		$wart = new Wart();
		$wart['foo'] = function () {
			return 'foo';
		};
		$foo = $wart['foo'];
		$wart['bar'] = function () {
			return 'bar';
		};
		$this->assertSame('bar',$wart['bar']);
	}
	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Cannot override frozen service `foo`.
	 */
	public function testOverridingServiceAfterFreeze() {
		$wart = new Wart();
		$wart['foo'] = function () {
			return 'foo';
		};
		$foo = $wart['foo'];
		$wart['foo'] = function () {
			return 'bar';
		};
	}
	public function testRemovingServiceAfterFreeze() {
		$wart = new Wart();
		$wart['foo'] = function () {
			return 'foo';
		};
		$foo = $wart['foo'];
		unset($wart['foo']);
		$wart['foo'] = function () {
			return 'bar';
		};
		$this->assertSame('bar',$wart['foo']);
	}
	public function testExtendingService() {
		$wart = new Wart();
		$wart['foo'] = function () {
			return 'foo';
		};
		$wart['foo'] = $wart->extend('foo',function ($foo,$app) {
			return "$foo.bar";
		});
		$wart['foo'] = $wart->extend('foo',function ($foo,$app) {
			return "$foo.baz";
		});
		$this->assertSame('foo.bar.baz',$wart['foo']);
	}
	public function testExtendingServiceAfterOtherServiceFreeze() {
		$wart = new Wart();
		$wart['foo'] = function () {
			return 'foo';
		};
		$wart['bar'] = function () {
			return 'bar';
		};
		$foo = $wart['foo'];
		$wart['bar'] = $wart->extend('bar',function ($bar,$app) {
			return "$bar.baz";
		});
		$this->assertSame('bar.baz',$wart['bar']);
	}
}
