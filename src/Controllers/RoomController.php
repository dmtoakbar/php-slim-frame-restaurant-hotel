<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Room;
use App\Models\RoomBooking;
use Slim\Views\Twig;
use Respect\Validation\Validator as v;

class RoomController
{
    public function index(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);

        $query = Room::where('status', 'available');

        // Filter by type
        $params = $request->getQueryParams();
        if (isset($params['type']) && !empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        // Filter by price
        if (isset($params['min_price']) && !empty($params['min_price'])) {
            $query->where('price', '>=', $params['min_price']);
        }
        if (isset($params['max_price']) && !empty($params['max_price'])) {
            $query->where('price', '<=', $params['max_price']);
        }

        // Filter by capacity
        if (isset($params['capacity']) && !empty($params['capacity'])) {
            $query->where('capacity', '>=', $params['capacity']);
        }

        $rooms = $query->orderBy('price')->get();

        return $view->render($response, 'rooms/index.twig', [
            'rooms' => $rooms,
            'filters' => $params
        ]);
    }

    public function show(Request $request, Response $response, $args)
    {
        $view = Twig::fromRequest($request);

        $room = Room::find($args['id']);

        if (!$room) {
            return $response->withHeader('Location', '/rooms')->withStatus(302);
        }

        return $view->render($response, 'rooms/show.twig', [
            'room' => $room
        ]);
    }


       public function initialCheckAvailability(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        
        // Validate input
        $validator = v::key('check_in', v::date('Y-m-d'))
                      ->key('check_out', v::date('Y-m-d'))
                      ->key('adults', v::intVal()->positive())
                      ->key('children', v::optional(v::intVal()));
        
        try {
            $validator->assert($data);
            
            // Check if check_out is after check_in
            if (strtotime($data['check_out']) <= strtotime($data['check_in'])) {
                throw new \Exception('Check-out date must be after check-in date');
            }
            
            // Find available rooms
            $availableRooms = Room::where('status', 'available')
                ->where('capacity', '>=', $data['adults'] + ($data['children'] ?? 0))
                ->whereNotIn('id', function($query) use ($data) {
                    $query->select('room_id')
                        ->from('room_bookings')
                        ->where(function($q) use ($data) {
                            $q->whereBetween('check_in', [$data['check_in'], $data['check_out']])
                              ->orWhereBetween('check_out', [$data['check_in'], $data['check_out']])
                              ->orWhere(function($q2) use ($data) {
                                  $q2->where('check_in', '<=', $data['check_in'])
                                     ->where('check_out', '>=', $data['check_out']);
                              });
                        })
                        ->where('status', '!=', 'cancelled');
                })
                ->get();
            
            $payload = [
                'success' => true,
                'rooms' => $availableRooms
            ];
            
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $payload = [
                'success' => false,
                'message' => $e->getMessage()
            ];
            
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    public function checkAvailability(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        // Validate input
        $validator = v::key('check_in', v::date('Y-m-d'))
            ->key('check_out', v::date('Y-m-d'))
            ->key('adults', v::intVal()->positive())
            ->key('children', v::optional(v::intVal()));

        try {
            $validator->assert($data);

            // Check if check_out is after check_in
            if (strtotime($data['check_out']) <= strtotime($data['check_in'])) {
                throw new \Exception('Check-out date must be after check-in date');
            }

            // Find available rooms
            $existingBooking = RoomBooking::where('room_id', $data['room_id'])
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($data) {
                    $query->whereBetween('check_in', [$data['check_in'], $data['check_out']])
                        ->orWhereBetween('check_out', [$data['check_in'], $data['check_out']])
                        ->orWhere(function ($q) use ($data) {
                            $q->where('check_in', '<=', $data['check_in'])
                                ->where('check_out', '>=', $data['check_out']);
                        });
                })
                ->exists();

            $payload = [
                'success' => true,
                'message' => $existingBooking ? 'Room is not available for selected dates' : 'Room is available',
                'available' => !$existingBooking
            ];

            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $payload = [
                'success' => false,
                'message' => $e->getMessage()
            ];

            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }
}
