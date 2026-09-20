<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allowlist sanitizer for CMS rich text saved from the admin editor.
 */
final class RichText
{
    /**
     * @var array<int, string>
     */
    private const AllowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'a', 'blockquote'];

    public static function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $trimmed = trim($html);

        if ($trimmed === '' || trim(strip_tags($trimmed)) === '') {
            return null;
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="rich-text-root">'.$trimmed.'</div>',
            LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('rich-text-root');

        if (! $root instanceof DOMElement) {
            return null;
        }

        self::clean($root);

        $output = '';

        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        $output = trim($output);

        return $output === '' ? null : $output;
    }

    private static function clean(DOMNode $node): void
    {
        $toUnwrap = [];
        $toDestroy = [];

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (! in_array($tag, self::AllowedTags, true)) {
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'textarea', 'link', 'meta'], true)) {
                        $toDestroy[] = $child;
                    } else {
                        $toUnwrap[] = $child;
                    }

                    continue;
                }

                self::cleanAttributes($child, $tag);
                self::clean($child);
            }
        }

        foreach ($toDestroy as $child) {
            $node->removeChild($child);
        }

        foreach ($toUnwrap as $child) {
            while ($child->firstChild !== null) {
                $node->insertBefore($child->firstChild, $child);
            }

            $node->removeChild($child);
        }
    }

    private static function cleanAttributes(DOMElement $element, string $tag): void
    {
        $allowed = [];

        if ($tag === 'a') {
            $href = $element->getAttribute('href');

            if (self::isSafeUrl($href)) {
                $allowed['href'] = $href;

                if (! str_starts_with($href, '/') && ! str_starts_with($href, 'mailto:')) {
                    $allowed['rel'] = 'noopener noreferrer';
                    $allowed['target'] = '_blank';
                }
            }
        }

        while ($element->attributes->length > 0) {
            $element->removeAttribute($element->attributes->item(0)?->name ?? '');
        }

        foreach ($allowed as $name => $value) {
            $element->setAttribute($name, $value);
        }
    }

    private static function isSafeUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '') {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//');
        }

        return (bool) preg_match('/^(https?:\/\/|mailto:)/i', $url);
    }
}
