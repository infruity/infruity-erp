// Keep the real asset graph; headless checks do not need desktop notifications.
// Mix 5's bundled macOS notifier is x86-only and can fail after compilation.
require('../webpack.mix.js');
require('laravel-mix').disableNotifications();
