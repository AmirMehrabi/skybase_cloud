import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

window.L = L;

let ticketEditorLoader;

window.initializeTicketEditors = function initializeTicketEditors() {
    const containers = document.querySelectorAll('[data-ticket-editor]');

    if (containers.length === 0) {
        return;
    }

    ticketEditorLoader ??= Promise.all([
        import('@toast-ui/editor'),
        import('@toast-ui/editor/dist/toastui-editor.css'),
    ]).then(([editorModule]) => editorModule.default);

    ticketEditorLoader.then((Editor) => {
        containers.forEach((container) => {
            if (container.dataset.initialized === 'true') {
                return;
            }

            const textarea = document.getElementById(container.dataset.target);

            if (! textarea) {
                return;
            }

            const editor = new Editor({
                el: container,
                height: container.dataset.height || '280px',
                initialEditType: 'wysiwyg',
                previewStyle: 'vertical',
                initialValue: textarea.value || '',
                usageStatistics: false,
                toolbarItems: [
                    ['bold', 'italic', 'quote'],
                    ['ul', 'ol'],
                    ['link'],
                    ['scrollSync'],
                ],
            });

            const syncTextarea = () => {
                textarea.value = editor.getMarkdown();
            };

            editor.on('change', syncTextarea);
            textarea.form?.addEventListener('submit', syncTextarea);

            container.dataset.initialized = 'true';
            container.editor = editor;
        });
    });
};

document.addEventListener('DOMContentLoaded', () => {
    window.initializeTicketEditors();
});

import('./bootstrap');
