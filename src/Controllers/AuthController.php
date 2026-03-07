<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;
use Slim\Views\Twig;

class AuthController
{
    public function showLogin(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);
        $code = substr(md5(random_bytes(64)), 0, 6);

        $_SESSION['captcha_code'] = $code;

        return $view->render($response, 'login.twig', [
            'captcha_code' => $code
        ]);
    }
    
    public function login(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

          if (!isset($data['captcha']) || $data['captcha'] !== $_SESSION['captcha_code']) {

                $view = \Slim\Views\Twig::fromRequest($request);
                $code = substr(md5(random_bytes(64)), 0, 6);
                $_SESSION['captcha_code'] = $code;
                return $view->render($response, 'login.twig', [
                    'error' => 'Invalid captcha code',
                    'captcha_code' => $code
                ]);
            }
        
        $user = User::where('username', $data['username'])->first();
        
        if ($user && password_verify($data['password'], $user->password)) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->full_name;
            $_SESSION['user_role'] = $user->role;
            
            return $response->withHeader('Location', '/admin')->withStatus(302);
        }
        
        $view = Twig::fromRequest($request);
        $code = substr(md5(random_bytes(64)), 0, 6);
        $_SESSION['captcha_code'] = $code;
        return $view->render($response, 'login.twig', [
            'error' => 'Invalid username or password',
            'captcha_code' => $code
        ]);
    }
    
    public function logout(Request $request, Response $response)
    {
        session_destroy();
        return $response->withHeader('Location', '/')->withStatus(302);
    }
}