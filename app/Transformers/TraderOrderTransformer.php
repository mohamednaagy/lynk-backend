<?php

namespace App\Transformers;

use App\Enums\BursamProductCode;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\DataTransferObjects\CommodityProductDto;
use App\Support\FinancingOrders\TraderOrderHelper;
use Illuminate\Support\Collection as LCollection;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\NullResource;
use League\Fractal\Resource\Primitive;
use League\Fractal\TransformerAbstract;

class TraderOrderTransformer extends TransformerAbstract
{
    use TraderOrderHelper;

    protected $area = null;

    private ?LCollection $traderOrders = null;

    protected array $defaultIncludes = [];

    protected array $availableIncludes = [
        'id',
        'financing_order_id',
        'reference',
        'provider',
        'version',
        'failure_reason',
        'refunded_at',
        'refund_reason',
        'purchasing_commodity_information',
        'products',
        'status',
        'is_cancellable',
        'history',
        'created_at',
    ];

    public function transform(TraderOrder $traderOrder)
    {
        return [];
    }

    public function includeId(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->id);
    }

    public function includeFinancingOrderId(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->financing_order_id);
    }

    public function includeReference(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->reference ?: $traderOrder->id);
    }

    public function includeProvider(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->provider);
    }

    public function includeVersion(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->version);
    }

    public function includeFailureReason(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->failure_reason);
    }

    public function includeRefundedAt(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->refunded_at);
    }

    public function includeRefundReason(TraderOrder $traderOrder): Primitive|NullResource
    {
        $baseTraderOrder = $this->findBaseTraderOrder($traderOrder) ?? $traderOrder;

        if ($this->shouldSkipRefundReason($traderOrder)) {
            return $this->null();
        }

        $refundReason = $this->getRefundReason($traderOrder, $baseTraderOrder);

        return $this->primitive($this->formatRefundReason($refundReason, $baseTraderOrder));
    }

    public function includeIsCancellable(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->isCancellable($this->area));
    }

    public function includeHistory(TraderOrder $traderOrder)
    {
        $traderMurabhaSteps = collect(get_murabha_steps($traderOrder->provider, $traderOrder->version))
            ->except([
                MurabhaStep::TraderOrderCreated,
                MurabhaStep::TransferOwnershipToLender,
            ])
            ->keys()
            ->flatten()
            ->toArray();

        $historiesActions = $traderOrder->traderHistories()->pluck('action')->toArray();

        return $this->collection([$historiesActions], new TraderHistoryTransformer($traderOrder, $traderMurabhaSteps));
    }

    public function includeProducts(TraderOrder $traderOrder): Collection
    {
        $products = collect($traderOrder->products)->map(
            fn ($product) => CommodityProductDto::fromArray($product)
        );

        return $this->collection($products, new ProductTransformer());
    }

    public function includeStatus(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive([
            'description' => TraderOrderStatus::fromValue($traderOrder->status)->description,
            'value' => TraderOrderStatus::fromValue($traderOrder->status)->value,
        ]);
    }

    public function includePurchasingCommodityInformation(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive([
            'products' => collect($traderOrder->products)->map(function ($product) {
                $productDescription = in_array($product['product'], BursamProductCode::getValues())
                    ? BursamProductCode::fromValue($product['product'])->description
                    : $product['product'];

                $product['product_description'] = $productDescription;

                return $product;
            }),
            'ptp_document' => $traderOrder->getFirstMedia(TraderOrderMediaCollection::PromiseToPurchase)?->file_url,
            'exchange_rate' => $traderOrder->exchange_rate,
            'original_holding_certificate' => $traderOrder->getFirstMedia(TraderOrderMediaCollection::TtiHoldingCertificate)?->file_url,
            'auto_generate_financing_institution_certificate' => $traderOrder->auto_generate_financing_institution_certificate,
            'financing_institution_certificate' => $traderOrder->getFirstMedia(TraderOrderMediaCollection::TransferOwnershipToLender)?->file_url,
        ]);
    }

    public function includeCreatedAt(TraderOrder $traderOrder): Primitive
    {
        return $this->primitive($traderOrder->created_at?->clone()->tz('Asia/Riyadh')->toDateTimeString());
    }

    public function setArea($area): static
    {
        $this->area = $area;

        return $this;
    }

    public function setTraderOrders(LCollection $traderOrders): static
    {
        $this->traderOrders = $traderOrders;

        return $this;
    }
}
