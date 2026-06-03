import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';

window.grapesjsEditor = function (config) {
    return {
        editor: null,

        init() {
            this.$nextTick(() => {
                this.bootEditor();
            });
        },

        bootEditor() {
            const initial = config.initialContent || {};

            this.editor = grapesjs.init({
                container: this.$refs.canvas,
                height: config.minHeight || '70vh',
                storageManager: false,
                undoManager: { trackSelection: false },
                fromElement: false,
                components: initial.html || '',
                style: initial.css || '',
                projectData: initial.project_data || undefined,
                plugins: [],
                canvas: {
                    styles: config.externalCss || [],
                },
                blockManager: {
                    appendTo: '.gjs-blocks-c',
                },
            });

            // Load custom blocks from server
            if (config.loadDefaultBlocks !== false && config.blocksUrl) {
                this.loadBlocks();
            }

            // Auto-save on any change
            this.editor.on('update', () => this.syncData());
            this.editor.on('storage:store', () => this.syncData());
        },

        async loadBlocks() {
            try {
                const response = await fetch(config.blocksUrl);
                const data = await response.json();
                const blockManager = this.editor.BlockManager;

                data.blocks.forEach((block) => {
                    blockManager.add(block.id, {
                        label: block.name,
                        category: block.category,
                        content: block.template,
                        media: block.thumbnail || undefined,
                    });
                });
            } catch (e) {
                console.warn('FilaBuilder: Failed to load blocks', e);
            }
        },

        syncData() {
            const html = this.editor.getHtml();
            const css = this.editor.getCss();
            const projectData = this.editor.getProjectData();

            const payload = JSON.stringify({
                html: html,
                css: css,
                project_data: projectData,
            });

            // Push to Livewire form state (skip re-render to avoid breaking editor)
            Livewire.find(this.$el.closest('[wire\\:id]')?.getAttribute('wire:id'))
                ?.set(config.statePath, payload, false);
        },
    };
};
