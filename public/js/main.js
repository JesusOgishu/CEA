/******/ (() => { // webpackBootstrap
/*!******************************!*\
  !*** ./resources/js/main.js ***!
  \******************************/
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
var menuBtn = document.getElementById("menu-btn");
var navLinks = document.getElementById("nav-links");
var menuBtnIcon = menuBtn.querySelector("i");
menuBtn.addEventListener("click", function (e) {
  navLinks.classList.toggle("open");
  var isOpen = navLinks.classList.contains("open");
  menuBtnIcon.setAttribute("class", isOpen ? "ri-close-line" : "ri-menu-line");
});
navLinks.addEventListener("click", function (e) {
  navLinks.classList.remove("open");
  menuBtnIcon.setAttribute("class", "ri-menu-line");
});
var scrollRevealOption = {
  distance: "50px",
  origin: "bottom",
  duration: 1000
};
ScrollReveal().reveal(".header__image img", _objectSpread(_objectSpread({}, scrollRevealOption), {}, {
  origin: "right"
}));
ScrollReveal().reveal(".header__content h1", _objectSpread(_objectSpread({}, scrollRevealOption), {}, {
  delay: 500
}));
ScrollReveal().reveal(".header__content p", _objectSpread(_objectSpread({}, scrollRevealOption), {}, {
  delay: 1000
}));
ScrollReveal().reveal(".header__content form", _objectSpread(_objectSpread({}, scrollRevealOption), {}, {
  delay: 1500
}));
ScrollReveal().reveal(".header__content .bar", _objectSpread(_objectSpread({}, scrollRevealOption), {}, {
  delay: 2000
}));
ScrollReveal().reveal(".header__image__card", {
  duration: 1000,
  interval: 500,
  delay: 2500
});
/******/ })()
;