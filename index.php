
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bienvenido al Sistema</title>
  <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Arial', sans-serif;
      background-color: #f4f4f9;
      color: #333;
    }

    .swiper-container {
      width: 100%;
      height: 100vh;
      position: relative;
      overflow: hidden;
    }

    .swiper-slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .swiper-pagination-bullet {
      background-color: #fff !important;
      opacity: 1;
    }

    .swiper-button-next, .swiper-button-prev {
      color: #fff;
    }

    header {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 10;
      text-align: center;
      padding: 20px 0;
    }

    .logo-container img {
      width: 100px;
      height: auto;
      margin-right:80%;
    }

    .welcome-container {
      text-align: center;
      padding: 50px 20px;

    }
    .welcome-message p {
      font-size: 3em;
      font-weight: bold;
      color: #ffffff;
      margin-top: 12%;
      margin-bottom: 10px;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);
    }
    .welcome-message {
      font-size: 3em;
      font-weight: bold;
      color: #ffffff;
      margin-top: 12%;
      margin-bottom: 10px;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);
    }

    .welcome-container p {
      font-size: 1.2em;
      margin-bottom: 20px;
      color: #ffffff;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.6);
    }

    .btn-ingresar {
      display: inline-block;
      font-size: 1.2em;
      margin-top: 5%;
      padding: 15px 30px;
      background-color:rgb(28, 97, 187);
      color: #fff;
      text-decoration: none;
      border-radius: 30px;
      transition: all 0.3s ease;
      box-shadow: 0 5px 15px rgba(48, 126, 216, 0.4);
    }

    .btn-ingresar:hover {
      background-color:rgb(24, 121, 231);
      transform: translateY(-5px);
    }

    @media (max-width: 768px) {
      .logo-container img {
        width: 100px;
      }
      .welcome-message {
        font-size: 2em;
      }
      .btn-ingresar {
        font-size: 1em;
        padding: 10px 20px;
      }
    }
  </style>
</head>
<body>

<div class="swiper-container">
  <div class="swiper-wrapper">
    <div class="swiper-slide"><img src="./assets/images/caro1.png" alt="Imagen 1"></div>
    <div class="swiper-slide"><img src="./assets/images/caro2.png" alt="Imagen 2"></div>
    <div class="swiper-slide"><img src="./assets/images/caro3.png" alt="Imagen 3"></div>
    <div class="swiper-slide"><img src="./assets/images/caro3.png" alt="Imagen 3"></div>
  </div>
  <header>
    <div class="logo-container">
      <img src="assets/images/prueba03.png" alt="Logo de la empresa">
    </div>
    <h2 class="welcome-message">¡Bienvenido!</h2>
    <p>Explora todas las funcionalidades que nuestro sistema tiene para ofrecerte.</p>
    <a href="login.php" class="btn-ingresar">Ingresar</a>
  </header>
  <div class="swiper-pagination"></div>
  <div class="swiper-button-next"></div>
  <div class="swiper-button-prev"></div>
</div>

<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script>
  var swiper = new Swiper('.swiper-container', {
    slidesPerView: 1,
    spaceBetween: 10,
    loop: true,
    navigation: {
      nextEl: '.swiper-button-next',
      prevEl: '.swiper-button-prev',
    },
    pagination: {
      el: '.swiper-pagination',
      clickable: true,
    },
    autoplay: {
      delay: 3000,
      disableOnInteraction: false,
    },
    effect:'fade',
  });
</script>
</body>
</html>
