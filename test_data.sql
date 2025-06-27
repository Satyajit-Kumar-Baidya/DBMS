-- Insert test users (doctors and patients)
INSERT INTO users (first_name, last_name, email, password, role) VALUES
('John', 'Smith', 'john.smith@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor'),
('Sarah', 'Johnson', 'sarah.johnson@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor'),
('Michael', 'Brown', 'michael.brown@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor'),
('Robert', 'Patient', 'robert@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient'),
('Mary', 'Wilson', 'mary@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient');

-- Insert doctors with detailed information
INSERT INTO doctors (user_id, specialization, qualification, experience, hospital, location, background, available_days, consultation_fee) VALUES
(1, 'Cardiologist', 'MD, DM Cardiology', 15, 'Heart Care Hospital', 'Downtown Medical Center, New York', 'Specialized in interventional cardiology with extensive research in heart diseases. Former chief of cardiology at Mayo Clinic.', 'Monday,Tuesday,Wednesday,Friday', 150.00),
(2, 'Dermatologist', 'MD, DVD', 8, 'Skin & Care Clinic', 'Westside Medical Plaza, Chicago', 'Expert in cosmetic dermatology and skin cancer treatment. Published researcher in dermatological conditions.', 'Monday,Wednesday,Thursday,Saturday', 120.00),
(3, 'Orthopedic Surgeon', 'MS Ortho, FRCS', 12, 'Joint Care Hospital', 'Eastside Healthcare Center, Boston', 'Specialized in joint replacement surgery and sports injuries. Team doctor for Boston Marathon.', 'Tuesday,Thursday,Friday,Saturday', 180.00);

-- Insert patients with details
INSERT INTO patients (user_id, dob, gender, blood_group, address, phone, emergency_contact, medical_history) VALUES
(4, '1985-06-15', 'male', 'O+', '123 Patient Street, New York', '555-0123', '555-0124', 'No major health issues'),
(5, '1990-03-22', 'female', 'A+', '456 Health Avenue, Chicago', '555-0125', '555-0126', 'Mild asthma');

-- Insert sample appointments
INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status, reason, created_at) VALUES
(1, 1, CURDATE(), '10:00:00', 'pending', 'Regular heart checkup', NOW()),
(1, 2, CURDATE(), '14:30:00', 'accepted', 'Skin rash consultation', NOW()),
(2, 3, CURDATE() + INTERVAL 1 DAY, '11:00:00', 'pending', 'Knee pain evaluation', NOW()),
(2, 1, CURDATE() + INTERVAL 2 DAY, '15:00:00', 'rejected', 'Heart palpitations', NOW()); 