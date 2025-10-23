// resources/js/main.js

document.addEventListener('DOMContentLoaded', (event) => {
  const menuBtn = document.getElementById("menu-btn");
  const navLinks = document.getElementById("nav-links");
  const menuBtnIcon = menuBtn ? menuBtn.querySelector("i") : null;

  // Check if menuBtn exists before adding event listeners
  if (menuBtn) {
    menuBtn.addEventListener("click", (e) => {
      if (navLinks) {
        navLinks.classList.toggle("open");
        const isOpen = navLinks.classList.contains("open");
        if (menuBtnIcon) {
          menuBtnIcon.setAttribute("class", isOpen ? "ri-close-line" : "ri-menu-line");
        }
      }
    });
  }

  // Check if navLinks exists before adding event listeners
  if (navLinks) {
    navLinks.addEventListener("click", (e) => {
      navLinks.classList.remove("open");
      if (menuBtnIcon) {
        menuBtnIcon.setAttribute("class", "ri-menu-line");
      }
    });
  }

  // ScrollReveal options
  const scrollRevealOption = {
    distance: "50px",
    origin: "bottom",
    duration: 1000,
  };

  // Only run ScrollReveal if the library is loaded
  if (typeof ScrollReveal !== 'undefined') {
    ScrollReveal().reveal(".header__image img", {
      ...scrollRevealOption,
      origin: "right",
    });

    ScrollReveal().reveal(".header__content h1", {
      ...scrollRevealOption,
      delay: 500,
    });

    ScrollReveal().reveal(".header__content p", {
      ...scrollRevealOption,
      delay: 1000,
    });

    ScrollReveal().reveal(".header__content form", {
      ...scrollRevealOption,
      delay: 1500,
    });

    ScrollReveal().reveal(".header__content .bar", {
      ...scrollRevealOption,
      delay: 2000,
    });

    ScrollReveal().reveal(".header__image__card", {
      duration: 1000,
      interval: 500,
      delay: 2500,
    });
  }
});