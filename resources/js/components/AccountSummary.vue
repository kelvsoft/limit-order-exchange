<script setup lang="ts">
const props = defineProps<{
    usdBalance: number;
    assets: any[];
    orders: any[];
}>();

const emit = defineEmits(['cancelOrder']);

const getStatusClass = (status: number) => {
    switch(status) {
        case 1: return 'text-yellow-500 bg-yellow-500/10'; // Open
        case 2: return 'text-green-500 bg-green-500/10';   // Filled
        case 3: return 'text-gray-500 bg-gray-500/10';    // Cancelled
        default: return 'text-white';
    }
};

const getStatusLabel = (status: number) => {
    return ['Unknown', 'Open', 'Filled', 'Cancelled'][status];
};
</script>

<template>
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-900 border border-gray-800 p-6 rounded-2xl shadow-xl">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Available USD</p>
                <h3 class="text-3xl font-black text-white font-mono">${{ Number(usdBalance).toLocaleString() }}</h3>
            </div>
            
            <div v-for="asset in assets" :key="asset.symbol" class="bg-gray-900 border border-gray-800 p-6 rounded-2xl shadow-xl">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">{{ asset.symbol }} Balance</p>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-3xl font-black text-white font-mono">{{ asset.amount }}</h3>
                    <span class="text-xs text-gray-500 font-bold">Locked: {{ asset.locked_amount }}</span>
                </div>
            </div>
        </div>

        <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="p-4 border-b border-gray-800 bg-gray-950/50">
                <h2 class="font-bold text-sm uppercase tracking-widest text-gray-400">My Recent Orders</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] uppercase text-gray-500 bg-gray-950/30">
                            <th class="p-4">Type</th>
                            <th class="p-4">Price</th>
                            <th class="p-4">Amount</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs font-mono divide-y divide-gray-800">
                        <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-800/30 transition-colors">
                            <td class="p-4">
                                <span :class="order.side === 'buy' ? 'text-green-500' : 'text-red-500'" class="font-bold uppercase">
                                    {{ order.side }}
                                </span>
                            </td>
                            <td class="p-4 text-gray-300">${{ Number(order.price).toLocaleString() }}</td>
                            <td class="p-4 text-gray-300">{{ order.amount }}</td>
                            <td class="p-4">
                                <span :class="getStatusClass(order.status)" class="px-2 py-1 rounded-md text-[10px] font-bold">
                                    {{ getStatusLabel(order.status) }}
                                </span>
                            </td>
                            <td class="p-4">
                                <button 
                                    v-if="order.status === 1"
                                    @click="emit('cancelOrder', order.id)"
                                    class="text-red-400 hover:text-red-300 underline underline-offset-4"
                                >
                                    Cancel
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!orders.length">
                            <td colspan="5" class="p-8 text-center text-gray-600 italic">No orders found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>