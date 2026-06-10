/**
 * Tailwind config used only at build time by the standalone CLI to compile
 * a static CSS asset (public/css/app.css). It is NOT part of the running app
 * and adds no JavaScript to the client — the deliverable is plain CSS.
 *
 * Rebuild after changing classes in any template:
 *   ./tailwind/tailwindcss -c tailwind/tailwind.config.js \
 *       -i tailwind/app.css -o public/css/app.css --minify
 */
module.exports = {
  content: [
    './src/**/*.php',
    './public/**/*.php',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
};
