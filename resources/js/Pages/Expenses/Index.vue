<script setup>
import { computed, ref, watch } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    expenses: { type: Array, default: () => [] },
    total: { type: [Number, String], default: 0 },
    month: { type: Number, default: null },
    monthTotal: { type: [Number, String], default: null },
});

const page = usePage();

/* ----------------------------- Helpers ----------------------------- */
const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

const money = (value) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value || 0));

const formatDate = (value) =>
    new Date(value).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

const today = new Date().toISOString().slice(0, 10);

/* --------------------------- Add expense --------------------------- */
const addForm = useForm({
    description: '',
    amount: '',
    date: today,
});

const submitAdd = () => {
    addForm.post('/expenses', {
        preserveScroll: true,
        onSuccess: () => addForm.reset('description', 'amount'),
    });
};

/* --------------------------- Edit expense -------------------------- */
const editing = ref(null);
const editForm = useForm({ description: '', amount: '', date: '' });

const openEdit = (expense) => {
    editing.value = expense;
    editForm.clearErrors();
    editForm.description = expense.description;
    editForm.amount = expense.amount;
    editForm.date = String(expense.date).slice(0, 10);
};

const closeEdit = () => {
    editing.value = null;
};

const submitEdit = () => {
    editForm.put(`/expenses/${editing.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeEdit(),
    });
};

/* ----------------------------- Delete ------------------------------ */
const destroy = (expense) => {
    if (confirm(`Delete "${expense.description}"?`)) {
        router.delete(`/expenses/${expense.id}`, { preserveScroll: true });
    }
};

/* -------------------------- Month summary -------------------------- */
const selectedMonth = ref(props.month ?? '');

watch(selectedMonth, (value) => {
    router.get('/', value ? { month: value } : {}, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
});

const monthLabel = computed(() =>
    props.month ? months[props.month - 1] : null
);

/* --------------------------- Flash toast --------------------------- */
const flash = computed(() => page.props.flash?.success);
const showToast = ref(false);
let toastTimer = null;

watch(flash, (message) => {
    if (message) {
        showToast.value = true;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => (showToast.value = false), 3000);
    }
}, { immediate: true });
</script>

<template>
    <Head title="Expenses" />

    <div class="min-h-full">
        <!-- Toast -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            leave-active-class="transition duration-200 ease-in"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-if="showToast"
                class="fixed top-5 right-5 z-50 flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-3 text-sm font-medium text-white shadow-lg"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                {{ flash }}
            </div>
        </Transition>

        <!-- Header -->
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-5">
                <div>
                    <h1 class="text-xl font-semibold tracking-tight text-slate-900">Expense Tracker</h1>
                    <p class="text-sm text-slate-500">Manage and review your spending</p>
                </div>
                <a
                    href="/expenses/export"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export CSV
                </a>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-8">
            <!-- Summary cards -->
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Total expenses</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ money(total) }}</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Number of entries</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">{{ expenses.length }}</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-slate-500">Monthly summary</p>
                        <select
                            v-model="selectedMonth"
                            class="rounded-md border border-slate-300 bg-white py-1 pl-2 pr-7 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All</option>
                            <option v-for="(name, i) in months" :key="i" :value="i + 1">{{ name }}</option>
                        </select>
                    </div>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">
                        {{ monthTotal !== null ? money(monthTotal) : '—' }}
                    </p>
                    <p v-if="monthLabel" class="mt-1 text-xs text-slate-400">{{ monthLabel }} {{ new Date().getFullYear() }}</p>
                </div>
            </section>

            <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Add form -->
                <section class="lg:col-span-1">
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-900">Add expense</h2>
                        <form class="mt-4 space-y-4" @submit.prevent="submitAdd">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Description</label>
                                <input
                                    v-model="addForm.description"
                                    type="text"
                                    placeholder="e.g. Lunch"
                                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                                <p v-if="addForm.errors.description" class="mt-1 text-xs text-red-600">{{ addForm.errors.description }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Amount</label>
                                <div class="relative mt-1">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 sm:text-sm">$</span>
                                    <input
                                        v-model="addForm.amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        placeholder="0.00"
                                        class="block w-full rounded-lg border-slate-300 pl-7 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                </div>
                                <p v-if="addForm.errors.amount" class="mt-1 text-xs text-red-600">{{ addForm.errors.amount }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Date</label>
                                <input
                                    v-model="addForm.date"
                                    type="date"
                                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                                <p v-if="addForm.errors.date" class="mt-1 text-xs text-red-600">{{ addForm.errors.date }}</p>
                            </div>

                            <button
                                type="submit"
                                :disabled="addForm.processing"
                                class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:opacity-50"
                            >
                                {{ addForm.processing ? 'Adding…' : 'Add expense' }}
                            </button>
                        </form>
                    </div>
                </section>

                <!-- Expenses table -->
                <section class="lg:col-span-2">
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Description</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="expense in expenses" :key="expense.id" class="transition hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ formatDate(expense.date) }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ expense.description }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-slate-900">{{ money(expense.amount) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <button class="font-medium text-indigo-600 hover:text-indigo-500" @click="openEdit(expense)">Edit</button>
                                        <button class="ml-4 font-medium text-red-600 hover:text-red-500" @click="destroy(expense)">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="expenses.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-400">
                                        No expenses yet. Add your first one on the left.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>

        <!-- Edit modal -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div v-if="editing" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4" @click.self="closeEdit">
                <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                    <h2 class="text-base font-semibold text-slate-900">Edit expense</h2>
                    <form class="mt-4 space-y-4" @submit.prevent="submitEdit">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Description</label>
                            <input
                                v-model="editForm.description"
                                type="text"
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                            <p v-if="editForm.errors.description" class="mt-1 text-xs text-red-600">{{ editForm.errors.description }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Amount</label>
                            <div class="relative mt-1">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 sm:text-sm">$</span>
                                <input
                                    v-model="editForm.amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="block w-full rounded-lg border-slate-300 pl-7 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                            </div>
                            <p v-if="editForm.errors.amount" class="mt-1 text-xs text-red-600">{{ editForm.errors.amount }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Date</label>
                            <input
                                v-model="editForm.date"
                                type="date"
                                class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                            <p v-if="editForm.errors.date" class="mt-1 text-xs text-red-600">{{ editForm.errors.date }}</p>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="closeEdit">Cancel</button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                            >
                                {{ editForm.processing ? 'Saving…' : 'Save changes' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </div>
</template>
