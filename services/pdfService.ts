import * as pdfjsLib from 'pdfjs-dist';
import Tesseract from 'tesseract.js';

// Set the worker source - use Vite ?url import for the local worker
pdfjsLib.GlobalWorkerOptions.workerSrc = new URL(
    'pdfjs-dist/build/pdf.worker.min.mjs',
    import.meta.url
).toString();

/**
 * Extract text from a PDF using pdf.js text layer.
 * If text layer is empty (scanned PDF), falls back to OCR via Tesseract.js.
 */
export const extractTextFromPdfBlob = async (
    pdfBlob: Blob,
    onProgress?: (msg: string) => void
): Promise<{ text: string; pageCount: number }> => {
    try {
        const arrayBuffer = await pdfBlob.arrayBuffer();
        const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
        const pageCount = pdf.numPages;

        onProgress?.(`Found ${pageCount} pages, extracting text...`);

        // --- Step 1: Try pdf.js text extraction ---
        const textParts: string[] = [];
        for (let i = 1; i <= pageCount; i++) {
            const page = await pdf.getPage(i);
            const textContent = await page.getTextContent();
            const pageText = textContent.items
                .map((item: any) => item.str)
                .join(' ')
                .trim();
            if (pageText) {
                textParts.push(pageText);
            }
        }

        const fullText = textParts.join('\n\n');

        // If we got meaningful text (more than just whitespace/junk), use it
        if (fullText.replace(/\s/g, '').length > 50) {
            return { text: fullText, pageCount };
        }

        // --- Step 2: Fallback to OCR for scanned/image PDFs ---
        onProgress?.('No selectable text found. Running OCR on pages...');

        const ocrParts: string[] = [];
        const pagesToOcr = Math.min(pageCount, 20); // Limit OCR to 20 pages max

        for (let i = 1; i <= pagesToOcr; i++) {
            onProgress?.(`OCR: Processing page ${i} of ${pagesToOcr}...`);

            const page = await pdf.getPage(i);
            const viewport = page.getViewport({ scale: 2.0 }); // Higher scale = better OCR

            // Render page to canvas
            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            const ctx = canvas.getContext('2d')!;

            await page.render({ canvasContext: ctx, viewport, canvas } as any).promise;

            // OCR the canvas image
            try {
                const result = await Tesseract.recognize(canvas, 'eng', {
                    logger: () => { }, // Suppress Tesseract logs
                });
                const pageText = result.data.text.trim();
                if (pageText) {
                    ocrParts.push(pageText);
                }
            } catch (ocrErr) {
                console.warn(`OCR failed on page ${i}:`, ocrErr);
            }

            // Clean up canvas
            canvas.remove();
        }

        const ocrText = ocrParts.join('\n\n');

        if (pagesToOcr < pageCount) {
            return {
                text: ocrText + `\n\n--- OCR processed first ${pagesToOcr} of ${pageCount} pages ---`,
                pageCount,
            };
        }

        return { text: ocrText, pageCount };
    } catch (error) {
        console.error('PDF text extraction failed:', error);
        return { text: '', pageCount: 0 };
    }
};
