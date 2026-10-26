<?php
/**
 * StudentHub Portal - Shared Footer Component
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */
?>
    <footer>
        <hr>
        <p>&copy; <?= date('Y') ?> StudentHub Portal &bull; Dwij Patel (25CE077) &bull; Department of Computer Engineering, CSPIT, CHARUSAT</p>
        <div class="footer-links">
            <a href="Contact.php">Helpdesk Support</a> &bull;
            <a href="events.php">Campus Events</a> &bull;
            <a href="Notices.html">Academic Notices</a>
        </div>
    </footer>

    <script src="script.js"></script>
    <?php if (isset($extraJs)): ?>
        <?= $extraJs ?>
    <?php endif; ?>
</body>
</html>
