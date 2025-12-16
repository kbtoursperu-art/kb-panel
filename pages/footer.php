<footer class="footer">
    &copy; <?php echo date("Y"); ?> Mi Empresa. Todos los derechos reservados.
</footer>

<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script>
    $(document).ready(function () {
        $("#toggle-sidebar").click(function () {
            $("#sidebar").toggleClass("active");
        });
    });
</script>
</body>
</html>
