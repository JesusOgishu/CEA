/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

const mix = require('laravel-mix');

mix.js('resources/js/app.js', 'public/js')
   .postCss('resources/css/app.css', 'public/css')
   .styles([
       'resources/css/styles.css',
       'resources/css/task.css',
       'resources/css/dashboard.css',
       'resources/css/users.css',
       'resources/css/metrics.css',
       'resources/css/teammates.css'
   ], 'public/css/app.css'); // Bundle all CSS files into one