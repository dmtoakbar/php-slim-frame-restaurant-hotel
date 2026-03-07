<?php

namespace App\Controllers\Admin;

use Slim\Flash\Messages;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\RoomBooking;
use App\Models\RestaurantReservation;
use App\Models\ContactMessage;
use App\Models\Gallery;
use Slim\Views\Twig;



class DashboardController
{
    protected $flash;

    public function __construct()
    {
        $this->flash = new Messages();
    }

    public function index(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);

        $stats = [
            'total_bookings' => RoomBooking::count(),
            'pending_bookings' => RoomBooking::where('status', 'pending')->count(),
            'total_reservations' => RestaurantReservation::count(),
            'today_reservations' => RestaurantReservation::whereDate('reservation_date', date('Y-m-d'))->count(),
            'unread_messages' => ContactMessage::where('is_read', false)->count(),
            'revenue' => RoomBooking::where('payment_status', 'paid')->sum('total_price')
        ];

        $recentBookings = RoomBooking::with('room')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentReservations = RestaurantReservation::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return $view->render($response, 'admin/dashboard.twig', [
            'stats' => $stats,
            'recentBookings' => $recentBookings,
            'recentReservations' => $recentReservations
        ]);
    }

    public function messages(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);

        $messages = ContactMessage::orderBy('created_at', 'desc')->get();

        return $view->render($response, 'admin/messages.twig', [
            'messages' => $messages
        ]);
    }

    public function showMessage(Request $request, Response $response, $args)
    {
        $view = Twig::fromRequest($request);

        $message = ContactMessage::find($args['id']);

        if ($message) {
            $message->is_read = true;
            $message->save();
        }

        return $view->render($response, 'admin/message.twig', [
            'message' => $message
        ]);
    }

    public function deleteMessage(Request $request, Response $response, $args)
    {
        $message = ContactMessage::find($args['id']);

        if ($message) {
            $message->delete();
        }

        return $response->withHeader('Location', '/admin/messages')->withStatus(302);
    }

    public function gallery(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);

        $images = Gallery::orderBy('sort_order')->get();

        return $view->render($response, 'admin/gallery.twig', [
            'images' => $images
        ]);
    }

    public function uploadImage(Request $request, Response $response)
    {
        $uploadedFiles = $request->getUploadedFiles();

        if (isset($uploadedFiles['image'])) {
            $image = $uploadedFiles['image'];

            if ($image->getError() === UPLOAD_ERR_OK) {

                $filename = uniqid() . '_' . $image->getClientFilename();
                $image->moveTo(__DIR__ . '/../../../public/uploads/' . $filename);

                Gallery::create([
                    'title' => $request->getParsedBody()['title'] ?? '',
                    'image' => $filename,
                    'category' => $request->getParsedBody()['category'] ?? 'hotel',
                    'description' => $request->getParsedBody()['description'] ?? '',
                    'sort_order' => $request->getParsedBody()['sort_order'] ?? 0
                ]);

                $this->flash->addMessage('success', "Image uploaded successfully.");
            } else {
                $this->flash->addMessage('error', "Image upload failed.");
            }
        }

        return $response->withHeader('Location', '/admin/gallery')->withStatus(302);
    }

    public function deleteImage(Request $request, Response $response, $args)
    {
        $image = Gallery::find($args['id']);

        if ($image) {

            $filepath = __DIR__ . '/../../../public/uploads/' . $image->image;

            if (file_exists($filepath)) {
                unlink($filepath);
            }

            $image->delete();

            $this->flash->addMessage('success', "Image deleted successfully.");
        } else {
            $this->flash->addMessage('error', "Image not found.");
        }

        return $response->withHeader('Location', '/admin/gallery')->withStatus(302);
    }

    public function settings(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'admin/settings.twig');
    }

    public function updateSettings(Request $request, Response $response)
    {
        // Update settings logic here
        return $response->withHeader('Location', '/admin/settings')->withStatus(302);
    }
}
