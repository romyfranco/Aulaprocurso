import { GlobalWorkerOptions, getDocument } from 'pdfjs-dist';
import pdfWorkerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

GlobalWorkerOptions.workerSrc = pdfWorkerUrl;

class PdfPresentationViewer {
    constructor(root) {
        this.root = root;
        this.url = root.dataset.pdfUrl;
        this.stage = root.querySelector('[data-pdf-stage]');
        this.canvas = root.querySelector('[data-pdf-canvas]');
        this.context = this.canvas.getContext('2d', { alpha: false });
        this.loading = root.querySelector('[data-pdf-loading]');
        this.loadingStatus = root.querySelector('[data-pdf-loading-status]');
        this.progress = root.querySelector('[data-pdf-progress]');
        this.error = root.querySelector('[data-pdf-error]');
        this.currentPageLabel = root.querySelector('[data-current-page]');
        this.totalPagesLabel = root.querySelector('[data-total-pages]');
        this.zoomLabel = root.querySelector('[data-zoom-value]');
        this.previousButton = root.querySelector('[data-pdf-action="previous"]');
        this.nextButton = root.querySelector('[data-pdf-action="next"]');
        this.fullscreenLabel = root.querySelector('[data-fullscreen-label]');
        this.pdf = null;
        this.loadingTask = null;
        this.loadSequence = 0;
        this.pageNumber = 1;
        this.zoom = 1;
        this.renderTask = null;
        this.renderSequence = 0;
        this.resizeTimer = null;

        this.bindEvents();
        this.load();
    }

    bindEvents() {
        this.root.querySelector('[data-pdf-action="previous"]')?.addEventListener('click', () => this.previous());
        this.root.querySelector('[data-pdf-action="next"]')?.addEventListener('click', () => this.next());
        this.root.querySelector('[data-pdf-action="zoom-out"]')?.addEventListener('click', () => this.changeZoom(-0.1));
        this.root.querySelector('[data-pdf-action="zoom-in"]')?.addEventListener('click', () => this.changeZoom(0.1));
        this.root.querySelector('[data-pdf-action="fullscreen"]')?.addEventListener('click', () => this.toggleFullscreen());
        this.root.querySelector('[data-pdf-action="retry"]')?.addEventListener('click', () => this.load());

        window.addEventListener('keydown', (event) => this.handleKeyboard(event));
        document.addEventListener('fullscreenchange', () => this.updateFullscreenLabel());

        const observer = new ResizeObserver(() => {
            window.clearTimeout(this.resizeTimer);
            this.resizeTimer = window.setTimeout(() => {
                if (this.pdf) this.renderPage();
            }, 140);
        });
        observer.observe(this.stage);
    }

    async load() {
        const loadSequence = ++this.loadSequence;

        if (this.loadingTask) {
            await this.loadingTask.destroy().catch(() => {});
        } else if (this.pdf) {
            await this.pdf.destroy().catch(() => {});
        }

        this.pdf = null;
        this.loadingTask = null;
        this.pageNumber = 1;
        this.zoom = 1;
        this.canvas.classList.remove('is-ready');
        this.error.hidden = true;
        this.showLoading('Conectando con el documento…', 4);
        this.updateControls();

        try {
            const loadingTask = getDocument({ url: this.url, withCredentials: true });
            let downloadComplete = false;

            this.loadingTask = loadingTask;
            loadingTask.onProgress = ({ loaded, total }) => {
                if (downloadComplete || loadSequence !== this.loadSequence) return;

                if (!total) {
                    this.showLoading('Descargando la presentación…', 35);
                    return;
                }

                const percent = Math.min(78, Math.max(8, Math.round((loaded / total) * 78)));
                this.showLoading(`Descargando la presentación… ${percent}%`, percent);
            };

            const pdf = await loadingTask.promise;
            downloadComplete = true;

            if (loadSequence !== this.loadSequence) {
                await pdf.destroy();
                return;
            }

            this.pdf = pdf;
            this.totalPagesLabel.textContent = this.pdf.numPages;
            this.showLoading('Renderizando la primera página…', 88);
            await this.renderPage();
        } catch (error) {
            console.error('No se pudo cargar la presentación PDF.', error);
            this.loading.hidden = true;
            this.error.hidden = false;
            this.updateControls();
        }
    }

    async renderPage() {
        if (!this.pdf) return;

        const sequence = ++this.renderSequence;
        this.renderTask?.cancel();
        this.canvas.classList.remove('is-ready');
        this.showLoading(`Preparando la página ${this.pageNumber}…`, 92);
        this.updateControls(true);

        try {
            const page = await this.pdf.getPage(this.pageNumber);
            if (sequence !== this.renderSequence) return;

            const baseViewport = page.getViewport({ scale: 1 });
            const padding = window.innerWidth <= 760 ? 24 : 48;
            const availableWidth = Math.max(220, this.stage.clientWidth - padding);
            const availableHeight = Math.max(220, this.stage.clientHeight - padding);
            const fitScale = Math.min(availableWidth / baseViewport.width, availableHeight / baseViewport.height);
            const cssScale = fitScale * this.zoom;
            const outputScale = Math.min(window.devicePixelRatio || 1, 2);
            const viewport = page.getViewport({ scale: cssScale * outputScale });

            this.canvas.width = Math.floor(viewport.width);
            this.canvas.height = Math.floor(viewport.height);
            this.canvas.style.width = `${Math.floor(viewport.width / outputScale)}px`;
            this.canvas.style.height = `${Math.floor(viewport.height / outputScale)}px`;

            this.renderTask = page.render({ canvasContext: this.context, viewport });
            await this.renderTask.promise;
            if (sequence !== this.renderSequence) return;

            this.currentPageLabel.textContent = this.pageNumber;
            this.loading.hidden = true;
            this.error.hidden = true;
            this.canvas.classList.add('is-ready');
            this.updateControls();
        } catch (error) {
            if (error?.name === 'RenderingCancelledException') return;

            console.error('No se pudo renderizar la página del PDF.', error);
            this.loading.hidden = true;
            this.error.hidden = false;
            this.updateControls();
        }
    }

    previous() {
        if (!this.pdf || this.pageNumber <= 1) return;
        this.pageNumber -= 1;
        this.renderPage();
    }

    next() {
        if (!this.pdf || this.pageNumber >= this.pdf.numPages) return;
        this.pageNumber += 1;
        this.renderPage();
    }

    changeZoom(change) {
        if (!this.pdf) return;
        const nextZoom = Math.min(2, Math.max(0.6, Number((this.zoom + change).toFixed(1))));
        if (nextZoom === this.zoom) return;
        this.zoom = nextZoom;
        this.zoomLabel.textContent = `${Math.round(this.zoom * 100)}%`;
        this.renderPage();
    }

    async toggleFullscreen() {
        try {
            if (document.fullscreenElement) {
                await document.exitFullscreen();
            } else {
                await this.root.requestFullscreen();
            }
        } catch (error) {
            console.error('No se pudo cambiar el modo de pantalla completa.', error);
        }
    }

    updateFullscreenLabel() {
        if (!this.fullscreenLabel) return;
        this.fullscreenLabel.textContent = document.fullscreenElement ? 'Salir de pantalla completa' : 'Pantalla completa';
    }

    handleKeyboard(event) {
        if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) return;
        if (event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement) return;

        if (['ArrowLeft', 'PageUp'].includes(event.key)) {
            event.preventDefault();
            this.previous();
        }

        if (['ArrowRight', 'PageDown', ' '].includes(event.key)) {
            event.preventDefault();
            this.next();
        }
    }

    showLoading(status, percent) {
        this.loading.hidden = false;
        this.error.hidden = true;
        this.loadingStatus.textContent = status;
        this.progress.style.width = `${percent}%`;
    }

    updateControls(rendering = false) {
        const ready = Boolean(this.pdf) && !rendering;
        this.previousButton.disabled = !ready || this.pageNumber <= 1;
        this.nextButton.disabled = !ready || this.pageNumber >= (this.pdf?.numPages ?? 0);
        this.zoomLabel.textContent = `${Math.round(this.zoom * 100)}%`;
    }
}

document.querySelectorAll('[data-pdf-viewer]').forEach((root) => new PdfPresentationViewer(root));
