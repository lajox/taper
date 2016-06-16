### Routing

Routing in Taper is done by matching a URL pattern with a callback function.

	$app = new \Taper\Application();
	
	$app->get('/', function() use ($app) {
		echo 'hello world!';
	    $app['logger']->record("test demo", \Taper\Log::DEBUG);
	});

	$app->start();


The callback can be any object that is callable. So you can use a regular function:

	function hello(){
	    echo 'hello world!';
	}
	
	$app->get('/', 'hello');


Or a class method:

	class Greeting {
	    public static function hello() {
	        echo 'hello world!';
	    }
	}
	
	$app->get('/', array('Greeting','hello'));

Routes are matched in the order they are defined. The first route to match a request will be invoked.

### Method Routing

By default, route patterns are matched against all request methods. You can respond to specific methods by placing an identifier before the URL.

	$app->post('/', function() {
		echo 'I received a POST request.';
	});

Or a map method:

	$app->map(['POST'], '/', function() {
		echo 'I received a POST request.';
	});

You can also map multiple methods to a single callback by using a | delimiter:

	$app->map(['GET','POST'], '/', function() {
		echo 'I received either a GET or a POST request.';
	});

### Regular Expressions

You can use regular expressions in your routes:

	$app->get('/user/[0-9]+', function() {
		// This will match /user/1234
	});

### Named Parameters

You can specify named parameters in your routes which will be passed along to your callback function.

	$app->map(['*'], '/@name/@id', function() {
		echo "hello, $name ($id)!";
	});

You can also include regular expressions with your named parameters by using the : delimiter:

	$app->map(['*'], '/@name/@id:[0-9]{3}', function($name, $id){
	    // This will match /bob/123
	    // But will not match /bob/12345
	});

### Optional Parameters

You can specify named parameters that are optional for matching by wrapping segments in parentheses.

	$app->map(['*'], '/blog(/@year(/@month(/@day)))', function($year, $month, $day){
	    // This will match the following URLS:
	    // /blog/2012/12/10
	    // /blog/2012/12
	    // /blog/2012
	    // /blog
	});


Any optional parameters that are not matched will be passed in as NULL.

### Wildcards

Matching is only done on individual URL segments. If you want to match multiple segments you can use the * wildcard.

	$app->map(['*'], '/blog/*', function() {
		// This will match /blog/2000/02/01
	});


To route all requests to a single callback, you can do:

	$app->map(['*'], '*', function() {
		// Do something
	});

### Passing

You can pass execution on to the next matching route by returning true from your callback function.

	$app->map(['*'], '/user/@name', function($name) {
	    // Check some condition
	    if ($name != "Bob") {
	        // Continue to next route
	        return true;
	    }
	});

	$app->map(['*'], '/user/*', function() {
	    // This will get called
	});


### Extending

Taper is designed to be an extensible framework. The framework comes with a set of default methods and components, but it allows you to map your own methods, register your own classes, or even override existing classes and methods.

### Mapping Methods

To map your own custom method, you use the map function:

	// Map your method
	$app->map('hello', function($name){
	    echo "hello $name!";
	});

	// Call your custom method
	$app->hello('Bob');

### Overriding

Taper allows you to override its default functionality to suit your own needs, without having to modify any code.

For example, when Taper cannot match a URL to a route, it invokes the notFound method which sends a generic HTTP 404 response. You can override this behavior by using the map method:
	
	$app->map('notFound', function(){
	    // Display custom 404 page
	    include 'errors/404.html';
	});

### Filtering

Taper allows you to filter methods before and after they are called. There are no predefined hooks you need to memorize. You can filter any of the default framework methods as well as any custom methods that you’ve mapped.

A filter function looks like this:

	function(&$params, &$output) {
	    // Filter code
	}

Using the passed in variables you can manipulate the input parameters and/or the output.

You can have a filter run before a method by doing:

	$app->before('start', function(&$params, &$output){
	    // Do something
	});

You can have a filter run after a method by doing:

	$app->after('start', function(&$params, &$output){
	    // Do something
	});

You can add as many filters as you want to any method. They will be called in the order that they are declared.

Here’s an example of the filtering process:
	
	// Map a custom method
	$app->map('hello', function($name){
	    return "Hello, $name!";
	});

	// Add a before filter
	$app->before('hello', function(&$params, &$output){
	    // Manipulate the parameter
	    $params[0] = 'Fred';
	});

	// Add an after filter
	$app->after('hello', function(&$params, &$output){
	    // Manipulate the output
	    $output .= " Have a nice day!";
	});

	// Invoke the custom method
	echo $app->hello('Bob');

This should display:

	Hello Fred! Have a nice day!


If you have defined multiple filters, you can break the chain by returning false in any of your filter functions:
	
	$app->before('start', function(&$params, &$output){
	    echo 'one';
	});
	
	$app->before('start', function(&$params, &$output){
	    echo 'two';
	
	    // This will end the chain
	    return false;
	});
	
	// This will not get called
	$app->before('start', function(&$params, &$output){
	    echo 'three';
	});

Note, core methods such as map and register cannot be filtered because they are called directly and not invoked dynamically.

### Errors and Exceptions

All errors and exceptions are caught by Taper and passed to the error method. The default behavior is to send a generic HTTP 500 Internal Server Error response with some error information.

You can override this behavior for your own needs:

	$app->map('error', function(Exception $ex){
		// Handle error
		echo $ex->getTraceAsString();
	});

### Not Found

When a URL can’t be found, calls the notFound method. The default behavior is to send an HTTP 404 Not Found response with a simple message.

You can override this behavior for your own needs:

	$app->map('notFound', function(){
		// Handle not found
	});

### Redirects

You can redirect the current request by using the redirect method and passing in a new URL:

	$app->redirect('/new/location');

By default sends a HTTP 303 status code. You can optionally set a custom code:

	$app->redirect('/new/location', 401);

### Requests

Taper encapsulates the HTTP request into a single object, which can be accessed by doing:

	$request = $app->request();

The request object provides the following properties:

+ url - The URL being requested
+ base - The parent subdirectory of the URL
+ method - The request method (GET, POST, PUT, DELETE)
+ referrer - The referrer URL
+ ip - IP address of the client
+ ajax - Whether the request is an AJAX request
+ scheme - The server protocol (http, https)
+ user_agent - Browser information
+ type - The content type
+ length - The content length
+ query - Query string parameters
+ data - Post data or JSON data
+ cookies - Cookie data
+ files - Uploaded files
+ secure - Whether the connection is secure
+ accept - HTTP accept parameters
+ proxy_ip - Proxy IP address of the client
+ You can access the query, data, cookies, and files properties as arrays or objects.

So, to get a query string parameter, you can do:

	$id = $app->request()->query['id'];

Or you can do:

	$id = $app->request()->query->id;

RAW Request Body

To get the raw HTTP request body, for example when dealing with PUT requests, you can do:

	$body = $app->request()->getBody();

JSON Input

If you send a request with the type application/json and the data {"id": 123} it will be available from the data property:

	$id = $app->request()->data->id;

Stopping

You can stop the framework at any point by calling the halt method:

	$app->halt();

You can also specify an optional HTTP status code and message:

	$app->halt(200, 'Be right back...');

Calling halt will discard any response content up to that point. If you want to stop the framework and output the current response, use the stop method:

	$app->stop();

### JSON

Taper provides support for sending JSON and JSONP responses. To send a JSON response you pass some data to be JSON encoded:

	$app->json(array('id' => 123));

For JSONP requests you, can optionally pass in the query parameter name you are using to define your callback function:

	$app->jsonp(array('id' => 123), 'q');

So, when making a GET request using ?q=my_func, you should receive the output:

	my_func({"id":123});

If you don’t pass in a query parameter name it will default to jsonp.
