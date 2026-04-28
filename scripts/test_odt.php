<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Row;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\TextBreak;

$odtPath = __DIR__ . '/../prueba.odt';

echo "=== ODT Test ===\n";
echo "File: $odtPath\n";
echo "Exists: " . (file_exists($odtPath) ? 'YES' : 'NO') . "\n";
echo "Size: " . filesize($odtPath) . " bytes\n\n";

// Test 1: IOFactory::load (auto-detect by extension)
echo "--- Test 1: IOFactory::load (auto-detect) ---\n";
try {
    $doc = IOFactory::load($odtPath);
    $text = extractAllText($doc);
    echo "SUCCESS - Text length: " . strlen($text) . "\n";
    echo "First 500 chars:\n" . substr($text, 0, 500) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Test 2: explicit ODText reader
echo "\n--- Test 2: Explicit ODText reader ---\n";
try {
    $reader = IOFactory::createReader('ODText');
    $doc = $reader->load($odtPath);
    $text = extractAllText($doc);
    echo "SUCCESS - Text length: " . strlen($text) . "\n";
    echo "First 500 chars:\n" . substr($text, 0, 500) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Test 3: simulate temp path (no extension) via IOFactory::load
echo "\n--- Test 3: Temp path without extension (simulates bug) ---\n";
$tmpPath = sys_get_temp_dir() . '/testfile_' . uniqid();
copy($odtPath, $tmpPath);
try {
    $doc = IOFactory::load($tmpPath);
    $text = extractAllText($doc);
    echo "SUCCESS - Text length: " . strlen($text) . "\n";
} catch (Exception $e) {
    echo "ERROR (expected): " . $e->getMessage() . "\n";
}
@unlink($tmpPath);

// Test 4: temp path with .odt extension
echo "\n--- Test 4: Temp path WITH .odt extension ---\n";
$tmpPath2 = sys_get_temp_dir() . '/testfile_' . uniqid() . '.odt';
copy($odtPath, $tmpPath2);
try {
    $doc = IOFactory::load($tmpPath2);
    $text = extractAllText($doc);
    echo "SUCCESS - Text length: " . strlen($text) . "\n";
    echo "First 500 chars:\n" . substr($text, 0, 500) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
@unlink($tmpPath2);

// Test 5: what detectReader returns for no-extension file
echo "\n--- Test 5: IOFactory detectReader for no-extension path ---\n";
try {
    $noext = sys_get_temp_dir() . '/noextfile_' . uniqid();
    copy($odtPath, $noext);
    $readerType = IOFactory::detectReader($noext);
    echo "Detected reader: $readerType\n";
    @unlink($noext);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

function extractAllText($doc): string {
    $lines = [];
    foreach ($doc->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            processElement($element, $lines);
        }
    }
    return implode("\n", $lines);
}

function processElement($element, array &$lines): void {
    if ($element instanceof Text) {
        $t = trim($element->getText());
        if ($t !== '') $lines[] = $t;
        return;
    }
    if ($element instanceof TextRun) {
        $parts = [];
        foreach ($element->getElements() as $child) {
            if ($child instanceof Text) {
                $t = $child->getText();
                if ($t !== '') $parts[] = $t;
            } elseif ($child instanceof TextBreak) {
                $parts[] = "\n";
            }
        }
        $line = implode('', $parts);
        if (trim($line) !== '') $lines[] = $line;
        return;
    }
    if ($element instanceof ListItem) {
        $textRun = $element->getTextObject();
        if ($textRun instanceof TextRun) {
            $parts = [];
            foreach ($textRun->getElements() as $child) {
                if ($child instanceof Text) $parts[] = $child->getText();
            }
            $line = trim(implode('', $parts));
            if ($line !== '') $lines[] = '• ' . $line;
        }
        return;
    }
    if ($element instanceof Table) {
        foreach ($element->getRows() as $row) {
            if ($row instanceof Row) {
                $cells = [];
                foreach ($row->getCells() as $cell) {
                    if ($cell instanceof Cell) {
                        $cellParts = [];
                        foreach ($cell->getElements() as $cellEl) {
                            $cellLines = [];
                            processElement($cellEl, $cellLines);
                            $cellParts[] = implode(' ', $cellLines);
                        }
                        $cells[] = trim(implode(' ', $cellParts));
                    }
                }
                $rowText = implode(' | ', array_filter($cells, fn($c) => $c !== ''));
                if ($rowText !== '') $lines[] = $rowText;
            }
        }
        return;
    }
    if (method_exists($element, 'getElements')) {
        foreach ($element->getElements() as $child) {
            processElement($child, $lines);
        }
    }
}
