/**
 * execCommand('strikeThrough') inserts <strike>, but the backend sanitizer
 * only allows <b><i><u><s><a><br> — normalize before sending.
 */
export function normalizeCommentBody(html: string): string {
    return html
        .replace(/<strike>/g, '<s>')
        .replace(/<\/strike>/g, '</s>')
}
