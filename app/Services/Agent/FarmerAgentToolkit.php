<?php

namespace App\Services\Agent;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Helpers\MoneyFormatter;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderStatusUpdater;
use App\Services\ProductStockUpdater;

// Farmer AI ke tools - order status OrderStatusUpdater se aur stock ProductStockUpdater se badalta hai,
// taake audit trail aur restock alerts button wale tareeqe jaise hi chalen.
// Naya field AI tak khud nahi pohanchta, neeche wale result array mein add karna parta hai.
class FarmerAgentToolkit
{
    public function __construct(
        private readonly OrderStatusUpdater $orderStatusUpdater,
        private readonly ProductStockUpdater $stockUpdater,
    ) {
    }

    public function schemas(User $actor): array
    {
        return [
            $this->schema('get_my_products', 'List the farmer\'s own products, optionally filtered by availability.', [
                'status' => ['type' => 'string', 'description' => 'One of: available, sold_out, temporarily_unavailable'],
            ], []),

            $this->schema('update_stock', 'Update how many units of a product the farmer has left.', [
                'product_name' => ['type' => 'string', 'description' => 'Name of the product to update'],
                'new_quantity' => ['type' => 'integer', 'description' => 'The new stock quantity'],
            ], ['product_name', 'new_quantity']),

            $this->schema('mark_sold_out', 'Mark one of the farmer\'s products as sold out.', [
                'product_name' => ['type' => 'string', 'description' => 'Name of the product to mark sold out'],
            ], ['product_name']),

            $this->schema('get_pending_orders', 'List orders waiting for the farmer to accept or decline, with their items.', [], []),

            $this->schema('get_order_details', 'Get the full details of one of the farmer\'s own orders - items, customer, pickup and status.', [
                'order_number' => ['type' => 'string', 'description' => 'The order number, e.g. ML-1234'],
            ], ['order_number']),

            $this->schema('update_order_status', 'Move one of the farmer\'s orders to a new status. Declining needs a reason for the customer - ask the farmer for one if they haven\'t given it.', [
                'order_id' => ['type' => 'integer', 'description' => 'The id of the order to update'],
                'new_status' => ['type' => 'string', 'description' => 'One of: accept, decline, ready, complete'],
                'reason' => ['type' => 'string', 'description' => 'Why the order is being declined - required for decline, shown to the customer'],
            ], ['order_id', 'new_status']),

            $this->schema('get_sales_insights', 'Summarise the farmer\'s recent sales.', [
                'period' => ['type' => 'string', 'description' => 'One of: this_week, this_month'],
            ], []),
        ];
    }

    public function execute(User $actor, string $toolName, array $arguments): array
    {
        $farmer = $actor->farmerProfile;

        if (! $farmer) {
            return ['error' => 'This account has no farmer stall set up yet.'];
        }

        return match ($toolName) {
            'get_my_products' => $this->getMyProducts($farmer, $arguments),
            'update_stock' => $this->updateStock($farmer, $arguments),
            'mark_sold_out' => $this->markSoldOut($farmer, $arguments),
            'get_pending_orders' => $this->getPendingOrders($farmer),
            'get_order_details' => $this->getOrderDetails($farmer, $arguments),
            'update_order_status' => $this->updateOrderStatus($actor, $farmer, $arguments),
            'get_sales_insights' => $this->getSalesInsights($farmer, $arguments),
            default => ['error' => "Unknown tool: {$toolName}"],
        };
    }

    private function getMyProducts($farmer, array $arguments): array
    {
        $products = $farmer->products()
            ->when($arguments['status'] ?? null, fn ($query, $status) => $query->where('availability', $status))
            ->get();

        return [
            'count' => $products->count(),
            'products' => $products->map(fn (Product $product) => [
                'name' => $product->name,
                'price' => (float) $product->price,
                'price_text' => MoneyFormatter::format($product->price),
                'stock_quantity' => $product->stock_quantity,
                'availability' => $product->availability->label(),
            ])->all(),
        ];
    }

    private function updateStock($farmer, array $arguments): array
    {
        $product = AgentNameMatcher::findOne($farmer->products()->getQuery(), 'name', $arguments['product_name'], 'product on this stall');

        if (is_array($product)) {
            return $product;
        }

        $this->stockUpdater->update($product, ['stock_quantity' => max(0, (int) $arguments['new_quantity'])]);

        return ['success' => true, 'product_name' => $product->name, 'stock_quantity' => $product->stock_quantity];
    }

    private function markSoldOut($farmer, array $arguments): array
    {
        $product = AgentNameMatcher::findOne($farmer->products()->getQuery(), 'name', $arguments['product_name'], 'product on this stall');

        if (is_array($product)) {
            return $product;
        }

        $this->stockUpdater->markSoldOut($product);

        return ['success' => true, 'product_name' => $product->name];
    }

    private function getPendingOrders($farmer): array
    {
        $orders = $farmer->orders()
            ->where('status', OrderStatus::Placed->value)
            ->with(['customer', 'items'])
            ->orderBy('pickup_date')
            ->take(10)
            ->get();

        return [
            'count' => $orders->count(),
            'orders' => $orders->map(fn (Order $order) => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer->name,
                'pickup_date' => $order->pickup_date->toFormattedDateString(),
                'items' => $this->orderItemsForAgent($order),
                'total_amount' => (float) $order->total_amount,
                'total_text' => MoneyFormatter::format($order->total_amount),
            ])->all(),
        ];
    }

    private function getOrderDetails($farmer, array $arguments): array
    {
        $order = $farmer->orders()
            ->where('order_number', $arguments['order_number'] ?? '')
            ->with(['customer', 'items'])
            ->first();

        if (! $order) {
            return ['error' => "That order doesn't belong to this stall."];
        }

        return [
            'order_number' => $order->order_number,
            'status' => $order->status->label(),
            'customer_name' => $order->customer->name,
            'items' => $this->orderItemsForAgent($order),
            'total_text' => MoneyFormatter::format($order->total_amount),
            'pickup_summary' => $order->pickupSummary(),
            'customer_note' => $order->customer_note,
            'is_urgent' => $order->is_urgent,
        ];
    }

    private function orderItemsForAgent(Order $order): array
    {
        return $order->items->map(fn ($item) => [
            'product_name' => $item->product_name,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'line_total_text' => MoneyFormatter::format($item->line_total),
        ])->all();
    }

    private function updateOrderStatus(User $actor, $farmer, array $arguments): array
    {
        $order = $farmer->orders()->find($arguments['order_id']);

        if (! $order) {
            return ['error' => "That order doesn't belong to this stall."];
        }

        $newStatus = match ($arguments['new_status'] ?? null) {
            'accept' => OrderStatus::Accepted,
            'decline' => OrderStatus::Declined,
            'ready' => OrderStatus::ReadyForPickup,
            'complete' => OrderStatus::Completed,
            default => null,
        };

        if (! $newStatus) {
            return ['error' => 'new_status must be one of: accept, decline, ready, complete.'];
        }

        // decline form jaisa rule - customer ko reason dikhta hai
        $declineReason = trim((string) ($arguments['reason'] ?? ''));

        if ($newStatus === OrderStatus::Declined && $declineReason === '') {
            return ['error' => 'A reason is required to decline an order - the customer sees it. Ask the farmer why.'];
        }

        try {
            $this->orderStatusUpdater->transition($order, $newStatus, $actor, $newStatus === OrderStatus::Declined ? $declineReason : null);
        } catch (InvalidOrderTransitionException $exception) {
            return ['error' => $exception->getMessage()];
        }

        return ['success' => true, 'order_number' => $order->order_number, 'new_status' => $newStatus->label()];
    }

    private function getSalesInsights($farmer, array $arguments): array
    {
        $periodStart = match ($arguments['period'] ?? 'this_week') {
            'this_month' => now()->startOfMonth(),
            default => now()->startOfWeek(),
        };

        $revenue = (float) $farmer->orders()
            ->completed()
            ->where('completed_at', '>=', $periodStart)
            ->sum('total_amount');

        $ordersCompleted = $farmer->orders()->completed()->where('completed_at', '>=', $periodStart)->count();

        return [
            'period' => $arguments['period'] ?? 'this_week',
            'revenue' => $revenue,
            'revenue_text' => MoneyFormatter::format($revenue),
            'orders_completed' => $ordersCompleted,
            'best_sellers' => $farmer->bestSellingProducts(5)->map(fn ($row) => [
                'product_name' => $row->product_name,
                'quantity_sold' => (int) $row->total_quantity_sold,
            ])->all(),
        ];
    }

    private function schema(string $name, string $description, array $properties, array $required): array
    {
        return [
            'permission' => null,
            'schema' => [
                'type' => 'function',
                'function' => [
                    'name' => $name,
                    'description' => $description,
                    'parameters' => [
                        'type' => 'object',
                        // empty array json mein [] banta hai {} nahi, Groq ye reject karta hai
                        'properties' => $this->allowNullOnOptionalProperties($properties, $required) ?: (object) [],
                        'required' => $required,
                    ],
                ],
            ],
        ];
    }

    // model skip kiye hue param pe null bhej deta hai aur Groq strict schema pe fail kar deta hai,
    // is liye har optional param ka type null bhi allow karta hai
    private function allowNullOnOptionalProperties(array $properties, array $required): array
    {
        foreach ($properties as $propertyName => &$propertySchema) {
            if (! in_array($propertyName, $required, true) && isset($propertySchema['type'])) {
                $propertySchema['type'] = [$propertySchema['type'], 'null'];
            }
        }

        return $properties;
    }
}
