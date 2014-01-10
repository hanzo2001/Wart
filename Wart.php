<?php
/*
 * Copyright (c) 2009-2013 Fabien Potencier
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
/*
 * Wart (Pimple got serious)
 * 
 * Another Dependency injection container, cooler than DIC with IoC on top
 */
namespace Wart;

use ArrayAccess;
use Closure;
use SplObjectStorage;
use SplFixedArray;
use RuntimeException;
use InvalidArgumentException;

class Wart implements ArrayAccess {
	const VALUE  = 0;
	const IS_OBJ = 1;
	const IS_INV = 2;
	const IS_FRZ = 3;
	private $factory;
	private $protect;
	private $v;
	public function __construct(array $values=array()) {
		$this->factory = new SplObjectStorage;
		$this->protect = new SplObjectStorage;
		foreach ($values as $id => $value) $this->offsetSet($id,$value);
	}
	public function offsetExists($id) {
		return isset($this->v[$id]);
	}
	public function offsetGet($id) {
		$this->assertId($id);
		$v = &$this->v[$id];
		$value = $v[self::VALUE];
		if (
			!$v[self::IS_OBJ]
			|| $v[self::IS_FRZ]
			|| $this->protect->contains($value)
			|| !$v[self::IS_INV]
		) {
			return $value;
		}
		if ($this->factory->contains($value)) return $value($this);
		$v[self::IS_FRZ] = $value;
		$v[self::VALUE]  = $value = $value($this);
		$v[self::IS_OBJ] = \is_object($value);
		return $value;
	}
	public function offsetSet($id, $value) {
		if (isset($this->v[$id]) && $this->v[$id][self::IS_FRZ]) {
			throw new RuntimeException("Cannot override frozen service `$id`.");
		}
		$this->v[$id] = SplFixedArray::fromArray(array(
				$value,
				$o = \is_object($value),
				$o && \method_exists($value,'__invoke'),
				null
		),false);
	}
	public function offsetUnset($id) {
		if (!isset($this->v[$id])) return;
		if ($this->v[$id][self::IS_OBJ]) {
			$this->factory->detach($this->v[$id][self::VALUE]);
			$this->protect->detach($this->v[$id][self::VALUE]);
		}
		unset($this->v[$id]);
	}
	public function protect($invocable) {
		$this->assertInvocation($invocable,'Callable');
		$this->protect->attach($invocable);
		return $invocable;
	}
	public function factory($invocable) {
		$this->assertInvocation($invocable,'Service definition');
		$this->factory->attach($invocable);
		return $invocable;
	}
	public function extend($id, $callable) {
		$this->assertId($id);
		$this->assertInvocation($this->v[$id][self::VALUE],"Identifier `$id`");
		$this->assertInvocation($callable,'Extension service definition');
		$service = $this->v[$id][self::VALUE];
		$extended = function ($c) use ($callable,$service) {
			return $callable($service($c),$c);
		};
		if ($this->factory->contains($service)) {
			$this->factory->detach($service);
			$this->factory->attach($extended);
		}
		$this->offsetSet($id,$extended);
		return $extended;
	}
	public function raw($id) {
		$this->assertId($id);
		return $this->v[$id][self::IS_FRZ] ? $this->v[$id][self::IS_FRZ] : $this->v[$id][self::VALUE];
	}
	public function keys() {
		return \array_keys($this->v);
	}
	public function share($share) {
		return $share;
	}
	private function assertId($id) {
		if (isset($this->v[$id])) return;
		throw new InvalidArgumentException("Identifier `$id` is not defined.");
	}
	private function assertInvocation($invocable, $type) {
		if (\method_exists($invocable,'__invoke')) return;
		if ($invocable instanceof Closure) return;
		throw new InvalidArgumentException($type.' is not a Closure or invokable object.');
	}
}
