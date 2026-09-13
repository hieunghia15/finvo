<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>Finvo</title>

    <!-- Links Of CSS File -->
    <link rel="stylesheet" href="assets/trezo/css/sidebar-menu.css">
    <link rel="stylesheet" href="assets/trezo/css/simplebar.css">
    <link rel="stylesheet" href="assets/trezo/css/apexcharts.css">
    <link rel="stylesheet" href="assets/trezo/css/prism.css">
    <link rel="stylesheet" href="assets/trezo/css/rangeslider.css">
    <link rel="stylesheet" href="assets/trezo/css/quill.snow.css">
    <link rel="stylesheet" href="assets/trezo/css/google-icon.css">
    <link rel="stylesheet" href="assets/trezo/css/remixicon.css">
    <link rel="stylesheet" href="assets/trezo/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="assets/trezo/css/fullcalendar.main.css">
    <link rel="stylesheet" href="assets/trezo/css/jsvectormap.min.css">
    <link rel="stylesheet" href="assets/trezo/css/lightpick.css">
    <link rel="stylesheet" href="assets/trezo/css/style.css">

    <!-- Assets / Inertia Head -->
    @viteReactRefresh
    @vite(['resources/js/app.tsx'])
    @inertiaHead
</head>
<body>
    @inertia
    <!-- Link Of JS File -->
    <script src="assets/trezo/js/bootstrap.bundle.min.js"></script>
    <script src="assets/trezo/js/sidebar-menu.js"></script>
    <script src="assets/trezo/js/dragdrop.js"></script>
    <script src="assets/trezo/js/rangeslider.min.js"></script>
    <script src="assets/trezo/js/quill.min.js"></script>
    <script src="assets/trezo/js/data-table.js"></script>
    <script src="assets/trezo/js/prism.js"></script>
    <script src="assets/trezo/js/clipboard.min.js"></script>
    <script src="assets/trezo/js/feather.min.js"></script>
    <script src="assets/trezo/js/simplebar.min.js"></script>
    <script src="assets/trezo/js/apexcharts.min.js"></script>
    <script src="assets/trezo/js/echarts.min.js"></script>
    <script src="assets/trezo/js/swiper-bundle.min.js"></script>
    <script src="assets/trezo/js/fullcalendar.main.js"></script>
    <script src="assets/trezo/js/jsvectormap.min.js"></script>
    <script src="assets/trezo/js/world-merc.js"></script>
    <script src="assets/trezo/js/moment.min.js"></script>
    <script src="assets/trezo/js/lightpick.js"></script>
    <script src="assets/trezo/js/custom/apexcharts.js"></script>
    <script src="assets/trezo/js/custom/echarts.js"></script>
    <script src="assets/trezo/js/custom/custom.js"></script>
</body>
</html>
