Wart
====

Wart is an ugly and small Dependency Injection Container for PHP 5.3
that consists of just one file and one class (more than 80 lines of code
for sure).

`Download it`_, require it in your code, and you're good to go

.. code-block:: php

    require_once '/path/to/Wart.php';

or, if you like namespaces like me and you are not using a simple 
autoloader

.. code-block:: php

    require_once '/path/to/Wart/Wart.php';

Creating a container is a matter of instating the ``Wart`` class

.. code-block:: php

    $container = new \Wart();

As many other dependency injection containers, Wart is able to manage two
different kinds of data: *services* and *parameters*.

Defining Parameters
-------------------

Defining a parameter is as simple as using the Wart instance as an array. This
has some caveats but keep on reading (just avoid setting invocable objects in 
this manner, those have special behaviour)

.. code-block:: php

    // define some parameters
    $container['cookie_name'] = 'SESSION_ID';
    $container['session_storage_class'] = 'SessionStorage';

Defining Services
-----------------

A service is an object that does something as part of a larger system.
Examples of services: Database connection, templating engine, mailer. Almost
any object could be a service.

Services are defined by anonymous functions that return an instance of an
object

.. code-block:: php

    // define some services. Any invocable object is considered a service
    $container['session_storage'] = function ($c) {
        return new $c['session_storage_class']($c['cookie_name']);
    };
    // look!, another invocable
    $container['session'] = function ($c) {
        return new Session($c['session_storage']);
    };
    // if you're not sure just run the following test
    // echo \method_exists($obj,'__invoke()') ? '( ^^)d' : '( ¡¡)p';

Notice that the anonymous function has access to the current container
instance, allowing references to other services or parameters.

As objects are only created when you get them, the order of the definitions
does not matter, and there is no performance penalty.

Using the defined service is also very easy

.. code-block:: php

    // get the session object
    $session = $container['session'];

    // the above call is roughly equivalent to the following code:
    // $storage = new SessionStorage('SESSION_ID');
    // $session = new Session($storage);

Protecting Parameters
---------------------

Because Wart sees anonymous functions as service definitions, you need to
wrap anonymous functions with the ``protect()`` method to store them as a
parameter. The magic method ``__invoke`` is what marks objects as possible
service deifinitions. Objects of class ``\Closure`` implement ``__invoke``
and that's what makes them so magical

.. code-block:: php

    $container['random'] = $container->protect(function () { return rand(); });

Modifying services after creation
---------------------------------

In some cases you may want to modify a service definition after it has been
defined. You can use the ``extend()`` method to define additional code to
be run on your service just after it is created. **BUT REMEMBER:** once you
start using a service it becomes frozen, and will throw a ``\RuntTimeException``
**in your face!** (bad Wart, bad!)

.. code-block:: php

    $container['mail'] = function ($c) {
        return new \Zend_Mail();
    };
    // you don't need to set the offset with the result, the container 
    // does this for you
    $container->extend('mail', function($mail, $c) {
        // equivalent to: $mail = $c['mail'];
        $mail->setFrom($c['mail.default_from']);
        return $mail;
    });

The first argument is the name of the object, the second is a function that
can should have 2 parameters: 1) access to the object instance, 2) the container.

Fetching the service creation function
--------------------------------------

When you access an object, Wart automatically calls the anonymous function
that you defined, which creates the service object for you. If you want to get
raw access to this function, you can use the ``raw()`` method.

In other words, ``\Wart`` pops but we keep your stuff intact, somewhere... raw.
So if you need your stuff back for some reason...

.. code-block:: php

    $container['session'] = function ($c) {
        return new Session($c['session_storage']);
    };
    // this will cause the service to become frozen
    $somePreviousCall = $container['session'];
    // this way you are guaranteed to get the same instance over and over again
    $someOtherCall = $container['session'];
    // in the event that you need to recover the gunk that defined the puss
    $sessionFunction = $container->raw('session');

Packaging a Container for reusability
-------------------------------------

If you use the same libraries over and over, you might want to create reusable
containers. Creating a reusable container is as simple as creating a class that 
extends ``Wart``, and configuring it in the constructor

.. code-block:: php

    class UglyVerruca extends \Wart {
        public function __construct() {
            // don't forget the constructor, it's mandatory for \Wart to squirt
            parent::__construct();
            // you may safely add anything you want afterwards
            $this['parameter'] = 'foo';
            $this['object'] = function () { return stdClass(); };
        }
    }

Using this container from your own is as easy as it can get

.. code-block:: php

    $container = new \Wart();

    // define your project parameters and services
    // ...

    // embed the SomeContainer container
    $container['grafted'] = function () {
        return new namespace\to\UglyVerruca();
    };

    // configure it
    $container['grafted']['parameter'] = 'bar';

    // use it
    $container['grafted']['object']->...;

Defining Factory Services
-------------------------

By default, each time you get a service, Wart returns the **same instance**
of it. If you want a different instance to be returned for all calls, wrap your
anonymous function with the ``factory()`` method

.. code-block:: php

    // do it this way, set the offset with the result of factory()
    $container['session'] = $container->factory(function ($c) {
        return new Session($c['session_storage']);
    });

.. _Download Fabien Potencier's original masterpiece at: https://github.com/fabpot/Wart

.. _Download my ugly \Wart at: not loaded yet
