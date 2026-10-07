const PAGE_WIDTH = 1123;
const PAGE_HEIGHT = 794;
const MIN_ELEMENT_SIZE = 20;
const ACCEPTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const MAX_IMAGE_SIZE = 5 * 1024 * 1024;

function createId(prefix) {
    if (window.crypto?.randomUUID) {
        return `${prefix}-${window.crypto.randomUUID()}`;
    }

    return `${prefix}-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

export function snapValue(value, enabled, gridSize = 10) {
    return enabled ? Math.round(value / gridSize) * gridSize : value;
}

export function constrainRect(rect, pageWidth = PAGE_WIDTH, pageHeight = PAGE_HEIGHT) {
    const width = Math.min(pageWidth, Math.max(MIN_ELEMENT_SIZE, Number(rect.width)));
    const height = Math.min(pageHeight, Math.max(MIN_ELEMENT_SIZE, Number(rect.height)));

    return {
        x: Math.max(0, Math.min(Number(rect.x), pageWidth - width)),
        y: Math.max(0, Math.min(Number(rect.y), pageHeight - height)),
        width,
        height,
    };
}

export function calculateDraggedRect(rect, screenDeltaX, screenDeltaY, zoom, snap, gridSize = 10) {
    return constrainRect({
        ...rect,
        x: snapValue(rect.x + (screenDeltaX / zoom), snap, gridSize),
        y: snapValue(rect.y + (screenDeltaY / zoom), snap, gridSize),
    });
}

export function calculateResizedRect(rect, screenEdges, zoom, snap, gridSize = 10) {
    let left = rect.x;
    let top = rect.y;
    let right = rect.x + rect.width;
    let bottom = rect.y + rect.height;

    if (screenEdges.left !== undefined) left += screenEdges.left / zoom;
    if (screenEdges.right !== undefined) right += screenEdges.right / zoom;
    if (screenEdges.top !== undefined) top += screenEdges.top / zoom;
    if (screenEdges.bottom !== undefined) bottom += screenEdges.bottom / zoom;

    if (screenEdges.left !== undefined) left = snapValue(left, snap, gridSize);
    if (screenEdges.right !== undefined) right = snapValue(right, snap, gridSize);
    if (screenEdges.top !== undefined) top = snapValue(top, snap, gridSize);
    if (screenEdges.bottom !== undefined) bottom = snapValue(bottom, snap, gridSize);

    left = Math.max(0, Math.min(left, PAGE_WIDTH));
    top = Math.max(0, Math.min(top, PAGE_HEIGHT));
    right = Math.max(0, Math.min(right, PAGE_WIDTH));
    bottom = Math.max(0, Math.min(bottom, PAGE_HEIGHT));

    if (right - left < MIN_ELEMENT_SIZE) {
        if (screenEdges.left !== undefined) left = Math.max(0, right - MIN_ELEMENT_SIZE);
        else right = Math.min(PAGE_WIDTH, left + MIN_ELEMENT_SIZE);
    }
    if (bottom - top < MIN_ELEMENT_SIZE) {
        if (screenEdges.top !== undefined) top = Math.max(0, bottom - MIN_ELEMENT_SIZE);
        else bottom = Math.min(PAGE_HEIGHT, top + MIN_ELEMENT_SIZE);
    }

    return constrainRect({ x: left, y: top, width: right - left, height: bottom - top });
}

function normalizePages(pages, storageBaseUrl) {
    return (Array.isArray(pages) ? pages : []).map((page) => ({
        ...page,
        id: page.id || createId('page'),
        editorType: 'enhanced',
        schemaVersion: 2,
        width: Number(page.width) || PAGE_WIDTH,
        height: Number(page.height) || PAGE_HEIGHT,
        background_image_path: page.background_image_path ?? null,
        backgroundUrl: page.background_image_path ? `${storageBaseUrl}${page.background_image_path}` : null,
        backgroundSize: page.backgroundSize || 'cover',
        backgroundPosition: page.backgroundPosition || 'center',
        elements: (Array.isArray(page.elements) ? page.elements : []).map((element) => ({
            ...element,
            id: element.id ?? createId('element'),
            fontFamily: element.fontFamily || 'Arial',
            isBold: Boolean(element.isBold),
            isItalic: Boolean(element.isItalic),
            isUnderline: Boolean(element.isUnderline),
            textAlign: element.textAlign || 'left',
            zIndex: element.zIndex || 10,
        })),
    }));
}

function newPage() {
    return {
        id: createId('page'),
        editorType: 'enhanced',
        schemaVersion: 2,
        width: PAGE_WIDTH,
        height: PAGE_HEIGHT,
        background_image_path: null,
        backgroundUrl: null,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        elements: [],
    };
}

function isEditableTarget(target) {
    return target instanceof Element && Boolean(target.closest('input, textarea, select, [contenteditable="true"]'));
}

function escapeHtml(value) {
    const node = document.createElement('div');
    node.textContent = String(value ?? '');
    return node.innerHTML;
}

function safeCss(value, fallback) {
    const string = String(value ?? '');
    return /^[a-zA-Z0-9 #(),.'"%-]+$/.test(string) ? string : fallback;
}

export function enhancedCertificateEditor(options = {}) {
    return {
        pages: [],
        activePageIndex: 0,
        selectedElementId: null,
        customText: '',
        zoom: 1,
        showGrid: true,
        snapToGrid: true,
        gridSize: 10,
        pendingBackgroundFiles: {},
        objectUrls: {},
        gesture: null,
        isInteracting: false,
        isSubmitting: false,
        editorActive: false,
        keyboardHandler: null,
        pointerHandler: null,
        resizeObserver: null,
        fitZoom: 1,
        interactable: null,

        get selectedElement() {
            if (!this.selectedElementId) return null;
            return this.pages[this.activePageIndex]?.elements.find(
                (element) => String(element.id) === String(this.selectedElementId),
            ) || null;
        },

        init() {
            this.pages = normalizePages(options.initialPages, options.storageBaseUrl || '/storage/');
            if (this.pages.length === 0) this.pages.push(newPage());

            this.keyboardHandler = (event) => {
                if (event.isComposing || isEditableTarget(event.target) || !this.editorActive) return;
                if (event.key === 'Delete' && this.selectedElement) {
                    event.preventDefault();
                    this.removeElement();
                } else if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'd' && this.selectedElement) {
                    event.preventDefault();
                    this.duplicateElement();
                } else if (event.key === 'Escape') {
                    this.selectedElementId = null;
                }
            };
            this.pointerHandler = (event) => { this.editorActive = this.$root.contains(event.target); };
            document.addEventListener('keydown', this.keyboardHandler);
            document.addEventListener('pointerdown', this.pointerHandler, true);
            this.$nextTick(() => {
                this.fitCanvas();
                this.resizeObserver = new ResizeObserver(() => this.fitCanvas());
                this.resizeObserver.observe(this.$refs.canvasViewport);
                this.initInteract();
            });
        },

        destroy() {
            if (this.keyboardHandler) document.removeEventListener('keydown', this.keyboardHandler);
            if (this.pointerHandler) document.removeEventListener('pointerdown', this.pointerHandler, true);
            if (this.resizeObserver) this.resizeObserver.disconnect();
            if (this.interactable) this.interactable.unset();
            Object.values(this.objectUrls).forEach((url) => URL.revokeObjectURL(url));
        },

        addPage() {
            this.pages.push(newPage());
            this.activePageIndex = this.pages.length - 1;
            this.selectedElementId = null;
        },

        removePage(index) {
            if (this.pages.length <= 1) return alert('Anda harus memiliki setidaknya satu halaman.');
            if (!confirm('Apakah Anda yakin ingin menghapus halaman ini?')) return;
            this.releasePageFile(this.pages[index].id);
            this.pages.splice(index, 1);
            this.activePageIndex = Math.max(0, Math.min(this.activePageIndex, this.pages.length - 1));
            this.selectedElementId = null;
        },

        setActivePage(index) {
            this.activePageIndex = index;
            this.selectedElementId = null;
        },

        fitCanvas() {
            if (this.isInteracting) return;
            const availableWidth = Math.max(200, (this.$refs.canvasViewport?.clientWidth || PAGE_WIDTH) - 32);
            const wasFitted = Math.abs(this.zoom - this.fitZoom) < 0.01;
            this.fitZoom = Math.max(0.2, Math.min(1, availableWidth / PAGE_WIDTH));
            if (wasFitted || this.zoom > this.fitZoom) this.zoom = this.fitZoom;
        },
        zoomIn() { this.zoom = Math.min(this.fitZoom, Math.round((this.zoom + 0.1) * 10) / 10); },
        zoomOut() { this.zoom = Math.max(0.2, Math.round((this.zoom - 0.1) * 10) / 10); },
        resetZoom() { this.zoom = this.fitZoom; },

        addElement(content) {
            const page = this.pages[this.activePageIndex];
            if (!page?.backgroundUrl) return alert('Silakan unggah gambar latar belakang untuk halaman aktif terlebih dahulu.');
            const element = {
                id: createId('element'), content, x: 50, y: 50, width: 200, height: 40,
                fontSize: 24, fontFamily: 'Arial', color: '#000000', isBold: false,
                isItalic: false, isUnderline: false, textAlign: 'left', zIndex: 10,
            };
            page.elements.push(element);
            this.selectedElementId = element.id;
        },

        addCustomText() {
            if (!this.customText.trim()) return;
            this.addElement(this.customText);
            this.customText = '';
        },

        selectElement(elementId) { this.selectedElementId = elementId; },
        deselectElement(event) {
            if (!event.target.closest('.enhanced-editor-element')) this.selectedElementId = null;
        },
        removeElement() {
            if (!this.selectedElement) return;
            const page = this.pages[this.activePageIndex];
            page.elements = page.elements.filter((element) => String(element.id) !== String(this.selectedElementId));
            this.selectedElementId = null;
        },
        duplicateElement() {
            if (!this.selectedElement) return;
            const source = this.selectedElement;
            const rect = constrainRect({ ...source, x: Number(source.x) + 20, y: Number(source.y) + 20 });
            const copy = { ...structuredClone(source), ...rect, id: createId('element') };
            this.pages[this.activePageIndex].elements.push(copy);
            this.selectedElementId = copy.id;
        },
        bringToFront() {
            if (!this.selectedElement) return;
            this.selectedElement.zIndex = Math.max(...this.pages[this.activePageIndex].elements.map((el) => el.zIndex || 10)) + 1;
        },
        sendToBack() {
            if (!this.selectedElement) return;
            this.selectedElement.zIndex = Math.max(1, Math.min(...this.pages[this.activePageIndex].elements.map((el) => el.zIndex || 10)) - 1);
        },
        normalizeSelectedRect() {
            if (!this.selectedElement) return;
            const values = ['x', 'y', 'width', 'height'].map((key) => Number(this.selectedElement[key]));
            if (!values.every(Number.isFinite)) return alert('Posisi dan ukuran elemen harus berupa angka yang valid.');
            Object.assign(this.selectedElement, constrainRect(this.selectedElement));
        },

        handleBackgroundUpload(event, pageId) {
            const file = event.target.files?.[0];
            event.target.value = '';
            if (!file) return;
            if (!ACCEPTED_IMAGE_TYPES.includes(file.type) || file.size > MAX_IMAGE_SIZE) {
                return alert('Gunakan gambar JPEG, PNG, GIF, atau WEBP dengan ukuran maksimal 5 MB.');
            }
            const page = this.pages.find((item) => String(item.id) === String(pageId));
            if (!page) return;
            this.releaseObjectUrl(page.id);
            const objectUrl = URL.createObjectURL(file);
            this.pendingBackgroundFiles[page.id] = file;
            this.objectUrls[page.id] = objectUrl;
            page.backgroundUrl = objectUrl;
            page.background_image_path = null;
        },
        changeBackground(event) {
            const page = this.pages[this.activePageIndex];
            if (page) this.handleBackgroundUpload(event, page.id);
        },
        removeBackground() {
            const page = this.pages[this.activePageIndex];
            if (!page || !confirm('Apakah Anda yakin ingin menghapus latar belakang untuk halaman ini?')) return;
            this.releasePageFile(page.id);
            page.backgroundUrl = null;
            page.background_image_path = null;
        },
        releaseObjectUrl(pageId) {
            if (this.objectUrls[pageId]) URL.revokeObjectURL(this.objectUrls[pageId]);
            delete this.objectUrls[pageId];
        },
        releasePageFile(pageId) {
            this.releaseObjectUrl(pageId);
            delete this.pendingBackgroundFiles[pageId];
        },

        findGestureTarget(target) {
            const page = this.pages.find((item) => String(item.id) === target.dataset.pageId);
            const element = page?.elements.find((item) => String(item.id) === target.dataset.elementId);
            return page && element ? { page, element } : null;
        },
        startGesture(event, type) {
            const found = this.findGestureTarget(event.target);
            if (!found) return;
            this.activePageIndex = this.pages.findIndex((page) => page.id === found.page.id);
            this.selectedElementId = found.element.id;
            this.isInteracting = true;
            this.gesture = {
                type,
                element: found.element,
                rect: { x: Number(found.element.x), y: Number(found.element.y), width: Number(found.element.width), height: Number(found.element.height) },
                zoom: this.zoom,
                dx: 0,
                dy: 0,
                edges: { left: 0, right: 0, top: 0, bottom: 0 },
            };
        },
        initInteract() {
            if (!window.interact) return;
            this.interactable = window.interact('.enhanced-editor-element')
                .draggable({
                    ignoreFrom: '.resize-handle',
                    listeners: {
                        start: (event) => this.startGesture(event, 'drag'),
                        move: (event) => {
                            if (!this.gesture) return;
                            this.gesture.dx += event.dx;
                            this.gesture.dy += event.dy;
                            Object.assign(this.gesture.element, calculateDraggedRect(
                                this.gesture.rect, this.gesture.dx, this.gesture.dy,
                                this.gesture.zoom, this.snapToGrid, this.gridSize,
                            ));
                        },
                        end: () => { this.gesture = null; this.isInteracting = false; },
                    },
                })
                .resizable({
                    edges: { left: '.resize-handle-w, .resize-handle-nw, .resize-handle-sw', right: '.resize-handle-e, .resize-handle-ne, .resize-handle-se', top: '.resize-handle-n, .resize-handle-nw, .resize-handle-ne', bottom: '.resize-handle-s, .resize-handle-sw, .resize-handle-se' },
                    listeners: {
                        start: (event) => this.startGesture(event, 'resize'),
                        move: (event) => {
                            if (!this.gesture) return;
                            if (event.edges.left) this.gesture.edges.left += event.deltaRect.left;
                            if (event.edges.right) this.gesture.edges.right += event.deltaRect.right;
                            if (event.edges.top) this.gesture.edges.top += event.deltaRect.top;
                            if (event.edges.bottom) this.gesture.edges.bottom += event.deltaRect.bottom;
                            const movedEdges = {};
                            Object.keys(event.edges).forEach((edge) => {
                                if (event.edges[edge]) movedEdges[edge] = this.gesture.edges[edge];
                            });
                            Object.assign(this.gesture.element, calculateResizedRect(
                                this.gesture.rect, movedEdges, this.gesture.zoom, this.snapToGrid, this.gridSize,
                            ));
                        },
                        end: () => { this.gesture = null; this.isInteracting = false; },
                    },
                });
        },

        getSanitizedPages() {
            return this.pages.map(({ backgroundUrl, ...page }) => ({
                ...page,
                id: page.id,
                editorType: 'enhanced',
                schemaVersion: 2,
                width: Number(page.width) || PAGE_WIDTH,
                height: Number(page.height) || PAGE_HEIGHT,
                background_image_path: page.background_image_path ?? null,
                backgroundSize: page.backgroundSize || 'cover',
                backgroundPosition: page.backgroundPosition || 'center',
            }));
        },
        submitForm() {
            if (this.isSubmitting || this.isInteracting) return;
            const form = this.$root.querySelector('form');
            if (!form.reportValidity()) return;
            for (let pageIndex = 0; pageIndex < this.pages.length; pageIndex += 1) {
                const page = this.pages[pageIndex];
                if (options.requireBackground && !page.backgroundUrl) {
                    this.setActivePage(pageIndex);
                    return alert(`Silakan unggah gambar latar belakang untuk Halaman ${pageIndex + 1}.`);
                }
                for (const element of page.elements) {
                    if (![element.x, element.y, element.width, element.height].map(Number).every(Number.isFinite)) {
                        this.setActivePage(pageIndex);
                        return alert(`Posisi atau ukuran elemen pada Halaman ${pageIndex + 1} tidak valid.`);
                    }
                }
            }

            form.querySelectorAll('[data-generated-background]').forEach((input) => input.remove());
            this.pages.forEach((page, index) => {
                const file = this.pendingBackgroundFiles[page.id];
                if (!file) return;
                const input = document.createElement('input');
                input.type = 'file';
                input.name = `backgrounds[${index}]`;
                input.hidden = true;
                input.dataset.generatedBackground = 'true';
                const transfer = new DataTransfer();
                transfer.items.add(file);
                input.files = transfer.files;
                form.appendChild(input);
            });
            form.querySelector('input[name="layout_data"]').value = JSON.stringify(this.getSanitizedPages());
            this.isSubmitting = true;
            form.submit();
        },

        openPreview() {
            const previewWindow = window.open('', '_blank', 'width=1200,height=900');
            if (!previewWindow) return alert('Popup pratinjau diblokir. Izinkan popup untuk halaman ini.');
            previewWindow.document.write(this.generatePreviewHTML());
            previewWindow.document.close();
        },
        generatePreviewHTML() {
            const pages = this.pages.map((page) => {
                const background = page.backgroundUrl
                    ? `<img src="${escapeHtml(page.backgroundUrl)}" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:${page.backgroundSize === '100% 100%' ? 'fill' : safeCss(page.backgroundSize, 'cover')};object-position:${safeCss(page.backgroundPosition, 'center')}">`
                    : '';
                const elements = page.elements.map((element) => `<div style="position:absolute;box-sizing:border-box;display:flex;align-items:center;justify-content:${element.textAlign === 'center' ? 'center' : (element.textAlign === 'right' ? 'flex-end' : 'flex-start')};overflow:hidden;padding:8px;white-space:pre-wrap;line-height:1.4;left:${Number(element.x)}px;top:${Number(element.y)}px;width:${Number(element.width)}px;height:${Number(element.height)}px;font-size:${Number(element.fontSize)}px;color:${safeCss(element.color, '#000')};font-family:${safeCss(element.fontFamily, 'Arial')};font-weight:${element.isBold ? 'bold' : 'normal'};font-style:${element.isItalic ? 'italic' : 'normal'};text-decoration:${element.isUnderline ? 'underline' : 'none'};text-align:${safeCss(element.textAlign, 'left')};z-index:${Number(element.zIndex) || 10}">${escapeHtml(element.content)}</div>`).join('');
                return `<section style="position:relative;box-sizing:border-box;width:${page.width || PAGE_WIDTH}px;height:${page.height || PAGE_HEIGHT}px;margin:0 auto 24px;background:white;box-shadow:0 4px 12px #999">${background}${elements}</section>`;
            }).join('');
            const fontStylesheet = options.fontStylesheetUrl
                ? `<link rel="stylesheet" href="${escapeHtml(options.fontStylesheetUrl)}">`
                : '';
            return `<!doctype html><html><head><meta charset="utf-8"><title>Certificate Preview</title>${fontStylesheet}</head><body style="margin:0;padding:20px;background:#eee;font-family:Arial,sans-serif">${pages}</body></html>`;
        },
    };
}

if (typeof window !== 'undefined') {
    window.enhancedCertificateEditor = enhancedCertificateEditor;
}
