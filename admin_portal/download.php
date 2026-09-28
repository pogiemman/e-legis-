<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

function sanitizeDocxFileName(string $title): string
{
    $safe = preg_replace('/[^A-Za-z0-9\-_]+/', '-', trim($title));
    $safe = trim((string)$safe, '-');
    return $safe !== '' ? $safe : 'legislative-bill';
}

function detectDocxTitle(string $content, string $fallback = ''): string
{
    $plainText = html_entity_decode(strip_tags(preg_replace('/<\/(p|h[1-6]|br)>/i', "\n", $content)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $lines = preg_split('/\R+/', $plainText) ?: [];
    foreach ($lines as $line) {
        $line = trim(preg_replace('/\s+/', ' ', $line));
        if ($line === '' || preg_match('/^(LEGISLATIVE RECORD|BILL CONTENT|={3,}|-{3,})$/i', $line)) {
            continue;
        }
        if (preg_match('/^title\s*:\s*(.+)$/i', $line, $match)) {
            return trim($match[1]);
        }
        if (preg_match('/^(AN|A)\s+(ACT|BILL|ORDINANCE|RESOLUTION)\b/i', $line)) {
            return substr($line, 0, 255);
        }
        if (strlen($line) >= 3 && strlen($line) <= 255 && !preg_match('/^(TYPE|STATUS|REFERENCE|DESCRIPTION|CREATED|UPDATED|SECTION\s+\d+)/i', $line)) {
            return $line;
        }
    }
    return trim($fallback);
}

function escapeXml(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function buildDocxXml(string $content): string
{
    $lines = preg_split('/\r\n|\r|\n/', $content) ?: [''];
    $paragraphs = [];

    foreach ($lines as $line) {
        $paragraphs[] = '<w:p><w:pPr><w:spacing w:after="80"/><w:jc w:val="left"/></w:pPr><w:r><w:t xml:space="preserve">' . escapeXml($line) . '</w:t></w:r></w:p>';
    }

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" mc:Ignorable="w14 wp14">
  <w:body>
    ' . implode("\n", $paragraphs) . '
    <w:sectPr>
      <w:pgSz w:w="12240" w:h="15840"/>
      <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="720" w:footer="720" w:gutter="0"/>
    </w:sectPr>
  </w:body>
</w:document>';
}

function createDocxFromText(string $title, string $content): string
{
    $tempPath = tempnam(sys_get_temp_dir(), 'docx_bill_');
    if ($tempPath === false) {
        throw new RuntimeException('Unable to create temporary document file.');
    }

    $docxPath = $tempPath . '.docx';
    rename($tempPath, $docxPath);

    $zip = new ZipArchive();
    if ($zip->open($docxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Unable to create .docx package.');
    }

    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>');

    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>');

    $zip->addFromString('word/document.xml', buildDocxXml($content));
    $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Microsoft Office Word</Application>
  <DocSecurity>0</DocSecurity>
  <ScaleCrop>false</ScaleCrop>
  <HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Title</vt:lpstr></vt:variant><vt:variant><vt:i4>1</vt:i4></vt:variant></vt:vector></HeadingPairs>
  <TitlesOfParts><vt:vector size="1" baseType="lpstr"><vt:lpstr>' . escapeXml($title) . '</vt:lpstr></vt:vector></TitlesOfParts>
</Properties>');

    $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>' . escapeXml($title) . '</dc:title>
  <dc:creator>Admin Portal</dc:creator>
  <cp:lastModifiedBy>Admin Portal</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">' . date('c') . '</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">' . date('c') . '</dcterms:modified>
</cp:coreProperties>');

    $zip->close();

    return $docxPath;
}

function buildLegislativeExportText(array $bill): string
{
    $lines = [
        'LEGISLATIVE RECORD',
        '==================',
        'Title: ' . ($bill['title'] ?? ''),
        'Type: ' . ($bill['type'] ?? ''),
        'Status: ' . ($bill['status'] ?? ''),
        'Reference: ' . ($bill['ref'] ?? ''),
        'Description: ' . ($bill['desc'] ?? ''),
        'Created by: ' . ($bill['createdByName'] ?? ''),
        'Created at: ' . ($bill['createdAt'] ?? ''),
        'Updated at: ' . ($bill['updatedAt'] ?? ''),
        'Intake classification: ' . ($bill['intake_classification'] ?? ''),
        'Routing status: ' . ($bill['routing_status'] ?? ''),
        'Stream type: ' . ($bill['stream_type'] ?? ''),
        'Assigned committee: ' . ($bill['assigned_committee'] ?? ''),
        'Date referred: ' . ($bill['date_referred'] ?? ''),
        'Committee report status: ' . ($bill['committee_report_status'] ?? ''),
        'Hearing date: ' . ($bill['hearing_date'] ?? ''),
        'Control number: ' . ($bill['control_number'] ?? ''),
        'Workflow status: ' . ($bill['workflow_status'] ?? ''),
        'SHA-256: ' . ($bill['documentHash'] ?? ''),
        'File name: ' . ($bill['file_name'] ?? ''),
        'File type: ' . ($bill['file_type'] ?? ''),
        'File revision hash: ' . ($bill['fileRevisionHash'] ?? ''),
        '',
        'BILL CONTENT',
        '============',
        (string)($bill['content'] ?? ''),
    ];

    return implode("\n", $lines);
}

if (isset($_GET['docx']) && ($_GET['docx'] === '1' || $_GET['docx'] === 'true')) {
    $title = trim((string)($_GET['title'] ?? 'Legislative Bill'));
    $content = trim((string)($_GET['content'] ?? ''));

    $legislativeId = trim((string)($_GET['legislative_id'] ?? ''));
    if ($legislativeId !== '') {
        require_once __DIR__ . '/app/core/Database.php';
        require_once __DIR__ . '/app/models/AdminModel.php';
        $bill = (new App\Models\AdminModel())->findLegislative($legislativeId);
        if ($bill === null) {
            header('HTTP/1.1 404 Not Found');
            echo 'Legislative record not found.';
            exit;
        }

        $title = trim((string)($bill['title'] ?? $title));
        $content = buildLegislativeExportText($bill);
    } elseif ($content !== '') {
        if ($title === '' || strtolower($title) === 'legislative-bill') {
            $title = detectDocxTitle($content, $title);
        }
        $content = buildLegislativeExportText([
            'title' => $title,
            'type' => $_GET['type'] ?? '',
            'status' => $_GET['status'] ?? '',
            'ref' => $_GET['ref'] ?? '',
            'desc' => $_GET['desc'] ?? '',
            'content' => $content,
        ]);
    }

    if ($content === '') {
        $content = 'No content provided for this legislative bill preview.';
    }

    try {
        $docxPath = createDocxFromText($title, $content);
        $fileName = sanitizeDocxFileName($title) . '.docx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($docxPath));
        readfile($docxPath);
        unlink($docxPath);
        exit;
    } catch (Throwable $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo 'Unable to generate the bill document.';
        exit;
    }
}

if (!isset($_GET['file']) || trim($_GET['file']) === '') {
    header('HTTP/1.1 400 Bad Request');
    echo 'Invalid file request.';
    exit;
}

$fileName = basename($_GET['file']);
$filePath = __DIR__ . '/storage/uploads/' . $fileName;

if (!file_exists($filePath) || !is_file($filePath)) {
    header('HTTP/1.1 404 Not Found');
    echo 'File not found.';
    exit;
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$contentType = finfo_file($finfo, $filePath) ?: 'application/octet-stream';
finfo_close($finfo);
$inline = isset($_GET['inline']) && ($_GET['inline'] === '1' || $_GET['inline'] === 'true');

header('Content-Type: ' . $contentType);
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . rawurldecode($fileName) . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
