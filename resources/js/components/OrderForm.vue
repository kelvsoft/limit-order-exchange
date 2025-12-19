<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps<{
    usdBalance: number;
    symbol: string;
}>();

const emit = defineEmits(['orderPlaced']);

const side = ref<'buy' | 'sell'>('buy');
const price = ref<number | null>(null);
const amount = ref<number | null>(null);
const loading = ref(false);

// Calculate totals and fees in real-time
const totalValue = computed(() => (price.value || 0) * (amount.value || 0));
const fee = computed(() => totalValue.value * 0.015);
const grandTotal = computed(() => totalValue.value + fee.value);

const submitOrder = async () => {
    if (!price.value || !amount.value) return;
    
    loading.value = true;
    try {
        await axios.post('/api/orders', {
            symbol: props.symbol,
            side: side.value,
            price: price.value,
            amount: amount.value
        });
        
        // Reset form
        price.value = null;
        amount.value = null;
        emit('orderPlaced'); // Refresh parent data
        alert('Order submitted successfully!');
    } catch (e: any) {
        alert(e.response?.data?.error || 'Failed to place order');
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-xl text-white">
        <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
            <span class="w-2 h-6 bg-blue-500 rounded-full"></span>
            Place Order
        </h2>

        <div class="flex p-1 bg-gray-950 rounded-xl mb-6">
            <button 
                @click="side = 'buy'"
                :class="side === 'buy' ? 'bg-green-600 text-white shadow-lg' : 'text-gray-500 hover:text-gray-300'"
                class="flex-1 py-3 rounded-lg transition-all font-bold uppercase tracking-widest text-sm"
            >
                Buy
            </button>
            <button 
                @click="side = 'sell'"
                :class="side === 'sell' ? 'bg-red-600 text-white shadow-lg' : 'text-gray-500 hover:text-gray-300'"
                class="flex-1 py-3 rounded-lg transition-all font-bold uppercase tracking-widest text-sm"
            >
                Sell
            </button>
        </div>

        <div class="space-y-5">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Price (USD)</label>
                <input 
                    v-model="price" 
                    type="number" 
                    placeholder="0.00"
                    class="w-full bg-gray-950 border-gray-800 rounded-xl focus:ring-2 focus:ring-blue-500 text-white p-4 font-mono"
                >
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Amount ({{ symbol }})</label>
                <input 
                    v-model="amount" 
                    type="number" 
                    placeholder="0.00"
                    class="w-full bg-gray-950 border-gray-800 rounded-xl focus:ring-2 focus:ring-blue-500 text-white p-4 font-mono"
                >
            </div>
        </div>

        <div v-if="totalValue > 0" class="mt-6 p-4 bg-blue-500/5 border border-blue-500/20 rounded-xl space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-gray-400">Principal</span>
                <span class="font-mono text-gray-200">${{ totalValue.toLocaleString() }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-400">Fee (1.5%)</span>
                <span class="font-mono text-yellow-500">+ ${{ fee.toLocaleString() }}</span>
            </div>
            <div class="pt-2 border-t border-gray-800 flex justify-between font-bold">
                <span class="text-gray-300">Total</span>
                <span class="font-mono text-blue-400">${{ grandTotal.toLocaleString() }}</span>
            </div>
        </div>

        <button 
            @click="submitOrder"
            :disabled="loading || (side === 'buy' && grandTotal > usdBalance)"
            :class="[
                side === 'buy' ? 'bg-green-600 hover:bg-green-500 shadow-green-900/20' : 'bg-red-600 hover:bg-red-500 shadow-red-900/20',
                'w-full mt-8 py-4 rounded-xl font-black text-lg transition-all shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed'
            ]"
        >
            <span v-if="!loading">CONFIRM {{ side.toUpperCase() }}</span>
            <span v-else>PROCESSING...</span>
        </button>
        
        <p v-if="side === 'buy' && grandTotal > usdBalance" class="mt-3 text-center text-xs text-red-400">
            Insufficient funds (Max: ${{ usdBalance.toLocaleString() }})
        </p>
    </div>
</template>