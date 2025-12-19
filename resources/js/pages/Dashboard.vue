<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Chart from 'chart.js/auto'; // The most stable import for Vite 7
import { computed, onMounted, ref, watch } from 'vue';

// 1. Data Setup
const page = usePage();
const user = computed(() => page.props.auth.user);
const props = defineProps({
    initialBalance: [Number, String],
    initialAssets: Array,
    activeOrders: Array,
    orderBook: Object,
    recentTrades: Array,
});

// 2. Chart logic
const chartCanvas = ref(null);
let chartInstance = null;

const initChart = () => {
    if (!chartCanvas.value) return;
    if (chartInstance) chartInstance.destroy();

    const ctx = chartCanvas.value.getContext('2d');

    // Process trade data for the line chart
    const trades = [...(props.recentTrades || [])].reverse();
    const labels = trades.map((t) =>
        new Date(t.created_at).toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
        }),
    );
    const prices = trades.map((t) => parseFloat(t.price));

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.length ? labels : ['No Data'],
            datasets: [
                {
                    label: 'Price',
                    data: prices.length ? prices : [0],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 0,
                    borderWidth: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    display: true,
                    grid: { display: false },
                    ticks: { color: '#4b5563' },
                },
                y: {
                    display: true,
                    grid: { color: '#1f2937' },
                    ticks: { color: '#4b5563' },
                },
            },
        },
    });
};

// 3. Form Logic
const orderType = ref('buy');
const price = ref('');
const amount = ref('');
const isLoading = ref(false);

const submitOrder = async () => {
    if (!price.value || !amount.value) return alert('Enter price and amount');
    isLoading.value = true;
    try {
        await axios.post('/orders', {
            symbol: 'BTC',
            side: orderType.value,
            price: parseFloat(price.value),
            amount: parseFloat(amount.value),
        });
        price.value = '';
        amount.value = '';
        router.reload({
            only: [
                'auth',
                'initialAssets',
                'activeOrders',
                'orderBook',
                'recentTrades',
            ],
        });
    } catch (error) {
        alert(error.response?.data?.error || 'Order failed');
    } finally {
        isLoading.value = false;
    }
};

const cancelOrder = (id) => {
    if (!confirm('Cancel this order?')) return;

    // Using router.post instead of axios.post
    router.post(
        `/orders/${id}/cancel`,
        {},
        {
            onSuccess: (page) => {
                const msg = page.props.flash?.message || 'Order Cancelled!';
                alert(msg);
            },
            onError: (errors) => {
                alert('Failed to cancel: ' + (errors.error || 'Unknown error'));
            },
        },
    );
};

// Lifecycle
onMounted(() => {
    initChart();

    if (window.Echo) {
        console.log('Echo is active, listening on channel: orders');
        
        window.Echo.channel('orders')
            .listen('.OrderMatched', (e) => {
                console.log('MATCH EVENT RECEIVED:', e);
                // router.reload fetches the new props (balance, trades) without a full refresh
                router.reload({ 
                    preserveScroll: true,
                    only: ['auth', 'initialAssets', 'activeOrders', 'orderBook', 'recentTrades'],
                });
            });
    } else {
        console.error('Echo failed to initialize. Check bootstrap.js');
    }
});

watch(
    () => props.recentTrades,
    () => {
        initChart();
    },
    { deep: true },
);
</script>

<template>
    <Head title="Trade BTC" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-950 p-4 text-gray-200">
            <div class="mx-auto grid max-w-[1600px] grid-cols-12 gap-4">
                <div class="col-span-12 space-y-4 lg:col-span-3">
                    <div
                        class="rounded-xl border border-gray-800 bg-gray-900 p-5 shadow-xl"
                    >
                        <div class="mb-4 flex rounded-lg bg-gray-800 p-1">
                            <button
                                @click="orderType = 'buy'"
                                :class="
                                    orderType === 'buy'
                                        ? 'bg-green-600 text-white'
                                        : 'text-gray-400'
                                "
                                class="flex-1 rounded py-2 font-bold transition"
                            >
                                BUY
                            </button>
                            <button
                                @click="orderType = 'sell'"
                                :class="
                                    orderType === 'sell'
                                        ? 'bg-red-600 text-white'
                                        : 'text-gray-400'
                                "
                                class="flex-1 rounded py-2 font-bold transition"
                            >
                                SELL
                            </button>
                        </div>
                        <input
                            v-model="price"
                            type="number"
                            class="mb-3 w-full rounded border-none bg-gray-800 text-white focus:ring-1 focus:ring-green-500"
                            placeholder="Price (USD)"
                        />
                        <input
                            v-model="amount"
                            type="number"
                            class="mb-4 w-full rounded border-none bg-gray-800 text-white focus:ring-1 focus:ring-green-500"
                            placeholder="Amount (BTC)"
                        />
                        <button
                            @click="submitOrder"
                            :disabled="isLoading"
                            :class="
                                orderType === 'buy'
                                    ? 'bg-green-600 hover:bg-green-500'
                                    : 'bg-red-600 hover:bg-red-500'
                            "
                            class="w-full rounded-lg py-3 font-bold uppercase tracking-wider transition-all"
                        >
                            {{
                                isLoading
                                    ? 'Processing...'
                                    : 'Place ' + orderType
                            }}
                        </button>
                    </div>

                    <div
                        class="rounded-xl border border-gray-800 bg-gray-900 p-5"
                    >
                        <h3
                            class="mb-2 text-xs font-bold uppercase tracking-widest text-gray-500"
                        >
                            Portfolio
                        </h3>
                        <div
                            class="mb-2 font-mono text-2xl font-bold text-green-400"
                        >
                            ${{ Number(user?.balance || 0).toLocaleString() }}
                        </div>
                        <div
                            v-for="asset in initialAssets"
                            :key="asset.symbol"
                            class="flex justify-between border-t border-gray-800 py-1 text-sm"
                        >
                            <span class="font-bold uppercase text-gray-400">{{
                                asset.symbol
                            }}</span>
                            <span class="font-mono">{{ asset.amount }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 lg:col-span-6">
                    <div
                        class="flex h-[550px] flex-col rounded-xl border border-gray-800 bg-gray-900 p-6 shadow-xl"
                    >
                        <div class="mb-4">
                            <h2
                                class="text-xl font-black uppercase tracking-tighter"
                            >
                                BTC / USD
                            </h2>
                            <div
                                class="font-mono text-xl font-bold text-green-500"
                            >
                                ${{ recentTrades?.[0]?.price || '---' }}
                            </div>
                        </div>
                        <div class="relative flex-grow">
                            <canvas ref="chartCanvas"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 space-y-4 lg:col-span-3">
                    <div
                        class="h-[320px] overflow-hidden rounded-xl border border-gray-800 bg-gray-900 p-5"
                    >
                        <h3
                            class="mb-4 text-center text-xs font-bold uppercase tracking-widest text-gray-500"
                        >
                            Order Book
                        </h3>
                        <div class="space-y-1 font-mono text-[11px]">
                            <div
                                v-for="ask in orderBook?.asks || []"
                                :key="ask.price"
                                class="flex justify-between text-red-400"
                            >
                                <span
                                    >${{
                                        parseFloat(ask.price).toLocaleString()
                                    }}</span
                                ><span>{{ ask.amount }}</span>
                            </div>
                            <div
                                class="my-1 border-y border-gray-800 bg-gray-800/20 py-2 text-center font-bold text-white"
                            >
                                ${{ recentTrades?.[0]?.price || '---' }}
                            </div>
                            <div
                                v-for="bid in orderBook?.bids"
                                class="flex justify-between text-green-400"
                            >
                                <span
                                    >${{
                                        parseFloat(bid.price).toLocaleString()
                                    }}</span
                                ><span>{{ bid.amount }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="h-[215px] rounded-xl border border-gray-800 bg-gray-900 p-5"
                    >
                        <h3
                            class="mb-4 text-center text-xs font-bold uppercase tracking-widest text-gray-500"
                        >
                            Recent Trades
                        </h3>
                        <div
                            class="h-[140px] space-y-1 overflow-y-auto font-mono text-[10px]"
                        >
                            <div
                                v-for="trade in recentTrades"
                                class="flex justify-between border-b border-gray-800 pb-1"
                            >
                                <span class="font-bold text-green-400"
                                    >${{
                                        parseFloat(trade.price).toLocaleString()
                                    }}</span
                                >
                                <span class="font-bold text-gray-400"
                                    >{{ trade.amount }} BTC</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-12">
                    <div
                        class="rounded-xl border border-gray-800 bg-gray-900 p-6 shadow-xl"
                    >
                        <h3
                            class="mb-4 text-xs font-bold uppercase tracking-widest text-gray-500"
                        >
                            Open Orders
                        </h3>
                        <table class="w-full text-left">
                            <thead
                                class="border-b border-gray-800 text-xs text-gray-500"
                            >
                                <tr>
                                    <th class="pb-3">Side</th>
                                    <th class="pb-3">Price</th>
                                    <th class="pb-3">Remaining</th>
                                    <th class="pb-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                <tr
                                    v-for="order in activeOrders"
                                    :key="order.id"
                                    class="border-b border-gray-800/50"
                                >
                                    <td
                                        :class="
                                            order.side === 'buy'
                                                ? 'text-green-400'
                                                : 'text-red-400'
                                        "
                                        class="py-4 font-black uppercase"
                                    >
                                        {{ order.side }}
                                    </td>
                                    <td
                                        class="py-4 font-mono font-bold text-white"
                                    >
                                        ${{
                                            parseFloat(
                                                order.price,
                                            ).toLocaleString()
                                        }}
                                    </td>
                                    <td class="py-4 font-mono text-gray-400">
                                        {{ order.amount }} BTC
                                    </td>
                                    <td class="py-4 text-right">
                                        <button
                                            @click="cancelOrder(order.id)"
                                            class="rounded border border-red-500/20 px-3 py-1 text-xs font-bold uppercase text-red-500 transition-all hover:bg-red-500/10"
                                        >
                                            Cancel
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!activeOrders?.length">
                                    <td
                                        colspan="4"
                                        class="py-10 text-center italic text-gray-600"
                                    >
                                        No active orders
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
