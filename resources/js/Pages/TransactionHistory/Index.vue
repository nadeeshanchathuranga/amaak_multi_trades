<style>
/* General DataTables Pagination Container Style */
.dataTables_wrapper .dataTables_paginate {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 20px;
}

/* Style the filter container */
#TransitionTable_filter {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  margin-bottom: 16px; /* Add spacing below the filter */
}

/* Style the label and input field inside the filter */
#TransitionTable_filter label {
  font-size: 17px;
  color: #000000; /* Match text color of the table header */
  display: flex;
  align-items: center;
}

/* Style the input field */
#TransitionTable_filter input[type="search"] {
  font-weight: 400;
  padding: 9px 15px;
  font-size: 14px;
  color: #000000cc;
  border: 1px solid rgb(209 213 219);
  border-radius: 5px;
  background: #fff;
  outline: none;
  transition: all 0.5s ease;
}
#TransitionTable_filter input[type="search"]:focus {
  outline: none; /* Removes the default outline */
  border: 1px solid #4b5563;
  box-shadow: none; /* Removes any focus box-shadow */
}

#TransitionTable_filter {
  float: left;
}

.dataTables_wrapper {
  margin-bottom: 10px;
}
</style>

<template>
    <Head title="Order History" />
     <Banner />
     <div class="flex flex-col items-center justify-start min-h-screen py-8 space-y-8 bg-gray-100 md:px-36 px-16">
        <Header />
        <div class="w-full md:w-5/6 py-12 space-y-24">
            <div class="flex items-center justify-between float-end">
                <p class="text-3xl italic font-bold text-black">
                <span class="px-4 py-1 mr-3 text-white bg-black rounded-xl">{{
                    totalhistoryTransactions

                }}</span>
                <span class="text-xl">/ Total History Transition</span>
                </p>
            </div>

            <div class="flex w-full">
                <div class="flex items-center w-full h-16 space-x-4 rounded-2xl">
                <Link href="/">
                    <img src="/images/back-arrow.png" class="w-14 h-14" />
                </Link>
                <p class="text-4xl font-bold tracking-wide text-black uppercase">
                    Order History
                </p>
                </div>
                <div class="flex justify-end md:inline hidden w-full"></div>
            </div>


            <template v-if="allhistoryTransactions && allhistoryTransactions.length > 0">
                <div class="overflow-x-auto">
                <table
                    id="TransitionTable" class="w-full text-gray-700 bg-white border border-gray-300 rounded-lg shadow-md table-auto">
                    <thead>
                    <tr class="bg-gradient-to-r from-blue-600 via-blue-500 to-blue-600 text-[12px] text-white border-b border-blue-700">
                        <th class="p-4 font-semibold tracking-wide text-left uppercase">#</th>
                        <th class="p-4 font-semibold tracking-wide text-left uppercase">Oredr ID</th>
                        <th class="p-4 font-semibold tracking-wide text-left uppercase">Total Amount</th>
                        <th class="p-4 font-semibold tracking-wide text-left uppercase"> Discount</th>
                        <th class="p-4 font-semibold tracking-wide text-left uppercase">Payment Method</th>
                        <th class="p-4 font-semibold tracking-wide text-left uppercase">Sale Date</th>
                        <th class="p-4 font-semibold tracking-wide text-left uppercase"> Action</th>
                    </tr>
                    </thead>
                    <tbody class="text-[13px] font-normal">
                        <tr
                            v-for="(history, index) in allhistoryTransactions"
                            :key="history.id"
                            class="transition duration-200 ease-in-out hover:bg-gray-200 hover:shadow-lg"
                        >
                            <td class="px-6 py-3 text- first-letter:">{{ index + 1 }}</td>

<td class="p-4 font-bold border-gray-200">
  {{ history.order_id || "N/A" }}
</td>

<td class="p-4 font-bold border-gray-200">
  {{ (Number(history.total_amount) || 0).toFixed(2) }}
</td>

<td class="p-4 font-bold border-gray-200">
  {{ ((parseFloat(history.discount) || 0) + (parseFloat(history.custom_discount) || 0)).toFixed(2) }}
</td>

<td class="p-4 font-bold border-gray-200">
  {{ history.payment_method || "N/A" }}
</td>

<td class="p-4 font-bold border-gray-200">
  {{ history.sale_date || "N/A" }}
</td>
                            <td class="p-4 font-bold border-gray-200">
                                <div class="flex gap-2">
                                    <button
                                        @click="printReceipt(history)"
                                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
                                    >
                                        Print
                                    </button>
                                    <button class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600" @click="deleteReceipt(history.order_id)">
                                        Delete
                                    </button>
                                </div>
                            </td>

                        </tr>
                    </tbody>
                </table>
                </div>
            </template>

            <template v-else>
                <div class="col-span-4 text-center text-blue-500">
                <p class="text-center text-red-500 text-[17px]">
                    No Stock Transition Available
                </p>
                </div>
            </template>
        </div>
     </div>
<Footer />
</template>


<script setup>
import { ref } from "vue";
import { router,useForm } from "@inertiajs/vue3";
import { Head, Link } from "@inertiajs/vue3";
import Header from "@/Components/custom/Header.vue";
import Footer from "@/Components/custom/Footer.vue";
import Banner from "@/Components/Banner.vue";
import { HasRole } from "@/Utils/Permissions";

const props = defineProps({
  allhistoryTransactions: Array,
  totalhistoryTransactions: Number,
  companyInfo: Array
});
const form = useForm({});


const deleteReceipt = (orderId) => {
  if (confirm("Are you sure you want to delete this record? This action cannot be undone.")) {
    router.post(route("transactions.delete"), { order_id: orderId }, {
      onError: (error) => {
        alert("Error: " + (error.message || "Something went wrong."));
      },
    });
  }
};



$(document).ready(function () {
  let table = $("#TransitionTable").DataTable({
    dom: "Bfrtip",
    pageLength: 10,
    buttons: [],
    columnDefs: [
      {
        targets: 2,
        searchable: false,
        orderable: false,
      },
    ],
    initComplete: function () {
      let searchInput = $("div.dataTables_filter input");
      searchInput.attr("placeholder", "Search ...");
      searchInput.on("keypress", function (e) {
        if (e.which == 13) {
          table.search(this.value).draw();
        }
      });
    },
    language: {
      search: "",
    },
  });
});

const printReceipt = (history) => {
  const companyData = props.companyInfo[0];
  const saleItems = Array.isArray(history.sale_items) ? history.sale_items : [];

  const receiptHTML = `
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt</title>
    <style>
      @media print {
        body {
          margin: 0;
          padding: 0;
          -webkit-print-color-adjust: exact;
        }
      }
      body {
        background-color: #ffffff;
        font-size: 12px;
        font-family: 'Arial', sans-serif;
        margin: 0;
        padding: 10px;
        color: #000;
      }
      .header {
        text-align: center;
        margin-bottom: 16px;
      }
      .header h1 {
        font-size: 20px;
        font-weight: bold;
        margin: 0;
      }
      .header p {
        font-size: 10px;
        margin: 4px 0;
      }
      .section {
        margin-bottom: 16px;
        padding-top: 8px;
        border-top: 1px solid #000;
      }
      .info-row {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        margin-top: 8px;
      }
      .info-row p {
        margin: 0;
        font-weight: bold;
      }
      .info-row small {
        font-weight: normal;
      }
      table {
        width: 100%;
        font-size: 10px;
        border-collapse: collapse;
        margin-top: 8px;
      }
      table th, table td {
        padding: 6px 8px;
      }
      table th {
        text-align: left;
      }
      table td {
        text-align: right;
      }
      table td:first-child {
        text-align: left;
      }
      .totals {
        border-top: 1px solid #000;
        padding-top: 8px;
        font-size: 12px;
      }
      .totals div {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
      }
      .totals .total-line {
        font-size: 14px;
        font-weight: bold;
      }
      .footer {
        text-align: center;
        font-size: 10px;
        margin-top: 16px;
      }
      .footer p {
        margin: 6px 0;
      }
      .footer .italic {
        font-style: italic;
      }
    </style>
  </head>
  <body>
    <div class="receipt-container">
      <!-- Header -->
      <div class="header" style="text-align:center;">
        <img src="/images/billlogo.png" style="width: 200px; height: 80px;" />
        ${companyData?.name ? `<h1>${companyData.name}</h1>` : ''}
        ${companyData?.address ? `<p>${companyData.address}</p>` : ''}
        ${(companyData?.phone || companyData?.phone2 || companyData?.email)
          ? `<p>${companyData.phone || ''} | ${companyData.phone2 || ''} ${companyData.email || ''}</p>`
          : ''}
      </div>

      <!-- Order Info -->
      <div class="section">
        <div class="info-row">
          <div>
            <p>Date:</p>
            <small>${new Date(history.created_at).toLocaleDateString()}</small>
          </div>
          <div style="text-align: right;">
            <p>Order No:</p>
            <small>${history.order_id}</small>
          </div>
        </div>
        <div class="info-row">
          <div>
            <p>Customer:</p>
            <small>${history.customer?.name || 'walk-in customer'}</small>
          </div>
          <div style="text-align: right;">
            <p>Cashier:</p>
            <small>${history.user?.name || ''}</small>
          </div>
        </div>
         <div class="info-row">
           <div>
             <p>Employee:</p>
             <small>${props.employee?.name || 'No Employee Selected'}</small>
           </div>
           <div></div>
         </div>
        <div class="info-row">
          <div>
            <p>Payment Method:</p>
            <small>${history.payment_method || 'Cash'}</small>
          </div>
          <div></div>
        </div>
      </div>

      <!-- Items Table -->
      <div class="section">
        <table style="width:100%; border-collapse: collapse;">
          <thead>
            <tr>
              <th style="text-align:left;">Item</th>
              <th style="text-align:center;">Qty × Price</th>
              <th style="text-align:right;">Total</th>
            </tr>
          </thead>
          <tbody>
            ${saleItems.map(item => {
              const originalPrice = Number(item.unit_price || item.selling_price || 0);
              const hasDiscount = Number(item.discount || 0) > 0;
              let discountedPrice = originalPrice;

              if (hasDiscount) {
                if (item.discounted_price != null) {
                  discountedPrice = Number(item.discounted_price);
                } else if (item.discount_type === "percent") {
                  discountedPrice = originalPrice * (1 - Number(item.discount) / 100);
                } else {
                  discountedPrice = originalPrice - Number(item.discount);
                }
                if (discountedPrice < 0) discountedPrice = 0;
              }

              const unitName = item.unit?.name || item.product?.unit?.name || '';
              const itemName = item.name || item.product?.name || "Item";

              return `
                <tr style="border-bottom: 1px dashed #000;">
                  <td style="text-align: left; padding: 8px 4px;">
                    <b>${itemName}</b>
                    ${hasDiscount ? `<br><small style="background-color: #000; color: #fff; font-size: 9px; font-weight: 600; padding: 2px 6px; border-radius: 4px;">
                      ${(item.discount_type === 'percent' || item.discount_type === 'percentage' || item.discount_type === '%') ? Number(item.discount).toFixed(2) + '% off' : Number(item.discount).toFixed(2) + ' % off'}
                    </small>` : ''}
                  </td>
                  <td style="text-align: center; padding: 8px 4px;">${item.quantity}${unitName ? ' ' + unitName : ''} × ${originalPrice.toFixed(2)}</td>
                  <td style="text-align: right; padding: 8px 4px;">${(originalPrice * item.quantity).toFixed(2)}</td>
                </tr>
              `;
            }).join('')}
          </tbody>
        </table>
      </div>

      <!-- Totals -->
      <div class="totals">
        <div>
          <span>Sub Total</span>
          <span>${((Number(history.total_amount) || 0) + (Number(history.discount) || 0) + (Number(history.custom_discount) || 0)).toFixed(2)} LKR</span>
        </div>
        <div>
          <span>Discount</span>
          <span>${(Number(history.discount) || 0).toFixed(2)} LKR</span>
        </div>
        <div>
          <span>Custom Discount</span>
          <span>${(Number(history.custom_discount) || 0).toFixed(2)} ${history.custom_discount > 0 ? 'LKR' : '%'}</span>
        </div>
        <div class="total-line">
          <span>Total</span>
          <span>${(Number(history.total_amount) || 0).toFixed(2)} LKR</span>
        </div>
        <div>
          <span>Cash</span>
          <span>${(Number(history.cash) || 0).toFixed(2)} LKR</span>
        </div>
        <div style="font-weight: bold;">
          <span>Balance</span>
          <span>${(Number(history.cash) - (Number(history.total_amount) || 0)).toFixed(2)} LKR</span>
        </div>
      </div>

      <!-- Footer -->
      <div class="footer" style="text-align:center; margin-top:10px;">
        <p>THANK YOU COME AGAIN</p>
        <p class="italic">Let the quality define its own standards</p>
        <p style="font-weight: bold;">Powered by JAAN Network Ltd.</p>
        <p>${new Date(history.created_at).toLocaleTimeString()}</p>
      </div>
    </div>
  </body>
  </html>
  `;

  // Open a new window
  const printWindow = window.open("", "_blank");
  if (!printWindow) {
    alert("Failed to open print window. Please check your browser settings.");
    return;
  }

  // Write the content to the new window
  printWindow.document.open();
  printWindow.document.write(receiptHTML);
  printWindow.document.close();

  // Wait for the content to load before triggering print
  printWindow.onload = () => {
    printWindow.focus();
    printWindow.print();
    printWindow.close();
  };
};


</script>
