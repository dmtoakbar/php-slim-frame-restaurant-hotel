<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

// Start session
session_start();

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Create app
$app = AppFactory::create();

// IMPORTANT MIDDLEWARES
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

// Add error middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Set up Eloquent ORM
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_NAME'] ?? 'hotel_restaurant',
    'username' => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASS'] ?? '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Set up Twig
$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

// Add session to twig
$twig->getEnvironment()->addGlobal('session', $_SESSION);
$flash = new \Slim\Flash\Messages();
$twig->getEnvironment()->addGlobal('flash', $flash->getMessages());
$twig->getEnvironment()->addGlobal('currency', '₹');
$twig->getEnvironment()->addGlobal('currency_icon', 'fa-rupee-sign');

// Register routes
(require __DIR__ . '/../src/routes.php')($app);

// Run app
$app->run();