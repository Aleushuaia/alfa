<?php
/**
 * Dump all element types returned by the ODT reader
 */
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;

$odtPath = __DIR__ . '/../prueba.odt';

echo "=== ODT Element Tree Dump ===\n\n";

$reader = IOFactory::createReader('ODText');
$doc    = $reader->load($odtPath);

$totalElements = 0;

foreach ($doc->getSections() as $si => $section) {
    echo "SECTION $si\n";
    foreach ($section->getElements() as $ei => $element) {
        dumpElement($element, 1, $totalElements);
    }
}

echo "\nTotal elements visited: $totalElements\n";

// ── Also dump raw XML from content.xml ───────────────────────────────────────
echo "\n=== Raw XML text extraction from content.xml ===\n";
$zip = new ZipArchive();
if ($zip->open($odtPath) === true) {
    $xmlNames = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $xmlNames[] = $zip->getNameIndex($i);
    }
    echo "Files in ODT: " . implode(', ', array_slice($xmlNames, 0, 15)) . "\n\n";

    $content = $zip->getFromName('content.xml');
    $zip->close();

    if ($content) {
        // Extract text nodes from XML directly
        $dom = new DOMDocument();
        $dom->loadXML($content);
        $xpath = new DOMXPath($dom);
        // Register ODT namespaces
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
        $xpath->registerNamespace('office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');

        $paragraphs = $xpath->query('//text:p | //text:h');
        $lines = [];
        foreach ($paragraphs as $p) {
            $t = trim($p->textContent);
            if ($t !== '') $lines[] = $t;
        }
        $fullText = implode("\n", $lines);
        echo "Direct XML extraction: " . strlen($fullText) . " chars, " . count($lines) . " paragraphs\n";
        echo "First 800 chars:\n$fullText\n";
    }
}

function dumpElement($element, int $depth, int &$count): void {
    $count++;
    $class = get_class($element);
    $shortClass = substr($class, strrpos($class, '\\') + 1);
    $indent = str_repeat('  ', $depth);

    $extra = '';
    if (method_exists($element, 'getText')) {
        $t = $element->getText();
        if (is_string($t) && $t !== '') $extra = ' TEXT="' . substr($t, 0, 60) . '"';
    }

    echo "{$indent}[{$shortClass}]{$extra}\n";

    if (method_exists($element, 'getElements')) {
        foreach ($element->getElements() as $child) {
            dumpElement($child, $depth + 1, $count);
            if ($count > 300) { echo "{$indent}  ... (truncated)\n"; return; }
        }
    }
}
