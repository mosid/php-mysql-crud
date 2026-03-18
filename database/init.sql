-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create sample data
INSERT INTO users (name, email, phone) VALUES
('John Doe', 'john@example.com', '+1-234-567-8900'),
('Jane Smith', 'jane@example.com', '+1-234-567-8901'),
('Bob Johnson', 'bob@example.com', '+1-234-567-8902');

-- Create an index on email for faster lookups
CREATE INDEX idx_email ON users(email);
