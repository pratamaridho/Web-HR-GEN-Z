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
            colors: {
                ink: '#0F1B33',
                navy: '#0A2463',
                cobalt: { DEFAULT: '#1F4FD8', dark: '#1A43BD' },
                sky: '#E6EEFF',
                mist: '#F3F6FC',
                line: '#D6E0F2',
                mute: '#566683',
                ok: '#157A4B',
                okbg: '#E4F4EB',
                bad: '#B3261E',
                badbg: '#FDECEA',
                warn: '#8A5200',
                warnbg: '#FFF3D6',
            },
            borderRadius: {
                md: '4px',
                lg: '6px',
            },
            fontFamily: {
                sans: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
