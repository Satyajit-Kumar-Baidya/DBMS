<?php
if (isset($_POST['message'])) {
    $userMessage = strtolower(trim($_POST['message']));
} else {
    $userMessage = "";
}

$response = "";

if ($userMessage == "") {
    $response = "Please type a message to get a response.";
}elseif (strpos($userMessage, 'hi') !== false) {
    $response = "Hello there.How can I help you";
} 
elseif (strpos($userMessage, 'hello') !== false) {
    $response = "Hello there.How can I help you";
} 

 elseif (strpos($userMessage, 'fever') !== false) {
    $response = "Take paracetamol or Napa. 🤒";
} 

elseif (strpos($userMessage, 'headache') !== false) {
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

echo $response;
?>