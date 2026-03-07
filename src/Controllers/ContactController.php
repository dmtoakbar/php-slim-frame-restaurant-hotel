<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\ContactMessage;
use Slim\Views\Twig;
use PHPMailer\PHPMailer\PHPMailer;

class ContactController
{
    public function index(Request $request, Response $response)
    {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'contact.twig');
    }
    
    public function send(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        
        // Save to database
        $message = ContactMessage::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? '',
            'subject' => $data['subject'],
            'message' => $data['message'],
            'is_read' => false
        ]);
        
        // Send email notification to admin
        $this->sendEmailNotification($message);
        
        $payload = [
            'success' => true,
            'message' => 'Message sent successfully'
        ];
        
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    private function sendEmailNotification($message)
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
            $mail->addAddress($_ENV['SMTP_USER']);
            $mail->addReplyTo($message->email, $message->name);
            
            $mail->isHTML(true);
            $mail->Subject = 'New Contact Message: ' . $message->subject;
            
            $body = "
            <h2>New Contact Message</h2>
            <p><strong>Name:</strong> {$message->name}</p>
            <p><strong>Email:</strong> {$message->email}</p>
            <p><strong>Phone:</strong> {$message->phone}</p>
            <p><strong>Subject:</strong> {$message->subject}</p>
            <p><strong>Message:</strong></p>
            <p>{$message->message}</p>
            ";
            
            $mail->Body = $body;
            $mail->send();
            
        } catch (\Exception $e) {
            error_log("Email notification failed: " . $e->getMessage());
        }
    }
}