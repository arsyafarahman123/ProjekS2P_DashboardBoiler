<?php

namespace App\Support;

use Illuminate\Http\Response;
use RuntimeException;
use ZipArchive;

/**
 * Writer XLSX super minimal, TANPA dependency composer (maatwebsite/excel /
 * phpoffice/phpspreadsheet). Alasannya: sampai sekarang belum ada rilis
 * stable phpoffice/phpspreadsheet yang support PHP 8.5 (lihat
 * https://github.com/PHPOffice/PhpSpreadsheet/issues/4890), dan project ini
 * jalan di PHP 8.5.7 — jadi daripada nunggu package-nya rilis, kita bikin
 * writer sendiri yang cukup buat kebutuhan export tabel sederhana (bukan
 * chart/formula/formatting kompleks).
 *
 * Format XLSX itu sendiri cuma ZIP berisi file XML (OOXML SpreadsheetML),
 * jadi bisa dibangun manual pakai ext-zip (ekstensi bawaan PHP, biasanya
 * sudah aktif di Laragon/XAMPP).
 *
 * Cara pakai:
 *   $writer = new SimpleXlsxWriter();
 *   $writer->addSheet('Sheet1', [
 *       ['Judul Sheet'],                 // baris 1 (akan di-bold kalau $boldRows >= 1)
 *       ['Kolom A', 'Kolom B'],          // baris 2 (akan di-bold kalau $boldRows >= 2)
 *       ['data1', 123],                   // baris data biasa
 *   ], boldRows: 2);
 *   return $writer->download('laporan.xlsx');
 */
class SimpleXlsxWriter
{
    /** @var array<int, array{title: string, rows: array, boldRows: int}> */
    protected array $sheets = [];

    /**
     * Tambah satu sheet. $rows = array of array (tiap baris = array nilai
     * sel, boleh string/int/float/null). $boldRows = berapa baris pertama
     * yang di-bold (biasanya 1-2 baris judul/header).
     */
    public function addSheet(string $title, array $rows, int $boldRows = 0): static
    {
        // Nama sheet Excel maksimal 31 karakter & nggak boleh ada karakter
        // \ / ? * [ ] :
        $safeTitle = substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '-', $title), 0, 31);

        $this->sheets[] = [
            'title'    => $safeTitle,
            'rows'     => $rows,
            'boldRows' => $boldRows,
        ];

        return $this;
    }

    /**
     * Build file XLSX dan return sebagai Laravel Response (download).
     */
    public function download(string $filename): Response
    {
        $binary = $this->build();

        return response($binary, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length'      => strlen($binary),
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Build isi file .xlsx (binary string).
     */
    protected function build(): string
    {
        if (empty($this->sheets)) {
            $this->addSheet('Sheet1', [['(kosong)']]);
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'xlsx_');
        if ($tmpPath === false) {
            throw new RuntimeException('Gagal membuat file sementara untuk export Excel.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tmpPath, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuka ZipArchive untuk export Excel.');
        }

        $zip->addEmptyDir('_rels');
        $zip->addEmptyDir('xl');
        $zip->addEmptyDir('xl/_rels');
        $zip->addEmptyDir('xl/worksheets');

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());

        foreach ($this->sheets as $i => $sheet) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $this->sheetXml($sheet));
        }

        $zip->close();

        $binary = file_get_contents($tmpPath);
        @unlink($tmpPath);

        if ($binary === false) {
            throw new RuntimeException('Gagal membaca hasil export Excel.');
        }

        return $binary;
    }

    protected function contentTypesXml(): string
    {
        $overrides = '';
        foreach ($this->sheets as $i => $sheet) {
            $n = $i + 1;
            $overrides .= "<Override PartName=\"/xl/worksheets/sheet{$n}.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml\"/>";
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $overrides
            . '</Types>';
    }

    protected function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    protected function workbookXml(): string
    {
        $sheetTags = '';
        foreach ($this->sheets as $i => $sheet) {
            $n = $i + 1;
            $sheetTags .= '<sheet name="' . $this->escape($sheet['title']) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheetTags . '</sheets>'
            . '</workbook>';
    }

    protected function workbookRelsXml(): string
    {
        $rels = '';
        foreach ($this->sheets as $i => $sheet) {
            $n = $i + 1;
            $rels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
        }
        $stylesRid = count($this->sheets) + 1;
        $rels .= '<Relationship Id="rId' . $stylesRid . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels
            . '</Relationships>';
    }

    protected function stylesXml(): string
    {
        // xf index 0 = normal, xf index 1 = bold (dipakai buat baris judul/header)
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';
    }

    protected function sheetXml(array $sheet): string
    {
        $rowsXml = '';
        foreach ($sheet['rows'] as $r => $row) {
            $rowNum = $r + 1;
            $isBold = $rowNum <= $sheet['boldRows'];
            $cellsXml = '';

            foreach (array_values($row) as $c => $value) {
                $cellRef = $this->columnLetter($c) . $rowNum;
                $styleAttr = $isBold ? ' s="1"' : '';

                if ($value === null || $value === '') {
                    // sel kosong, skip (nggak wajib ditulis)
                    continue;
                } elseif (is_numeric($value)) {
                    $cellsXml .= '<c r="' . $cellRef . '"' . $styleAttr . '><v>' . $this->escape((string) $value) . '</v></c>';
                } else {
                    $cellsXml .= '<c r="' . $cellRef . '" t="inlineStr"' . $styleAttr . '><is><t xml:space="preserve">' . $this->escape((string) $value) . '</t></is></c>';
                }
            }

            $rowsXml .= '<row r="' . $rowNum . '">' . $cellsXml . '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . $rowsXml . '</sheetData>'
            . '</worksheet>';
    }

    /**
     * Konversi index kolom (0-based) ke huruf kolom Excel (0=A, 1=B, ..., 25=Z, 26=AA, ...).
     */
    protected function columnLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = intdiv($index - $mod, 26);
        }

        return $letter;
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
