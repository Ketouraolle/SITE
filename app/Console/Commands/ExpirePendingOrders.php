<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpirePendingOrders extends Command
{
    protected $signature = 'shop:expire-pending-orders';

    protected $description = 'Annule les commandes nouvelles dont le delai de reservation est depasse';

    public function handle(OrderService $orders): int
    {
        $hours = (int) config('shop.pending_order_expiration_hours');

        if ($hours < 1) {
            $this->components->error('Le delai doit etre au moins egal a une heure.');

            return self::FAILURE;
        }

        $cutoff = now()->subHours($hours);
        $cancelled = 0;

        Order::query()
            ->where('status', OrderStatus::Pending->value)
            ->where('created_at', '<=', $cutoff)
            ->select('id')
            ->chunkById(100, function ($candidates) use ($orders, $cutoff, &$cancelled): void {
                foreach ($candidates as $candidate) {
                    $didCancel = DB::transaction(function () use ($candidate, $orders, $cutoff): bool {
                        $order = Order::whereKey($candidate->id)
                            ->lockForUpdate()
                            ->first();

                        if (
                            ! $order
                            || $order->status !== OrderStatus::Pending
                            || $order->created_at->greaterThan($cutoff)
                        ) {
                            return false;
                        }

                        $orders->changeStatus($order, OrderStatus::Cancelled);

                        return true;
                    });

                    if ($didCancel) {
                        $cancelled++;
                    }
                }
            });

        $this->components->info("{$cancelled} commande(s) annulee(s).");

        return self::SUCCESS;
    }
}
