import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * PRD §4.4 design tokens. Single source of truth for the brand palette —
 * see docs/PRD_Indogate_v3.1.md §4 for the reasoning behind the exact
 * values and the "one red-600 button per screen" usage rules.
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                red: {
                    50: '#FEF2F3', 100: '#FDE3E5', 200: '#FBC9CE', 300: '#F79AA4', 400: '#EF6274',
                    500: '#DC2F45', 600: '#C41230', 700: '#A50E28', 800: '#871024', 900: '#701223',
                },
                blue: {
                    50: '#F0F5FB', 100: '#DEE9F6', 200: '#C0D6EE', 300: '#92B8DF', 400: '#5C93CC',
                    500: '#3573B4', 600: '#1F5896', 700: '#1A467A', 800: '#183C65', 900: '#173455',
                },
                neutral: {
                    0: '#FFFFFF', 50: '#F8F8F7', 100: '#F0F0EF', 200: '#E2E2E0', 300: '#C8C8C5',
                    400: '#9B9B97', 500: '#74746F', 600: '#575753', 700: '#414140', 800: '#2B2B2A', 900: '#1A1A19',
                },
                success: '#1E7D5A',
                warning: '#B45309',
                danger: '#C41230',
                info: '#1F5896',
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                // Serif display = the one place the UI is allowed to be expressive.
                display: ['Fraunces', 'Georgia', 'serif'],
                body: ['Figtree', ...defaultTheme.fontFamily.sans],
                // Money, codes, dates. Always pair with `tabular-nums`.
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
                arabic: ['IBM Plex Sans Arabic', 'Noto Kufi Arabic', 'sans-serif'],
            },
            borderRadius: {
                sm: '2px', DEFAULT: '4px', md: '6px', lg: '8px', xl: '12px',
            },
            boxShadow: {
                sm: '0 1px 2px 0 rgb(26 26 25 / 0.04)',
                DEFAULT: '0 1px 3px 0 rgb(26 26 25 / 0.08)',
                md: '0 4px 12px -2px rgb(26 26 25 / 0.10)',
                lg: '0 12px 28px -6px rgb(26 26 25 / 0.14)',
            },
        },
    },

    plugins: [forms],
};
