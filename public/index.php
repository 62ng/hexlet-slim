<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;
use Slim\Views\PhpRenderer;

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new Messages();
});

$app = AppFactory::createFromContainer($container);
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$repo = new An\HexletSlim\RepoInSession();

$app->post('/login', function ($request, $response) {
    $email = $request->getParsedBodyParam('email');
    $_SESSION['email'] = $email;

    return $response->withRedirect('/', 302);
});

$app->post('/logout', function ($request, $response) {
    $_SESSION = [];
    session_destroy();

    return $response->withRedirect('/', 302);
});

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!</br><a href="/users">All users</a>');
    return $response;
});

$app->get('/users', function ($request, $response) use ($repo) {
    $term = $request->getQueryParam('term');

    $users = $repo->all();
    $filteredUsers = array_filter($users, fn($user) => str_contains($user['name'], $term));

    $messages = $this->get('flash')->getMessages();
    $params = [
        'flash' => $messages,
        'term' => $term,
        'users' => $filteredUsers
    ];

    return $this->get('renderer')->render(
        $response,
        'users/index.phtml',
        $params
    );
})->setName('users');

$app->post('/users', function ($request, $response) use ($repo, $router) {
    $user = $request->getParsedBodyParam('user');

    if (strlen($user['name']) < 4) {
        $this->get('flash')->addMessage('errors', 'User was added successfully');
    } else {
        $repo->save($user);
        $this->get('flash')->addMessage('success', 'User was added successfully');

        return $response->withRedirect($router->urlFor('users'), 302);
    }

    $messages = $this->get('flash')->getMessages();
    $params = [
        'flash' => $messages,
        'user' => $user
    ];

    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => ''],
        'flash' => ['errors' => [], 'success' => []]
    ];

    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->get('/users/{id}', function ($request, $response, $args) use ($repo, $router) {
    $user = $repo->find($args['id']);
    if (!$user) {
        return $response->withRedirect($router->urlFor('users'), 404);
    }

    $params = ['user' => $user];

    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');

$app->get('/users/{id}/edit', function ($request, $response, $args) use ($repo) {
    $user = $repo->find($args['id']);
    $params = ['user' => $user];

    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
});

$app->patch('/users/{id}', function ($request, $response, $args) use ($repo, $router) {
    $userData = $request->getParsedBodyParam('user');
    
    $user = $repo->find($args['id']);
    if ($user) {
        $user['name'] = $userData['name'];
        $user['email'] = $userData['email'];

        $repo->save($user);

        return $response->withRedirect($router->urlFor('user', ['id' => $user['id']]));
    }

    $params = ['user' => $userData];

    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
});

$app->get('/users/{id}/delete', function ($request, $response, $args) use ($repo) {
    $user = $repo->find($args['id']);
    $params = ['user' => $user];

    return $this->get('renderer')->render($response, 'users/delete.phtml', $params);
});

$app->delete('/users/{id}', function ($request, $response, $args) use ($repo, $router) {
    $repo->destroy($args['id']);

    return $response->withRedirect($router->urlFor('users'));
});

$app->run();
