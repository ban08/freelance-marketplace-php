-- Enable foreign key support
PRAGMA foreign_keys = ON;

-- Drop tables if they exist
DROP TABLE IF EXISTS service_images;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    profile_picture TEXT DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    joined_date DATE DEFAULT CURRENT_TIMESTAMP,
    is_admin BOOLEAN DEFAULT 0,
    CONSTRAINT valid_email CHECK (email LIKE '%_@_%._%')
);

-- Create categories table
CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT DEFAULT NULL
);

-- Create services table
CREATE TABLE services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    freelancer_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    base_price REAL NOT NULL,
    delivery_time_days INTEGER NOT NULL,
    status TEXT DEFAULT 'active' NOT NULL, -- active, paused, deleted
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (freelancer_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT,
    CONSTRAINT positive_price CHECK (base_price >= 0),
    CONSTRAINT positive_delivery_time CHECK (delivery_time_days > 0),
    CONSTRAINT valid_status CHECK (status IN ('active', 'paused', 'deleted'))
);

-- Create service_images table
CREATE TABLE service_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_id INTEGER NOT NULL,
    image_path TEXT NOT NULL,
    caption TEXT DEFAULT NULL,
    display_order INTEGER DEFAULT 0,
    FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE
);

-- Create orders table
CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    service_id INTEGER NOT NULL,
    price REAL NOT NULL,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TEXT DEFAULT 'pending' NOT NULL, -- pending, in_progress, completed, cancelled, disputed
    completion_date DATETIME DEFAULT NULL,
    requirements TEXT DEFAULT NULL,
    FOREIGN KEY (client_id) REFERENCES users (id) ON DELETE RESTRICT,
    FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE RESTRICT,
    CONSTRAINT positive_order_price CHECK (price >= 0),
    CONSTRAINT valid_order_status CHECK (status IN ('pending', 'in_progress', 'completed', 'cancelled', 'disputed'))
);

-- Create messages table
CREATE TABLE messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER NOT NULL,
    receiver_id INTEGER NOT NULL,
    order_id INTEGER DEFAULT NULL,
    content TEXT NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT 0,
    FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE SET NULL
);

-- Create reviews table
CREATE TABLE reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    service_id INTEGER NOT NULL,
    rating INTEGER NOT NULL,
    comment TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE,
    CONSTRAINT rating_range CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT one_review_per_service_per_client UNIQUE (client_id, service_id)
);

CREATE TABLE service_packages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_id INTEGER NOT NULL,
    package_name TEXT NOT NULL, -- 'Basic', 'Standard', 'Premium'
    description TEXT NOT NULL,
    price REAL NOT NULL,
    delivery_time_days INTEGER NOT NULL,
    revisions INTEGER DEFAULT 1,
    FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE,
    CONSTRAINT valid_package CHECK (package_name IN ('Basic', 'Standard', 'Premium'))
);

CREATE TABLE skills (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);

CREATE TABLE user_skills (
    user_id INTEGER NOT NULL,
    skill_id INTEGER NOT NULL,
    proficiency_level TEXT DEFAULT 'Intermediate', -- 'Beginner', 'Intermediate', 'Expert'
    PRIMARY KEY (user_id, skill_id),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills (id) ON DELETE CASCADE,
    CONSTRAINT valid_proficiency CHECK (proficiency_level IN ('Beginner', 'Intermediate', 'Expert'))
);

CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    content TEXT NOT NULL,
    type TEXT NOT NULL, -- 'message', 'order', 'review', etc.
    related_id INTEGER, -- ID of the related entity (order_id, message_id, etc.)
    is_read BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE saved_services (
    user_id INTEGER NOT NULL,
    service_id INTEGER NOT NULL,
    saved_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, service_id),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE
);

-- Create indexes for frequently queried columns
CREATE INDEX idx_services_freelancer ON services (freelancer_id);
CREATE INDEX idx_services_category ON services (category_id);
CREATE INDEX idx_services_status ON services (status);
CREATE INDEX idx_orders_client ON orders (client_id);
CREATE INDEX idx_orders_service ON orders (service_id);
CREATE INDEX idx_orders_status ON orders (status);
CREATE INDEX idx_messages_sender ON messages (sender_id);
CREATE INDEX idx_messages_receiver ON messages (receiver_id);
CREATE INDEX idx_messages_order ON messages (order_id);
CREATE INDEX idx_reviews_service ON reviews (service_id);

-- Insert default admin user
INSERT INTO users (username, password, name, email, is_admin)
VALUES ('admin', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'Site Administrator', 'admin@freelance.com', 1);

-- Insert sample categories
INSERT INTO categories (name, description)
VALUES 
('Web Development', 'Services related to website and web application development'),
('Graphic Design', 'Visual content creation and design services'),
('Writing & Translation', 'Content writing, copywriting, and translation services'),
('Digital Marketing', 'Services to promote businesses online'),
('Video & Animation', 'Video creation, editing, and animation services');
