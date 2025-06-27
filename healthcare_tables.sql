-- Create database if not exists
CREATE DATABASE IF NOT EXISTS healthcare_db;
USE healthcare_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'doctor', 'patient') NOT NULL DEFAULT 'patient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Doctors table
CREATE TABLE IF NOT EXISTS doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    qualification VARCHAR(200) NOT NULL,
    experience INT NOT NULL,
    hospital VARCHAR(200) NOT NULL,
    location VARCHAR(200) NOT NULL,
    background TEXT,
    available_days VARCHAR(200),
    consultation_fee DECIMAL(10,2),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Patients table
CREATE TABLE IF NOT EXISTS patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    dob DATE,
    gender ENUM('male', 'female', 'other'),
    blood_group VARCHAR(5),
    address TEXT,
    phone VARCHAR(20),
    emergency_contact VARCHAR(20),
    medical_history TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Appointments table
CREATE TABLE IF NOT EXISTS appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL AFTER appointment_date,
    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
    reason TEXT,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id),
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

-- Prescriptions table
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT,
    diagnosis TEXT NOT NULL,
    prescription_date DATE NOT NULL,
    next_visit_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

-- Prescription Medicines table
CREATE TABLE IF NOT EXISTS prescription_medicines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prescription_id INT NOT NULL,
    medicine_name VARCHAR(100) NOT NULL,
    dosage VARCHAR(50) NOT NULL,
    frequency VARCHAR(50) NOT NULL,
    duration VARCHAR(50) NOT NULL,
    instructions TEXT,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE
);

-- Medical Records table
CREATE TABLE IF NOT EXISTS medical_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    record_type VARCHAR(50) NOT NULL,
    record_date DATE NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- Create ambulances table
CREATE TABLE IF NOT EXISTS ambulances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_number VARCHAR(50) NOT NULL,
    vehicle_type VARCHAR(100) NOT NULL,
    driver_name VARCHAR(100) NOT NULL,
    driver_contact VARCHAR(20) NOT NULL,
    location VARCHAR(255) NOT NULL,
    price_per_km DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'busy', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create ambulance_bookings table
CREATE TABLE IF NOT EXISTS ambulance_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ambulance_id INT NOT NULL,
    patient_id INT NOT NULL,
    pickup_location VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ambulance_id) REFERENCES ambulances(id),
    FOREIGN KEY (patient_id) REFERENCES patients(id)
);

-- Blood Stock table
CREATE TABLE IF NOT EXISTS blood_stock (
    id INT PRIMARY KEY AUTO_INCREMENT,
    blood_group VARCHAR(5) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Blood Donors table
CREATE TABLE IF NOT EXISTS blood_donors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    blood_group VARCHAR(5) NOT NULL,
    age INT NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT NOT NULL,
    last_donation_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
);

-- Blood Requests table
CREATE TABLE IF NOT EXISTS blood_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT,
    requester_name VARCHAR(100) NOT NULL,
    blood_group VARCHAR(5) NOT NULL,
    units_needed INT NOT NULL,
    urgency ENUM('normal', 'urgent', 'critical') NOT NULL DEFAULT 'normal',
    hospital_name VARCHAR(200) NOT NULL,
    contact_phone VARCHAR(20) NOT NULL,
    contact_email VARCHAR(100),
    request_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
);

-- Blood Donations table
CREATE TABLE IF NOT EXISTS blood_donations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    donor_id INT NOT NULL,
    donation_date DATE NOT NULL,
    units INT NOT NULL DEFAULT 1,
    blood_group VARCHAR(5) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES blood_donors(id) ON DELETE CASCADE
);