import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Main brand green (buttons, links, active menu)
                leaf: {
                    50: '#EEF5F0',
                    100: '#D5E8DB',
                    200: '#ABD1B8',
                    300: '#7BB38F',
                    400: '#4E9068',
                    500: '#2F6B3F',
                    600: '#285C36',
                    700: '#214B2C',
                    800: '#1A3B23',
                    900: '#13291A',
                },
                // Warm accent for highlights, badges and "fresh" tags
                tomato: {
                    50: '#FDF1EB',
                    100: '#FBE3D6',
                    200: '#F6C2A6',
                    300: '#EF9B72',
                    400: '#E77E4E',
                    500: '#E0662F',
                    600: '#C4521F',
                    700: '#A3421A',
                    800: '#8A3413',
                    900: '#5E230D',
                },
                cream: {
                    DEFAULT: '#FBF7EE',
                    dark: '#F3ECDD',
                },
                soil: {
                    DEFAULT: '#1F2A22',
                    muted: '#5B665E',
                },
            },
            borderRadius: {
                card: '1.25rem',
            },
        },
    },

    plugins: [forms],
};
