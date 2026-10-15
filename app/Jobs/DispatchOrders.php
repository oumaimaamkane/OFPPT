<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Order;

class DispatchOrders implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $confirmers = User::whereHas('roles', function ($query) {
            $query->where('name', 'Confirmation');
        })
        ->pluck('id')
        ->toArray();


        if (empty($confirmers)) {
            return;
        }

        // 🔹 2. Get current load per confirmer (🔥 key optimization)
        $loads = Order::select('assign_to', DB::raw('COUNT(*) as total'))
            ->whereIn('assign_to', $confirmers)
            ->groupBy('assign_to')
            ->pluck('total', 'assign_to')
            ->toArray();

        // initialize missing confirmers with 0
        foreach ($confirmers as $id) {
            if (!isset($loads[$id])) {
                $loads[$id] = 0;
            }
        }
         // 🔹 3. Sort confirmers by الأقل تحميل
        asort($loads); // ascending
         // 🔹 4. Process orders in chunks
        Order::whereNull('assign_to')
            ->orderBy('id')
            ->chunk(1000, function ($orders) use (&$loads) {

                $confirmerIds = array_keys($loads);
                $countConfirmers = count($confirmerIds);

                $updates = [];
                $i = 0;

                foreach ($orders as $order) {
                    // 🔥 round-robin based on sorted loads
                    // $confirmerId = $confirmerIds[$i % $countConfirmers];
                        $confirmerId = array_key_first($loads);


                   $updates[] = [
                        'id' => (int) $order->id,
                        'assign_to' => (int) $confirmerId,
                    ];

                    $loads[$confirmerId]++;
    asort($loads);

                    // update load in memory
                    // $loads[$confirmerId]++;
                    // $i++;
                }

                // 🔹 re-sort after batch (important for fairness)
                asort($loads);

                // 🔹 bulk update (no save in loop)
                $this->bulkUpdate($updates);
            });

        
    }

    private function bulkUpdate(array $rows)
    {
        if (empty($rows)) return;

        $cases = [];
        $ids = [];

        foreach ($rows as $row) {
            $ids[] = $row['id'];
            $cases[] = "WHEN {$row['id']} THEN {$row['assign_to']}";
        }

        $ids = implode(',', $ids);
        $cases = implode(' ', $cases);

        DB::statement("
            UPDATE orders
            SET assign_to = CASE id
                $cases
            END
            WHERE id IN ($ids)
        ");
    }
}
