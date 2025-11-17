import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  
  darkMode: 'class',
  
  theme: {
    extend: {
      fontFamily: {
        sans: [
          'Inter',
          '-apple-system',
          'BlinkMacSystemFont',
          'Segoe UI',
          'Roboto',
          'Helvetica Neue',
          'Arial',
          'sans-serif',
          ...defaultTheme.fontFamily.sans,
        ],
        mono: ['SF Mono', 'Monaco', 'Consolas', 'Liberation Mono', 'Courier New', 'monospace'],
      },
      
      colors: {
        // Colores personalizados si los necesitas
        primary: {
          50: '#f0f9ff',
          100: '#e0f2fe',
          200: '#bae6fd',
          300: '#7dd3fc',
          400: '#38bdf8',
          500: '#0ea5e9',
          600: '#0284c7',
          700: '#0369a1',
          800: '#075985',
          900: '#0c4a6e',
        },
      },
      
    fontSize: {
  'xs': ['0.8125rem', { lineHeight: '1.125rem' }],  // 13px (era 12px)
  'sm': ['0.9375rem', { lineHeight: '1.375rem' }],  // 15px (era 14px)
  'base': ['1.0625rem', { lineHeight: '1.625rem' }], // 17px (era 16px)
  'lg': ['1.1875rem', { lineHeight: '1.875rem' }],  // 19px (era 18px)
  'xl': ['1.3125rem', { lineHeight: '1.875rem' }],  // 21px (era 20px)
  '2xl': ['1.5625rem', { lineHeight: '2.125rem' }], // 25px (era 24px)
  '3xl': ['1.9375rem', { lineHeight: '2.375rem' }], // 31px (era 30px)
  '4xl': ['2.3125rem', { lineHeight: '2.625rem' }], // 37px (era 36px)
  '5xl': ['3.125rem', { lineHeight: '1.1' }],       // 50px (era 48px)
},
      
      fontWeight: {
        'light': 300,
        'normal': 400,
        'medium': 500,
        'semibold': 600,
        'bold': 700,
        'extrabold': 800,
      },
      
      spacing: {
        '128': '32rem',
        '144': '36rem',
      },
      
      borderRadius: {
        '4xl': '2rem',
      },
      
      screens: {
        'xs': '475px',
        ...defaultTheme.screens,
      },
    },
  },
  
  plugins: [
    require('@tailwindcss/forms'),
  ],
};