<?php
require 'vendor/autoload.php';

/*
use \Slim\Http\Request;
use \Slim\Http\Response;

$app = new \Slim\App;

$app->get('/hello/{name}', function (Request $request, Response $response) {

	$myvar1 = $request->getParam('myvar'); //checks both _GET and _POST
	$myvar2 = $request->getParsedBody()['myvar']; //checks _POST
	//$myvar3 = $request->getQueryParams()['myvar']; //checks _GET

	$name = $request->getAttribute('name');
	$response->getBody()->write("Hello, $name");
	return $response;
});

$app->run();
*/

$app = new \Taper\Application();


$app->get('/', function() use ($app) {
    $app['logger']->record("haha", \Taper\Log::INFO);
    echo 'ssss';
});

/*

$app->after('start', function() use ($app) {
    $app['logger']->record("form success.", \Taper\Log::INFO);

    //$info = $app['debugger']->info('start', 'end');

    //var_dump($app['debugger']);
    //var_dump($info);
});
*/

/*
$app->get('/hello/', function() use ($app) {
    echo $app->hello('Bob');
});


$app->after('start', function() {
    echo '++++';
});
$app->before('start', function() {
    echo '-----';
});
*/

$app->hook('hello', function($name){
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
    echo $output;
});

$app->hello('sss');


//$app->start();

/*
$router = new \Taper\Router\Router(
    array('GET' => array(
        ':' => array(),
        'LEAF' => array(0 => function(){ echo "Hello world 7887!!!"; }, 1 => array()))),
    array());
$router->execute(array(), 'GET', '');
*/

echo "\n<br>\n";
exit('===end===');