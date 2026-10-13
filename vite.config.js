import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        {
            name: 'patched-editor-sanitizer',
            enforce: 'pre',
            transform(code, id) {
                if (! /\/@toast-ui\/editor\/dist\/esm\/index(?:Viewer)?\.js$/.test(id)) {
                    return;
                }

                const start = code.indexOf('/*! @license DOMPurify 2.3.3');
                const endMarker = 'var purify = createDOMPurify();';
                const end = code.indexOf(endMarker, start);
                if (start < 0 || end < start) {
                    throw new Error('Editor sanitizer bundle changed; review its DOMPurify integration before building.');
                }

                return {
                    code: code.slice(0, start) + "import purify from 'dompurify';\n" + code.slice(end + endMarker.length),
                    map: null,
                };
            },
        },
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    optimizeDeps: {
        exclude: ['@toast-ui/editor', '@toast-ui/editor/viewer'],
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
