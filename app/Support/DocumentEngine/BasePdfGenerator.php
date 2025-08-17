<?php

namespace App\Support\DocumentEngine;

abstract class BasePdfGenerator
{
    // Template method (final so subclasses don't override the flow)
    final public function generate(array $context): void
    {
        $this->beforeGenerate();
        $data = $this->prepareData($context);
        $html = $this->renderTemplate($data);
        $this->exportPdf($html);
        $this->afterGenerate();
    }

    // Steps that subclasses implement
    abstract protected function prepareData(array $context): array;

    abstract protected function getTemplatePath(): string;

    public function afterGenerate(): void {}

    public function beforeGenerate(): void {}

    // Shared implementation for rendering HTML
    protected function renderTemplate(array $data): string
    {
        return view($this->getTemplatePath(), $data)->render();
    }

    // Shared PDF export logic (MPDF, DomPDF, Browserless, etc.)
    protected function exportPdf(string $html): void {}
}
