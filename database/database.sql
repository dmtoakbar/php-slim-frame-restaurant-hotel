-- Create database
CREATE DATABASE IF NOT EXISTS hotel_restaurant;
USE hotel_restaurant;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Rooms table
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) UNIQUE NOT NULL,
    type ENUM('standard', 'deluxe', 'suite', 'presidential') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL,
    description TEXT,
    amenities TEXT,
    status ENUM('available', 'occupied', 'maintenance', 'reserved') DEFAULT 'available',
    image VARCHAR(255) DEFAULT 'default-room.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Room bookings table
CREATE TABLE IF NOT EXISTS room_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT,
    user_id INT,
    guest_name VARCHAR(100) NOT NULL,
    guest_email VARCHAR(100),
    guest_phone VARCHAR(20),
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    adults INT DEFAULT 1,
    children INT DEFAULT 0,
    total_price DECIMAL(10,2),
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Menu categories table
CREATE TABLE IF NOT EXISTS menu_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Menu items table
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT 'default-food.jpg',
    is_available BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    dietary_info VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES menu_categories(id) ON DELETE CASCADE
);

-- Restaurant reservations table
CREATE TABLE IF NOT EXISTS restaurant_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_name VARCHAR(100) NOT NULL,
    guest_email VARCHAR(100),
    guest_phone VARCHAR(20),
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    guests INT NOT NULL,
    special_requests TEXT,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
);

-- Gallery table
CREATE TABLE IF NOT EXISTS gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100),
    image VARCHAR(255) NOT NULL,
    category ENUM('hotel', 'restaurant', 'events') DEFAULT 'hotel',
    description TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Contact messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: password)
INSERT INTO users (username, password, email, full_name, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'Administrator', 'admin');

-- Insert sample menu categories
INSERT INTO menu_categories (name, description, sort_order) VALUES
('Appetizers', 'Start your meal with our delicious appetizers', 1),
('Main Courses', 'Hearty and satisfying main dishes', 2),
('Desserts', 'Sweet endings to your meal', 3),
('Beverages', 'Refreshing drinks and cocktails', 4);

-- Insert sample rooms
INSERT INTO rooms (room_number, type, price, capacity, description, amenities, image) VALUES
('101', 'standard', 99.99, 2, 'Comfortable standard room with city view. Perfect for business travelers.', 'WiFi,TV,Air Conditioning,Work Desk', 'room-standard.jpg'),
('102', 'standard', 99.99, 2, 'Cozy standard room with modern amenities and comfortable bedding.', 'WiFi,TV,Air Conditioning,Mini Fridge', 'room-standard-2.jpg'),
('201', 'deluxe', 149.99, 3, 'Spacious deluxe room with sea view. Features a king-size bed and sitting area.', 'WiFi,TV,Air Conditioning,Mini Bar,Sea View', 'room-deluxe.jpg'),
('202', 'deluxe', 149.99, 3, 'Elegant deluxe room with panoramic views and luxury amenities.', 'WiFi,TV,Air Conditioning,Mini Bar,Bathrobe', 'room-deluxe-2.jpg'),
('301', 'suite', 249.99, 4, 'Luxury suite with separate living area and jacuzzi. Perfect for families.', 'WiFi,TV,Air Conditioning,Mini Bar,Jacuzzi,Living Area', 'room-suite.jpg'),
('401', 'presidential', 399.99, 6, 'The ultimate luxury experience. Presidential suite with private terrace and butler service.', 'WiFi,TV,Air Conditioning,Mini Bar,Jacuzzi,Terrace,Butler Service', 'room-presidential.jpg');

-- Insert sample menu items
INSERT INTO menu_items (category_id, name, description, price, image, is_featured, dietary_info) VALUES
(1, 'Bruschetta', 'Toasted bread with fresh tomatoes, garlic, and basil', 8.99, 'menu-bruschetta.jpg', true, 'Vegetarian'),
(1, 'Calamari', 'Crispy fried calamari served with marinara sauce', 10.99, 'menu-calamari.jpg', true, 'Seafood'),
(1, 'Stuffed Mushrooms', 'Mushrooms stuffed with cream cheese and herbs', 9.99, 'menu-mushrooms.jpg', false, 'Vegetarian'),
(2, 'Grilled Salmon', 'Fresh salmon grilled to perfection with lemon butter sauce', 22.99, 'menu-salmon.jpg', true, 'Gluten-Free'),
(2, 'Beef Tenderloin', 'Prime beef tenderloin with red wine reduction', 28.99, 'menu-beef.jpg', true, 'Contains Nuts'),
(2, 'Chicken Parmesan', 'Breaded chicken breast with marinara and melted cheese', 18.99, 'menu-chicken.jpg', false, 'Contains Dairy'),
(3, 'Tiramisu', 'Classic Italian dessert with coffee and mascarpone', 7.99, 'menu-tiramisu.jpg', true, 'Contains Dairy'),
(3, 'Chocolate Lava Cake', 'Warm chocolate cake with molten center', 6.99, 'menu-lava-cake.jpg', false, 'Contains Dairy'),
(4, 'Fresh Lemonade', 'Homemade lemonade with fresh mint', 4.99, 'menu-lemonade.jpg', true, 'Vegan'),
(4, 'House Wine', 'Selection of red and white wines', 6.99, 'menu-wine.jpg', false, 'Contains Sulfites');

-- Insert sample gallery images
INSERT INTO gallery (title, image, category, description, sort_order) VALUES
('Luxury Suite', 'gallery-suite.jpg', 'hotel', 'Our premium suite with panoramic views', 1),
('Hotel Lobby', 'gallery-lobby.jpg', 'hotel', 'Elegant lobby with modern design', 2),
('Restaurant Dining Area', 'gallery-restaurant.jpg', 'restaurant', 'Fine dining restaurant with cozy atmosphere', 3),
('Swimming Pool', 'gallery-pool.jpg', 'hotel', 'Outdoor swimming pool with lounge area', 4),
('Gourmet Dish', 'gallery-dish.jpg', 'restaurant', 'Chef\'s special creation', 5),
('Conference Room', 'gallery-conference.jpg', 'events', 'Fully equipped conference room', 6);