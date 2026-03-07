<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\RestaurantReservation;
use Slim\Views\Twig;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class ReservationController
{
    public function index(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);

        $rooms = Room::where('status', 'available')->get();

        return $view->render($response, 'reservations/index.twig', [
            'rooms' => $rooms
        ]);
    }

    public function bookRoom(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        // 1️⃣ Validate required fields
        if (
            empty($data['room_id']) ||
            empty($data['name']) ||
            empty($data['email']) ||
            empty($data['phone']) ||
            empty($data['check_in']) ||
            empty($data['check_out'])
        ) {
            $payload = [
                'success' => false,
                'message' => 'Please fill all required fields'
            ];

            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        }

        // 2️⃣ Check if room exists
        $room = Room::find($data['room_id']);

        if (!$room) {
            $payload = [
                'success' => false,
                'message' => 'Room not found'
            ];

            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        }

        // 3️⃣ Validate dates
        $checkIn = strtotime($data['check_in']);
        $checkOut = strtotime($data['check_out']);

        if ($checkOut <= $checkIn) {
            $payload = [
                'success' => false,
                'message' => 'Check-out must be after check-in'
            ];

            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        }

        // 4️⃣ Check room availability
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

        if ($existingBooking) {
            $payload = [
                'success' => false,
                'message' => 'Room is not available for selected dates'
            ];

            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        }

        // 5️⃣ Calculate nights
        $nights = ($checkOut - $checkIn) / (60 * 60 * 24);

        // 6️⃣ Calculate total price
        $totalPrice = $room->price * $nights;

        // 7️⃣ Create booking
        $booking = RoomBooking::create([
            'room_id' => $data['room_id'],
            'guest_name' => $data['name'],
            'guest_email' => $data['email'],
            'guest_phone' => $data['phone'],
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'adults' => $data['adults'],
            'children' => $data['children'] ?? 0,
            'total_price' => $totalPrice,
            'special_requests' => $data['special_requests'] ?? '',
            'status' => 'pending',
            'payment_status' => 'pending'
        ]);

        // 8️⃣ Send confirmation email
        $this->sendBookingConfirmation($booking);

        $payload = [
            'success' => true,
            'message' => 'Room booked successfully',
            'booking_id' => $booking->id
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }


    public function bookRestaurant(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        // -------------------------------
        // 1️⃣ Server-side validation
        // -------------------------------
        if (empty($data['guest_name']) || empty($data['reservation_date']) || empty($data['reservation_time']) || empty($data['guests'])) {
            $payload = [
                'success' => false,
                'message' => 'Please fill in all required fields'
            ];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // -------------------------------
        // 2️⃣ Check availability
        // -------------------------------
        // Sum of guests already booked at the same date & time, excluding cancelled
        $existingReservations = RestaurantReservation::where('reservation_date', $data['reservation_date'])
            ->where('reservation_time', $data['reservation_time'])
            ->where('status', '!=', 'cancelled')
            ->sum('guests');

        // Assuming restaurant capacity = 50
        $restaurantCapacity = 50;

        if ($existingReservations + $data['guests'] > $restaurantCapacity) {
            $payload = [
                'success' => false,
                'message' => 'Sorry, no availability for this time slot'
            ];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        // -------------------------------
        // 3️⃣ Create reservation
        // -------------------------------
        $reservation = RestaurantReservation::create([
            'guest_name' => $data['guest_name'],
            'guest_email' => $data['guest_email'] ?? null,
            'guest_phone' => $data['guest_phone'] ?? null,
            'reservation_date' => $data['reservation_date'],
            'reservation_time' => $data['reservation_time'],
            'guests' => $data['guests'],
            'special_requests' => $data['special_requests'] ?? '',
            'status' => 'pending'
        ]);

        // -------------------------------
        // 4️⃣ Send confirmation email (optional)
        // -------------------------------
        $this->sendReservationConfirmation($reservation);

        // -------------------------------
        // 5️⃣ Return success response
        // -------------------------------
        $payload = [
            'success' => true,
            'message' => 'Reservation created successfully',
            'reservation_id' => $reservation->id
        ];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function sendBookingConfirmation($booking)
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'];
            $mail->Password   = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['SMTP_PORT'];

            // Recipients
            $mail->setFrom($_ENV['SMTP_USER'], $_ENV['APP_NAME']);
            $mail->addAddress($booking->guest_email, $booking->guest_name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Room Booking Confirmation - ' . $_ENV['APP_NAME'];

            $room = Room::find($booking->room_id);

            $body = "
            <h2>Booking Confirmation</h2>
            <p>Dear {$booking->guest_name},</p>
            <p>Thank you for booking with us. Your booking details are below:</p>
            
            <h3>Booking Details:</h3>
            <ul>
                <li><strong>Booking ID:</strong> #{$booking->id}</li>
                <li><strong>Room:</strong> {$room->room_number} ({$room->type})</li>
                <li><strong>Check-in:</strong> " . date('F j, Y', strtotime($booking->check_in)) . "</li>
                <li><strong>Check-out:</strong> " . date('F j, Y', strtotime($booking->check_out)) . "</li>
                <li><strong>Guests:</strong> {$booking->adults} Adults, {$booking->children} Children</li>
                <li><strong>Total Price:</strong> ₹" . number_format($booking->total_price, 2) . "</li>
            </ul>
            
            <p>We look forward to hosting you!</p>
            <p>Best regards,<br>{$_ENV['APP_NAME']}</p>
            ";

            $mail->Body = $body;
            $mail->send();
        } catch (Exception $e) {
            // Log error but don't stop the process
            error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        }
    }

    private function sendReservationConfirmation($reservation)
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'];
            $mail->Password   = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['SMTP_PORT'];

            $mail->setFrom($_ENV['SMTP_USER'], $_ENV['APP_NAME']);
            $mail->addAddress($reservation->guest_email, $reservation->guest_name);

            $mail->isHTML(true);
            $mail->Subject = 'Restaurant Reservation Confirmation - ' . $_ENV['APP_NAME'];

            $body = "
            <h2>Reservation Confirmation</h2>
            <p>Dear {$reservation->guest_name},</p>
            <p>Your table reservation has been confirmed.</p>
            
            <h3>Reservation Details:</h3>
            <ul>
                <li><strong>Reservation ID:</strong> #{$reservation->id}</li>
                <li><strong>Date:</strong> " . date('F j, Y', strtotime($reservation->reservation_date)) . "</li>
                <li><strong>Time:</strong> " . date('g:i A', strtotime($reservation->reservation_time)) . "</li>
                <li><strong>Number of Guests:</strong> {$reservation->guests}</li>
            </ul>
            
            <p>We look forward to serving you!</p>
            <p>Best regards,<br>{$_ENV['APP_NAME']}</p>
            ";

            $mail->Body = $body;
            $mail->send();
        } catch (Exception $e) {
            error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        }
    }
}
