<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
      href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css"
      rel="stylesheet"
    />
    <script src="https://unpkg.com/scrollreveal"></script>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
    <title>C.E.A | by Jmac</title>
  </head>
  <body>
    <nav>
      <div class="nav__header">
        <div class="nav__logo">
          <a href="{{ route('login.asana') }}">Eisenhower<span>Asana</span>.</a>
        </div>
        <div class="nav__menu__btn" id="menu-btn">
          <span><i class="ri-menu-line"></i></span>
        </div>
      </div>
      <ul class="nav__links" id="nav-links">
        <li><a href="https://www.gruposame.mx/">Grupo Same</a></li>
        <li><a href="https://www.atura.mx/">Atura</a></li>
        <li><a href="https://www.tecmilenio.mx/es">Tecmilenio</a></li>
        <li><a href="https://jesusogishu.github.io/cvJesusCampos/">Jesus Campos</a></li>
      </ul>
      <div class="nav__btns">
        <button class="btn sign__in"><a style="color: white" href="{{ route('login.asana') }}">Sign in with Asana</a></button>
      </div>
    </nav>
    <header class="header__container">
      <div class="header__image">
        <div class="header__image__card header__image__card-1">
          <span><i class="ri-key-line"></i></span>
          Organize
        </div>
        <div class="header__image__card header__image__card-2">
          <span><i class="ri-passport-line"></i></span>
          Visualize
        </div>
        <div class="header__image__card header__image__card-3">
          <span><i class="ri-map-2-line"></i></span>
          Prioritize
        </div>
        <div class="header__image__card header__image__card-4">
          <span><i class="ri-guide-line"></i></span>
          Delegate
        </div>
        <img src="{{ asset('img/icon/CEAHOME.png') }}" alt="header" />
      </div>
      <div class="header__content">
        <h1>GET TO KNOW US!<br /><span>ORDER</span> AWAITS YOU</h1>
        <p>
          By using the C.E.A app (Eisenhower quadrant with Asana), you will organize your
          tasks in a more visual way, and it will be easy for you to complete them.
        </p>
        <form action="/">
          <div class="input__row">
            <div class="input__group">
              <h5>E-mail</h5>
              <div>
                <h4>alvarez1campos@gmail.com</h4>
              </div>
            </div>
            <div class="input__group">
              <h5>Phone</h5>
              <div>
                <h4>9321220200</h4>
              </div>
            </div>
          </div>
          <button type="submit">Contact Us</button>
        </form>
      </div>
    </header>
    <footer>
      <div class="header__content">
        <div class="bar">
          Copyright © 2025 jesusOgishu. All rights reserved.
        </div>
      </div>
    </footer>
    <script src="{{ asset('js/main.js') }}"></script>
  </body>
</html>