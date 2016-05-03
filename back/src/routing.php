<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 * App routes
 */
$app->get('/app', 'Cakebox\Controller\MainController::getAppInfos');

/**
 * Directories routes
 */
$app->get('/directories',         'Cakebox\Controller\DirectoryController::get');
$app->delete('/directories',      'Cakebox\Controller\DirectoryController::delete');
$app->get('/directories/archive', 'Cakebox\Controller\DirectoryController::archive');

/**
 * Files routes
 */
$app->get('/files',          'Cakebox\Controller\FileController::get');
$app->delete('/files',       'Cakebox\Controller\FileController::delete');
$app->get('/files/download', 'Cakebox\Controller\FileController::download');

/**
 * Betaseries routes
 */
$app->get('/betaseries/config',          'Cakebox\Controller\BetaseriesController::getConfig');
$app->get('/betaseries/info/{name}',     'Cakebox\Controller\BetaseriesController::getInfos');
$app->post('/betaseries/watched/{id}',   'Cakebox\Controller\BetaseriesController::setWatched');
$app->delete('/betaseries/watched/{id}', 'Cakebox\Controller\BetaseriesController::unsetWatched');

$app->get('/signin', function(Request $request) use ($app) {
    return $app->json(['test'=>false], Response::HTTP_OK);
});

$app->post('/signin', function(Request $request) use ($app) {

    $vars = json_decode($request->getContent(), true);

    try {
        if (!isset($vars['username']) || empty($vars['username']) || !isset($vars['password']) || empty($vars['password'])) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $vars['_username']));
        }

        /**
         * @var $user User
         */
        $user = $app['users']->loadUserByUsername($vars['username']);

        if (! $app['security.encoder.digest']->isPasswordValid($user->getPassword(), $vars['password'], '')) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $vars['username']));
        } else {
            $response = [
                'success' => true,
                'token' => $app['security.jwt.encoder']->encode(['name' => $user->getUsername()]),
            ];
        }
    } catch (UsernameNotFoundException $e) {
        $response = [
            'success' => false,
            'error' => 'Invalid credentials',
        ];
    }

    return $app->json($response, ($response['success'] == true ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST));
});