function sendMessage() {
  const input = document.getElementById('userInput');
  const message = input.value.trim();
  if (!message) return;

  const messagesDiv = document.getElementById('messages');
  messagesDiv.innerHTML += `<div class="msg user">You: ${message}</div>`;
  input.value = '';
  messagesDiv.innerHTML += `<div class="typing-indicator" id="typing">Bot is typing...</div>`;
  messagesDiv.scrollTop = messagesDiv.scrollHeight;

  fetch('bot.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'message=' + encodeURIComponent(message)  // <-- key is now 'message'
  })
  .then(response => response.text())
  .then(reply => {
    const typingIndicator = document.getElementById('typing');
    if (typingIndicator) typingIndicator.remove();
    messagesDiv.innerHTML += `<div class="msg bot">Bot: ${reply}</div>`;
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
  });
}

function toggleDarkMode() {
  document.body.classList.toggle('dark');
}