<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response;

class AuthMiddleware
{
    public function __invoke(Request $request, Handler $handler): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $response = new Response();
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
        
        // Check if user is admin
        if ($_SESSION['user_role'] !== 'admin') {
            $response = new Response();
            return $response->withHeader('Location', '/')->withStatus(302);
        }
        
        return $handler->handle($request);
    }
}