/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './**/*.php',
    './assets/js/**/*.js',
    // exclude node_modules and dist
    '!./node_modules/**',
    '!./dist/**',
  ],
  theme: {
    extend: {
      fontFamily: {
        // resolved at runtime via CSS custom properties set by PHP
        title: ['var(--font-title)', 'Georgia', 'serif'],
        body:  ['var(--font-body)',  'system-ui', 'sans-serif'],
      },
      maxWidth: {
        content: '760px',
        layout:  '1180px',
      },
      width: {
        toc: '220px',
      },
      typography: {
        DEFAULT: {
          css: {
            maxWidth: 'none',
            'h1, h2, h3, h4, h5, h6': {
              fontFamily: 'var(--font-title)',
            },
            a: {
              textUnderlineOffset: '3px',
            },
          },
        },
      },
    },
  },
  plugins: [
    require('@tailwindcss/typography'),
  ],
};
