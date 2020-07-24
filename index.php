<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
require_once 'task.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'todo',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

// Make this Capsule instance available globally via static methods... (optional)
$capsule->setAsGlobal();

// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
$capsule->bootEloquent();

use Relay\Relay;

$request = Zend\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

$loader = new Twig_Loader_Filesystem('.');
$twig = new \Twig_Environment($loader, array(
    'debug' => true,
    'cache' => false,
));

$router = new Aura\Router\RouterContainer();
$map = $router->getMap();
$map->get('todo.list', '/php-database-crud/', function ($request) use ($twig) {
    /*$tasks = [
        [
            'id' => 1,
            'description' => 'Aprender inglés',
            'done' => false
        ],
        [
            'id' => 1,
            'description' => 'Hacer la tarea',
            'done' => true
        ],
        [
            'id' => 1,
            'description' => 'Pasear al perro',
            'done' => false
        ],
        [
            'id' => 1,
            'description' => 'Ver el curso de introducción a PHP',
            'done' => false
        ]
    ];*/
    $tasks = Task::all();
    $response = new Zend\Diactoros\Response\HtmlResponse($twig->render('template.twig', [
        'tasks' => $tasks
    ]));
    return $response;
});

$map->post('todo.add', '/php-database-crud/add/', function ($request) {
    $data = $request->getParsedBody();

    $tasks = new Task();

    $tasks->description = $data['description'];
    $tasks->save();

    $response = new Zend\Diactoros\Response\RedirectResponse('/php-database-crud/');
    return $response;
});

$map->get('todo.check', '/php-database-crud/check/{id}', function ($request) {

    $id = $request->getAttribute('id');

    $tasks = Task::find($id);

    $tasks->done = true;
    $tasks->save();

    $response = new Zend\Diactoros\Response\RedirectResponse('/php-database-crud/');
    return $response;
});

$map->get('todo.uncheck', '/php-database-crud/uncheck/{id}', function ($request) {

    $id = $request->getAttribute('id');

    $tasks = Task::find($id);

    $tasks->done = false;
    $tasks->save();

    $response = new Zend\Diactoros\Response\RedirectResponse('/php-database-crud/');
    return $response;
});

$map->get('todo.delete', '/php-database-crud/delete/{id}', function ($request) {

    $id = $request->getAttribute('id');

    $tasks = Task::find($id);

    
    $tasks->delete();

    $response = new Zend\Diactoros\Response\RedirectResponse('/php-database-crud/');
    return $response;
});

$relay = new Relay([
    new Middlewares\AuraRouter($router),
    new Middlewares\RequestHandler()
]);

$response = $relay->handle($request);

foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}
echo $response->getBody();