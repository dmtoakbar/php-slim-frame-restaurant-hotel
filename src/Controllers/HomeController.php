<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Room;
use App\Models\MenuItem;
use App\Models\Gallery;
use Slim\Views\Twig;

class HomeController
{
    public function index(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);
        
        $featuredRooms = Room::where('status', 'available')
            ->limit(3)
            ->get();
        
        $featuredMenu = MenuItem::with('category')
            ->where('is_featured', true)
            ->where('is_available', true)
            ->limit(6)
            ->get();
        
        return $view->render($response, 'home.twig', [
            'featuredRooms' => $featuredRooms,
            'featuredMenu' => $featuredMenu
        ]);
    }
    
    public function about(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'about.twig');
    }
    
    public function gallery(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);
        
        $images = Gallery::orderBy('sort_order')->get();
        
        return $view->render($response, 'gallery.twig', [
            'images' => $images
        ]);
    }
}