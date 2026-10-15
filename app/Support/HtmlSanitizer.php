<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list HTML sanitiser for newsroom post bodies. Keeps basic
 * formatting, links (http/https/mailto/relative) and images (relative or
 * https); drops scripts, styles, event handlers and anything else.
 */
class HtmlSanitizer
{
    private const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [], 'h2' => [], 'h3' => [], 'h4' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [], 'code' => [], 'pre' => [],
        'a' => ['href', 'title'], 'img' => ['src', 'alt', 'title', 'width', 'height'],
        'figure' => [], 'figcaption' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => [],
    ];

    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button',
        'textarea', 'select', 'svg', 'math', 'noscript', 'template', 'head', 'title', 'meta', 'link', 'base'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__root');
        if (! $root) {
            return '';
        }

        self::walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->nodeName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);

                continue;
            }

            self::walk($child);

            if (! array_key_exists($tag, self::ALLOWED)) {
                // Unwrap: keep the children, drop the element.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->nodeName);
                if (! in_array($name, self::ALLOWED[$tag], true)) {
                    $child->removeAttribute($attr->nodeName);
                }
            }

            if ($tag === 'a') {
                $href = trim($child->getAttribute('href'));
                if (! self::safeUrl($href, ['http', 'https', 'mailto'])) {
                    $child->removeAttribute('href');
                } elseif (preg_match('#^https?://#i', $href)) {
                    $child->setAttribute('rel', 'noopener noreferrer nofollow');
                }
            }

            if ($tag === 'img') {
                $src = trim($child->getAttribute('src'));
                if (! self::safeUrl($src, ['https'])) {
                    $node->removeChild($child);

                    continue;
                }
                $child->setAttribute('loading', 'lazy');
            }
        }
    }

    private static function safeUrl(string $url, array $schemes): bool
    {
        if ($url === '' || preg_match('/[\x00-\x1f]/', $url)) {
            return false;
        }
        if (str_starts_with($url, '//') || str_starts_with($url, '/\\')) {
            return false;
        }
        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return $scheme !== '' && in_array($scheme, $schemes, true);
    }
}
