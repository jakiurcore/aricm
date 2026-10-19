<?php

namespace Modules\Notices\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * The small slice of HTML a notice body may carry (what the admin editor's bold, italic, list and link buttons make).
 * Everything else is unwrapped to its text, every attribute but a safe link target is dropped. A body without any tag
 * is plain text, shown with its line breaks.
 */
final class SafeHtml
{
    private const array ALLOWED = ['b', 'strong', 'i', 'em', 'u', 'ul', 'ol', 'li', 'br', 'p', 'div', 'a'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '' || ! preg_match('/<[a-z!\/]/i', $html)) {
            return $html;
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?><body>'.$html.'</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body) {
            return e(strip_tags($html));
        }

        self::sanitize($body);

        $output = '';

        foreach ($body->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    /**
     * The body ready to print inside the notice page.
     */
    public static function render(?string $body): string
    {
        $body = (string) $body;

        return preg_match('/<[a-z!\/]/i', $body) ? self::clean($body) : nl2br(e($body));
    }

    /**
     * The body as plain text with its line breaks (list items on their own lines), for places that print text only.
     */
    public static function plain(?string $body): string
    {
        $body = (string) $body;

        if (! preg_match('/<[a-z!\/]/i', $body)) {
            return $body;
        }

        $text = preg_replace(['#<br\s*/?>#i', '#</(p|div|li|ul|ol)>#i', '#<li[^>]*>#i'], ["\n", "\n", '• '], $body);

        return trim(html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private static function sanitize(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                if ($child->nodeType !== XML_TEXT_NODE) {
                    $node->removeChild($child);
                }

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed'], true)) {
                $node->removeChild($child);

                continue;
            }

            self::sanitize($child);

            if (! in_array($tag, self::ALLOWED, true)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }

                $node->removeChild($child);

                continue;
            }

            $href = $tag === 'a' ? trim($child->getAttribute('href')) : '';

            foreach (iterator_to_array($child->attributes) as $attribute) {
                $child->removeAttribute($attribute->name);
            }

            if ($tag === 'a') {
                if (preg_match('#^(https?://|mailto:|tel:)#i', $href)) {
                    $child->setAttribute('href', $href);
                    $child->setAttribute('rel', 'noopener noreferrer');
                    $child->setAttribute('target', '_blank');
                } else {
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }

                    $node->removeChild($child);
                }
            }
        }
    }
}
