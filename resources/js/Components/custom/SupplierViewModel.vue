<template :key="modalKey">
  <TransitionRoot as="template" :show="open">
    <Dialog class="relative z-50" @close="$emit('update:open', false)">
      <!-- Overlay -->
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity" />
      </TransitionChild>

      <!-- Modal Content -->
      <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0 scale-95"
          enter-to="opacity-100 scale-100"
          leave="ease-in duration-200"
          leave-from="opacity-100 scale-100"
          leave-to="opacity-0 scale-95"
        >
          <DialogPanel
            class="bg-[#111827] border border-blue-600 rounded-2xl shadow-2xl max-w-2xl w-full p-8 text-white space-y-8"
          >
            <!-- Title -->
            <DialogTitle class="text-2xl font-semibold text-center text-blue-500">
              Supplier Invoice & Payment Management
            </DialogTitle>

            <!-- Supplier Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm mb-6">
              <div>
                <label class="text-gray-400">Supplier Name:</label>
                <p class="text-lg font-bold text-white">
                  {{ supplier?.name || 'N/A' }}
                </p>
              </div>

              <div>
                <label class="text-gray-400">Total Outstanding:</label>
                <p class="text-red-400 text-lg font-bold">
                  LKR {{ (currentDue || 0).toFixed(2) }}
                </p>
              </div>
            </div>

            <!-- Action Selection -->
            <div class="mb-6">
              <label class="block text-gray-400 text-sm font-medium mb-3">
                Select Action:
              </label>
              <div class="flex gap-4">
                <button
                  type="button"
                  @click="actionType = 'create_invoice'"
                  :class="actionType === 'create_invoice' ? 'bg-blue-600' : 'bg-gray-600'"
                  class="px-4 py-2 rounded-lg text-white font-medium transition"
                >
                  Create Invoice
                </button>
                <button
                  type="button"
                  @click="actionType = 'make_payment'"
                  :class="actionType === 'make_payment' ? 'bg-green-600' : 'bg-gray-600'"
                  class="px-4 py-2 rounded-lg text-white font-medium transition"
                >
                  Make Payment
                </button>
              </div>
            </div>

            <!-- Existing Invoices -->
            <div v-if="invoices && invoices.length > 0" class="mb-6">
              <h3 class="text-lg font-semibold text-white mb-3">Existing Invoices</h3>
              <div class="max-h-48 overflow-y-auto space-y-2">
                <div 
                  v-for="invoice in invoices" 
                  :key="invoice.id"
                  class="bg-gray-700 p-3 rounded-lg text-sm"
                >
                  <div class="flex justify-between items-center">
                    <div>
                      <p class="text-white font-medium">{{ invoice.invoice_number }}</p>
                      <p class="text-gray-400">{{ invoice.description || 'No description' }}</p>
                    </div>
                    <div class="text-right">
                      <p class="text-white">LKR {{ parseFloat(invoice.total_amount).toFixed(2) }}</p>
                      <p class="text-gray-400">Paid: LKR {{ parseFloat(invoice.paid_amount).toFixed(2) }}</p>
                      <p :class="invoice.status === 'paid' ? 'text-green-400' : 'text-red-400'">{{ invoice.status.toUpperCase() }}</p>
                    </div>
                  </div>
                  
                  <!-- Payment History -->
                  <div v-if="invoice.payments && invoice.payments.length > 0" class="mt-2 pt-2 border-t border-gray-600">
                    <p class="text-xs text-gray-400 mb-1">Payment History:</p>
                    <div class="space-y-1 max-h-20 overflow-y-auto">
                      <div 
                        v-for="payment in invoice.payments" 
                        :key="payment.id" 
                        class="flex justify-between text-xs"
                      >
                        <span class="text-gray-300">{{ new Date(payment.created_at).toLocaleDateString() }}</span>
                        <span class="text-green-400">LKR {{ parseFloat(payment.pay).toFixed(2) }}</span>
                      </div>
                    </div>
                  </div>

                  <button
                    v-if="actionType === 'make_payment' && invoice.status !== 'paid'"
                    @click="selectInvoice(invoice)"
                    class="mt-2 px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700"
                  >
                    Pay This Invoice
                  </button>
                </div>
              </div>
            </div>

            <!-- Payment Form -->
            <form @submit.prevent="submit" class="space-y-6">
              <!-- Invoice Number -->
              <div>
                <label class="block text-gray-400 text-sm font-medium mb-2">
                  Invoice Number {{ actionType === 'create_invoice' ? '*' : '' }}
                </label>
                <input
                  v-model="form.invoice_number"
                  type="text"
                  :placeholder="actionType === 'create_invoice' ? 'Enter new invoice number' : 'Enter invoice number (optional)'"
                  :required="actionType === 'create_invoice'"
                  :readonly="selectedInvoice !== null"
                  class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <div v-if="form.errors.invoice_number" class="mt-2 text-red-400 text-sm">
                  {{ form.errors.invoice_number }}
                </div>
              </div>

              <!-- Description -->
              <div>
                <label class="block text-gray-400 text-sm font-medium mb-2">
                  Description
                </label>
                <textarea
                  v-model="form.description"
                  placeholder="Enter description (optional)"
                  rows="3"
                  :readonly="selectedInvoice !== null"
                  class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ></textarea>
                <div v-if="form.errors.description" class="mt-2 text-red-400 text-sm">
                  {{ form.errors.description }}
                </div>
              </div>

              <!-- Total Invoice Amount (only for create_invoice) -->
              <div v-if="actionType === 'create_invoice'">
                <label class="block text-gray-400 text-sm font-medium mb-2">
                  Total Invoice Amount *
                </label>
                <input
                  v-model="form.total_cost"
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="Enter total invoice amount"
                  required
                  class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <div v-if="form.errors.total_cost" class="mt-2 text-red-400 text-sm">
                  {{ form.errors.total_cost }}
                </div>
              </div>

              <!-- Payment Amount (only for make_payment) -->
              <div v-if="actionType === 'make_payment'">
                <label class="block text-gray-400 text-sm font-medium mb-2">
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
                  class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <div v-if="form.errors.pay" class="mt-2 text-red-400 text-sm">
                  {{ form.errors.pay }}
                </div>
                <div v-if="selectedInvoice" class="mt-2 text-sm text-gray-400">
                  Remaining: LKR {{ parseFloat(selectedInvoice.remaining_amount || 0).toFixed(2) }}
                </div>
              </div>

              <!-- Outstanding After Payment (only for make_payment) -->
              <div v-if="actionType === 'make_payment'" class="bg-gray-800 px-4 py-3 rounded-lg border border-gray-600">
                <label class="block text-sm text-gray-400 mb-1">
                  Outstanding After Payment:
                </label>
                <p
                  :class="(remainingDue || 0) < 0 ? 'text-red-400' : 'text-green-400'"
                  class="text-lg font-semibold"
                >
                  LKR {{ (remainingDue || 0).toFixed(2) }}
                </p>
              </div>

              <!-- Buttons -->
              <div class="flex justify-between pt-4">
                <button
                  type="submit"
                  :disabled="isSubmitting || 
                    (actionType === 'create_invoice' && (!form.invoice_number || !form.total_cost || Number(form.total_cost) <= 0)) ||
                    (actionType === 'make_payment' && (!form.pay || Number(form.pay) <= 0 || (remainingDue || 0) < 0))"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ actionType === 'create_invoice' ? 'Create Invoice' : 'Submit Payment' }}
                </button>

                <button
                  type="button"
                  @click="handleCancel"
                  class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg text-sm font-medium transition"
                >
                  Cancel
                </button>
              </div>
            </form>
          </DialogPanel>
        </TransitionChild>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'

const props = defineProps({
  supplier: {
    type: Object,
    required: true,
  },
  open: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:open', 'submitted'])

// Data
const actionType = ref('create_invoice')
const isSubmitting = ref(false)
const invoices = ref([])
const selectedInvoice = ref(null)

const form = useForm({
  supplier_id: props.supplier?.id || null,
  invoice_number: '',
  description: '',
  total_cost: '',
  pay: '',
  action_type: 'create_invoice',
  invoice_id: null,
})

// Computed properties
const currentDue = computed(() => {
  return props.supplier && typeof props.supplier.due_amount === 'number'
    ? props.supplier.due_amount
    : 0
})

const remainingDue = computed(() => {
  if (actionType.value === 'make_payment') {
    const payAmount = Number(form.pay) || 0
    if (selectedInvoice.value) {
      return selectedInvoice.value.remaining_amount - payAmount
    }
    return (currentDue.value || 0) - payAmount
  }
  return 0
})

// Methods
const loadInvoices = async () => {
  try {
    const response = await fetch(`/suppliers/${props.supplier.id}/invoices`)
    if (response.ok) {
      const data = await response.json()
      invoices.value = data.invoices || []
    }
  } catch (error) {
    console.error('Error loading invoices:', error)
  }
}

const selectInvoice = (invoice) => {
  selectedInvoice.value = invoice
  form.invoice_number = invoice.invoice_number
  form.description = invoice.description
  form.invoice_id = invoice.id
  form.pay = ''
}

const resetForm = () => {
  form.reset()
  form.supplier_id = props.supplier?.id || null
  form.action_type = actionType.value
  form.invoice_id = null
  selectedInvoice.value = null
}

const submit = async () => {
  isSubmitting.value = true
  
  // Set the action type
  form.action_type = actionType.value
  
  // Clear unused fields based on action type
  if (actionType.value === 'create_invoice') {
    // For invoice creation, clear payment amount
    form.pay = ''
    form.invoice_id = null
  } else if (actionType.value === 'make_payment') {
    // For payments, clear total cost and set invoice ID if selected
    form.total_cost = ''
    if (selectedInvoice.value) {
      form.invoice_id = selectedInvoice.value.id
    }
  }

  form.post('/suppliers/payment', {
    onSuccess: (page) => {
      resetForm()
      emit('submitted')
      if (actionType.value === 'create_invoice') {
        // After creating invoice, switch to payment mode and reload invoices
        actionType.value = 'make_payment'
        loadInvoices()
      }
    },
    onError: (errors) => {
      console.error('Form submission errors:', errors)
    },
    onFinish: () => {
      isSubmitting.value = false
    },
  })
}

const handleCancel = () => {
  resetForm()
  actionType.value = 'create_invoice'
  emit('update:open', false)
}

// Watchers
watch(
  () => actionType.value,
  () => {
    resetForm()
    if (actionType.value === 'make_payment') {
      loadInvoices()
    }
  }
)

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      loadInvoices()
    } else {
      resetForm()
      actionType.value = 'create_invoice'
    }
  }
)

// Lifecycle
onMounted(() => {
  if (props.open) {
    loadInvoices()
  }
})
</script>
