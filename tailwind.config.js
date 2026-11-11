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
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
                display: ['Montserrat', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                app: {
                    base: '#faf7f2',
                    surface: '#f5f0e8',
                    'surface-strong': '#efe5d8',
                    ink: '#3d2e24',
                    muted: '#6f5d46',
                    accent: '#5c4033',
                    gold: '#c4956a',
                    amber: '#d4a574',
                    success: '#295d2d',
                    'success-surface': '#ecffec',
                    danger: '#863a37',
                    'danger-surface': '#ffe3de',
                },
                bakery: {
                    cream: '#faf7f2',
                    warm: '#f5f0e8',
                    brown: '#5c4033',
                    gold: '#c4956a',
                    dark: '#3d2e24',
                    muted: '#6f5d46',
                    sage: '#295d2d',
                    amber: '#d4a574',
                    terracotta: '#863a37',
                },
            },
        },
    },

    plugins: [forms],
};
