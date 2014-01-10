<?php
class Service {
	public $value;
}
class Invokable {
	public function __invoke($value=null) {
		$service = new Service;
		$service->value = $value;
		return $service;
	}
}
class NonInvokable {
	public function __call($name, $arguments) {}
}

// PHPUnit autoloader file
require '../Wart.php';
require 'PHPUnit/Autoload.php';
