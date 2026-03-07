<?php
namespace App\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;
use Slim\Views\Twig;

class UserController
{
    public function index(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);
        
        $users = User::orderBy('created_at', 'desc')->get();
        
        return $view->render($response, 'admin/users.twig', [
            'users' => $users
        ]);
    }
    
    public function create(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'admin/users/create.twig');
    }
    
    public function store(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        
        User::create([
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'email' => $data['email'],
            'full_name' => $data['full_name'],
            'role' => $data['role']
        ]);
        
        return $response->withHeader('Location', '/admin/users')->withStatus(302);
    }
    
    public function edit(Request $request, Response $response, $args)
    {
        $view = Twig::fromRequest($request);
        
        $user = User::find($args['id']);
        
        if (!$user) {
            return $response->withHeader('Location', '/admin/users')->withStatus(302);
        }
        
        return $view->render($response, 'admin/users/edit.twig', [
            'user' => $user
        ]);
    }
    
    public function update(Request $request, Response $response, $args)
    {
        $user = User::find($args['id']);
        $data = $request->getParsedBody();
        
        $updateData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'full_name' => $data['full_name'],
            'role' => $data['role']
        ];
        
        if (!empty($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $user->update($updateData);
        
        return $response->withHeader('Location', '/admin/users')->withStatus(302);
    }
    
    public function delete(Request $request, Response $response, $args)
    {
        $user = User::find($args['id']);
        
        if ($user && $user->id !== 1) { // Prevent deleting main admin
            $user->delete();
        }
        
        return $response->withHeader('Location', '/admin/users')->withStatus(302);
    }
}