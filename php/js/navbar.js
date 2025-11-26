// Aspetta che il DOM sia pronto
  document.addEventListener('DOMContentLoaded', () => {
    feather.replace();

    const toggle = document.querySelector('.navbar__toggle');
    const navbar = document.querySelector('.navbar');

    toggle.addEventListener('click', () => {
      navbar.classList.toggle('navbar--open');
      toggle.classList.toggle('active');
    });
  });