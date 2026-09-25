<?php

namespace App\Http\Requests;

use App\Models\MaintenanceReport;
use App\Models\StockTakeItem;
use App\Services\InventoryReportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(InventoryReportService::TYPES))],
            'format' => ['required', Rule::in(['xlsx', 'print'])],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'condition' => ['nullable', Rule::in(array_keys(MaintenanceReport::CONDITIONS))],
            'status' => ['nullable', Rule::in(array_keys(InventoryReportService::STATUSES))],
            'stock_take_id' => ['required_if:type,stock_take', 'nullable', 'integer', 'exists:stock_takes,id'],
            'result' => ['nullable', Rule::in(array_keys(StockTakeItem::RESULTS))],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', Rule::when($this->filled('date_from'), 'after_or_equal:date_from')],
        ];
    }
}
