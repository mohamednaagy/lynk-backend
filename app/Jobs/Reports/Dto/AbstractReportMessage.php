<?php

namespace App\Jobs\Reports\Dto;

abstract class AbstractReportMessage implements ReportMessage
{
    public function __construct(
        protected readonly string $type,
        protected readonly string $modelType,
        protected readonly int $modelId,
        protected readonly array $filters = [],
        protected readonly array $options = [],
    ) {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getModelType(): string
    {
        return $this->modelType;
    }

    public function getModelId(): int
    {
        return $this->modelId;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'model_type' => $this->getModelType(),
            'model_id' => $this->getModelId(),
            'filters' => $this->getFilters(),
            'options' => $this->getOptions(),
        ];
    }
}
