<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use Slim\Views\Twig;

class MenuController
{
    public function index(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);
        
        $categories = MenuCategory::with(['items' => function($query) {
            $query->where('is_available', true);
        }])->orderBy('sort_order')->get();
        
        return $view->render($response, 'menu/index.twig', [
            'categories' => $categories
        ]);
    }
    
    public function category(Request $request, Response $response, $args)
    {
        $view = Twig::fromRequest($request);
        
        $category = MenuCategory::with(['items' => function($query) {
            $query->where('is_available', true);
        }])->find($args['id']);
        
        if (!$category) {
            return $response->withHeader('Location', '/menu')->withStatus(302);
        }
        
        return $view->render($response, 'menu/category.twig', [
            'category' => $category
        ]);
    }
}