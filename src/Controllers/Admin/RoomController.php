<?php

namespace App\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Room;
use Slim\Views\Twig;
use Slim\Flash\Messages;

class RoomController
{

    protected $flash;

    public function __construct()
    {
        $this->flash = new Messages();
    }

    public function index(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);

        $rooms = Room::orderBy('room_number')->get();

        return $view->render($response, 'admin/rooms/index.twig', [
            'rooms' => $rooms
        ]);
    }

    public function create(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'admin/rooms/create.twig');
    }

    public function store(Request $request, Response $response)
    {
        $data = $request->getParsedBody();


        // Check if room number already exists
        $existingRoom = Room::where('room_number', $data['room_number'])->first();

        if ($existingRoom) {
            $this->flash->addMessage('error', 'Room number already exists.');
            return $response->withHeader('Location', '/admin/rooms')->withStatus(302);
        }


        // Handle image upload
        $image = 'default-room.jpg';
        $uploadedFiles = $request->getUploadedFiles();

        if (isset($uploadedFiles['image']) && $uploadedFiles['image']->getError() === UPLOAD_ERR_OK) {
            $uploadedFile = $uploadedFiles['image'];
            $filename = uniqid() . '_' . $uploadedFile->getClientFilename();
            $uploadedFile->moveTo(__DIR__ . '/../../../public/uploads/' . $filename);
            $image = $filename;
        }

        try {
            Room::create([
                'room_number' => $data['room_number'],
                'type' => $data['type'],
                'price' => $data['price'],
                'capacity' => $data['capacity'],
                'description' => $data['description'],
                'amenities' => $data['amenities'],
                'status' => $data['status'],
                'image' => $image
            ]);

            $this->flash->addMessage('success', "Room created successfully.");
            return $response->withHeader('Location', '/admin/rooms')->withStatus(302);
        } catch (\Exception $e) {
            $this->flash->addMessage('error', "Failed to create room: ");
            return $response->withHeader('Location', '/admin/rooms')->withStatus(302);
        }
    }

    public function edit(Request $request, Response $response, $args)
    {
        $view = Twig::fromRequest($request);

        $room = Room::find($args['id']);

        if (!$room) {
            return $response->withHeader('Location', '/admin/rooms')->withStatus(302);
        }

        return $view->render($response, 'admin/rooms/edit.twig', [
            'room' => $room
        ]);
    }

    public function update(Request $request, Response $response, $args)
    {
        try {
            $room = Room::find($args['id']);
            $data = $request->getParsedBody();

            // Check duplicate room number except current room
            $existingRoom = Room::where('room_number', $data['room_number'])
                ->where('id', '!=', $args['id'])
                ->first();

            if ($existingRoom) {
                $this->flash->addMessage('error', 'Room number already exists.');
                return $response->withHeader('Location', '/admin/rooms')->withStatus(302);
            }

            // Handle image upload
            $image = $room->image;
            $uploadedFiles = $request->getUploadedFiles();

            if (isset($uploadedFiles['image']) && $uploadedFiles['image']->getError() === UPLOAD_ERR_OK) {
                // Delete old image
                if ($room->image && $room->image !== 'default-room.jpg') {
                    $oldFile = __DIR__ . '/../../../public/uploads/' . $room->image;
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                $uploadedFile = $uploadedFiles['image'];
                $filename = uniqid() . '_' . $uploadedFile->getClientFilename();
                $uploadedFile->moveTo(__DIR__ . '/../../../public/uploads/' . $filename);
                $image = $filename;
            }

            $room->update([
                'room_number' => $data['room_number'],
                'type' => $data['type'],
                'price' => $data['price'],
                'capacity' => $data['capacity'],
                'description' => $data['description'],
                'amenities' => $data['amenities'],
                'status' => $data['status'],
                'image' => $image
            ]);

            $this->flash->addMessage('success', "Room updated successfully.");
            return $response->withHeader('Location', '/admin/rooms')->withStatus(302);
        } catch (\Exception $e) {
            $this->flash->addMessage('error', "Failed to update room: ");
            return $response->withHeader('Location', '/admin/rooms')->withStatus(302);
        }
    }


    public function delete(Request $request, Response $response, $args)
    {
        $room = Room::find($args['id']);

        if ($room) {
            // Delete image
            if ($room->image && $room->image !== 'default-room.jpg') {
                $file = __DIR__ . '/../../../public/uploads/' . $room->image;
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            $room->delete();
        }

        return $response->withHeader('Location', '/admin/rooms')->withStatus(302);
    }
}
