<?php
// includes/admin/admin_markdown.php

// Convert Markdown to HTML for admin overview
// Supports headings, lists, bold, italic, links, code, hr, tables
//
// Accepts string of markdown text
// Returns HTML output as string
//
function conboard_markdown_to_html(string $markdown): string {
    $lines = explode("\n", $markdown);
    $html = [];
    $in_list = false;
    $in_olist = false;
    $in_code_block = false;
    $code_block_content = '';

    foreach ($lines as $line) {
        $line = rtrim($line);

        // Code block fence ```
        if (preg_match('/^```/', $line)) {
            if ($in_code_block) {
                // close code block
                $html[] = '<pre><code>' . esc_html($code_block_content) . '</code></pre>';
                $in_code_block = false;
                $code_block_content = '';
            } else {
                $in_code_block = true;
            }
            continue;
        }

        if ($in_code_block) {
            $code_block_content .= $line . "\n";
            continue;
        }

        // Horizontal rule
        if (preg_match('/^---$/', $line)) {
            $html[] = '<hr>';
            continue;
        }

        // Headings
        if (preg_match('/^####\s+(.+)$/', $line, $m)) {
            $html[] = '<h3>' . esc_html($m[1]) . '</h3>';
            continue;
        }
        if (preg_match('/^###\s+(.+)$/', $line, $m)) {
            $html[] = '<h3>' . esc_html($m[1]) . '</h3>';
            continue;
        }
        if (preg_match('/^##\s+(.+)$/', $line, $m)) {
            $html[] = '<h2>' . esc_html($m[1]) . '</h2>';
            continue;
        }
        if (preg_match('/^#\s+(.+)$/', $line, $m)) {
            $html[] = '<h1>' . esc_html($m[1]) . '</h1>';
            continue;
        }

        // Unordered list
        if (preg_match('/^\s*-\s+(.+)$/', $line, $m)) {
            if (!$in_list) {
                $in_list = true;
                $html[] = '<ul>';
            }
            $item = conboard_process_inline($m[1]);
            $html[] = '<li>' . $item . '</li>';
            continue;
        } else {
            if ($in_list) {
                $html[] = '</ul>';
                $in_list = false;
            }
        }

        // Ordered list
        if (preg_match('/^\s*\d+\.\s+(.+)$/', $line, $m)) {
            if (!$in_olist) {
                $in_olist = true;
                $html[] = '<ol>';
            }
            $item = conboard_process_inline($m[1]);
            $html[] = '<li>' . $item . '</li>';
            continue;
        } else {
            if ($in_olist) {
                $html[] = '</ol>';
                $in_olist = false;
            }
        }

        // Table row (simple pipe tables)
        if (strpos($line, '|') !== false) {
            $cols = array_map('trim', explode('|', trim($line, '|')));
            $cols = array_map('esc_html', $cols);
            $html[] = '<tr><td>' . implode('</td><td>', $cols) . '</td></tr>';
            continue;
        }

        // Paragraph (inline processing)
        $html[] = '<p>' . conboard_process_inline($line) . '</p>';
    }

    // Close open lists
    if ($in_list) $html[] = '</ul>';
    if ($in_olist) $html[] = '</ol>';

    return implode("\n", $html);
}

//
// Process inline Markdown formatting:
// - bold **text**
// - italic *text*
// - inline code `code`
// - links [text](url)
//
// @param string $text
// @return string
//
function conboard_process_inline(string $text): string {
    // Escape HTML first
    $text = esc_html($text);

    // Links first, with a callback
    $text = preg_replace_callback('/\[(.+?)\]\((.+?)\)/', function($matches) {
        $raw_text = $matches[1]; // link text
        $url = esc_url($matches[2]);

        // Apply inline formatting only inside the link text
        $formatted_text = $raw_text;

        // Inline code first
        $formatted_text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $formatted_text);

        // Bold **text**
        $formatted_text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $formatted_text);

        // Italic *text*
        $formatted_text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $formatted_text);

        return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $formatted_text . '</a>';
    }, $text);

    // Remaining inline formatting outside of links

    // Inline code
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

    // Bold **text**
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

    // Italic *text*
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);

    return $text;
}
