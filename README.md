This is my attempt to make a microframework for web application.
Actually it is more like template for web application with authorization and ACL ready for use.
In this template every controller is a class which should be available as a service in Zend Service Manager.
Since URLs are parsed as /controller/action and controller is name of service in
ZSM, you should alias your class name to get nice URL.
The division into a set of micro-services makes testing relatively simple.
This template includes blackbox testing (which tests service using PHP builtin
we server) and regular unit tests which test class as service in ZSM.
