const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                // Poppins jadi font default (dipakai semua elemen Tailwind, termasuk sisa halaman Breeze)
                sans: ['Poppins', ...defaultTheme.fontFamily.sans],
                poppins: ['Poppins', ...defaultTheme.fontFamily.sans],
                garamond: ['"Cormorant Garamond"', 'serif'],
            },
            colors: {
                // Palet Owner Panel — jangan dipakai di storefront
                maroon: {
                    900: '#4A1220',
                    800: '#611B2B',
                    700: '#8C2E3C',
                    500: '#A5424F',
                },
                pink: {
                    50: '#FDF1EF',
                    100: '#FBE4E1',
                    200: '#F6D2CD',
                },
                cream: '#FFFCFA',
                ink: {
                    900: '#2E1319',
                    soft: '#6B5257',
                },
            },
            boxShadow: {
                'owner-card': '0 8px 24px rgba(74,18,32,0.08)',
                'owner-modal': '0 30px 60px rgba(74,18,32,0.18)',
            },
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
