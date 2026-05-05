import { toJpeg } from 'html-to-image';
import jsPDF from 'jspdf';

const SCALE = 2;

function sanitizeFileName(value) {
    return (value || 'kad-keahlian')
        .toString()
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9-]+/g, '-')
        .replace(/-{2,}/g, '-')
        .replace(/^-|-$/g, '');
}

async function cardToJpeg(element) {
    return toJpeg(element, {
        cacheBust: true,
        pixelRatio: SCALE,
        quality: 0.96,
        backgroundColor: '#f8fafc',
    });
}

export async function downloadMemberCardJpg(element, fileBaseName) {
    const dataUrl = await cardToJpeg(element);
    const link = document.createElement('a');
    link.download = `${sanitizeFileName(fileBaseName)}.jpg`;
    link.href = dataUrl;
    link.click();
}

export async function downloadMemberCardPdf(element, fileBaseName) {
    const dataUrl = await cardToJpeg(element);
    const pdf = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: [170, 108],
    });

    pdf.addImage(dataUrl, 'JPEG', 0, 0, 108, 170);
    pdf.save(`${sanitizeFileName(fileBaseName)}.pdf`);
}

export async function copyText(value) {
    await navigator.clipboard.writeText(value);
}

export async function shareLink({ title, text, url }) {
    if (navigator.share) {
        await navigator.share({ title, text, url });

        return 'shared';
    }

    await copyText(url);

    return 'copied';
}
