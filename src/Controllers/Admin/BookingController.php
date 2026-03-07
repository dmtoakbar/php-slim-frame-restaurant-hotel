<?php
namespace App\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\RoomBooking;
use Slim\Views\Twig;

class BookingController
{
    public function index(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);
        
        $bookings = RoomBooking::with('room')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return $view->render($response, 'admin/bookings.twig', [
            'bookings' => $bookings
        ]);
    }
    
    public function show(Request $request, Response $response, $args)
    {
        $view = Twig::fromRequest($request);
        
        $booking = RoomBooking::with('room')->find($args['id']);
        
        if (!$booking) {
            return $response->withHeader('Location', '/admin/bookings')->withStatus(302);
        }
        
        return $view->render($response, 'admin/booking.twig', [
            'booking' => $booking
        ]);
    }
    
    public function updateStatus(Request $request, Response $response, $args)
    {
        $booking = RoomBooking::find($args['id']);
        $data = $request->getParsedBody();
        
        if ($booking) {
            $booking->update(['status' => $data['status']]);
        }
        
        $payload = ['success' => true];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
}