<?php

namespace App\Services;

class SimplePdfReport
{
    /** @param list<string> $lines */
    public function render(string $title, array $lines): string
    {
        $pages = array_chunk($lines, 46);
        $pages = $pages === [] ? [[]] : $pages;
        $objects = [];
        $pageIds = [];
        $nextId = 3;

        foreach ($pages as $pageNumber => $pageLines) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $pageIds[] = $pageId;
            $stream = $this->pageStream($title, $pageLines, $pageNumber + 1, count($pages));
            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 1 0 R >> >> /Contents {$contentId} 0 R >>";
            $objects[$contentId] = '<< /Length '.strlen($stream).">>\nstream\n{$stream}\nendstream";
        }

        $objects[1] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', array_map(fn (int $id): string => "{$id} 0 R", $pageIds)).'] /Count '.count($pageIds).' >>';
        $catalogId = $nextId;
        $objects[$catalogId] = '<< /Type /Catalog /Pages 2 0 R >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".($catalogId + 1)."\n0000000000 65535 f \n";
        for ($id = 1; $id <= $catalogId; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        return $pdf."trailer\n<< /Size ".($catalogId + 1)." /Root {$catalogId} 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    /** @param list<string> $lines */
    private function pageStream(string $title, array $lines, int $page, int $totalPages): string
    {
        $commands = ['BT', '/F1 16 Tf', '50 750 Td', '('.$this->escape($title).') Tj', '/F1 9 Tf', '0 -24 Td'];
        foreach ($lines as $line) {
            $commands[] = '('.$this->escape($line).') Tj';
            $commands[] = '0 -15 Td';
        }
        $commands[] = 'ET';
        $commands[] = 'BT /F1 8 Tf 50 28 Td (Page '.$page.' of '.$totalPages.') Tj ET';

        return implode("\n", $commands);
    }

    private function escape(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $value);
    }
}
