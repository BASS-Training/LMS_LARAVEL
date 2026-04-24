import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    darkMode: 'class',

    theme: {
        screens: {
            'sm':  '640px',   // Tablet portrait
            'md':  '768px',   // Tablet landscape
            'lg':  '1024px',  // Laptop
            'xl':  '1280px',  // Desktop
            '2xl': '1440px',  // Wide desktop
        },
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'bass-red':  '#DA1E1E',
                'bass-gold': '#FFD600',
            },
            maxWidth: {
                'screen-2xl': '1440px',
            },
            spacing: {
                '4.5': '1.125rem',  // 18px  (8px scale extra)
                '13':  '3.25rem',   // 52px
                '18':  '4.5rem',    // 72px
            },
            borderRadius: {
                'xl':  '0.75rem',
                '2xl': '1rem',
                '3xl': '1.5rem',
            },
            boxShadow: {
                'card':  '0 1px 3px 0 rgb(0 0 0 / .06), 0 1px 2px -1px rgb(0 0 0 / .06)',
                'card-hover': '0 4px 12px 0 rgb(0 0 0 / .08), 0 2px 4px -1px rgb(0 0 0 / .06)',
                'nav':   '0 1px 4px 0 rgb(0 0 0 / .08)',
            },
            transitionProperty: {
                'height': 'height',
                'spacing': 'margin, padding',
            },
            keyframes: {
                fadeInUp: {
                    '0%':   { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideDown: {
                    '0%':   { opacity: '0', transform: 'translateY(-4px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                'fade-in-up': 'fadeInUp 0.3s ease-out',
                'slide-down': 'slideDown 0.2s ease-out',
            },
        },
    },

    plugins: [forms],
};
