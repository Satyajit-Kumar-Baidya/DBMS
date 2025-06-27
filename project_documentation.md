# Healthcare Management System - Blood Bank and Chatbot Documentation

## Table of Contents
1. [Blood Bank Implementation](#blood-bank-implementation)
2. [Chatbot Implementation](#chatbot-implementation)
3. [Integration with Main Project](#integration-with-main-project)
4. [Potential Interview Questions](#potential-interview-questions)

## Blood Bank Implementation

### Overview
The Blood Bank module is a comprehensive system that allows different user roles (admin, doctor, patient) to manage blood donations and requests. The system is built using PHP and uses file-based storage for simplicity.

### Key Features
1. **Role-Based Access Control**
   - Admins and doctors can add/edit/delete donors
   - Patients can request blood
   - Different navigation menus based on user role

2. **Donor Management**
   - Add new donors with details (name, address, email, phone, blood type)
   - Edit existing donor information
   - Delete donor records
   - Search donors by blood type

3. **Blood Request System**
   - Patients can request blood from specific donors
   - Request tracking and management
   - Request history viewing

### Code Structure

#### 1. Navigation System (`navigation.php`)
```php
// Session variables to check user roles
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_person = isset($_SESSION['person_logged_in']) && $_SESSION['person_logged_in'] === true;
$is_doctor = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'doctor';
$is_patient = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'patient';

// Doctors get admin privileges
if ($is_doctor) {
    $is_admin = true;
}

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
```
This code manages the navigation menu based on user roles. It checks session variables to determine user type and provides appropriate menu items.

#### 2. Donor Management (`add_donor.php`, `edit_donor.php`, `delete_donor.php`)
- File-based storage using `donors.txt`
- Each donor record format: `name|address|email|phone|blood_type`
- Role-based access control for donor management

#### 3. Blood Request System (`request.php`, `add_request.php`)
- Patients can request blood from specific donors
- Request tracking with timestamps
- Request history management

### Integration with Main Project
The Blood Bank module is integrated into the main Healthcare Management System through:

1. **Navigation Integration**
```php
// In header.php
<li class="nav-item">
    <a class="nav-link" href="blood_bank/index.php">
        <i class="fas fa-tint"></i> Blood Bank
    </a>
</li>
```

2. **Session Management**
- Uses the main system's session management
- Role-based access control integrated with main system

## Chatbot Implementation

### Overview
The Chatbot module provides a simple health consultation interface where users can ask about common health issues and receive automated responses.

### Key Features
1. **Real-time Chat Interface**
   - Modern UI with dark mode support
   - Typing indicators
   - Message history

2. **Health Consultation**
   - Pre-defined responses for common health issues
   - Emoji support for better user experience
   - Fallback responses for unknown queries

### Code Structure

#### 1. Frontend (`index.html`)
```html
<div class="chat-container">
    <h2>HealthBot 💬</h2>
    <div id="messages"></div>
    <div class="input-area">
        <input type="text" id="userInput" placeholder="Type your symptoms...">
        <button onclick="sendMessage()">Send</button>
    </div>
</div>
```

#### 2. Backend Logic (`bot.php`)
```php
// Process user message
if (isset($_POST['message'])) {
    $userMessage = strtolower(trim($_POST['message']));
} else {
    $userMessage = "";
}

// Generate response based on keywords
if (strpos($userMessage, 'fever') !== false) {
    $response = "Take paracetamol or Napa. 🤒";
} elseif (strpos($userMessage, 'headache') !== false) {
    $response = "Drink water and rest. You can also take Napa Extra. 💊";
} elseif (strpos($userMessage, 'cough') !== false) {
    $response = "Try warm fluids and lozenges. If it continues, see a doctor. 🤧";
} elseif (strpos($userMessage, 'stomach pain') !== false) {
    $response = "Take antacids if mild. If severe, consult a doctor. 🍵";
} elseif (strpos($userMessage, 'cold') !== false) {
    $response = "Stay warm and rest well. Drink plenty of fluids. 🧣";
} elseif (strpos($userMessage, 'dizziness') !== false) {
    $response = "Sit or lie down immediately. Drink water and avoid sudden movements. ⚠️";
} elseif (strpos($userMessage, 'allergy') !== false) {
    $response = "Avoid allergens and consider antihistamines after consulting a doctor. 🌼";
} elseif (strpos($userMessage, 'back pain') !== false) {
    $response = "Use heat packs and try gentle stretching exercises. See a doctor if pain persists. 🧘";
} elseif (strpos($userMessage, 'diabetes') !== false) {
    $response = "Maintain a healthy diet and monitor your blood sugar regularly. Consult your doctor for meds. 🍎";
} elseif (strpos($userMessage, 'fatigue') !== false) {
    $response = "Ensure you get enough rest, stay hydrated, and eat nutritious food. 💤";
} else {
    $response = "Sorry, I don't understand. Please ask another health-related question. 🤷‍♂️";
}
```
This file processes user messages and generates appropriate responses based on keywords.

#### 3. JavaScript Integration (`chatbot.js`)
```javascript
function sendMessage() {
    const input = document.getElementById('userInput');
    const message = input.value.trim();
    if (!message) return;

    // Display user message
    const messagesDiv = document.getElementById('messages');
    messagesDiv.innerHTML += `<div class="msg user">You: ${message}</div>`;
    input.value = '';
    
    // Show typing indicator
    messagesDiv.innerHTML += `<div class="typing-indicator" id="typing">Bot is typing...</div>`;
    messagesDiv.scrollTop = messagesDiv.scrollHeight;

    // Send message to backend
    fetch('bot.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'message=' + encodeURIComponent(message)
    })
    .then(response => response.text())
    .then(reply => {
        // Remove typing indicator and show bot response
        const typingIndicator = document.getElementById('typing');
        if (typingIndicator) typingIndicator.remove();
        messagesDiv.innerHTML += `<div class="msg bot">Bot: ${reply}</div>`;
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    });
}

// Dark mode toggle
function toggleDarkMode() {
    document.body.classList.toggle('dark');
}
```
This file handles the chat interface functionality, including message sending, response display, and dark mode toggle.

### Integration with Main Project
The Chatbot is integrated into the main system through:

1. **Navigation Integration**
```php
// In header.php
<li class="nav-item">
    <a class="nav-link" href="chatbot.php">
        <i class="fas fa-robot"></i> Chatbot
    </a>
</li>
```

2. **Session Management**
- Uses the main system's session management
- Role-based access control integrated with main system

## Potential Interview Questions

### Blood Bank Module

1. **Q: How did you implement role-based access control in the Blood Bank system?**
   A: The system uses session variables to track user roles:
   ```php
   $is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
   $is_doctor = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'doctor';
   $is_patient = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'patient';
   ```

2. **Q: How do you handle blood requests in your system?**
   A: The system uses a file-based approach where each request is stored with donor and requester information:
   ```php
   $line = "$donor_name|$donor_blood|$donor_email|$requester_name|$requester_email|$requester_phone|$timestamp\n";
   file_put_contents('requests.txt', $line, FILE_APPEND);
   ```

### Chatbot Module

1. **Q: How does your chatbot handle different types of health queries?**
   A: The chatbot uses string matching to identify keywords in user messages:
   ```php
   if (strpos($userMessage, 'fever') !== false) {
       $response = "Take paracetamol or Napa. 🤒";
   } elseif (strpos($userMessage, 'headache') !== false) {
       $response = "Drink water and rest. You can also take Napa Extra. 💊";
   }
   ```

2. **Q: How did you implement the real-time chat interface?**
   A: The interface uses JavaScript fetch API for asynchronous communication:
   ```javascript
   fetch('bot.php', {
       method: 'POST',
       headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
       body: 'message=' + encodeURIComponent(message)
   })
   ```

### General Integration Questions

1. **Q: How did you integrate your modules with the main Healthcare Management System?**
   A: The integration was done through:
   - Navigation menu additions in header.php
   - Session management integration
   - Consistent styling and UI elements

2. **Q: What security measures did you implement in your modules?**
   A: Security measures include:
   - Session-based authentication
   - Role-based access control
   - Input sanitization
   - File-based data storage with proper validation 