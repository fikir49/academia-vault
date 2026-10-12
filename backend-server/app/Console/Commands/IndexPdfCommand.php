<?php

namespace App\Console\Commands;

use App\Services\PdfIndexerService;
use Illuminate\Console\Command;

class IndexPdfCommand extends Command
{
    protected $signature = 'vault:index-pdf {file : The path to the PDF file} {--doc-id= : Custom Document ID} {--dept=Information Systems : Target Department} {--clearance=1 : ABAC Clearance Level}';

    protected $description = 'Automatically extract text, calculate TF scores, and populate SQLite FTS5 index from a PDF document';

    public function handle(PdfIndexerService $indexer)
    {
        $filePath = $this->argument('file');
        $docId = $this->option('doc-id') ?? basename($filePath);
        $dept = $this->option('dept');
        $clearance = (int) $this->option('clearance');

        $this->info("Parsing and indexing document: {$docId}...");

        try {
            $result = $indexer->indexPdf($filePath, $docId, $dept, $clearance);
            $this->green("Successfully indexed {$result['indexed_terms_count']} technical terms into SQLite FTS5!");
        } catch (\Exception $e) {
            $this->error("Indexing failed: " . $e->getMessage());
        }
    }

    private function green($string)
    {
        $this->line("<info>{$string}</info>");
    }
}