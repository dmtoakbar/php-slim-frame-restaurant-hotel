<?php

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\HomeController;
use App\Controllers\RoomController;
use App\Controllers\MenuController;
use App\Controllers\ReservationController;
use App\Controllers\ContactController;
use App\Controllers\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\RoomController as AdminRoomController;
use App\Controllers\Admin\MenuController as AdminMenuController;
use App\Controllers\Admin\BookingController;
use App\Controllers\Admin\ReservationController as AdminReservationController;
use App\Controllers\Admin\UserController;
use App\Middleware\AuthMiddleware;
use Slim\Views\Twig;

return function ($app) {
    // Public routes
    $app->get('/', [HomeController::class, 'index'])->setName('home');
    $app->get('/rooms', [RoomController::class, 'index'])->setName('rooms');
    $app->get('/rooms/{id}', [RoomController::class, 'show'])->setName('room.detail');
    $app->post('/rooms/check-availability', [RoomController::class, 'checkAvailability'])->setName('room.check');

    $app->get('/menu', [MenuController::class, 'index'])->setName('menu');
    $app->get('/menu/category/{id}', [MenuController::class, 'category'])->setName('menu.category');

    $app->get('/reservation', [ReservationController::class, 'index'])->setName('reservation');
    $app->post('/reservation/room', [ReservationController::class, 'bookRoom'])->setName('reservation.room');
    $app->post('/reservation/restaurant', [ReservationController::class, 'bookRestaurant'])->setName('reservation.restaurant');

    $app->get('/contact', [ContactController::class, 'index'])->setName('contact');
    $app->post('/contact', [ContactController::class, 'send'])->setName('contact.send');

    $app->get('/gallery', [HomeController::class, 'gallery'])->setName('gallery');
    $app->get('/about', [HomeController::class, 'about'])->setName('about');

    // Auth routes
    $app->get('/login', [AuthController::class, 'showLogin'])->setName('login');
    $app->post('/login', [AuthController::class, 'login']);
    $app->get('/refresh-captcha', function ($request, $response) {

        $code = substr(md5(random_bytes(64)), 0, 6);

        $_SESSION['captcha_code'] = $code;

        $response->getBody()->write(json_encode([
            'captcha' => $code
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/logout', [AuthController::class, 'logout'])->setName('logout');

    $app->get('/privacy', function ($request, $response) {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'privacy.twig');
    });

    $app->get('/terms', function ($request, $response) {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'terms.twig');
    });

    // Admin routes (protected)
    $app->group('/admin', function (RouteCollectorProxy $group) {
        $group->get('', [DashboardController::class, 'index'])->setName('admin.dashboard');
        $group->get('/dashboard', [DashboardController::class, 'index'])->setName('admin.dashboard');

        // Room management
        $group->get('/rooms', [AdminRoomController::class, 'index'])->setName('admin.rooms');
        $group->get('/rooms/create', [AdminRoomController::class, 'create'])->setName('admin.rooms.create');
        $group->post('/rooms', [AdminRoomController::class, 'store'])->setName('admin.rooms.store');
        $group->get('/rooms/{id}/edit', [AdminRoomController::class, 'edit'])->setName('admin.rooms.edit');
        $group->post('/rooms/{id}', [AdminRoomController::class, 'update'])->setName('admin.rooms.update');
        $group->delete('/rooms/{id}', [AdminRoomController::class, 'delete'])->setName('admin.rooms.delete');

        // Menu management
        $group->get('/menu', [AdminMenuController::class, 'index'])->setName('admin.menu');
        $group->get('/menu/create', [AdminMenuController::class, 'create'])->setName('admin.menu.create');
        $group->post('/menu', [AdminMenuController::class, 'store'])->setName('admin.menu.store');
        $group->get('/menu/{id}/edit', [AdminMenuController::class, 'edit'])->setName('admin.menu.edit');
        $group->post('/menu/{id}', [AdminMenuController::class, 'update'])->setName('admin.menu.update');
        $group->delete('/menu/{id}', [AdminMenuController::class, 'delete'])->setName('admin.menu.delete');

        // Menu categories
        $group->get('/menu-categories', [AdminMenuController::class, 'categories'])->setName('admin.menu.categories');
        $group->post('/menu-categories', [AdminMenuController::class, 'storeCategory'])->setName('admin.menu.categories.store');
        $group->post('/menu-categories/{id}', [AdminMenuController::class, 'updateCategory'])->setName('admin.menu.categories.update');
        $group->delete('/menu-categories/{id}', [AdminMenuController::class, 'deleteCategory'])->setName('admin.menu.categories.delete');

        // Room bookings
        $group->get('/bookings', [BookingController::class, 'index'])->setName('admin.bookings');
        $group->get('/bookings/{id}', [BookingController::class, 'show'])->setName('admin.bookings.show');
        $group->post('/bookings/{id}/status', [BookingController::class, 'updateStatus'])->setName('admin.bookings.status');

        // Restaurant reservations
        $group->get('/reservations', [AdminReservationController::class, 'index'])->setName('admin.reservations');
        $group->get('/reservations/{id}', [AdminReservationController::class, 'show'])->setName('admin.reservations.show');
        $group->post('/reservations/{id}/status', [AdminReservationController::class, 'updateStatus'])->setName('admin.reservations.status');

        // Contact messages
        $group->get('/messages', [DashboardController::class, 'messages'])->setName('admin.messages');
        $group->get('/messages/{id}', [DashboardController::class, 'showMessage'])->setName('admin.messages.show');
        $group->delete('/messages/{id}', [DashboardController::class, 'deleteMessage'])->setName('admin.messages.delete');

        // Gallery management
        $group->get('/gallery', [DashboardController::class, 'gallery'])->setName('admin.gallery');
        $group->post('/gallery', [DashboardController::class, 'uploadImage'])->setName('admin.gallery.upload');
        $group->delete('/gallery/{id}', [DashboardController::class, 'deleteImage'])->setName('admin.gallery.delete');

        // User management
        $group->get('/users', [UserController::class, 'index'])->setName('admin.users');
        $group->get('/users/create', [UserController::class, 'create'])->setName('admin.users.create');
        $group->post('/users', [UserController::class, 'store'])->setName('admin.users.store');
        $group->get('/users/{id}/edit', [UserController::class, 'edit'])->setName('admin.users.edit');
        $group->post('/users/{id}', [UserController::class, 'update'])->setName('admin.users.update');
        $group->delete('/users/{id}', [UserController::class, 'delete'])->setName('admin.users.delete');

        // Settings
        $group->get('/settings', [DashboardController::class, 'settings'])->setName('admin.settings');
        $group->post('/settings', [DashboardController::class, 'updateSettings'])->setName('admin.settings.update');
    })->add(AuthMiddleware::class);
};
