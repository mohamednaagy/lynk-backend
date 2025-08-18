<?php

namespace App\Support\DocumentEngine;

use App\Support\PdfGenerator\PdfGenerator;

abstract class BasePdfGenerator
{
    protected $context = [];

    // Template method (final so subclasses don't override the flow)
    final public function generate()
    {
        if ($this->isGeneratedBefore()) {
            return $this->getGeneratedBeforePath();
        }

        $this->beforeGenerate();
        $data = $this->prepareData();
        $html = $this->renderTemplate($data);
        $this->exportPdf($html);
        $this->afterGenerate();
    }

    // Steps that subclasses implement
    abstract protected function prepareData();

    abstract protected function getTemplatePath(): string;

    public function afterGenerate(): void {}

    public function beforeGenerate(): void {}

    abstract protected function getStorageCallback(): callable;

    abstract protected function isGeneratedBefore(): bool;

    abstract protected function getGeneratedBeforePath(): string;

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    // Shared implementation for rendering HTML
    protected function renderTemplate(array $data): string
    {
        return view($this->getTemplatePath(), $data)->render();
    }

    // Shared PDF export logic (MPDF, DomPDF, Browserless, etc.)
    protected function exportPdf(string $html): mixed
    {
        return PdfGenerator::outputFromHtml(
            $html,
            $this->getStorageCallback()
        );
    }
}
