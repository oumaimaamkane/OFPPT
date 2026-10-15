<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class DetectDuplicateOrdersService
{
    public function handle()
    {
        DB::transaction(function () {

            $seenIds = [];
            $seenPhones = [];

            // ⚠️ IMPORTANT: order matters (same as sheet order)
            $orders = Order::orderBy('id')->get();

            foreach ($orders as $order) {

                $id = $order->order_id ? trim((string) $order->order_id) : '';
                $phone = $order->phone ? trim((string) $order->phone) : '';

                $note = '';

                // 🔹 Check duplicate ID
                if ($id !== '') {
                    if (isset($seenIds[$id])) {
                        $note .= "📌 مكرر بالمعرف";
                    } else {
                        $seenIds[$id] = true;
                    }
                }

                // 🔹 Check duplicate phone
                if ($phone !== '') {
                    if (isset($seenPhones[$phone])) {
                        if ($note !== '') {
                            $note .= " + ";
                        }
                        $note .= "📌 مكرر برقم الهاتف";
                    } else {
                        $seenPhones[$phone] = true;
                    }
                }

                // 🔹 Save note
                $order->note = $note;
                $order->save();
            }
        });
    }

    public function detectForOrder(Order $order): string
    {
        $note = '';

        // 🔹 Duplicate by order_id
        if (!empty($order->order_id)) {
            $exists = Order::where('order_id', $order->order_id)
                ->where('id', '!=', $order->id)
                ->exists();

            if ($exists) {
                $note .= "📌 مكرر بالمعرف";
            }
        }

        // 🔹 Normalize phone
        $phone = $this->normalizePhone($order->phone);

        if (!empty($phone)) {
            $exists = Order::where('phone', $phone)
                ->where('id', '!=', $order->id)
                ->exists();

            if ($exists) {
                if ($note !== '') {
                    $note .= " + ";
                }
                $note .= "📌 مكرر برقم الهاتف";
            }
        }

        return $note;
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (!$phone) return null;

        // remove spaces, +, etc
        return preg_replace('/\D/', '', $phone);
    }
}