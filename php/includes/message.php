
<?php if (!empty($message)): ?>
  <div id="message-box" class="message-box">
    <?php echo htmlspecialchars($message); ?>
    <button id="message-close-btn" class="message-close-btn">OK</button>
  </div>

  <script>
    document.getElementById('message-close-btn').addEventListener('click', function() {
      document.getElementById('message-box').style.display = 'none';
    });
  </script>
<?php endif; ?>