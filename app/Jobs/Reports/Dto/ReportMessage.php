<?php

namespace App\Jobs\Reports\Dto;

interface ReportMessage
{
    public function getType(): string;

    public function getModelType(): string;

    public function getModelId(): int;

    /**
     * @return array<string, mixed>
     */
    public function getFilters(): array;

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array;

    /**
     * @return array{
     *     type:string,
     *     model_type:string,
     *     model_id:int,
     *     filters:array<string,mixed>,
     *     options:array<string,mixed>
     * }
     */
    public function toArray(): array;
}
