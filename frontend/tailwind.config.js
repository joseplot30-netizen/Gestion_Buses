/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./src/**/*.{html,js}", "./public/**/*.html"],
    theme: {
        extend: {
            colors: {
                "automotive-blue": "#1a2b3c", // Un azul corporativo para software de autos
            },
        },
    },
    plugins: [],
};
