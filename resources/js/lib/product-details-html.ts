export interface ProductDetailSection {
    id: string;
    title: string;
    html: string;
}

function slugify(text: string): string {
    return text
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '')
        .slice(0, 80);
}

function stripTags(text: string): string {
    return text.replace(/<[^>]+>/g, '').trim();
}

function stripHorizontalRules(html: string): string {
    return html.replace(/<hr\b[^>]*>/gi, '');
}

/** Pull the meaningful markup out of imported Shopify / AI-generated HTML wrappers. */
export function extractProductDetailsMarkup(html: string): string {
    const markdownMatch = html.match(/<div\b[^>]*class="[^"]*\bmarkdown\b[^"]*"[^>]*>([\s\S]*)<\/div>\s*(?:<\/div>\s*)*$/i);

    if (markdownMatch?.[1]) {
        return markdownMatch[1].trim();
    }

    if (typeof DOMParser !== 'undefined') {
        const doc = new DOMParser().parseFromString(html, 'text/html');

        doc.querySelectorAll('script, style, section[data-turn], section[data-testid], br').forEach((node) => node.remove());

        const markdown = doc.querySelector('.markdown, [class*="markdown"]');

        if (markdown instanceof HTMLElement) {
            return markdown.innerHTML.trim();
        }

        return doc.body.innerHTML.trim();
    }

    return html.trim();
}

function splitByHeadingRegex(html: string, tag: 'h1' | 'h2'): ProductDetailSection[] | null {
    const pattern = new RegExp(`<${tag}\\b[^>]*>([\\s\\S]*?)<\\/${tag}>`, 'gi');
    const matches = [...html.matchAll(pattern)];

    if (matches.length === 0) {
        return null;
    }

    const sections: ProductDetailSection[] = [];
    const usedIds = new Set<string>();

    const introEnd = matches[0].index ?? 0;

    if (introEnd > 0) {
        const introHtml = stripHorizontalRules(html.slice(0, introEnd).trim());

        if (introHtml !== '') {
            sections.push({ id: 'overview', title: 'Overview', html: introHtml });
        }
    }

    for (let index = 0; index < matches.length; index++) {
        const match = matches[index];
        const title = stripTags(match[1]) || 'Details';
        const start = (match.index ?? 0) + match[0].length;
        const end = index + 1 < matches.length ? (matches[index + 1].index ?? html.length) : html.length;
        const sectionHtml = stripHorizontalRules(html.slice(start, end).trim());

        let id = slugify(title);

        if (usedIds.has(id)) {
            id = `${id}-${index + 1}`;
        }

        usedIds.add(id);

        sections.push({ id, title, html: sectionHtml });
    }

    return sections;
}

function splitWithDom(html: string): ProductDetailSection[] | null {
    if (typeof DOMParser === 'undefined') {
        return null;
    }

    const doc = new DOMParser().parseFromString(`<div id="product-details-root">${html}</div>`, 'text/html');
    const root = doc.getElementById('product-details-root');

    if (!root) {
        return null;
    }

    const headings = Array.from(root.querySelectorAll('h1'));

    if (headings.length === 0) {
        return null;
    }

    const sections: ProductDetailSection[] = [];
    const usedIds = new Set<string>();

    for (let index = 0; index < headings.length; index++) {
        const heading = headings[index];
        const title = heading.textContent?.trim() || 'Details';
        const range = doc.createRange();
        range.setStartAfter(heading);

        const nextHeading = headings[index + 1];

        if (nextHeading) {
            range.setEndBefore(nextHeading);
        } else if (root.lastChild) {
            range.setEndAfter(root.lastChild);
        } else {
            continue;
        }

        const container = doc.createElement('div');
        container.appendChild(range.cloneContents());
        container.querySelectorAll('hr').forEach((rule) => rule.remove());

        let id = slugify(title);

        if (usedIds.has(id)) {
            id = `${id}-${index + 1}`;
        }

        usedIds.add(id);

        sections.push({
            id,
            title,
            html: container.innerHTML.trim(),
        });
    }

    return sections.length > 0 ? sections : null;
}

/** Split long product copy into scannable sections keyed by top-level headings. */
export function splitProductDetailsIntoSections(html: string): ProductDetailSection[] {
    const cleaned = extractProductDetailsMarkup(html);

    if (cleaned === '') {
        return [];
    }

    const fromDom = splitWithDom(cleaned);

    if (fromDom && fromDom.length > 0) {
        return fromDom;
    }

    const fromH1 = splitByHeadingRegex(cleaned, 'h1');

    if (fromH1 && fromH1.length > 0) {
        return fromH1;
    }

    const fromH2 = splitByHeadingRegex(cleaned, 'h2');

    if (fromH2 && fromH2.length > 0) {
        return fromH2;
    }

    return [{ id: 'details', title: 'Details', html: cleaned }];
}
