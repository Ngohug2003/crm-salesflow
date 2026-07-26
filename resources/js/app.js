import './echo';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

window.salesflow = {
    toggleTheme() {
        const dark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('salesflow-theme', dark ? 'dark' : 'light');
    },
};

window.salesflowRichTextEditor = (wire, model, placeholder) => ({
    editor: null,

    init() {
        const editorElement = this.$refs.editor;

        if (this.editor !== null) {
            return;
        }

        const existingEditor = Quill.find(editorElement);

        if (existingEditor && typeof existingEditor.getModule === 'function') {
            this.editor = existingEditor;
            this.removeDuplicateToolbars();

            return;
        }

        this.editor = new Quill(editorElement, {
            theme: 'snow',
            placeholder,
            modules: {
                toolbar: [
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['blockquote', 'link'],
                    ['clean'],
                ],
            },
        });

        this.removeDuplicateToolbars();

        const initialValue = wire.get(model) ?? '';

        if (initialValue !== '') {
            this.editor.clipboard.dangerouslyPasteHTML(initialValue);
        }

        this.editor.on('text-change', (_delta, _oldDelta, source) => {
            if (source !== 'user') {
                return;
            }

            const isEmpty = this.editor.getText().trim() === '';
            wire.set(model, isEmpty ? '' : this.editor.getSemanticHTML());
        });
    },

    removeDuplicateToolbars() {
        const activeToolbar = this.editor.getModule('toolbar')?.container;
        const editorWrapper = this.$refs.editor.parentElement;

        if (!activeToolbar || !editorWrapper) {
            return;
        }

        Array.from(editorWrapper.children)
            .filter((element) => element.classList.contains('ql-toolbar') && element !== activeToolbar)
            .forEach((element) => element.remove());
    },
});

document.addEventListener('livewire:navigating', () => {
    document.documentElement.classList.add('is-navigating');
});

document.addEventListener('livewire:navigated', () => {
    requestAnimationFrame(() => document.documentElement.classList.remove('is-navigating'));
});
