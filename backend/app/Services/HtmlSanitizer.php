<?php

namespace App\Services;

class HtmlSanitizer
{
    public function sanitize(string $html): string
    {
        $allowed_tags = ['p','br','strong','em','u','s','h2','h3','h4','ul','ol','li','blockquote','code','pre','hr','a','img'];
        $allowed_attr = ['href','target','rel','src','alt','width','height'];

        $doc = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>'
            . $html . '</body></html>',
            LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);

        foreach ($xpath->query('//@*') as $attr) {
            $name = strtolower($attr->nodeName);
            $parent = $attr->parentNode;

            if (in_array($name, $allowed_attr, true)) {
                if ($name === 'href' || $name === 'src') {
                    $val = $attr->value;
                    if (!preg_match('/^https?:\/\//i', $val) && !str_starts_with($val, '/')) {
                        $parent->removeAttribute($name);
                    }
                }
                continue;
            }

            $parent->removeAttribute($name);
        }

        // External links: force a new tab and protect against tabnabbing
        foreach ($xpath->query('//a[@href]') as $a) {
            if (preg_match('/^https?:\/\//i', $a->getAttribute('href'))) {
                $a->setAttribute('target', '_blank');
                $a->setAttribute('rel', 'noopener nofollow');
            }
        }

        $body = $doc->getElementsByTagName('body')->item(0);
        $result = '';
        foreach ($body->childNodes as $node) {
            $result .= $doc->saveHTML($node);
        }

        return strip_tags($result, '<' . implode('><', $allowed_tags) . '>');
    }
}
