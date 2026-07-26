<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

final class TaskDescriptionSanitizer
{
    /** @var list<string> */
    private const array ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
        'ol', 'ul', 'li', 'blockquote', 'h2', 'h3', 'a',
    ];

    /** @var list<string> */
    private const array DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math',
    ];

    public function sanitize(?string $html): ?string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return null;
        }

        if ($html === strip_tags($html)) {
            return '<p>'.nl2br(
                htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                false,
            ).'</p>';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div data-task-description-root>'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        $root = $document->getElementsByTagName('div')->item(0);
        if (! $root instanceof DOMElement) {
            return null;
        }

        $this->sanitizeChildren($root);

        $safeHtml = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $safeHtml .= $document->saveHTML($child);
        }

        $safeHtml = trim($safeHtml);
        $visibleText = trim(html_entity_decode(strip_tags(str_replace('<br>', '', $safeHtml))));

        return $visibleText === '' ? null : $safeHtml;
    }

    private function sanitizeChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if (! $node instanceof DOMElement) {
                if ($node->nodeType === XML_COMMENT_NODE) {
                    $parent->removeChild($node);
                }

                continue;
            }

            $tag = mb_strtolower($node->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $parent->removeChild($node);

                continue;
            }

            $this->sanitizeChildren($node);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                while ($node->firstChild !== null) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);

                continue;
            }

            $this->sanitizeAttributes($node, $tag);
        }
    }

    private function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $href = $tag === 'a' ? trim($element->getAttribute('href')) : '';
        $listType = $tag === 'li' ? trim($element->getAttribute('data-list')) : '';

        while ($element->attributes->length > 0) {
            $attribute = $element->attributes->item(0);
            if ($attribute === null) {
                break;
            }

            $element->removeAttributeNode($attribute);
        }

        if ($tag === 'li' && in_array($listType, ['ordered', 'bullet'], true)) {
            $element->setAttribute('data-list', $listType);
        }

        if ($tag === 'a' && $this->isSafeUrl($href)) {
            $element->setAttribute('href', $href);
            $element->setAttribute('rel', 'noopener noreferrer');
            $element->setAttribute('target', '_blank');
        }
    }

    private function isSafeUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        $scheme = mb_strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto'], true);
    }
}
