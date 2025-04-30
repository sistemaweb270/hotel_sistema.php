<?php
// admin/_admin_footer.php
// Assuming $conn is the database connection from db.php
if (isset($conn) && $conn) {
    $conn->close();
}
?>
        </div> </div> </body>
</html>