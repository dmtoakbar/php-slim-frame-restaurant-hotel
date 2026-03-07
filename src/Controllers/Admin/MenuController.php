<?php
namespace App\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\MenuItem;
use App\Models\MenuCategory;
use Slim\Views\Twig;

class MenuController
{
    public function index(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);
        
        $items = MenuItem::with('category')->orderBy('category_id')->get();
        $categories = MenuCategory::orderBy('sort_order')->get();
        
        return $view->render($response, 'admin/menu/index.twig', [
            'items' => $items,
            'categories' => $categories
        ]);
    }
    
    public function create(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);
        
        $categories = MenuCategory::orderBy('sort_order')->get();
        
        return $view->render($response, 'admin/menu/create.twig', [
            'categories' => $categories
        ]);
    }
    
    public function store(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        
        // Handle image upload
        $image = 'default-food.jpg';
        $uploadedFiles = $request->getUploadedFiles();
        
        if (isset($uploadedFiles['image']) && $uploadedFiles['image']->getError() === UPLOAD_ERR_OK) {
            $uploadedFile = $uploadedFiles['image'];
            $filename = uniqid() . '_' . $uploadedFile->getClientFilename();
            $uploadedFile->moveTo(__DIR__ . '/../../../public/uploads/' . $filename);
            $image = $filename;
        }
        
        MenuItem::create([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'image' => $image,
            'is_available' => isset($data['is_available']) ? true : false,
            'is_featured' => isset($data['is_featured']) ? true : false,
            'dietary_info' => $data['dietary_info'] ?? ''
        ]);
        
        return $response->withHeader('Location', '/admin/menu')->withStatus(302);
    }
    
    public function edit(Request $request, Response $response, $args)
    {
        $view = Twig::fromRequest($request);
        
        $item = MenuItem::find($args['id']);
        $categories = MenuCategory::orderBy('sort_order')->get();
        
        if (!$item) {
            return $response->withHeader('Location', '/admin/menu')->withStatus(302);
        }
        
        return $view->render($response, 'admin/menu/edit.twig', [
            'item' => $item,
            'categories' => $categories
        ]);
    }
    
    public function update(Request $request, Response $response, $args)
    {
        $item = MenuItem::find($args['id']);
        $data = $request->getParsedBody();
        
        // Handle image upload
        $image = $item->image;
        $uploadedFiles = $request->getUploadedFiles();
        
        if (isset($uploadedFiles['image']) && $uploadedFiles['image']->getError() === UPLOAD_ERR_OK) {
            // Delete old image
            if ($item->image && $item->image !== 'default-food.jpg') {
                $oldFile = __DIR__ . '/../../../public/uploads/' . $item->image;
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            
            $uploadedFile = $uploadedFiles['image'];
            $filename = uniqid() . '_' . $uploadedFile->getClientFilename();
            $uploadedFile->moveTo(__DIR__ . '/../../../public/uploads/' . $filename);
            $image = $filename;
        }
        
        $item->update([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'image' => $image,
            'is_available' => isset($data['is_available']) ? true : false,
            'is_featured' => isset($data['is_featured']) ? true : false,
            'dietary_info' => $data['dietary_info'] ?? ''
        ]);
        
        return $response->withHeader('Location', '/admin/menu')->withStatus(302);
    }
    
    public function delete(Request $request, Response $response, $args)
    {
        $item = MenuItem::find($args['id']);
        
        if ($item) {
            // Delete image
            if ($item->image && $item->image !== 'default-food.jpg') {
                $file = __DIR__ . '/../../../public/uploads/' . $item->image;
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            
            $item->delete();
        }
        
        return $response->withHeader('Location', '/admin/menu')->withStatus(302);
    }
    
    public function categories(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);
        
        $categories = MenuCategory::orderBy('sort_order')->get();
        
        return $view->render($response, 'admin/menu/categories.twig', [
            'categories' => $categories
        ]);
    }
    
    public function storeCategory(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        
        MenuCategory::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'sort_order' => $data['sort_order'] ?? 0
        ]);
        
        return $response->withHeader('Location', '/admin/menu-categories')->withStatus(302);
    }
    
    public function updateCategory(Request $request, Response $response, $args)
    {
        $category = MenuCategory::find($args['id']);
        $data = $request->getParsedBody();
        
        if ($category) {
            $category->update([
                'name' => $data['name'],
                'description' => $data['description'],
                'sort_order' => $data['sort_order'] ?? 0
            ]);
        }
        
        return $response->withHeader('Location', '/admin/menu-categories')->withStatus(302);
    }
    
    public function deleteCategory(Request $request, Response $response, $args)
    {
        $category = MenuCategory::find($args['id']);
        
        if ($category) {
            $category->delete();
        }
        
        return $response->withHeader('Location', '/admin/menu-categories')->withStatus(302);
    }
}