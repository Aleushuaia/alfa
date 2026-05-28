<?php

namespace App\Http\Controllers;

use App\Models\EntityBlacklist;
use App\Models\EntityWhitelist;
use App\Services\NlpEntityService;
use App\Services\OcrExtractorService;
use App\Services\TextNormalizationService;
use App\Services\UnidadActivaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Row;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\ListItem;

class WordAnonymizerController extends Controller
{
    public function __construct(
        private NlpEntityService $nlp,
        private OcrExtractorService $ocr,
        private TextNormalizationService $normalization,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // index
    // ─────────────────────────────────────────────────────────────────────────
    public function index()
    {
        $entityColors = EntityConfigController::getUserColors(auth()->id() ?? 0);

        return view('word-anonymizer.index', compact('entityColors'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // initializePersonas  —  Converts a list of full names to initials
    //
    // POST /word-anonymizer/initials
    // Body: { names: ["Juan Garcia", "Maria Lopez", ...] }
    // Returns: { initials: { "Juan Garcia": "J.G.", "Maria Lopez": "M.L." } }
    // ─────────────────────────────────────────────────────────────────────────
    public function initializePersonas(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'names'   => ['required', 'array', 'min:1', 'max:200'],
            'names.*' => ['required', 'string', 'max:500'],
        ]);

        $map = [];
        foreach ($request->input('names') as $name) {
            $map[$name] = $this->normalization->toInitials($name);
        }

        return response()->json(['initials' => $map]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // process  —  Upload Word file, extract text, keep file in session
    // ─────────────────────────────────────────────────────────────────────────
    public function process(Request $request)
    {
        $request->validate([
            'word' => [
                'required',
                'file',
                'mimes:doc,docx',
                'max:51200',
            ],
        ], [
            'word.required' => 'Debe seleccionar un archivo Word.',
            'word.mimes'    => 'Solo se aceptan archivos Word (.doc o .docx).',
            'word.max'      => 'El archivo no debe superar los 50 MB.',
        ]);

        $file = $request->file('word');

        // Clean up files from a previous session
        $this->cleanupSessionFiles();

        Storage::disk('local')->makeDirectory('temp-word');
        // Preserve original extension so the correct PhpWord reader can be selected
        $origExt = strtolower($file->getClientOriginalExtension() ?: 'docx');
        $tmpPath = $file->storeAs('temp-word', 'wa_' . uniqid() . '.' . $origExt, 'local');
        $absPath = Storage::disk('local')->path($tmpPath);

        // Keep file in session — needed later for anonymization
        session([
            'wa_doc_path'    => $tmpPath,
            'wa_doc_name'    => $file->getClientOriginalName(),
            'wa_source_type' => 'word',   // used to gate Word download
        ]);

        try {
            Log::info('WordAnonymizer: extrayendo texto', [
                'filename' => $file->getClientOriginalName(),
                'size_mb'  => round($file->getSize() / 1048576, 2),
            ]);

            $text  = $this->extractText($absPath, $origExt);
            $chars = strlen($text);
            $words = str_word_count($text);
            $lines = substr_count($text, "\n") + 1;

            Log::info('WordAnonymizer: extracción OK', ['chars' => $chars]);

            return response()->json([
                'text'  => $text,
                'chars' => $chars,
                'words' => $words,
                'lines' => $lines,
            ]);
        } catch (\Exception $e) {
            Storage::disk('local')->delete($tmpPath);
            session()->forget(['wa_doc_path', 'wa_doc_name', 'wa_source_type']);
            Log::error('WordAnonymizer: error en extracción', ['message' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // processPdf  —  Upload PDF, extract text (OCR first, native fallback)
    // ─────────────────────────────────────────────────────────────────────────
    public function processPdf(Request $request)
    {
        $request->validate([
            'pdf' => [
                'required',
                'file',
                'mimes:pdf',
                'max:51200',
            ],
        ], [
            'pdf.required' => 'Debe seleccionar un archivo PDF.',
            'pdf.mimes'    => 'Solo se aceptan archivos PDF.',
            'pdf.max'      => 'El archivo no debe superar los 50 MB.',
        ]);

        $file = $request->file('pdf');

        // Clean up files from a previous session
        $this->cleanupSessionFiles();

        Storage::disk('local')->makeDirectory('temp-word');
        $tmpPath = $file->store('temp-word', 'local');
        $absPath = Storage::disk('local')->path($tmpPath);

        // Mark source as PDF (no Word download available)
        session([
            'wa_doc_path'    => null,
            'wa_doc_name'    => $file->getClientOriginalName(),
            'wa_source_type' => 'pdf',
        ]);

        try {
            Log::info('WordAnonymizer PDF: extrayendo texto', [
                'filename' => $file->getClientOriginalName(),
                'size_mb'  => round($file->getSize() / 1048576, 2),
            ]);

            // Strategy 1: OCR via Tesseract (best for scanned / image PDFs)
            $text       = '';
            $method     = 'ocr';
            $ocrError   = null;

            try {
                $text = $this->ocr->extractFromPdf($absPath);
            } catch (\Exception $ocrEx) {
                $ocrError = $ocrEx->getMessage();
                Log::warning('WordAnonymizer PDF: OCR falló, intentando extracción nativa', [
                    'error' => $ocrError,
                ]);
            }

            // Strategy 2: Native text extraction via pdftotext (digital PDFs)
            if ($text === '') {
                $method = 'native';
                $text   = $this->extractPdfNative($absPath);
            }

            // Strategy 3: Last resort — try to parse any readable ASCII from the binary
            if (trim($text) === '') {
                throw new \RuntimeException(
                    'No se pudo extraer texto del PDF. ' .
                    'Verifique que el documento no esté protegido ni dañado.' .
                    ($ocrError ? " (OCR: {$ocrError})" : '')
                );
            }

            $chars = strlen($text);
            $words = str_word_count($text);
            $lines = substr_count($text, "\n") + 1;

            Log::info('WordAnonymizer PDF: extracción OK', [
                'method' => $method,
                'chars'  => $chars,
            ]);

            return response()->json([
                'text'    => $text,
                'chars'   => $chars,
                'words'   => $words,
                'lines'   => $lines,
                'method'  => $method,
            ]);

        } catch (\Exception $e) {
            Log::error('WordAnonymizer PDF: error', ['message' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 422);

        } finally {
            // PDF is never stored in session for download — always delete temp file
            if (Storage::disk('local')->exists($tmpPath)) {
                Storage::disk('local')->delete($tmpPath);
            }
        }
    }

    /**
     * Native text extraction from digital PDFs using pdftotext binary.
     * Fallback when OCR fails or the PDF is digital (not scanned).
     */
    private function extractPdfNative(string $absPath): string
    {
        $outFile = sys_get_temp_dir() . '/wa_pdf_' . uniqid() . '.txt';

        try {
            // pdftotext preserves layout; -enc UTF-8 for proper encoding
            $cmd  = sprintf('pdftotext -enc UTF-8 -layout %s %s 2>&1', escapeshellarg($absPath), escapeshellarg($outFile));
            exec($cmd, $out, $code);

            if ($code === 0 && file_exists($outFile)) {
                $text = file_get_contents($outFile);
                if (is_string($text) && trim($text) !== '') {
                    return $this->cleanPdfText($text);
                }
            }

            // pdftotext not available or failed — try raw binary scan for readable text
            // (last resort, very limited)
            return '';
        } finally {
            if (file_exists($outFile)) {
                @unlink($outFile);
            }
        }
    }

    /**
     * Clean up extracted PDF text (remove form-feed chars, normalize whitespace).
     */
    private function cleanPdfText(string $text): string
    {
        // Remove PDF form-feed characters
        $text = str_replace("\f", "\n\n", $text);
        // Normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        // Collapse excessive blank lines (>2 consecutive) to 2
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // analyzeText  —  NLP analysis on extracted text, returns grouped entities
    // ─────────────────────────────────────────────────────────────────────────
    public function analyzeText(Request $request)
    {
        $request->validate([
            'text'          => ['required', 'string', 'min:10', 'max:200000'],
            'entity_filter' => ['nullable', 'string'],
        ]);

        $text         = trim($request->input('text'));
        $entityFilter = $request->input('entity_filter');

        try {
            $result = $this->nlp->analyze($text);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if ($entityFilter) {
            $allowedTypes = array_map('trim', explode(',', $entityFilter));
            $result = $this->filterByEntityTypes($result, $allowedTypes);
        }

        $filtered = $this->filterEntitiesAndHtml($result['entities'] ?? [], $result['html'] ?? '');
        $final    = $this->injectWhitelistEntities($filtered['entities'], $filtered['html'], $text);
        $grouped  = $this->buildGroupedEntities($final['entities']);

        Log::info('WordAnonymizer: análisis NLP OK', [
            'entities' => count($final['entities']),
            'grouped'  => count($grouped),
        ]);

        return response()->json([
            'entities'        => $final['entities'],
            'groupedEntities' => $grouped,
            'html'            => $final['html'] ?? '',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // anonymize  —  Load DOCX from session, paragraph-level strtr, save new file
    // ─────────────────────────────────────────────────────────────────────────
    public function anonymize(Request $request)
    {
        $request->validate([
            'replacements' => ['required', 'array'],
        ]);

        $replacements = $request->input('replacements'); // {"original text" => "[LABEL N]"}

        if (empty($replacements)) {
            return response()->json(['error' => 'No hay entidades para anonimizar.'], 422);
        }

        $docPath = session('wa_doc_path');
        $sourceType = session('wa_source_type', 'word');

        // For PDF and plain-text sources there is no Word file to anonymize
        if ($sourceType !== 'word') {
            return response()->json([
                'success'       => true,
                'message'       => 'Texto anonimizado correctamente.',
                'download_url'  => null,
                'source_type'   => $sourceType,
            ]);
        }

        if (!$docPath || !Storage::disk('local')->exists($docPath)) {
            return response()->json([
                'error' => 'No hay documento Word en sesión. Por favor, suba el archivo nuevamente.',
            ], 422);
        }

        $absSourcePath = Storage::disk('local')->path($docPath);

        try {
            // Remove previous anonymized file
            $oldAnon = session('wa_anonymized_path');
            if ($oldAnon && file_exists($oldAnon)) {
                @unlink($oldAnon);
            }

            $outPath = $this->anonymizeDocxViaDom($absSourcePath, $replacements);
            session(['wa_anonymized_path' => $outPath]);

            Log::info('WordAnonymizer: anonimización OK', ['file' => basename($outPath)]);

            return response()->json([
                'success'      => true,
                'message'      => 'Documento anonimizado generado correctamente.',
                'download_url' => route('word-anonymizer.download'),
                'source_type'  => 'word',
            ]);
        } catch (\Exception $e) {
            Log::error('WordAnonymizer: error anonimizando', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Error al generar el documento: ' . $e->getMessage()], 422);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // download  —  Stream the anonymized DOCX
    // ─────────────────────────────────────────────────────────────────────────
    public function download()
    {
        $absPath = session('wa_anonymized_path');

        if (!$absPath || !file_exists($absPath)) {
            abort(404, 'El archivo no está disponible. Por favor, genere un nuevo documento anonimizado.');
        }

        $originalName = session('wa_doc_name', 'documento.docx');
        $baseName     = pathinfo($originalName, PATHINFO_FILENAME);
        // Always deliver as DOCX regardless of source format (DOC → converted)
        $downloadName = $baseName . '-anonimizado.docx';

        return response()->download($absPath, $downloadName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  TEXT EXTRACTION HELPERS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Map a file extension to the correct PhpWord reader name.
     */
    private function phpWordReaderType(string $ext): string
    {
        return match (strtolower($ext)) {
            'docx'  => 'Word2007',
            'doc'   => 'MsDoc',
            default => 'Word2007',
        };
    }

    private function extractText(string $path, string $ext = ''): string
    {
        $ext     = strtolower($ext ?: pathinfo($path, PATHINFO_EXTENSION));
        $reader  = IOFactory::createReader($this->phpWordReaderType($ext));
        $phpWord = $reader->load($path);
        $lines   = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $this->processElement($element, $lines);
            }
        }

        return implode("\n", $lines);
    }

    private function processElement($element, array &$lines): void
    {
        if ($element instanceof Text) {
            $text = trim($element->getText());
            if ($text !== '') {
                $lines[] = $text;
            }
            return;
        }

        if ($element instanceof TextRun) {
            $parts = [];
            foreach ($element->getElements() as $child) {
                if ($child instanceof Text) {
                    $t = $child->getText();
                    if ($t !== '') {
                        $parts[] = $t;
                    }
                } elseif ($child instanceof TextBreak) {
                    $parts[] = "\n";
                }
            }
            $line = implode('', $parts);
            if (trim($line) !== '') {
                $lines[] = $line;
            }
            return;
        }

        if ($element instanceof ListItem) {
            $textRun = $element->getTextObject();
            if ($textRun instanceof TextRun) {
                $parts = [];
                foreach ($textRun->getElements() as $child) {
                    if ($child instanceof Text) {
                        $parts[] = $child->getText();
                    }
                }
                $line = trim(implode('', $parts));
                if ($line !== '') {
                    $lines[] = '• ' . $line;
                }
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
                                $this->processElement($cellEl, $cellLines);
                                $cellParts[] = implode(' ', $cellLines);
                            }
                            $cells[] = trim(implode(' ', $cellParts));
                        }
                    }
                    $rowText = implode(' | ', array_filter($cells, fn($c) => $c !== ''));
                    if ($rowText !== '') {
                        $lines[] = $rowText;
                    }
                }
            }
            return;
        }

        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $child) {
                $this->processElement($child, $lines);
            }
        }
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  DOCX ANONYMIZATION  —  paragraph-level via ZipArchive + DOMDocument
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Anonimiza el DOCX trabajando por bloques de párrafo completos.
     *
     * Para cada <w:p>:
     *   Fase 1 — reemplazo run-a-run: cada <w:r> conserva su <w:rPr> intacto;
     *            solo se actualiza el contenido de <w:t>. Cubre el 90 % de los casos
     *            (negritas, cursivas, distintas fuentes, etc.).
     *   Fase 2 — spans inter-run: si un término de reemplazo abarca varios <w:r>
     *            consecutivos, se fusionan solo los runs implicados y se preservan
     *            los runs no afectados con su formato original.
     */
    private function anonymizeDocxViaDom(string $sourcePath, array $replacements): string
    {
        $outDir = storage_path('app/word-anonymized');
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $outPath = $outDir . '/anonimizado-' . uniqid() . '.docx';
        $ext     = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        // Non-DOCX formats: best-effort via PHPWord
        if ($ext !== 'docx') {
            return $this->anonymizeViaPHPWord($sourcePath, $outPath, $replacements);
        }

        if (!copy($sourcePath, $outPath)) {
            throw new \RuntimeException('No se pudo copiar el archivo de origen.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($outPath) !== true) {
            throw new \RuntimeException('No se pudo abrir el archivo DOCX para escritura.');
        }

        $xmlContent = $zip->getFromName('word/document.xml');
        if ($xmlContent === false) {
            $zip->close();
            throw new \RuntimeException('El archivo no es un DOCX válido (falta word/document.xml).');
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput       = false;

        if (!$dom->loadXML($xmlContent)) {
            $zip->close();
            throw new \RuntimeException('No se pudo parsear el XML del documento Word.');
        }

        $ns    = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', $ns);

        foreach ($xpath->query('//w:p') as $para) {
            $this->anonymizeParagraphRuns($dom, $xpath, $para, $ns, $replacements);
        }

        $zip->addFromString('word/document.xml', $dom->saveXML());
        $zip->close();

        return $outPath;
    }

    /**
     * Anonymize a single paragraph while preserving run-level formatting.
     *
     * Phase 1: replace text within each individual run — the run's <w:rPr>
     *          (bold, italic, font, color, etc.) is never touched.
     * Phase 2: for replacement terms that still span multiple runs, merge only
     *          those runs, keeping the first run's <w:rPr> and leaving all
     *          other runs in the paragraph untouched.
     */
    private function anonymizeParagraphRuns(
        \DOMDocument $dom,
        \DOMXPath    $xpath,
        \DOMElement  $para,
        string       $ns,
        array        $replacements
    ): void {
        // Quick escape: does this paragraph contain anything to replace?
        $fullText = '';
        foreach ($xpath->query('.//w:t', $para) as $t) {
            $fullText .= $t->nodeValue;
        }
        if ($fullText === '' || strtr($fullText, $replacements) === $fullText) {
            return;
        }

        // ── Phase 1: run-by-run replacement ──────────────────────────────────
        // Each run keeps its own <w:rPr> untouched; only <w:t> content changes.
        foreach ($xpath->query('w:r', $para) as $run) {
            /** @var \DOMElement $run */
            $tNodes = iterator_to_array($xpath->query('w:t', $run));
            if (empty($tNodes)) {
                continue;
            }

            $runText  = implode('', array_map(fn($t) => $t->nodeValue, $tNodes));
            $replaced = strtr($runText, $replacements);
            if ($replaced === $runText) {
                continue;
            }

            // Consolidate into the first <w:t> and remove extra nodes
            $tNodes[0]->nodeValue = $replaced;
            $tNodes[0]->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
            for ($i = 1, $cnt = count($tNodes); $i < $cnt; $i++) {
                $run->removeChild($tNodes[$i]);
            }
        }

        // ── Check if cross-run replacements remain ────────────────────────────
        $afterPhase1 = '';
        foreach ($xpath->query('.//w:t', $para) as $t) {
            $afterPhase1 .= $t->nodeValue;
        }
        if (strtr($afterPhase1, $replacements) === $afterPhase1) {
            return; // Everything resolved in Phase 1
        }

        // ── Phase 2: cross-run merging ────────────────────────────────────────
        // Sort longest-first so longer entities are not shadowed by substrings.
        $sorted = $replacements;
        uksort($sorted, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($sorted as $original => $label) {
            // Rebuild the run segment map on each iteration (DOM may have changed)
            $segments = [];
            $pos      = 0;
            foreach ($xpath->query('w:r', $para) as $run) {
                $text = '';
                foreach ($xpath->query('w:t', $run) as $t) {
                    $text .= $t->nodeValue;
                }
                $len        = mb_strlen($text);
                $segments[] = [
                    'run'   => $run,
                    'text'  => $text,
                    'start' => $pos,
                    'end'   => $pos + $len,
                ];
                $pos += $len;
            }

            $paraText = implode('', array_column($segments, 'text'));
            $origLen  = mb_strlen($original);

            $searchFrom = 0;
            while (($found = mb_strpos($paraText, $original, $searchFrom)) !== false) {
                $foundEnd = $found + $origLen;

                // All segments that overlap [found, foundEnd)
                $affected = array_values(array_filter(
                    $segments,
                    fn($s) => $s['start'] < $foundEnd && $s['end'] > $found
                ));

                if (count($affected) <= 1) {
                    // Single-run match — already handled in Phase 1; skip.
                    $searchFrom = $foundEnd;
                    continue;
                }

                // Distribute the replacement across the affected runs:
                //   first run  → text-before-match + label + text-after-match
                //   other runs → only the portion after the match (may be empty → remove run)
                foreach ($affected as $i => $seg) {
                    /** @var \DOMElement $run */
                    $run    = $seg['run'];
                    $rStart = $seg['start'];

                    if ($i === 0) {
                        $before  = mb_substr($seg['text'], 0, max(0, $found - $rStart));
                        $afterAbsStart = max($foundEnd, $rStart);
                        $after   = mb_substr($seg['text'], $afterAbsStart - $rStart);
                        $newText = $before . $label . $after;
                    } else {
                        $afterAbsStart = max($foundEnd, $rStart);
                        $newText = mb_substr($seg['text'], $afterAbsStart - $rStart);
                    }

                    $tNodeList = iterator_to_array($xpath->query('w:t', $run));

                    if ($newText !== '') {
                        if (!empty($tNodeList)) {
                            $tNodeList[0]->nodeValue = $newText;
                            $tNodeList[0]->setAttributeNS(
                                'http://www.w3.org/XML/1998/namespace',
                                'xml:space',
                                'preserve'
                            );
                            for ($j = 1, $jcnt = count($tNodeList); $j < $jcnt; $j++) {
                                $run->removeChild($tNodeList[$j]);
                            }
                        }
                    } else {
                        // Empty run after the replacement → remove from DOM
                        $run->parentNode?->removeChild($run);
                    }
                }

                // Update paraText so we can find the next occurrence correctly,
                // then restart the segment map (DOM changed).
                $paraText   = mb_substr($paraText, 0, $found) . $label . mb_substr($paraText, $foundEnd);
                $searchFrom = $found + mb_strlen($label);

                // Rebuild segments after DOM mutation
                $segments = [];
                $pos      = 0;
                foreach ($xpath->query('w:r', $para) as $run) {
                    $text = '';
                    foreach ($xpath->query('w:t', $run) as $t) {
                        $text .= $t->nodeValue;
                    }
                    $len        = mb_strlen($text);
                    $segments[] = [
                        'run'   => $run,
                        'text'  => $text,
                        'start' => $pos,
                        'end'   => $pos + $len,
                    ];
                    $pos += $len;
                }
            }
        }
    }

    /** Fallback for DOC format: run-level text replacement via PHPWord */
    private function anonymizeViaPHPWord(string $sourcePath, string $outPath, array $replacements): string
    {
        $ext    = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        $reader = IOFactory::createReader($this->phpWordReaderType($ext));
        $phpWord = $reader->load($sourcePath);

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $this->anonymizeElementPHPWord($element, $replacements);
            }
        }

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($outPath);

        return $outPath;
    }

    private function anonymizeElementPHPWord($element, array $replacements): void
    {
        if ($element instanceof Text) {
            $element->setText(strtr($element->getText(), $replacements));
            return;
        }

        if ($element instanceof TextRun) {
            foreach ($element->getElements() as $child) {
                if ($child instanceof Text) {
                    $child->setText(strtr($child->getText(), $replacements));
                }
            }
            return;
        }

        if ($element instanceof Table) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $cellEl) {
                        $this->anonymizeElementPHPWord($cellEl, $replacements);
                    }
                }
            }
            return;
        }

        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $child) {
                $this->anonymizeElementPHPWord($child, $replacements);
            }
        }
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  NLP / ENTITY HELPERS  (mirrors PdfAnalyzerController)
    // ═════════════════════════════════════════════════════════════════════════

    private function filterByEntityTypes(array $result, array $allowedTypes): array
    {
        $allowed = array_map('strtoupper', $allowedTypes);

        $typeMap = [
            'PER'   => ['PER', 'PERSON'],
            'ORG'   => ['ORG'],
            'LOC'   => ['LOC', 'GPE'],
            'DATE'  => ['DATE'],
            'DNI'   => ['DNI'],
            'EMAIL' => ['EMAIL'],
            'PHONE' => ['PHONE'],
            'MISC'  => ['MISC', 'PATENTE'],
        ];

        $allowedRaw = [];
        foreach ($allowed as $type) {
            $allowedRaw = array_merge($allowedRaw, $typeMap[$type] ?? [$type]);
        }

        $entities = array_filter(
            $result['entities'] ?? [],
            fn($ent) => in_array(strtoupper($ent['label'] ?? ''), $allowedRaw, true)
        );

        $cssMap = [
            'PER' => 'person', 'PERSON' => 'person',
            'ORG' => 'org',
            'LOC' => 'location', 'GPE' => 'location',
            'DATE' => 'date', 'DNI' => 'dni',
            'EMAIL' => 'email', 'PHONE' => 'phone',
            'MISC' => 'misc', 'PATENTE' => 'misc',
        ];

        $html = $result['html'] ?? '';

        $classesToRemove = [];
        foreach ($cssMap as $rawLabel => $cssClass) {
            if (!in_array($rawLabel, $allowedRaw, true)) {
                $classesToRemove[$cssClass] = true;
            }
        }

        foreach (array_keys($classesToRemove) as $cssClass) {
            $html = preg_replace_callback(
                '/<span[^>]*class="entity\s+' . preg_quote($cssClass, '/') . '"[^>]*>(.*?)<\/span>/is',
                fn($m) => $m[1],
                $html
            );
        }

        return ['html' => $html, 'entities' => array_values($entities)];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // normalizeForMatch + buildFlexiblePattern  — Helpers de matching tolerante
    //   a mayúsculas, diacríticos y espacios. Idénticos al PdfAnalyzerController.
    // ─────────────────────────────────────────────────────────────────────────
    private static function normalizeForMatch(string $s): string
    {
        if (class_exists('Normalizer')) {
            $s = \Normalizer::normalize($s, \Normalizer::FORM_KD) ?: $s;
        }
        $s = preg_replace('/\p{M}/u', '', $s);
        $s = mb_strtolower($s);
        return trim((string) preg_replace('/\s+/u', ' ', $s));
    }

    private static function buildFlexiblePattern(string $term): string
    {
        static $ACCENT_MAP = [
            'a' => 'aáàâãäåā', 'e' => 'eéèêëē', 'i' => 'iíìîïī',
            'o' => 'oóòôõöø',  'u' => 'uúùûüū', 'n' => 'nñ',
            'c' => 'cç',       'y' => 'yý',      's' => 'sśš',
            'z' => 'zźž',      'l' => 'lł',
        ];

        $normalized   = self::normalizeForMatch($term);
        $wordPatterns = [];

        foreach (explode(' ', $normalized) as $word) {
            if ($word === '') continue;
            $charPat = '';
            foreach (preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) as $char) {
                $charPat .= isset($ACCENT_MAP[$char])
                    ? '[' . $ACCENT_MAP[$char] . ']'
                    : preg_quote($char, '/');
            }
            $wordPatterns[] = $charPat;
        }

        return implode('\s+', $wordPatterns);
    }

    private function filterEntitiesAndHtml(array $entities, string $html): array
    {
        $blacklist = $this->getActiveBlacklist();

        if (empty($blacklist)) {
            return ['entities' => $entities, 'html' => $html];
        }

        $entities = array_filter($entities, function (array $ent) use ($blacklist): bool {
            foreach ($blacklist as $entry) {
                if ($this->termMatchesEntity($ent['text'] ?? '', $ent['label'] ?? '', $entry)) {
                    return false;
                }
            }
            return true;
        });

        foreach ($blacklist as $entry) {
            $term          = $entry['term'];
            $entityType    = $entry['entity_type'] ?? null;
            $caseSensitive = (bool) ($entry['case_sensitive'] ?? false);
            $matchMode     = $entry['match_mode'] ?? 'exact';

            $escapedHtmlTerm = htmlspecialchars($term, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if ($matchMode === 'regex') {
                // Raw regex: no modificar, el usuario define el patrón
                $innerPattern = '[^<]*(?:' . $term . ')[^<]*';
                $flags        = $caseSensitive ? 'u' : 'iu';
            } elseif ($caseSensitive) {
                // Sensible al caso: solo espacios flexibles
                $wordParts   = preg_split('/\s+/u', trim($term), -1, PREG_SPLIT_NO_EMPTY);
                $termPattern = implode('\s+', array_map(
                    fn($w) => preg_quote(htmlspecialchars($w, ENT_QUOTES | ENT_HTML5, 'UTF-8'), '/'),
                    $wordParts
                ));
                $innerPattern = $matchMode === 'contains' ? '[^<]*' . $termPattern . '[^<]*' : '\s*' . $termPattern . '\s*';
                $flags        = 'u';
            } else {
                // Insensible a acentos + mayúsculas + espacios extra
                $termPattern  = self::buildFlexiblePattern($term);
                $innerPattern = $matchMode === 'contains' ? '[^<]*' . $termPattern . '[^<]*' : '\s*' . $termPattern . '\s*';
                $flags        = 'iu';
            }

            if ($entityType) {
                $escapedLabel = preg_quote($entityType, '/');
                $pattern = '/<span[^>]*\bclass="entity[^"]*"[^>]*\bdata-label="' . $escapedLabel . '"[^>]*>' . $innerPattern . '<\/span>/' . $flags;
            } else {
                $pattern = '/<span[^>]*\bclass="entity[^"]*"[^>]*>' . $innerPattern . '<\/span>/' . $flags;
            }

            $html = preg_replace($pattern, $escapedHtmlTerm, $html);
        }

        return ['entities' => array_values($entities), 'html' => $html];
    }

    private function termMatchesEntity(string $entityText, string $entityLabel, array $entry): bool
    {
        $term          = $entry['term'] ?? '';
        $type          = $entry['entity_type'] ?? null;
        $caseSensitive = (bool) ($entry['case_sensitive'] ?? false);
        $matchMode     = $entry['match_mode'] ?? 'exact';

        if ($type !== null && strcasecmp($type, $entityLabel) !== 0) {
            return false;
        }

        $haystack = trim($entityText);
        $needle   = trim($term);

        if ($matchMode === 'regex') {
            $flags   = $caseSensitive ? 'u' : 'iu';
            $pattern = '/' . $needle . '/' . $flags;
            return (bool) @preg_match($pattern, $haystack);
        }

        if ($caseSensitive) {
            // Sensible al caso: solo normalizar espacios
            $normHaystack = trim((string) preg_replace('/\s+/u', ' ', $haystack));
            $normNeedle   = trim((string) preg_replace('/\s+/u', ' ', $needle));
            return $matchMode === 'contains'
                ? str_contains($normHaystack, $normNeedle)
                : $normNeedle === $normHaystack;
        }

        // Default: insensible a acentos + mayúsculas + espacios extra
        $normHaystack = self::normalizeForMatch($haystack);
        $normNeedle   = self::normalizeForMatch($needle);

        return $matchMode === 'contains'
            ? str_contains($normHaystack, $normNeedle)
            : $normNeedle === $normHaystack;
    }

    private function buildGroupedEntities(array $entities): array
    {
        $grouped = [];
        $normMap = [];

        $normalize = static fn(string $s): string => self::normalizeForMatch($s);

        foreach ($entities as $ent) {
            $text  = trim($ent['text'] ?? '');
            $label = $ent['label'] ?? '';
            if ($text === '') {
                continue;
            }

            $norm = $normalize($text) . '||' . $label;

            if (!isset($normMap[$norm])) {
                $grouped[] = [
                    'text'           => $text,
                    'label'          => $label,
                    'count'          => 0,
                    'variants'       => [],
                    'variant_counts' => [],
                ];
                $normMap[$norm] = count($grouped) - 1;
            }

            $idx = $normMap[$norm];
            $grouped[$idx]['count']++;

            if (!in_array($text, $grouped[$idx]['variants'], true)) {
                $grouped[$idx]['variants'][]            = $text;
                $grouped[$idx]['variant_counts'][$text] = 0;
            }
            $grouped[$idx]['variant_counts'][$text]++;

            $best      = $grouped[$idx]['text'];
            $bestCount = $grouped[$idx]['variant_counts'][$best] ?? 0;
            $thisCount = $grouped[$idx]['variant_counts'][$text];

            if ($thisCount > $bestCount || ($thisCount === $bestCount && mb_strlen($text) > mb_strlen($best))) {
                $grouped[$idx]['text'] = $text;
            }
        }

        foreach ($grouped as &$g) {
            unset($g['variant_counts']);
        }

        return array_values($grouped);
    }

    private function entityTypeToClass(string $entityType): string
    {
        return match (strtoupper($entityType)) {
            'PER', 'PERSON' => 'person',
            'ORG'           => 'org',
            'LOC', 'GPE'    => 'location',
            'DATE'          => 'date',
            'DNI'           => 'dni',
            'EMAIL'         => 'email',
            'PHONE'         => 'phone',
            default         => 'misc',
        };
    }

    private function injectWhitelistEntities(array $entities, string $html, string $originalText): array
    {
        $whitelist = $this->getActiveWhitelist();
        if (empty($whitelist)) {
            return ['entities' => $entities, 'html' => $html];
        }

        // Lower number = higher priority
        $typePriority = [
            'PER' => 1, 'PERSON' => 1,
            'DNI' => 2, 'EMAIL' => 2, 'PHONE' => 2,
            'ORG' => 3, 'LOC' => 3, 'GPE' => 3,
            'DATE' => 4,
            'MISC' => 5,
        ];

        $normOriginal = self::normalizeForMatch($originalText);

        // Index existing entities by normalized text
        $existingByNorm = [];
        foreach ($entities as $i => $ent) {
            $norm = self::normalizeForMatch($ent['text'] ?? '');
            if ($norm !== '') {
                $existingByNorm[$norm][] = $i;
            }
        }

        // All whitelist terms to highlight — whitelist always wins
        $toInject = [];

        foreach ($whitelist as $entry) {
            $term = trim($entry['term'] ?? '');
            if ($term === '' || mb_strpos($normOriginal, self::normalizeForMatch($term)) === false) {
                continue;
            }

            $entityType = strtoupper($entry['entity_type'] ?? 'MISC') ?: 'MISC';
            $wlPriority = $typePriority[$entityType] ?? 5;
            $normTerm   = self::normalizeForMatch($term);
            $newClass   = $this->entityTypeToClass($entityType);
            $textPat    = self::buildFlexiblePattern($term);

            if (isset($existingByNorm[$normTerm])) {
                // NLP detected the exact same term — update type if whitelist has higher priority
                // Mark as whitelist so the strippedNorms cleanup does not accidentally remove it
                foreach ($existingByNorm[$normTerm] as $idx) {
                    $entities[$idx]['_whitelist'] = true;
                    $oldLabel    = $entities[$idx]['label'] ?? '';
                    $oldPriority = $typePriority[$oldLabel] ?? 5;

                    if ($wlPriority < $oldPriority) {
                        $entities[$idx]['label'] = $entityType;
                        $pattern = '/<span\b[^>]*\bdata-label="' . preg_quote($oldLabel, '/') . '"[^>]*>\s*(' . $textPat . ')\s*<\/span>/iu';
                        $html    = preg_replace(
                            $pattern,
                            '<span class="entity ' . $newClass . '" data-label="' . $entityType . '">$1</span>',
                            $html
                        );
                    }
                }
            } else {
                // Not detected by NLP — register as new entity
                $entities[]                = ['text' => $term, 'label' => $entityType, 'start' => null, 'end' => null, '_whitelist' => true];
                $existingByNorm[$normTerm] = [count($entities) - 1];
            }

            // Always add to injection list: whitelist priority is absolute
            $toInject[] = ['term' => $term, 'type' => $entityType, 'class' => $newClass];
        }

        if (empty($toInject)) {
            return ['entities' => array_values($entities), 'html' => $html];
        }

        // ── Priority pre-pass: strip entity spans that overlap with a whitelist term ─────
        // Handles two cases:
        //   A) The span text CONTAINS the whitelist term as a substring
        //      e.g. NLP span "Juan Carlos García" contains whitelist "Juan Carlos"
        //   B) The whitelist term CROSSES a span boundary
        //      e.g. text has "FRANCO, " before the span and the span contains
        //      "HÉCTOR S/ ABUSO SEXUAL" — whitelist term is "FRANCO, HÉCTOR".
        //      The NLP span absorbs "HÉCTOR", splitting the whitelist term.
        // In both cases the span is stripped so the injection pass can re-highlight
        // exactly the whitelist term. Exact-match spans are left untouched.
        $strippedNorms = [];
        foreach ($toInject as $inj) {
            $textPat  = self::buildFlexiblePattern($inj['term']);
            $normTerm = self::normalizeForMatch($inj['term']);

            // The regex captures: (text before span)(span inner text)
            // [^<]* matches the text node immediately preceding the span tag.
            $html = preg_replace_callback(
                '/([^<]*)<span\b([^>]*class="entity[^"]*"[^>]*)>([^<]+)<\/span>/iu',
                function ($m) use ($textPat, $normTerm, &$strippedNorms) {
                    $before    = $m[1]; // text node immediately before the span
                    $inner     = $m[3]; // text inside the span
                    $normInner = self::normalizeForMatch($inner);

                    // Case A: whitelist term fully inside the span (not exact match)
                    if ($normInner !== $normTerm && preg_match('/' . $textPat . '/iu', $inner)) {
                        $strippedNorms[] = $normInner;
                        return $before . $inner; // strip span wrapper
                    }

                    // Case B: whitelist term crosses the span boundary
                    // (part in $before text node, part inside the span)
                    $combined = $before . $inner;
                    if (
                        preg_match('/' . $textPat . '/iu', $combined) &&
                        !preg_match('/' . $textPat . '/iu', $inner) &&
                        !preg_match('/' . $textPat . '/iu', $before)
                    ) {
                        $strippedNorms[] = $normInner;
                        return $before . $inner; // strip span, reunite text
                    }

                    return $m[0]; // unchanged
                },
                $html
            );
        }

        // Remove entities whose spans were stripped (whitelist entity replaced them).
        // Whitelist-matched entities (_whitelist flag) are always preserved because
        // the injection pass will re-highlight them even if their NLP span was stripped.
        if (!empty($strippedNorms)) {
            $entities = array_values(array_filter(
                $entities,
                fn($e) => !empty($e['_whitelist']) || !in_array(self::normalizeForMatch($e['text'] ?? ''), $strippedNorms, true)
            ));
        }

        // Strip the internal _whitelist flag before returning
        foreach ($entities as &$e) {
            unset($e['_whitelist']);
        }
        unset($e);

        // ── Inject whitelist terms into plain-text nodes (outside entity spans) ──────
        $parts     = preg_split('/(<[^>]+>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result    = [];
        $spanDepth = 0;

        foreach ($parts as $i => $part) {
            if ($i % 2 === 1) {
                if (preg_match('/^<span\b/i', $part)) {
                    $spanDepth++;
                } elseif (preg_match('/^<\/span>/i', $part)) {
                    $spanDepth = max(0, $spanDepth - 1);
                }
                $result[] = $part;
            } else {
                if ($spanDepth === 0 && $part !== '') {
                    foreach ($toInject as $inj) {
                        $part = preg_replace_callback(
                            '/(' . self::buildFlexiblePattern($inj['term']) . ')/iu',
                            fn($m) => '<span class="entity ' . $inj['class'] . '" data-label="' . $inj['type'] . '">' . $m[0] . '</span>',
                            $part
                        );
                    }
                }
                $result[] = $part;
            }
        }

        $finalHtml = implode('', $result);

        // Rebuild the entities list directly from the painted HTML spans.
        // This is the only way to get an accurate count: Phase 3 injection may
        // have created spans for accent variants (e.g. "Alvarez" without accent)
        // that NLP missed, and those spans are not reflected in the tracked
        // $entities array.  By reading spans from the final HTML we guarantee
        // that buildGroupedEntities() counts every painted occurrence, regardless
        // of whether it came from NLP, the whitelist, or the injection pass.
        $entities = [];
        preg_match_all(
            '/<span\b[^>]*\bclass="entity[^"]*"[^>]*\bdata-label="([^"]+)"[^>]*>([^<]*)<\/span>/iu',
            $finalHtml,
            $htmlMatches,
            PREG_SET_ORDER
        );
        foreach ($htmlMatches as $hm) {
            $spanText = html_entity_decode($hm[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (trim($spanText) === '') {
                continue;
            }
            $entities[] = [
                'text'  => trim($spanText),
                'label' => $hm[1],
                'start' => null,
                'end'   => null,
            ];
        }

        return ['entities' => $entities, 'html' => $finalHtml];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  DB HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function getActiveBlacklist(): array
    {
        try {
            $unidadId = Auth::check()
                ? optional(app(UnidadActivaService::class)->get(Auth::user()))->id
                : null;

            return EntityBlacklist::active()
                ->where(function ($q) use ($unidadId) {
                    if ($unidadId) {
                        $q->where('unidad_id', $unidadId);
                    } else {
                        $q->whereNull('unidad_id');
                    }
                })
                ->get(['term', 'entity_type', 'match_mode', 'case_sensitive'])
                ->toArray();
        } catch (\Exception $e) {
            Log::warning('WordAnonymizer: no se pudo cargar la blacklist: ' . $e->getMessage());
            return [];
        }
    }

    private function getActiveWhitelist(): array
    {
        try {
            $unidadId = Auth::check()
                ? optional(app(UnidadActivaService::class)->get(Auth::user()))->id
                : null;

            return EntityWhitelist::active()
                ->where(function ($q) use ($unidadId) {
                    if ($unidadId) {
                        $q->where('unidad_id', $unidadId);
                    } else {
                        $q->whereNull('unidad_id');
                    }
                })
                ->get(['term', 'entity_type'])
                ->toArray();
        } catch (\Exception $e) {
            Log::warning('WordAnonymizer: no se pudo cargar la whitelist: ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  SESSION CLEANUP
    // ─────────────────────────────────────────────────────────────────────────

    private function cleanupSessionFiles(): void
    {
        try {
            $oldDoc  = session('wa_doc_path');
            $oldAnon = session('wa_anonymized_path');

            if ($oldDoc) {
                Storage::disk('local')->delete($oldDoc);
            }
            if ($oldAnon && file_exists($oldAnon)) {
                @unlink($oldAnon);
            }

            session()->forget(['wa_doc_path', 'wa_doc_name', 'wa_anonymized_path']);
        } catch (\Exception $e) {
            // Non-critical — silently ignore
        }
    }
}
