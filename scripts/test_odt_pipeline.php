<?php
/**
 * Full pipeline test: ODT → extract → anonymize → save DOCX
 * Run: php /var/www/scripts/test_odt_pipeline.php
 */
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

echo "=== Pipeline Test: ODT → extract → anonymize → DOCX ===\n\n";

// ── 1. Simulate upload: storeAs with .odt extension ──────────────────────────
$tmpDir = sys_get_temp_dir();
$origExt = 'odt';
$tmpPath = $tmpDir . '/wa_' . uniqid() . '.' . $origExt;
copy($odtPath, $tmpPath);
echo "Step 1 - Stored temp file: $tmpPath\n";
echo "Extension preserved: " . pathinfo($tmpPath, PATHINFO_EXTENSION) . "\n\n";

// ── 2. Extract text ───────────────────────────────────────────────────────────
echo "Step 2 - Extracting text...\n";
try {
    $text = extractText($tmpPath, $origExt);
    $chars = strlen($text);
    $words = str_word_count($text);
    $lines = substr_count($text, "\n") + 1;
    echo "SUCCESS: $chars chars, $words words, $lines lines\n";
    echo "First 400 chars:\n" . substr($text, 0, 400) . "\n\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// ── 3. Simulate anonymization (pick a few terms from the text) ────────────────
echo "Step 3 - Building replacements...\n";
$replacements = [];
// Find first few words that look like names (uppercased sequences)
preg_match_all('/[A-ZÁÉÍÓÚÜÑ][a-záéíóúüñ]{3,}(?:\s+[A-ZÁÉÍÓÚÜÑ][a-záéíóúüñ]{3,})?/', $text, $matches);
$candidates = array_unique(array_slice($matches[0], 0, 3));
foreach ($candidates as $i => $term) {
    $replacements[$term] = '[PERSONA ' . ($i + 1) . ']';
    echo "  Replace: \"$term\" → \"[PERSONA " . ($i + 1) . "]\"\n";
}
if (empty($replacements)) {
    // Fallback: replace a known fragment
    $first = trim(explode("\n", $text)[0]);
    $word = explode(' ', $first)[0];
    $replacements[$word] = '[ANON 1]';
    echo "  Replace: \"$word\" → \"[ANON 1]\"\n";
}
echo "\n";

// ── 4. Anonymize ODT → DOCX ──────────────────────────────────────────────────
echo "Step 4 - Anonymizing (ODT → DOCX)...\n";
$outDir  = sys_get_temp_dir();
$outPath = $outDir . '/anonimizado-' . uniqid() . '.docx';
$ext     = strtolower(pathinfo($tmpPath, PATHINFO_EXTENSION));

try {
    if ($ext !== 'docx') {
        $reader  = IOFactory::createReader(phpWordReaderType($ext));
        $phpWord = $reader->load($tmpPath);

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                anonymizeElement($element, $replacements);
            }
        }

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($outPath);
    }

    echo "SUCCESS: Output DOCX saved to $outPath\n";
    echo "Output file size: " . filesize($outPath) . " bytes\n\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// ── 5. Verify output DOCX contains anonymized text ───────────────────────────
echo "Step 5 - Verifying output DOCX...\n";
try {
    $docxReader = IOFactory::createReader('Word2007');
    $docx = $docxReader->load($outPath);
    $outText = extractText($outPath, 'docx');
    echo "Output text length: " . strlen($outText) . " chars\n";

    foreach ($replacements as $original => $label) {
        $stillPresent = str_contains($outText, $original);
        $labelPresent = str_contains($outText, $label);
        echo "  \"$original\" → still present: " . ($stillPresent ? 'YES (BAD!)' : 'NO (good)') . " | label found: " . ($labelPresent ? 'YES (good)' : 'NO (BAD!)') . "\n";
    }
} catch (Exception $e) {
    echo "ERROR verifying: " . $e->getMessage() . "\n";
}

echo "\n=== Cleanup ===\n";
@unlink($tmpPath);
@unlink($outPath);
echo "Done.\n";

// ─────────────────────────────────────────────────────────────────────────────
//  Helpers (mirror of controller methods)
// ─────────────────────────────────────────────────────────────────────────────

function phpWordReaderType(string $ext): string {
    return match (strtolower($ext)) {
        'docx', 'dotx', 'docm', 'dotm' => 'Word2007',
        'doc', 'dot'                   => 'MsDoc',
        'odt'                          => 'ODText',
        'rtf'                          => 'RTF',
        default                        => 'Word2007',
    };
}

function extractText(string $path, string $ext = ''): string {
    $ext    = strtolower($ext ?: pathinfo($path, PATHINFO_EXTENSION));
    $reader = IOFactory::createReader(phpWordReaderType($ext));
    $phpWord = $reader->load($path);
    $lines = [];
    foreach ($phpWord->getSections() as $section) {
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
            if ($child instanceof Text) { $t = $child->getText(); if ($t !== '') $parts[] = $t; }
            elseif ($child instanceof TextBreak) $parts[] = "\n";
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
        foreach ($element->getElements() as $child) processElement($child, $lines);
    }
}

function anonymizeElement($element, array $replacements): void {
    if ($element instanceof Text) {
        $element->setText(strtr($element->getText(), $replacements));
        return;
    }
    if ($element instanceof TextRun) {
        foreach ($element->getElements() as $child) {
            if ($child instanceof Text) $child->setText(strtr($child->getText(), $replacements));
        }
        return;
    }
    if ($element instanceof Table) {
        foreach ($element->getRows() as $row) {
            foreach ($row->getCells() as $cell) {
                foreach ($cell->getElements() as $cellEl) anonymizeElement($cellEl, $replacements);
            }
        }
        return;
    }
    if (method_exists($element, 'getElements')) {
        foreach ($element->getElements() as $child) anonymizeElement($child, $replacements);
    }
}
