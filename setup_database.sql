-- EasyComp Database Setup Script

-- Drop tables if they exist to avoid conflicts
DROP TABLE IF EXISTS competition_participants;
DROP TABLE IF EXISTS competitions;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS organizers;
DROP TABLE IF EXISTS admins;

-- Create admin table
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    profile_picture VARCHAR(255) DEFAULT 'default.jpg',
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Create student table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    profile_picture VARCHAR(255) DEFAULT 'default.jpg',
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    registration_number VARCHAR(50) UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Create organizers table
CREATE TABLE organizers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    profile_picture VARCHAR(255) DEFAULT 'default.jpg',
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Create categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create competitions table
CREATE TABLE competitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    category_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    organizer_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (organizer_id) REFERENCES organizers(id) ON DELETE CASCADE
);

-- Create competition_participants table
CREATE TABLE competition_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    competition_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (competition_id, student_id)
);

-- Insert default categories
INSERT INTO categories (name, description) VALUES
('Academic', 'Academic competitions including debates, quizzes, and knowledge tests'),
('Sports', 'Athletic competitions and tournaments'),
('Arts', 'Competitions focused on artistic expression and creativity'),
('Technology', 'Technical challenges, coding competitions, and hackathons'),
('Business', 'Entrepreneurship, case studies, and business plan competitions');

-- Insert default admin account
INSERT INTO admins (username, email, password, profile_picture) VALUES
('admin', 'admin@easycomp.com', '$2y$10$x5Hgc6VnTzB.vJLT0r5K7OqQ/a.jiUfhbYjBrLxNy9xQ8xjTMLOBe', 'default.jpg');

-- Add indices to improve query performance
ALTER TABLE competitions ADD INDEX organizer_id_idx (organizer_id);
ALTER TABLE competitions ADD INDEX category_id_idx (category_id);
ALTER TABLE competitions ADD INDEX status_idx (status);
ALTER TABLE competitions ADD INDEX date_idx (start_date, end_date);

ALTER TABLE competition_participants ADD INDEX competition_id_idx (competition_id);
ALTER TABLE competition_participants ADD INDEX student_id_idx (student_id);
ALTER TABLE competition_participants ADD INDEX status_idx (status);
ALTER TABLE competition_participants ADD INDEX created_idx (created_at); 