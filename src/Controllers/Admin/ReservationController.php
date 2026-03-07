<?php
namespace App\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\RestaurantReservation;
use Slim\Views\Twig;

class ReservationController
{
    public function index(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);
        
        $reservations = RestaurantReservation::orderBy('reservation_date', 'desc')
            ->orderBy('reservation_time', 'desc')
            ->get();
        
        return $view->render($response, 'admin/reservations.twig', [
            'reservations' => $reservations
        ]);
    }
    
    public function show(Request $request, Response $response, $args)
    {
        $view = Twig::fromRequest($request);
        
        $reservation = RestaurantReservation::find($args['id']);
        
        if (!$reservation) {
            return $response->withHeader('Location', '/admin/reservations')->withStatus(302);
        }
        
        return $view->render($response, 'admin/reservation.twig', [
            'reservation' => $reservation
        ]);
    }
    
    public function updateStatus(Request $request, Response $response, $args)
    {
        $reservation = RestaurantReservation::find($args['id']);
        $data = $request->getParsedBody();
        
        if ($reservation) {
            $reservation->update(['status' => $data['status']]);
        }
        
        $payload = ['success' => true];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
}