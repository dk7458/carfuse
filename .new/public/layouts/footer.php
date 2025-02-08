<?php
if (!empty($_SESSION['footer_loaded'])) {
    return;
}
$_SESSION['footer_loaded'] = true;
?>

<footer class="footer">
    <p>&copy; <?= date("Y") ?> CarFuse - Wszystkie prawa zastrzeżone.</p>
</footer>

</body>
</html>
