/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './admin/**/*.php',
    './admin/*.php',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
  corePlugins: {
    preflight: false, // Important for WP Admin to avoid conflicts
  }
}
