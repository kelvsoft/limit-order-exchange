<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    orders: any[]; // All open orders from the API
}>();

// 1. Filter and Sort Bids (Highest Buy Price first)
const bids = computed(() => {
    return props.orders
        .filter(o => o.side === 'buy' && o.status === 1)
        .sort((a, b) => b.price - a.price)
        .slice(0, 10);
});

// 2. Filter and Sort Asks (Lowest Sell Price first)
const asks = computed(() => {
    return props.orders
        .filter(o => o.side === 'sell' && o.status === 1)
        .sort((a, b) => a.price - b.price)
        .slice(0, 10);
});

// Calculate the Spread (Difference between highest buy and lowest sell)
const spread = computed(() => {
    if (bids.value.length && asks.value.length) {
        return Math.abs(asks.value[0].price - bids.value[0].price);
    }
    return 0;
});
</script>

<template>
    <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden shadow-xl text-white">
        <div class="p-4 border-b border-gray-800 bg-gray-950/50 flex justify-between items-center">
            <h2 class="font-bold text-sm uppercase tracking-widest text-gray-400">Order Book</h2>
            <span class="text-xs font-mono text-gray-500">Spread: ${{ spread.toLocaleString() }}</span>
        </div>

        <div class="grid grid-cols-2 divide-x divide-gray-800">
            <div class="p-4">
                <div class="flex justify-between text-[10px] font-bold text-gray-500 uppercase mb-3">
                    <span>Price</span>
                    <span>Amount</span>
                </div>
                <div class="space-y-1">
                    <div v-for="order in asks" :key="order.id" class="flex justify-between text-xs font-mono group cursor-default">
                        <span class="text-red-400 group-hover:text-red-300 transition-colors">${{ order.price }}</span>
                        <span class="text-gray-400">{{ order.amount }}</span>
                    </div>
                    <div v-if="!asks.length" class="text-center py-4 text-gray-600 text-xs italic">No sell orders</div>
                </div>
            </div>

            <div class="p-4">
                <div class="flex justify-between text-[10px] font-bold text-gray-500 uppercase mb-3">
                    <span>Amount</span>
                    <span>Price</span>
                </div>
                <div class="space-y-1">
                    <div v-for="order in bids" :key="order.id" class="flex justify-between text-xs font-mono group cursor-default">
                        <span class="text-gray-400">{{ order.amount }}</span>
                        <span class="text-green-400 group-hover:text-green-300 transition-colors">${{ order.price }}</span>
                    </div>
                    <div v-if="!bids.length" class="text-center py-4 text-gray-600 text-xs italic">No buy orders</div>
                </div>
            </div>
        </div>

        <div class="bg-gray-950 p-2 text-[10px] text-center text-gray-600 border-t border-gray-800">
            Real-time updates enabled via Pusher
        </div>
    </div>
</template>