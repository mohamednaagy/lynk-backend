<?php

namespace App\Support\DocumentEngine;

use App\Support\PdfGenerator\PdfGenerator;

abstract class BasePdfGenerator
{
    protected $context = [];

    // Template method (final so subclasses don't override the flow)
    final public function generate()
    {
        if (! $this->isGeneratedBefore()) {
            $this->beforeGenerate();
            $data = $this->prepareData();
            $html = $this->renderTemplate($data);
            $this->exportPdf($html);
            $this->afterGenerate();
        }
    }

    // Steps that subclasses implement
    abstract protected function prepareData();

    abstract protected function getTemplatePath(): string;

    protected function afterGenerate(): void {}

    protected function beforeGenerate(): void {}

    abstract protected function getStorageCallback(): callable;

    abstract protected function isGeneratedBefore(): bool;

    abstract protected function getGeneratedBeforePath(): string;

    /**
     * Public accessor to check if PDF was generated before
     */
    public function checkIfGeneratedBefore(): bool
    {
        return $this->isGeneratedBefore();
    }

    /**
     * Public accessor to get the path of previously generated PDF
     */
    public function getExistingPdfPath(): string
    {
        return $this->getGeneratedBeforePath();
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    // Shared implementation for rendering HTML
    protected function renderTemplate(array $data): string
    {
        return view($this->getTemplatePath(), $data)->render();
    }

    protected function exportPdf(string $html): void
    {
        PdfGenerator::outputFromHtml(
            $html,
            $this->getStorageCallback()
        );
    }

    public function getDisk(): string
    {
        return 'local';
    }
}
