<?php
require 'vendor/autoload.php';

$app = new \Taper\Application();


$app->get('/', function() use ($app) {
    $app['logger']->record("haha", \Taper\Log::INFO);

    $myvar1 = $app['request']->getParam('myvar'); //checks both _GET and _POST
    $myvar2 = $app['request']->getParsedBody()['myvar']; //checks _POST
    //$myvar3 = $app['request']->getQueryParams()['myvar']; //checks _GET

    //$name = $app['request']->getAttribute('name');
    //$app['request']->getBody()->write("Hello, $name");
    //$app['request']->getBody()->write("Hello33");

    echo 'demo';
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

/*
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

*/


$app->start();

echo "\n<br>\n";
exit('===end===');