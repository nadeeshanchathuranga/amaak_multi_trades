<template>
  <Head :title="`${supplier.name} - Payment History`" />
  <Banner />
  <div class="flex flex-col items-center justify-start min-h-screen py-8 space-y-8 bg-gray-100 md:px-36 px-16">
    <Header />
    
    <div class="w-full md:w-5/6 py-12 space-y-8">
      <!-- Back Navigation -->
      <div class="flex md:flex-row flex-col w-full">
        <div class="flex items-center w-full h-16 space-x-4 rounded-2xl">
          <Link href="/suppliers">
            <img src="/images/back-arrow.png" class="w-14 h-14" />
          </Link>
          <p class="text-4xl font-bold tracking-wide text-black uppercase">
            {{ supplier.name }} - Payments
          </p>
        </div>
      </div>
      
      <!-- Supplier Info Card -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ supplier.name }} - Payment History</h1>
            <p class="text-gray-600 mt-1">
              Contact: {{ supplier.contact }} | Email: {{ supplier.email }}
            </p>
          </div>
          <div class="text-right">
            <div class="text-sm text-gray-500">Total Outstanding</div>
            <div class="text-2xl font-bold text-red-600">
              LKR {{ parseFloat(summary.total_outstanding || 0).toFixed(2) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
          <div class="flex items-center">
            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center mr-3">
              <span class="text-white text-xs">₹</span>
            </div>
            <div>
              <p class="text-blue-600 text-sm font-medium">Total Invoices</p>
              <p class="text-2xl font-bold text-blue-900">
                LKR {{ parseFloat(summary.total_invoice_amount || 0).toFixed(2) }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
          <div class="flex items-center">
            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center mr-3">
              <span class="text-white text-xs">✓</span>
            </div>
            <div>
              <p class="text-green-600 text-sm font-medium">Total Paid</p>
              <p class="text-2xl font-bold text-green-900">
                LKR {{ parseFloat(summary.paid_total || 0).toFixed(2) }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
          <div class="flex items-center">
            <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center mr-3">
              <span class="text-white text-xs">○</span>
            </div>
            <div>
              <p class="text-red-600 text-sm font-medium">Balance</p>
              <p class="text-2xl font-bold text-red-900">
                LKR {{ parseFloat(summary.balance || 0).toFixed(2) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Method Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-6">
          <div class="flex items-center">
            <div class="w-8 h-8 bg-emerald-500 rounded-full flex items-center justify-center mr-3">
              <span class="text-white text-xs">💵</span>
            </div>
            <div>
              <p class="text-emerald-600 text-sm font-medium">Cash Payments</p>
              <p class="text-2xl font-bold text-emerald-900">
                LKR {{ parseFloat(paymentTotals?.cash || 0).toFixed(2) }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
          <div class="flex items-center">
            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center mr-3">
              <span class="text-white text-xs">💳</span>
            </div>
            <div>
              <p class="text-blue-600 text-sm font-medium">Card Payments</p>
              <p class="text-2xl font-bold text-blue-900">
                LKR {{ parseFloat(paymentTotals?.card || 0).toFixed(2) }}
              </p>
            </div>
          </div>
        </div>

        <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
          <div class="flex items-center">
            <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center mr-3">
              <span class="text-white text-xs">📄</span>
            </div>
            <div>
              <p class="text-purple-600 text-sm font-medium">Check Payments</p>
              <p class="text-2xl font-bold text-purple-900">
                LKR {{ parseFloat(paymentTotals?.check || 0).toFixed(2) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Form Section -->
      <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">Invoice & Payment Management</h2>
        </div>
        <div class="p-6">
          <!-- Action Selection -->
          <div class="mb-6">
            <label class="block text-gray-700 text-sm font-medium mb-3">
              Select Action:
            </label>
            <div class="flex gap-4">
              <button
                type="button"
                @click="actionType = 'create_invoice'"
                :class="actionType === 'create_invoice' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                class="px-4 py-2 rounded-lg font-medium transition"
              >
                Create Invoice
              </button>
              <button
                type="button"
                @click="actionType = 'make_payment'"
                :class="actionType === 'make_payment' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'"
                class="px-4 py-2 rounded-lg font-medium transition"
              >
                Make Payment
              </button>
            </div>
          </div>

          <!-- Existing Invoices (for payment selection) -->
          <div v-if="actionType === 'make_payment' && invoices.length > 0" class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Select Invoice to Pay</h3>
            <div class="flex gap-6 overflow-x-auto pb-6 px-4 py-2 scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 hover:scrollbar-thumb-gray-500" style="scroll-behavior: smooth;">
              <div 
                v-for="invoice in invoices.filter(inv => inv.status !== 'paid')" 
                :key="invoice.id"
                class="bg-gray-50 p-4 rounded-lg text-sm cursor-pointer hover:bg-gray-100 transition flex-shrink-0 min-w-[280px] m-1 border border-gray-200 hover:border-gray-300"
                :class="selectedInvoice?.id === invoice.id ? 'ring-4 ring-blue-500 bg-blue-50 shadow-lg shadow-blue-200 border-blue-300' : 'hover:shadow-md'"
                @click="selectInvoice(invoice)"
              >
                <div class="space-y-2">

                  <div>
                    <p class="text-gray-900 text-sm">LKR {{ parseFloat(invoice.total_amount).toFixed(2) }}</p>
                    <p class="text-gray-600 text-xs">Paid: LKR {{ parseFloat(invoice.paid_amount).toFixed(2) }}</p>
                    <p :class="invoice.status === 'paid' ? 'text-green-600' : 'text-red-600'" class="text-xs font-medium">{{ invoice.status.toUpperCase() }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Form -->
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Invoice Number -->
            <div>
              <label class="block text-gray-700 text-sm font-medium mb-2">
                Invoice Number {{ actionType === 'create_invoice' ? '*' : '' }}
              </label>
              <input
                v-model="form.invoice_number"
                type="text"
                :placeholder="actionType === 'create_invoice' ? 'Enter new invoice number' : 'Enter invoice number (optional)'"
                :required="actionType === 'create_invoice'"
                :readonly="selectedInvoice !== null"
                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <div v-if="form.errors.invoice_number" class="mt-2 text-red-600 text-sm">
                {{ form.errors.invoice_number }}
              </div>
            </div>

            <!-- Description -->
            <div v-if="actionType === 'make_payment'">
              <label class="block text-gray-700 text-sm font-medium mb-2">
                Payment Method *
              </label>
              <select
                v-model="form.description"
                required
                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Select Payment Method</option>
                <option value="Card">Card</option>
                <option value="Cash">Cash</option>
                <option value="Check">Check</option>
              </select>
              <div v-if="form.errors.description" class="mt-2 text-red-600 text-sm">
                {{ form.errors.description }}
              </div>
            </div>

            <!-- Total Invoice Amount (only for create_invoice) -->
            <div v-if="actionType === 'create_invoice'">
              <label class="block text-gray-700 text-sm font-medium mb-2">
                Total Invoice Amount *
              </label>
              <input
                v-model="form.total_cost"
                type="number"
                step="0.01"
                min="0"
                placeholder="Enter total invoice amount"
                required
                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <div v-if="form.errors.total_cost" class="mt-2 text-red-600 text-sm">
                {{ form.errors.total_cost }}
              </div>
            </div>

            <!-- Payment Amount (only for make_payment) -->
            <div v-if="actionType === 'make_payment'">
              <label class="block text-gray-700 text-sm font-medium mb-2">
                Payment Amount *
              </label>
              <input
                v-model="form.pay"
                type="number"
                step="0.01"
                min="0"
                placeholder="Enter payment amount"
                :max="selectedInvoice ? selectedInvoice.remaining_amount : undefined"
                required
                class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <div v-if="form.errors.pay" class="mt-2 text-red-600 text-sm">
                {{ form.errors.pay }}
              </div>
              <div v-if="selectedInvoice" class="mt-2 text-sm text-gray-600">
                Remaining: LKR {{ parseFloat(selectedInvoice.remaining_amount || 0).toFixed(2) }}
              </div>
            </div>

            <!-- Buttons -->
            <div class="flex justify-between pt-4">
              <button
                type="submit"
                :disabled="isSubmitting || (actionType === 'make_payment' && (Number(form.pay) <= 0 || (remainingDue || 0) < 0))"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ actionType === 'create_invoice' ? 'Create Invoice' : 'Submit Payment' }}
              </button>

              <button
                type="button"
                @click="resetForm"
                class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg text-sm font-medium transition"
              >
                Reset Form
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Tables Section - Stacked -->
      <div class="space-y-8">
        <!-- Invoices Table -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-semibold text-gray-900">Invoices</h3>
              <div class="max-w-sm">
                <input
                  v-model="invoiceSearch"
                  type="text"
                  placeholder="Search by invoice number..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remaining</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="invoice in filteredInvoices" :key="invoice.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ invoice.invoice_number }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ invoice.description || 'N/A' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ new Date(invoice.invoice_date || invoice.created_at).toLocaleDateString() }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    LKR {{ parseFloat(invoice.total_amount).toFixed(2) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                    LKR {{ parseFloat(invoice.paid_amount).toFixed(2) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                    LKR {{ parseFloat(invoice.remaining_amount).toFixed(2) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="{
                      'bg-green-100 text-green-800': invoice.status === 'paid',
                      'bg-yellow-100 text-yellow-800': invoice.status === 'partial',
                      'bg-red-100 text-red-800': invoice.status === 'pending'
                    }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ invoice.status.toUpperCase() }}
                    </span>
                  </td>
                </tr>
                <tr v-if="!filteredInvoices || filteredInvoices.length === 0">
                  <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                    {{ invoiceSearch ? 'No invoices match your search' : 'No invoices found' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- All Payments Table -->
        <div class="bg-white shadow rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-semibold text-gray-900">All Payments</h3>
              <div class="max-w-sm">
                <input
                  v-model="paymentSearch"
                  type="text"
                  placeholder="Search by invoice number..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="payment in filteredPayments" :key="payment.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ new Date(payment.created_at).toLocaleDateString() }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ payment.invoice_number || 'N/A' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ payment.description || 'N/A' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                    LKR {{ parseFloat(payment.pay).toFixed(2) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                      {{ payment.status.toUpperCase() }}
                    </span>
                  </td>
                </tr>
                <tr v-if="!filteredPayments || filteredPayments.length === 0">
                  <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                    {{ paymentSearch ? 'No payments match your search' : 'No payments found' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Back Button -->
      <div class="mt-6 flex justify-end">
        <Link 
          :href="route('suppliers.index')"
          class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-medium transition"
        >
          Back to Suppliers
        </Link>
      </div>
    </div>
  </div>
  <Footer />
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Header from '@/Components/custom/Header.vue';
import Footer from '@/Components/custom/Footer.vue';
import Banner from '@/Components/Banner.vue';

const props = defineProps({
  supplier: {
    type: Object,
    required: true,
  },
  invoices: {
    type: Array,
    default: () => [],
  },
  payments: {
    type: Array,
    default: () => [],
  },
  summary: {
    type: Object,
    default: () => ({}),
  },
  paymentTotals: {
    type: Object,
    default: () => ({
      cash: 0,
      credit: 0,
      check: 0
    }),
  },
});

// Data
const actionType = ref('create_invoice');
const isSubmitting = ref(false);
const selectedInvoice = ref(null);
const invoiceSearch = ref('');
const paymentSearch = ref('');

const form = useForm({
  supplier_id: props.supplier?.id || null,
  invoice_number: '',
  description: '',
  total_cost: '',
  pay: '',
  action_type: 'create_invoice',
  invoice_id: null,
});

// Computed properties
const filteredInvoices = computed(() => {
  if (!props.invoices) return [];
  if (!invoiceSearch.value) return props.invoices;
  
  return props.invoices.filter(invoice =>
    invoice.invoice_number.toLowerCase().includes(invoiceSearch.value.toLowerCase())
  );
});

const filteredPayments = computed(() => {
  if (!props.payments) return [];
  if (!paymentSearch.value) return props.payments;
  
  return props.payments.filter(payment =>
    (payment.invoice_number || '').toLowerCase().includes(paymentSearch.value.toLowerCase())
  );
});

const remainingDue = computed(() => {
  if (actionType.value === 'make_payment') {
    const payAmount = Number(form.pay) || 0;
    if (selectedInvoice.value) {
      return selectedInvoice.value.remaining_amount - payAmount;
    }
  }
  return 0;
});

// Methods
const selectInvoice = (invoice) => {
  selectedInvoice.value = invoice;
  form.invoice_number = invoice.invoice_number;
  form.invoice_id = invoice.id;
  form.pay = '';
  form.description = ''; // Clear payment method selection
};

const resetForm = () => {
  form.reset();
  form.supplier_id = props.supplier?.id || null;
  form.action_type = actionType.value;
  selectedInvoice.value = null;
  form.description = ''; // Clear description/payment method
};

const submit = async () => {
  isSubmitting.value = true;
  
  form.action_type = actionType.value;
  
  if (actionType.value === 'make_payment' && selectedInvoice.value) {
    form.invoice_id = selectedInvoice.value.id;
  }

  form.post('/suppliers/payment', {
    onSuccess: (page) => {
      resetForm();
      // Refresh page to show updated data
      window.location.reload();
    },
    onError: (errors) => {
      console.error('Form submission errors:', errors);
    },
    onFinish: () => {
      isSubmitting.value = false;
    },
  });
};
</script>