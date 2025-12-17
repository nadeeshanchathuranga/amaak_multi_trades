<template>

    <Head title="POS" />
    <Banner />
    <div class="flex flex-col items-center justify-start min-h-screen py-8 space-y-4 bg-gray-100 md:px-36 px-16">
        <!-- Include the Header -->
        <Header />
        <div class="w-full md:w-5/6 w-full py-12 space-y-16">
            <div class="flex items-center justify-between space-x-4">
                <div class="flex w-full space-x-4">
                    <Link href="/">
                    <img src="/images/back-arrow.png" class="w-14 h-14" />
                    </Link>
                    <p class="pt-3 text-4xl font-bold tracking-wide text-black uppercase">
                        PoS
                    </p>
                </div>
                <div class="flex items-center justify-between w-full space-x-4">
                    <p class="text-3xl font-bold tracking-wide text-black">
                        Order ID : #{{ orderid  }}
                    </p>
                    <p class="text-3xl text-black cursor-pointer">
                        <i @click="refreshData" class="ri-restart-line"></i>
                    </p>
                </div>
            </div>
            <div class="flex md:flex-row flex-col w-full gap-4">
                <div class="flex flex-col md:w-1/2 w-full">
                    <div class="flex flex-col w-full">
                        <div class="p-16 space-y-8 bg-black shadow-lg rounded-3xl">
                            <p class="mb-4 text-5xl font-bold text-white">Customer Details</p>
                            <div v-if="customer" class="mb-3">
                                <input v-model="customer.name" type="text" placeholder="Enter Customer Name"
                                    class="w-full px-4 py-4 text-black placeholder-black bg-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>
                            <div v-if="customer" class="flex gap-2 mb-3 text-black">
                                <input v-model="customer.contactNumber" type="text"
                                    placeholder="Enter Customer Contact Number"
                                    class="flex-grow px-4 py-4 text-black placeholder-black bg-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>
                            <div v-if="customer" class="text-black">
                                <input v-model="customer.email" type="email" placeholder="Enter Customer Email"
                                    class="w-full px-4 py-4 text-black placeholder-black bg-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>

                            <div class="text-black">
                                <select v-model="employee_id" id="employee_id"
                                    class="w-full px-4 py-4 text-black placeholder-black bg-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="" disabled selected>Select an Employee (Optional)</option>
                                    <option v-for="employee in allemployee" :key="employee.id" :value="employee.id">
                                        {{ employee.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-center justify-center w-full md:pt-32 py-8 md:py-0 space-y-8">
                        <img src="/images/Fading wheel.gif" class="object-cover w-32 h-32 rounded-full" />
                        <p class="text-3xl text-black">
                            Bar Code Scanner is in Progress...
                        </p>
                    </div>
                </div>
                <div class="flex md:w-1/2 w-full p-8 border-4 border-black rounded-3xl" :class="{ 'border-red-500 bg-red-50': isReturnMode }">
                    <div class="flex flex-col items-start justify-center w-full md:px-12 px-4">
                        <div class="flex items-center justify-between w-full">
                            <h2 class="md:text-5xl text-4xl font-bold text-black" :class="{ 'text-red-600': isReturnMode }">
                                {{ isReturnMode ? 'Return Mode' : 'Billing Details' }}
                            </h2>

                            <div class="flex items-center space-x-4">
                                <!-- Return Mode Toggle Button -->
                                <button
                                    @click="toggleReturnMode"
                                    :class="[
                                        'px-4 py-2 text-lg font-bold rounded-xl transition-all duration-300',
                                        isReturnMode
                                            ? 'bg-red-600 text-white hover:bg-red-700'
                                            : 'bg-orange-500 text-white hover:bg-orange-600'
                                    ]"
                                >
                                    <i :class="isReturnMode ? 'ri-close-line' : 'ri-exchange-line'" class="mr-2"></i>
                                    {{ isReturnMode ? 'Exit Return Mode' : 'Return Mode' }}
                                </button>

                                <span class="flex cursor-pointer" @click="isSelectModalOpen = true">
                                    <p class="text-xl text-blue-600 font-bold">User Manual</p>
                                    <img src="/images/selectpsoduct.svg" class="w-6 h-6 ml-2" />
                                </span>
                            </div>
                        </div>

                        <!-- Return Mode Banner -->
                        <div v-if="isReturnMode" class="w-full mt-4 p-4 bg-red-100 border-2 border-red-500 rounded-xl">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <i class="ri-exchange-funds-line text-3xl text-red-600"></i>
                                    <div>
                                        <p class="text-xl font-bold text-red-700">Return Mode Active</p>
                                        <p class="text-sm text-red-600">Select an order to process returns. Updates will be applied to the same order.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Return Mode Order Selection -->
                        <div v-if="isReturnMode && !returnModeSelectedSale" class="w-full mt-4 p-6 bg-white border-2 border-gray-300 rounded-xl shadow-lg">
                            <p class="text-xl font-bold text-gray-800 mb-4">
                                <i class="ri-search-line mr-2"></i>Select Order to Process Return
                            </p>

                            <!-- Search Input -->
                            <div class="mb-4">
                                <input
                                    v-model="returnModeSearchQuery"
                                    type="text"
                                    placeholder="Search by Order ID or Customer Name..."
                                    class="w-full h-14 px-4 text-lg border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                />
                            </div>

                            <!-- Orders List -->
                            <div class="max-h-80 overflow-y-auto border border-gray-200 rounded-lg">
                                <div
                                    v-for="sale in filteredSalesForReturnMode"
                                    :key="sale.id"
                                    @click="loadOrderForReturnMode(sale.id)"
                                    class="p-4 border-b border-gray-200 cursor-pointer hover:bg-red-50 transition-colors"
                                >
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-bold text-lg text-gray-800">{{ sale.order_id }}</p>
                                            <p class="text-sm text-gray-600">{{ sale.customer?.name || 'Walk-in Customer' }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-lg text-green-600">{{ sale.total_amount }} LKR</p>
                                            <p class="text-sm text-gray-500">{{ sale.sale_date }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="filteredSalesForReturnMode.length === 0" class="p-4 text-center text-gray-500">
                                    No orders found
                                </div>
                            </div>
                        </div>

                        <!-- Selected Order Info in Return Mode -->
                        <div v-if="isReturnMode && returnModeSelectedSale" class="w-full mt-4 p-4 bg-blue-50 border-2 border-blue-300 rounded-xl">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-xl font-bold text-blue-800">
                                        <i class="ri-file-list-3-line mr-2"></i>Order: {{ returnModeSelectedSale.order_id }}
                                    </p>
                                    <p class="text-sm text-blue-600">Customer: {{ returnModeSelectedSale.customer?.name || 'Walk-in Customer' }}</p>
                                    <p class="text-sm text-blue-600">Employee: {{ selectedSaleEmployee?.name || 'N/A' }}</p>
                                    <p class="text-sm text-blue-600">Original Total: {{ returnModeSelectedSale.total_amount }} LKR</p>
                                </div>
                                <button
                                    @click="returnModeSelectedSale = null; returnModeLoadedItems = []; returnItems = [];"
                                    class="px-3 py-1 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                                >
                                    <i class="ri-refresh-line mr-1"></i>Change Order
                                </button>
                            </div>
                        </div>

                        <!-- Available Items for Return in Return Mode -->
                        <div v-if="isReturnMode && returnModeSelectedSale && returnModeLoadedItems.length > 0" class="w-full mt-4 p-4 bg-gray-50 border-2 border-gray-300 rounded-xl">
                            <p class="text-lg font-bold text-gray-800 mb-3">
                                <i class="ri-shopping-bag-line mr-2"></i>Available Items for Return
                            </p>
                            <div class="max-h-60 overflow-y-auto">
                                <div
                                    v-for="item in returnModeLoadedItems"
                                    :key="'loaded-' + item.id"
                                    class="flex items-center justify-between p-3 mb-2 bg-white border rounded-lg"
                                    :class="{ 'border-green-500 bg-green-50': item.is_selected_for_return }"
                                >
                                    <div class="flex items-center space-x-3">
                                        <img :src="item.product?.image ? `/${item.product.image}` : '/images/placeholder.jpg'"
                                            alt="Product" class="w-12 h-12 object-cover rounded" />
                                        <div>
                                            <p class="font-semibold text-gray-800">{{ item.product?.name }}</p>
                                            <p class="text-sm text-gray-600">
                                                Qty: {{ item.remaining_quantity }} available
                                            </p>
                                            <div class="flex items-center space-x-2 text-sm">
                                                <span v-if="item.discount && item.discount > 0" class="text-gray-400 line-through">
                                                    {{ item.product?.selling_price }} LKR
                                                </span>
                                                <span class="font-bold text-green-600">@ {{ item.unit_price }} LKR</span>
                                                <span v-if="item.discount && item.discount > 0" class="px-1.5 py-0.5 bg-orange-100 text-orange-700 text-xs rounded">
                                                    Discount Applied
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        v-if="!item.is_selected_for_return"
                                        @click="selectItemForReturn(item)"
                                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                                    >
                                        <i class="ri-add-line mr-1"></i>Add to Return
                                    </button>
                                    <button
                                        v-else
                                        @click="deselectItemFromReturn(item.id)"
                                        class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
                                    >
                                        <i class="ri-subtract-line mr-1"></i>Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- P2P Products Section in Return Mode -->
                        <div v-if="isReturnMode && returnItems.some(item => item.return_type === 'p2p')" class="w-full mt-4 p-4 bg-green-50 border-2 border-green-300 rounded-xl">
                            <p class="text-lg font-bold text-green-800 mb-2">
                                <i class="ri-swap-line mr-2"></i>P2P Exchange Products
                            </p>
                            <p class="text-sm text-green-700 mb-3">Add replacement products using User Manual or barcode scanner</p>
                            <div v-if="products.length === 0" class="text-center py-4 text-green-600">
                                <i class="ri-shopping-cart-line text-3xl"></i>
                                <p class="mt-2">No replacement products added yet</p>
                            </div>
                        </div>

                        <!-- <div class="flex items-center justify-between w-full">
                            <button >Return Bills</button>
                        </div> -->

                        <!-- Barcode Input - Changes context based on mode -->
                        <div v-if="!isReturnMode || (isReturnMode && returnModeSelectedSale && returnItems.some(item => item.return_type === 'p2p'))"
                            class="flex items-end justify-between w-full my-5 border-2 rounded-2xl"
                            :class="isReturnMode ? 'border-green-500 bg-green-50' : 'border-black'">
                            <div class="flex items-center justify-center w-3/4">
                                <label for="search" class="text-xl font-medium text-gray-800"></label>
                                <input v-model="form.barcode" id="search" type="text"
                                    :placeholder="isReturnMode ? 'Scan/Enter Replacement Product BarCode' : 'Enter BarCode Here!'"
                                    @keyup.enter="submitBarcode"
                                    class="w-full h-16 px-4 rounded-l-2xl focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            </div>
                            <div class="flex items-end justify-end w-1/4">
                                <button @click="submitBarcode"
                                    :class="[
                                        'px-12 py-4 text-2xl font-bold tracking-wider text-white uppercase rounded-r-xl',
                                        isReturnMode ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600'
                                    ]">
                                    {{ isReturnMode ? 'Add' : 'Enter' }}
                                </button>
                            </div>
                        </div>

                        <div class="w-full text-center">
                            <p v-if="products.length === 0 && !isReturnMode" class="text-2xl text-red-500">
                                No Products to show
                            </p>
                            <p v-if="products.length === 0 && isReturnMode && returnModeSelectedSale && returnItems.some(item => item.return_type === 'p2p')" class="text-xl text-green-600">
                                <i class="ri-information-line mr-2"></i>Add replacement products for P2P exchange above
                            </p>
                        </div>

                        <!-- Return Items Section -->
                        <div v-for="item in returnItems" :key="'return-' + item.id"
                            class="flex items-center w-full py-4 border-b-2 border-red-500 bg-red-50">
                            <div class="flex w-1/6">
                                <img :src="item.product?.image ? `/${item.product.image}` : '/images/placeholder.jpg'"
                                    alt="Product Image" class="object-cover w-16 h-16 border border-gray-500" />
                            </div>
                            <div class="flex flex-col justify-between w-5/6">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <p class="text-xl text-red-600 font-bold">RETURN: {{ item.product?.name }}</p>
                                            <span class="px-2 py-1 bg-red-600 text-white text-xs rounded">Return Item</span>
                                        </div>
                                        <span v-if="item.return_type"
                                            :class="[
                                                'px-3 py-1 text-sm font-bold rounded-lg',
                                                item.return_type === 'cash'
                                                    ? 'bg-green-100 text-green-800 border-2 border-green-600'
                                                    : 'bg-blue-100 text-blue-800 border-2 border-blue-600'
                                            ]">
                                            {{ item.return_type === 'cash' ? '💵 CASH RETURN' : '🔄 P2P RETURN' }}
                                        </span>
                                    </div>

                                    <!-- Return Type Selection -->
                                    <div class="flex items-center space-x-4 mt-2 p-3 bg-gray-50 rounded-lg border-2 border-gray-300">
                                        <label class="text-lg font-semibold text-gray-700">Return Type:<span class="text-red-500">*</span></label>
                                        <select v-model="item.return_type" @change="handleReturnTypeChange(item)" required
                                            class="px-4 py-2 border-2 border-gray-400 rounded-lg text-lg font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                            <option value="" disabled>Select Return Type</option>
                                            <option value="cash">💵 Cash Return</option>
                                            <option value="p2p">🔄 Product-to-Product (P2P)</option>
                                        </select>
                                    </div>

                                    <!-- P2P Instruction -->
                                    <div v-if="item.return_type === 'p2p'" class="mt-3 p-3 bg-blue-50 border border-blue-300 rounded-lg">
                                        <p class="text-sm font-semibold text-blue-900 mb-1">📝 For Product-to-Product Exchange:</p>
                                        <p class="text-sm text-gray-700">Please add the new product manually using the <span class="font-bold text-blue-600">"User Manual"</span> button above.</p>
                                    </div>

                                    <!-- Quantity and Price Controls -->
                                    <div class="flex items-center justify-between w-full mt-3">
                                        <div class="flex items-center space-x-4">
                                            <span class="text-sm text-gray-600">Max: {{ item.remaining_quantity }}</span>
                                            <div class="flex space-x-2">
                                                <p @click="decrementReturnItemQuantity(item)"
                                                    class="flex items-center justify-center w-8 h-8 text-white bg-red-600 rounded cursor-pointer">
                                                    <i class="ri-subtract-line"></i>
                                                </p>
                                                <input type="number" v-model.number="item.return_quantity" min="1" :max="item.remaining_quantity"
                                                    class="w-16 px-2 text-center border-2 border-black rounded" />
                                                <p @click="incrementReturnItemQuantity(item)"
                                                    class="flex items-center justify-center w-8 h-8 text-white bg-red-600 rounded cursor-pointer">
                                                    <i class="ri-add-line"></i>
                                                </p>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span v-if="item.original_discount && item.original_discount > 0" class="text-sm text-gray-400 line-through">
                                                    {{ item.product?.selling_price || item.original_unit_price }} LKR
                                                </span>
                                                <span class="text-lg font-bold text-green-700">@ {{ item.unit_price }} LKR</span>
                                                <span v-if="item.original_discount && item.original_discount > 0" class="px-1.5 py-0.5 bg-orange-100 text-orange-700 text-xs rounded">
                                                    Discounted
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-2xl font-bold text-red-600">-{{ (item.return_quantity * item.unit_price).toFixed(2) }} LKR</p>
                                        </div>
                                    </div>

                                    <!-- Return Details -->
                                    <div class="grid grid-cols-2 gap-2 mt-2">
                                        <input v-model="item.reason" type="text" placeholder="Reason for return (required)"
                                            class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                        <input v-model="item.return_date" type="date"
                                            class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end w-1/6">
                                <p @click="removeReturnItem(item)"
                                    class="text-3xl text-red-600 border-2 border-red-600 rounded-full cursor-pointer hover:bg-red-600 hover:text-white">
                                    <i class="ri-close-line"></i>
                                </p>
                            </div>
                        </div>

                        <!-- P2P Replacement Products Header (only in return mode with products) -->
                        <div v-if="isReturnMode && products.length > 0" class="w-full mt-4 mb-2 p-3 bg-green-100 border-2 border-green-500 rounded-lg">
                            <p class="text-lg font-bold text-green-800">
                                <i class="ri-exchange-line mr-2"></i>Replacement Products (P2P Exchange)
                            </p>
                        </div>

                        <!-- Regular Products Section -->
                        <div class="flex items-center w-full py-4 border-b" v-for="item in products"
                            :key="item.id"
                            :class="isReturnMode ? 'border-green-500 bg-green-50' : 'border-black'">
                            <div class="flex w-1/6">
                                <img :src="item.image ? `/${item.image}` : '/images/placeholder.jpg'
                                    " alt="Supplier Image" class="object-cover w-16 h-16 border border-gray-500" />
                            </div>
                            <div class="flex flex-col justify-between w-5/6">
                          <!-- 2-column layout (left details, right actions/price) -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">

  <!-- LEFT -->
  <div class="space-y-2">
    <p class="text-xl font-semibold" :class="isReturnMode ? 'text-green-800' : 'text-black'">
      {{ item.name }}
      <span v-if="isReturnMode" class="ml-2 px-2 py-1 text-xs bg-green-600 text-white rounded">NEW</span>
    </p>

    <p class="text-xl text-black">
      <span class="font-semibold">Selling Price:</span>
      <span class="font-bold">{{ item.selling_price }}</span>
    </p>

    <p class="text-xl text-black">
      <span class="font-semibold">Cost Price:</span>
      <span class="font-bold">{{ item.cost_price }}</span>
    </p>

    <p
      v-if="item.unit_id"
      class="flex items-center gap-2 text-black text-xl"
    >
      <span class="text-base font-normal text-gray-500">Unit:</span>
      <span class="px-2 py-0.5 text-black font-bold">
        {{ item.unit?.name || '' }}
      </span>
    </p>
  </div>

  <!-- RIGHT -->
  <div class="flex justify-end">
    <div class="space-y-2 text-right md:border-l md:pl-6">
      <p
        v-if="
          item.discount &&
          item.discount > 0 &&
          item.apply_discount == false &&
          !appliedCoupon
        "
        @click="applyDiscount(item.id)"
        class="cursor-pointer py-1 px-1 bg-green-600 rounded-xl font-bold text-white tracking-wider inline-block"
      >
        Apply {{ item.discount }}% off
      </p>

      <p
        v-if="
          item.discount &&
          item.discount > 0 &&
          item.apply_discount == true &&
          !appliedCoupon
        "
        @click="removeDiscount(item.id)"
        class="cursor-pointer py-1 px-1 bg-red-600 rounded-xl font-bold text-white tracking-wider inline-block"
      >
        Remove {{ item.discount }}% Off
      </p>


    </div>
  </div>

</div>



                                <div class="flex items-center justify-between w-full">

                                    <div class="flex space-x-4">
                                        <p @click="incrementQuantity(item.id)"
                                            class="flex items-center justify-center w-8 h-8 text-white bg-black rounded cursor-pointer">
                                            <i class="ri-add-line"></i>
                                        </p>
                                        <!-- <p
                      class="bg-[#D9D9D9] border-2 border-black h-8 w-8 text-black flex justify-center items-center rounded"
                    >
                      {{ item.quantity }}
                    </p> -->
                                        <!-- <input type="number" v-model="item.quantity" min="0"
                                            class="bg-[#D9D9D9] border-2 border-black h-8 w-24 text-black flex justify-center items-center rounded text-center" /> -->

 <input
                                            type="number"
                                            v-model.number="item.quantity"
                                            min="0"
                                            step="0.1"
                                            @input="updateItemTotal(item)"
                                            class="bg-[#D9D9D9] border-2 border-black h-8 w-24 text-black text-center rounded"
                                            />



                                        <p @click="decrementQuantity(item.id)"
                                            class="flex items-center justify-center w-8 h-8 text-white bg-black rounded cursor-pointer">
                                            <i class="ri-subtract-line"></i>
                                        </p>



                                    </div>
                                    <div class="flex items-center justify-center">
                                        <div>



 <div class="flex items-center space-x-2">
  <!-- Discount value -->



  <input
    type="number"
    v-model.number="item.discount"
    min="0"
    placeholder="Value"
    @input="onDiscountChange(item)"
    class="w-24 h-12 px-3 py-2 text-lg text-black text-center
           border border-gray-400 rounded-lg shadow-sm
           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
  />

  <!-- Discount type -->
  <select
    v-model="item.discount_type"
    @change="onDiscountChange(item)"
    class="h-12 px-6 border border-gray-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
  >
    <option value="percent">%</option>
    <option value="fixed">Rs</option>
  </select>
</div>







                                            <p class="text-2xl font-bold text-black text-right">
                                               {{ item.apply_discount ? item.discounted_price : item.selling_price }} LKR
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end w-1/6">
                                <p @click="removeProduct(item.id)"
                                    class="text-3xl text-black border-2 border-black rounded-full cursor-pointer">
                                    <i class="ri-close-line"></i>
                                </p>
                            </div>
                        </div>
                        <div class="w-full pt-6 space-y-2">
                            <!-- Return Mode Summary Header -->
                            <div v-if="isReturnMode && returnModeSelectedSale" class="flex items-center justify-between w-full px-8 py-3 bg-blue-100 rounded-lg mb-2">
                                <p class="text-xl font-bold text-blue-800">Original Order: {{ returnModeSelectedSale.order_id }}</p>
                                <p class="text-xl font-bold text-blue-800">{{ returnModeSelectedSale.total_amount }} LKR</p>
                            </div>

                            <!-- Original Order Discount Info (in Return Mode) -->
                            <div v-if="isReturnMode && returnModeSelectedSale && (originalOrderDiscount > 0 || originalOrderCustomDiscount > 0)"
                                class="w-full px-8 py-3 bg-purple-50 border border-purple-200 rounded-lg mb-2">
                                <p class="text-lg font-bold text-purple-800 mb-2">
                                    <i class="ri-price-tag-3-line mr-2"></i>Original Order Discounts Applied
                                </p>
                                <div class="flex flex-col space-y-1 text-sm text-purple-700">
                                    <div v-if="originalOrderDiscount > 0" class="flex justify-between">
                                        <span>Product Discounts:</span>
                                        <span class="font-semibold">{{ originalOrderDiscount }} LKR</span>
                                    </div>
                                    <div v-if="originalOrderCustomDiscount > 0" class="flex justify-between">
                                        <span>Custom Discount:</span>
                                        <span class="font-semibold">{{ originalOrderCustomDiscount }} LKR</span>
                                    </div>
                                    <div class="flex justify-between border-t border-purple-300 pt-1 mt-1">
                                        <span class="font-bold">Total Original Discounts:</span>
                                        <span class="font-bold">{{ (parseFloat(originalOrderDiscount) + parseFloat(originalOrderCustomDiscount)).toFixed(2) }} LKR</span>
                                    </div>
                                </div>
                                <p class="text-xs text-purple-600 mt-2 italic">
                                    Note: Return prices already reflect the discounted amounts paid
                                </p>
                            </div>

                            <div class="flex items-center justify-between w-full px-8">
                                <p class="text-xl">{{ isReturnMode ? 'New Products Total' : 'Sub Total' }}</p>
                                <p class="text-xl">{{ subtotal }} LKR</p>
                            </div>
                            <div class="flex items-center justify-between w-full px-8 py-2 pb-4 border-b border-black">
                                <p class="text-xl">Discount</p>
                                <p class="text-xl">( {{ totalDiscount }} LKR )</p>
                            </div>
                            <div v-if="returnItems.length > 0" class="flex items-center justify-between w-full px-8 py-2 pb-4 border-b border-black bg-red-50">
                                <p class="text-xl text-red-600 font-bold">Return Amount</p>
                                <p class="text-xl text-red-600 font-bold">( {{ returnBillTotal.toFixed(2) }} LKR )</p>
                            </div>

                            <!-- Custom Discount Display for Return Mode -->
                            <div v-if="isReturnMode && custom_discount > 0" class="flex items-center justify-between w-full px-8 py-2 pb-4 border-b border-black bg-orange-50">
                                <p class="text-xl text-orange-600 font-bold">
                                    Return Discount {{ custom_discount_type === 'percent' ? `(${custom_discount}%)` : '' }}
                                </p>
                                <p class="text-xl text-orange-600 font-bold">( {{ customDiscountValue.toFixed(2) }} LKR )</p>
                            </div>

                            <!-- Return Mode Net Amount -->
                            <div v-if="isReturnMode && returnItems.length > 0" class="flex items-center justify-between w-full px-8 py-3 bg-yellow-50 border-2 border-yellow-400 rounded-lg">
                                <p class="text-xl font-bold text-yellow-800">
                                    <i class="ri-calculator-line mr-2"></i>Net {{ netReturnAmount >= 0 ? 'Receivable' : 'Refundable' }}
                                </p>
                                <p class="text-xl font-bold" :class="netReturnAmount >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ Math.abs(netReturnAmount).toFixed(2) }} LKR
                                </p>
                            </div>
                            <!-- <div class="flex items-center justify-between w-full px-8 pt-4 pb-4 border-b border-black">
                <p class="text-xl text-black">Custom Discount</p>
                <span>
                  <CurrencyInput
                    v-model="custom_discount"
                  />
                  <span class="ml-2">LKR</span>
                </span>
              </div> -->

                            <div class="flex items-center justify-between w-full px-8 pt-4 pb-4 border-b border-black">
                                <p class="text-xl text-black">{{ isReturnMode ? 'Return Discount' : 'Custom Discount' }}</p>
                                <span class="flex items-center">
                                    <CurrencyInput v-model="custom_discount" @blur="validateCustomDiscount"
                                        placeholder="Enter value" class=" rounded-md px-2 py-1 text-black text-md" />
                                    <select v-model="custom_discount_type"
                                        class="ml-2 px-8 border-black rounded-md text-black   py-1 text-md  ">
                                        <option value="percent">%</option>
                                        <option value="fixed">Rs</option>
                                    </select>
                                </span>
                            </div>
                            <div class="flex items-center justify-between w-full px-8 pt-4 pb-4 border-b border-black">
                                <p class="text-xl text-black">Cash</p>
                                <span>
                                    <CurrencyInput v-model="cash" :options="{ currency: 'EUR' }" />
                                    <span class="ml-2">LKR</span>
                                </span>
                            </div>
                            <div v-if="selectedPaymentMethod === 'Koko'" class="flex items-center justify-between w-full px-8 pt-4 pb-4 border-b border-black">
                                <p class="text-xl text-black">Koko Surcharge (11.5%)</p>
                                <p class="text-xl">{{ kokoSurcharge }} LKR</p>
                            </div>
                            <div class="flex items-center justify-between w-full px-8 pt-4">
                                <p class="text-3xl text-black">Total</p>
                                <div class="text-right">
                                    <p v-if="isReturnMode && (custom_discount > 0 || totalDiscount > 0)" class="text-lg text-gray-400 line-through">
                                        {{ (subtotal - returnBillTotal).toFixed(2) }} LKR
                                    </p>
                                    <p class="text-3xl text-black font-bold">{{ total }} LKR</p>
                                </div>
                            </div>


                            <div class="flex items-center justify-between w-full px-8 pt-4 pb-4 border-b border-black">
                                <p class="text-xl text-black">Balance</p>
                                <p>{{ balance }} LKR</p>
                            </div>
                        </div>

                        <!-- Coupon Section - Hidden in Return Mode -->
                        <div v-if="!isReturnMode" class="w-full my-5">
                            <div class="relative flex items-center">
                                <!-- Input Field -->
                                <label for="coupon" class="sr-only">Coupon Code</label>
                                <input id="coupon" v-model="couponForm.code" type="text" placeholder="Enter Coupon Code"
                                    class="w-full h-16 px-6 pr-40 text-lg text-gray-800 placeholder-gray-500 border-2 border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                                <template v-if="!appliedCoupon">
                                    <button type="button" @click="submitCoupon"
                                        class="absolute right-2 top-2 h-12 px-6 text-lg font-semibold text-white uppercase bg-blue-600 rounded-full hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        Apply Coupon
                                    </button>
                                </template>
                                <template v-else>
                                    <button type="button" @click="removeCoupon"
                                        class="absolute right-2 top-2 h-12 px-6 text-lg font-semibold text-white uppercase bg-red-600 rounded-full hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                        Remove Coupon
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="flex flex-col w-full space-y-8">
                            <!-- Hide payment method and credit bill options in return mode -->
                            <div v-if="!isReturnMode" class="flex items-center justify-center w-full pt-8 space-x-8">
                                <p class="text-xl text-black">Payment Method :</p>
                                <div @click="selectPaymentMethod('cash')" :class="[
                                    'cursor-pointer w-[100px]  border border-black rounded-xl flex flex-col justify-center items-center text-center',
                                    selectedPaymentMethod === 'cash'
                                        ? 'bg-yellow-500 font-bold'
                                        : 'text-black',
                                ]">
                                    <img src="/images/money-stack.png" alt="" class="w-24" />
                                </div>
                                <div @click="selectPaymentMethod('card')" :class="[
                                    'cursor-pointer w-[100px] border border-black rounded-xl flex flex-col justify-center items-center text-center',
                                    selectedPaymentMethod === 'card'
                                        ? 'bg-yellow-500 font-bold'
                                        : 'text-black',
                                ]">
                                    <img src="/images/bank-card.png" alt="" class="w-24" />
                                </div>
                                <div @click="selectPaymentMethod('Koko')" :class="[
                                    'cursor-pointer w-[100px] border border-black rounded-xl flex flex-col justify-center items-center text-center',
                                    selectedPaymentMethod === 'Koko'
                                        ? 'bg-yellow-500 font-bold'
                                        : 'text-black',
                                ]">
                                    <img src="/images/koko-logo.png" alt="Koko Payment" class="w-24" />
                                </div>
                            </div>

                            <!-- Credit Bill Checkbox - Hidden in Return Mode -->
                            <div v-if="!isReturnMode" class="flex items-center justify-center w-full pt-4">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        v-model="isCreditBill"
                                        @change="handleCreditBillChange"
                                        class="w-5 h-5 text-blue-600 border-2 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                                    />
                                    <span class="text-lg font-semibold text-black">Credit Bill</span>
                                </label>
                            </div>

                            <div class="flex items-center justify-center w-full">
                                <!-- Return Mode Submit Button -->
                                <button v-if="isReturnMode"
                                    @click="submitReturnModeOrder"
                                    type="button"
                                    :disabled="returnItems.length === 0"
                                    :class="[
                                        'w-full py-4 text-2xl font-bold tracking-wider text-center text-white uppercase rounded-xl transition-colors',
                                        returnItems.length === 0
                                            ? 'bg-gray-400 cursor-not-allowed'
                                            : 'bg-red-600 hover:bg-red-700 cursor-pointer',
                                    ]"
                                >
                                    <i class="pr-4 ri-exchange-funds-line"></i> Process Return
                                </button>
                                <!-- Regular Order Submit Button -->
                                <button v-else @click="() => {
                                    submitOrder();
                                }
                                    " type="button" :disabled="products.length === 0 && returnItems.length === 0" :class="[
                                        'w-full bg-black py-4 text-2xl font-bold tracking-wider text-center text-white uppercase rounded-xl',
                                        (products.length === 0 && returnItems.length === 0)
                                            ? ' cursor-not-allowed'
                                            : ' cursor-pointer',
                                    ]">
                                    <i class="pr-4 ri-add-circle-fill"></i> Confirm Order
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <PosSuccessModel :open="isSuccessModalOpen" @update:open="handleModalOpenUpdate" :products="isReturnBillPrinted ? modalProducts : products"
        :employee="modalEmployee" :cashier="loggedInUser" :customer="modalCustomer" :orderid="actualOrderId || orderid" :cash="cash"
        :balance="balance" :subTotal="subtotal" :totalDiscount="totalDiscount" :total="total"
        :custom_discount_type="custom_discount_type"
        :custom_discount="custom_discount" :paymentMethod="selectedPaymentMethod" :kokoSurcharge="kokoSurcharge"
        :returnAmount="returnAmount" :newProductAmount="newProductAmount" />
    <AlertModel v-model:open="isAlertModalOpen" :message="message" />

    <SelectProductModel v-model:open="isSelectModalOpen" :allcategories="allcategories" :colors="colors" :sizes="sizes" :suppliers="suppliers"
        @selected-products="handleSelectedProducts" />

    <Footer />

    <template v-if="isReturnBillsModalOpen">
     <!-- Return Bill Form -->
     <div class="fixed inset-0 z-50 flex items-start justify-center bg-black bg-opacity-30 overflow-auto">
        <div class="bg-white mt-20 p-20 rounded-lg shadow-lg w-full max-w-7xl">
                   <!-- Modal Header with Close Button -->
                   <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Return Bills</h2>
                        <button
                            type="button"
                            @click="closeReturnModal"
                            class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-2 transition-colors duration-200"
                            aria-label="Close modal"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                   </div>
                   <!-- <form @submit.prevent="submitReturnBill"> -->
                    <div error v-if="errorMessage" class="mb-4 flex items-center justify-between text-red-600 font-medium text-center text-2xl bg-red-100 rounded-lg border border-black-300 px-4 py-2">
                    {{ errorMessage }}<button type="button" class=" text-black text-align:right" @click="errorMessage=''">X</button>
                    </div>
                    <div class="space-y-6">
                        <!-- Order Code Dropdown -->
                        <div class="flex flex-col">
                            <label for="order_id" class="text-xl font-medium text-gray-700">Order Code</label>
                            <select id="order_id" v-model="ReturnbillForm.order_id"
                                class="mt-2 p-4 border border-gray-300 rounded-lg text-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                                <option value="" disabled>Select an order</option>
                                <option v-for="sale in props.sales" :key="sale.id" :value="sale.id">
                                    {{ sale.order_id }}
                                </option>
                            </select>
                            <p v-if="ReturnbillForm.errors.order_id" class="text-red-500 text-sm mt-1">{{ ReturnbillForm.errors.order_id }}
                            </p>
                        </div>
                        <!-- Display Selected Order Details -->
                        <div v-if="selectedSale" class="mt-6 p-4 border rounded-lg bg-gray-50">
                            <p class="text-lg font-medium">Selected Order Details:</p>
                            <div class="mt-4 space-y-2">
                                <p><span class="font-bold">Order ID:</span> {{ selectedSale.order_id }}</p>
                                <p><span class="font-bold">Customer Name:</span> {{ selectedSale?.customer?.name || 'N/A' }}</p>
                                <p><span class="font-bold">Employee:</span> {{ selectedSaleEmployee?.name || 'N/A' }}</p>
                                <p><span class="font-bold">Total Amount:</span> {{ selectedSale.total_amount }}</p>
                                <p><span class="font-bold">Discount:</span> {{ selectedSale.discount }}</p>
                                <p><span class="font-bold">Payment Method:</span> {{ selectedSale.payment_method }}</p>
                                <p><span class="font-bold">Sale Date:</span> {{ selectedSale.sale_date }}</p>
                            </div>
                        </div>
                        <!-- Display Sale Items -->
                        <div v-if="filteredSaleItems.length" class="mt-6 p-4 border rounded-lg bg-gray-50">
                            <p class="text-lg font-medium">Items in this Sale:</p>
                            <div class="mt-4 max-h-96 overflow-y-auto">
                            <table class="mt-4 w-full border-collapse border border-gray-200">
                                <thead>
                                    <tr>
                                        <th class="border border-gray-300 px-4 py-2">Product</th>
                                        <th class="border border-gray-300 px-4 py-2">Max Quantity</th>
                                        <th class="border border-gray-300 px-4 py-2">Unit Price</th>
                                        <th class="border border-gray-300 px-4 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in filteredSaleItems" :key="item.id">
                                        <td class="border border-gray-300 px-4 py-2">
                                            <div class="pb-2">{{ item.product.name }}</div>
                                            <img :src="item.product.image ? `/${item.product.image}` : '/images/placeholder.jpg'"
                                                alt="Product Image" class="w-20 h-20 object-cover rounded-lg" />
                                        </td>
                                        <td class="border border-gray-300 px-4 py-2">
                                            <p class="text-lg font-semibold">{{ item.remaining_quantity || item.quantity }}</p>
                                            <p class="text-sm text-gray-600">Available</p>
                                        </td>
                                        <td class="border border-gray-300 px-4 py-2">{{ item.unit_price }} LKR</td>
                                        <td class="border border-gray-300 px-4 py-2">
                                            <button @click="addReturnItemToBilling(item)"
                                                class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                                Add to Return
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            </div>
                        </div>
                        <!-- Instructions -->
                        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-lg font-medium text-blue-900">Instructions:</p>
                            <ol class="mt-2 ml-4 list-decimal text-blue-800">
                                <li>Select products to return and click "Add to Return"</li>
                                <li>Close this dialog</li>
                                <li>Configure return details in the Billing Details section</li>
                                <li>Click "Confirm Order" to process the return</li>
                            </ol>
                        </div>

                        <div class="flex justify-center gap-between w-full space-x-8">
                            <button type="button" @click="closeReturnModal"
                                    class="px-8 py-3 text-lg font-bold tracking-wider text-white uppercase bg-blue-600 rounded-xl hover:bg-blue-700 focus:outline-none">
                                    Done
                            </button>
                        </div>
                    </div>
                <!-- </form> -->
            </div>
     </div>
    </template>
</template>

<script setup>
import Header from "@/Components/custom/Header.vue";
import Footer from "@/Components/custom/Footer.vue";
import Banner from "@/Components/Banner.vue";
import PosSuccessModel from "@/Components/custom/PosSuccessModel.vue";
import AlertModel from "@/Components/custom/AlertModel.vue";
import { useForm, router } from "@inertiajs/vue3";
import { ref, onMounted, computed, watch, nextTick } from "vue";
import { Head } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import axios from "axios";
import CurrencyInput from "@/Components/custom/CurrencyInput.vue";
import SelectProductModel from "@/Components/custom/SelectProductModel.vue";
import ProductAutoComplete from "@/Components/custom/ProductAutoComplete.vue";
import { generateOrderId } from "@/Utils/Other.js";

// import ReturnSuccessModel from "@/Pages/ReturnItem/Index.vue";

const product = ref(null);
const returnReasons = ref(null);
const error = ref(null);
const products = ref([]);
const isSuccessModalOpen = ref(false);
const isAlertModalOpen = ref(false);
const isReturnBillsModalOpen = ref(false);
const isReturnBillPrinted = ref(false); // Track if this is a return bill print modal
const message = ref("");
const appliedCoupon = ref(null);
const cash = ref(0);
const custom_discount = ref(0);
const isSelectModalOpen = ref(false);
// const isSelectChequeModalOpen = ref(false);
const custom_discount_type = ref('percent');
const orderid = computed(() => generateOrderId());
const actualOrderId = ref(''); // For storing actual order IDs (returns, etc.)

const errorMessage = ref("");

// Return Mode State
const isReturnMode = ref(false);
const returnModeOrderId = ref('');
const returnModeSelectedSale = ref(null);
const returnModeSearchQuery = ref('');
const returnModeLoadedItems = ref([]); // Items loaded from original order for return processing
const originalOrderDiscount = ref(0); // Original order-level discount
const originalOrderCustomDiscount = ref(0); // Original custom discount from order

const clamp = (num, min, max) => Math.min(Math.max(num, min), max);

const onDiscountChange = (item) => {
  // set & lock the original price
  if (!item.original_price) {
    item.original_price = Number(item.selling_price);
  }

  const original = Number(item.original_price) || 0;

  // normalize missing fields
  if (!item.discount_type) item.discount_type = 'percent';

  let d = Number(item.discount);
  if (Number.isNaN(d)) d = 0;

  if (item.discount_type === 'percent') {
    // 0–100%
    d = clamp(d, 0, 100);
    item.discount = d;
    if (d === 0) {
      item.apply_discount = false;
      item.discounted_price = original;
    } else {
      item.apply_discount = true;
      const price = original * (1 - d / 100);
      item.discounted_price = Number(price.toFixed(2));
    }
  } else {
    // fixed Rs: 0–original
    d = clamp(d, 0, original);
    item.discount = d;
    if (d === 0) {
      item.apply_discount = false;
      item.discounted_price = original;
    } else {
      item.apply_discount = true;
      const price = original - d;
      item.discounted_price = Number(price.toFixed(2));
    }
  }
};





// const balance = ref(0);

const handleModalOpenUpdate = (newValue) => {
    isSuccessModalOpen.value = newValue;
    if (!newValue) {
        actualOrderId.value = ''; // Clear the actual order ID
        returnAmount.value = 0; // Clear return amount
        newProductAmount.value = 0; // Clear new product amount
        // Always refresh data after modal closes to update dashboard/report
        // This includes both regular sales and return bills
        if (!isReturnBillPrinted.value) {
            refreshData();
        } else {
            // For return bills, we still need to refresh after the print modal closes
            // to update the dashboard with the return information
            refreshData();
            isReturnBillPrinted.value = false; // Reset the flag for next transaction
        }
    }
};

const props = defineProps({
    loggedInUser: Object, // Using backend product name to avoid messing with selected products
    allcategories: Array,
    allemployee: Array,
    colors: Array,
    sizes: Array,
    sales:Array,
    saleItems: { // Add this prop
        type: Array,
        default: () => []
    },
    products: { // Add products prop
        type: Array,
        default: () => []
    },
    suppliers: { type: Array, default: () => [] },
});

const sales = ref([]);
const saleItemsState = ref([]);
const selectedSaleEmployee = ref(null);
const returnItems = ref([]);

const handleReturnTypeChange = (item) => {
    // P2P returns will be handled by manually adding products
    // No need to clear fields as they won't be used
};

// P2P exchange products are now added via User Manual Product Add
// These helper functions are no longer needed

const addReturnItemToBilling = (saleItem) => {
    // Check if already added
    const existing = returnItems.value.find(r => r.sale_item_id === saleItem.id);
    if (existing) {
        errorMessage.value = "This item is already added for return";
        return;
    }

    // Add to return items
    returnItems.value.push({
        id: saleItem.id,
        sale_item_id: saleItem.id,
        product_id: saleItem.product_id,
        product: saleItem.product,
        unit_price: saleItem.unit_price,
        return_quantity: 1,
        remaining_quantity: saleItem.remaining_quantity || saleItem.quantity,
        return_type: 'cash',
        reason: '',
        return_date: new Date().toISOString().split('T')[0],
        sale_id: ReturnbillForm.order_id,
    });
};

const removeReturnItem = (item) => {
    returnItems.value = returnItems.value.filter(r => r.id !== item.id);
    // If in return mode and all items removed, also clear from returnModeLoadedItems
    if (isReturnMode.value) {
        const idx = returnModeLoadedItems.value.findIndex(i => i.id === item.id);
        if (idx !== -1) {
            returnModeLoadedItems.value[idx].is_selected_for_return = false;
        }
    }
};

// ============ RETURN MODE FUNCTIONS ============

// Toggle Return Mode
const toggleReturnMode = () => {
    if (isReturnMode.value) {
        // Exiting return mode - clear all return mode data
        exitReturnMode();
    } else {
        // Entering return mode
        isReturnMode.value = true;
        // Clear any existing products and return items
        products.value = [];
        returnItems.value = [];
        returnModeLoadedItems.value = [];
        returnModeSelectedSale.value = null;
        returnModeOrderId.value = '';
        returnModeSearchQuery.value = '';
        custom_discount.value = 0;
        cash.value = 0;
    }
};

// Exit Return Mode
const exitReturnMode = () => {
    isReturnMode.value = false;
    returnModeOrderId.value = '';
    returnModeSelectedSale.value = null;
    returnModeSearchQuery.value = '';
    returnModeLoadedItems.value = [];
    returnItems.value = [];
    products.value = [];
    custom_discount.value = 0;
    originalOrderDiscount.value = 0;
    originalOrderCustomDiscount.value = 0;
    cash.value = 0;
    errorMessage.value = '';
};

// Filter sales for search in return mode
const filteredSalesForReturnMode = computed(() => {
    if (!returnModeSearchQuery.value || returnModeSearchQuery.value.length < 1) {
        return props.sales.slice(0, 20); // Show first 20 sales
    }
    const query = returnModeSearchQuery.value.toLowerCase();
    return props.sales.filter(sale =>
        sale.order_id?.toLowerCase().includes(query) ||
        sale.customer?.name?.toLowerCase().includes(query)
    ).slice(0, 20);
});

// Load order items for return mode
const loadOrderForReturnMode = async (saleId) => {
    if (!saleId) return;

    try {
        errorMessage.value = '';
        const response = await axios.post('/api/sale/items', { sale_id: saleId });

        // Get the sale details
        const sale = props.sales.find(s => s.id === saleId);
        returnModeSelectedSale.value = sale;
        returnModeOrderId.value = saleId;

        // Store the employee info
        selectedSaleEmployee.value = response.data.employee;

        // Load items into cart as return items with full details
        const saleItems = response.data.saleItems;

        // Clear existing items
        returnItems.value = [];
        products.value = [];
        returnModeLoadedItems.value = [];

        // Load each item from the original order
        saleItems.forEach(item => {
            // Check if item has remaining quantity to return
            if (item.remaining_quantity > 0) {
                // Add to returnModeLoadedItems for tracking
                returnModeLoadedItems.value.push({
                    ...item,
                    is_selected_for_return: false,
                    return_type: 'cash',
                    return_quantity: item.remaining_quantity,
                    reason: '',
                    return_date: new Date().toISOString().split('T')[0],
                    original_discount: item.discount || 0,
                    original_unit_price: item.unit_price,
                    original_quantity: item.original_sale_quantity || item.quantity + (item.returned_quantity || 0),
                });
            }
        });

        // Set customer info from original sale
        if (sale?.customer) {
            customer.value = {
                name: sale.customer.name || '',
                contactNumber: sale.customer.contactNumber || sale.customer.contact_number || '',
                email: sale.customer.email || '',
            };
        }

        // Set employee from original sale
        if (sale?.employee_id) {
            employee_id.value = sale.employee_id;
        }

        // Load original sale's discounts
        originalOrderDiscount.value = sale?.discount || 0;
        originalOrderCustomDiscount.value = sale?.custom_discount || 0;
        custom_discount.value = 0; // Reset custom discount for new return

    } catch (err) {
        console.error('Error loading order for return mode:', err);
        errorMessage.value = 'Failed to load order items. Please try again.';
    }
};

// Select item for return in return mode
const selectItemForReturn = (item) => {
    // Check if already selected
    const existing = returnItems.value.find(r => r.sale_item_id === item.id);
    if (existing) {
        errorMessage.value = "This item is already selected for return";
        return;
    }

    // Mark as selected in loaded items
    const loadedItem = returnModeLoadedItems.value.find(i => i.id === item.id);
    if (loadedItem) {
        loadedItem.is_selected_for_return = true;
    }

    // Add to return items with all original details
    returnItems.value.push({
        id: item.id,
        sale_item_id: item.id,
        product_id: item.product_id,
        product: item.product,
        unit_price: item.unit_price,
        original_unit_price: item.unit_price,
        return_quantity: item.remaining_quantity,
        remaining_quantity: item.remaining_quantity,
        return_type: 'cash',
        reason: '',
        return_date: new Date().toISOString().split('T')[0],
        sale_id: returnModeOrderId.value,
        original_discount: item.discount || 0,
        original_quantity: item.original_sale_quantity || item.quantity,
        cost_price: item.cost_price || item.product?.cost_price || 0,
    });
};

// Deselect item from return
const deselectItemFromReturn = (itemId) => {
    returnItems.value = returnItems.value.filter(r => r.sale_item_id !== itemId);
    const loadedItem = returnModeLoadedItems.value.find(i => i.id === itemId);
    if (loadedItem) {
        loadedItem.is_selected_for_return = false;
    }
};

// Add new product for P2P exchange in return mode
const addP2PProductInReturnMode = (fetchedProduct) => {
    if (fetchedProduct.stock_quantity < 1) {
        isAlertModalOpen.value = true;
        message.value = "Product is out of stock";
        return;
    }

    const existingProduct = products.value.find(item => item.id === fetchedProduct.id);

    if (existingProduct) {
        existingProduct.quantity += 1;
    } else {
        products.value.push({
            ...fetchedProduct,
            quantity: 1,
            discount_type: 'percent',
            apply_discount: false,
            is_p2p_product: true, // Mark as P2P product
        });
    }
};

// Validate return mode submission
const validateReturnModeSubmission = () => {
    if (returnItems.value.length === 0) {
        return { valid: false, message: "No items selected for return" };
    }

    // Check if all return items have return type
    const missingReturnType = returnItems.value.some(item => !item.return_type);
    if (missingReturnType) {
        return { valid: false, message: "Please select Return Type (Cash or P2P) for all return items" };
    }

    // Check if all return items have reason
    const missingReason = returnItems.value.some(item => !item.reason || !item.reason.trim());
    if (missingReason) {
        return { valid: false, message: "Please provide a reason for all return items" };
    }

    // Check if return quantities are valid
    const invalidQuantity = returnItems.value.some(item =>
        !item.return_quantity || item.return_quantity < 1 || item.return_quantity > item.remaining_quantity
    );
    if (invalidQuantity) {
        return { valid: false, message: "Please enter valid return quantities (cannot exceed remaining quantity)" };
    }

    // For P2P returns, check if new products are added
    const hasP2PReturns = returnItems.value.some(item => item.return_type === 'p2p');
    if (hasP2PReturns && products.value.length === 0) {
        return { valid: false, message: "For P2P returns, please add replacement product(s) using User Manual or barcode scanner" };
    }

    // Validate stock for new products
    for (const product of products.value) {
        if (product.quantity > product.stock_quantity) {
            return { valid: false, message: `Insufficient stock for ${product.name}. Available: ${product.stock_quantity}` };
        }
    }

    return { valid: true };
};

// Submit return mode order (updates same order)
const submitReturnModeOrder = async () => {
    const validation = validateReturnModeSubmission();
    if (!validation.valid) {
        isAlertModalOpen.value = true;
        message.value = validation.message;
        return;
    }

    try {
        // Prepare return items data
        const returnItemsData = returnItems.value.map(item => ({
            sale_id: returnModeOrderId.value,
            sale_item_id: item.sale_item_id,
            product_id: item.product_id,
            quantity: item.return_quantity,
            reason: item.reason,
            unit_price: item.unit_price,
            return_date: item.return_date,
            return_type: item.return_type,
        }));

        // Prepare new products data for P2P
        const newProductsData = products.value.map(product => ({
            product_id: product.id,
            quantity: product.quantity,
            selling_price: product.apply_discount ? product.discounted_price : product.selling_price,
        }));

        // Call the return endpoint
        const response = await axios.post('/return-bill', {
            return_items: returnItemsData,
            new_products: newProductsData.length > 0 ? newProductsData : undefined,
            update_same_order: true, // Flag to update same order
        });

        if (response.data.success) {
            const returnBillData = response.data.return_bill_data;
            const returnSaleData = response.data.return_sale_data;
            const cashReturnData = response.data.cash_return_data;

            // Prepare modal data for receipt
            if (returnSaleData) {
                actualOrderId.value = returnSaleData.order_id || "";
                modalCustomer.value = returnSaleData.customer || { name: "", contactNumber: "", email: "" };
                modalEmployee.value = returnSaleData.employee || { name: "" };
                modalProducts.value = returnSaleData.items || [];
                cash.value = returnSaleData.total_amount || 0;
                selectedPaymentMethod.value = returnSaleData.payment_method || "";
                returnAmount.value = returnSaleData.return_amount || 0;
                newProductAmount.value = returnSaleData.new_product_amount || 0;
                isReturnBillPrinted.value = true;
                isSuccessModalOpen.value = true;
            } else if (cashReturnData) {
                actualOrderId.value = cashReturnData.order_id || "";
                modalCustomer.value = cashReturnData.customer || { name: "", contactNumber: "", email: "" };
                modalEmployee.value = cashReturnData.employee || { name: "" };
                modalProducts.value = cashReturnData.return_items || [];
                cash.value = cashReturnData.total_amount || 0;
                selectedPaymentMethod.value = cashReturnData.payment_method || "Cash Return";
                isReturnBillPrinted.value = true;
                isSuccessModalOpen.value = true;
            } else {
                isAlertModalOpen.value = true;
                message.value = `Return processed successfully!\n\nReturn Amount: ${returnBillData.totals.return_amount.toFixed(2)} LKR`;
            }

            // Exit return mode and refresh
            exitReturnMode();
        }
    } catch (err) {
        console.error('Error processing return:', err);
        isAlertModalOpen.value = true;
        message.value = err.response?.data?.error || err.response?.data?.message || "Failed to process return. Please try again.";
    }
};

// ============ END RETURN MODE FUNCTIONS ============

const incrementReturnItemQuantity = (item) => {
    if (item.return_quantity < item.remaining_quantity) {
        item.return_quantity += 1;
    }
};

const decrementReturnItemQuantity = (item) => {
    if (item.return_quantity > 1) {
        item.return_quantity -= 1;
    }
};

const closeReturnModal = () => {
    isReturnBillsModalOpen.value = false;
    errorMessage.value = '';
};

const incrementReturnQuantity = (id) => {
    const item = saleItemsState.value.find((item) => item.id === id);
    if (item) {
        const maxQty = item.remaining_quantity || item.quantity;
        if (!item.return_quantity) item.return_quantity = 1;
        if (item.return_quantity < maxQty) {
            item.return_quantity += 1;
        }
    }
};

const decrementReturnQuantity = (id) => {
    const item = saleItemsState.value.find((item) => item.id === id);
    if (item && item.return_quantity > 1) {
        item.return_quantity -= 1;
    }
};
const discount = ref(0);

// const return_date = ref("");
// const sales_id = ref("");
// const reason = ref("");
// const orders = ref([]);

const customer = ref({
    name: "",
    countryCode: "",
    contactNumber: "",
    email: "",
});

const validateReturnItems = () => {
    if (returnItems.value.length === 0) {
        return { valid: false, message: "No items selected for return" };
    }

    // Validate all items have return types selected
    const missingReturnType = returnItems.value.some(item => !item.return_type);
    if (missingReturnType) {
        return { valid: false, message: "Please select Return Type (Cash or P2P) for all return items" };
    }

    // Validate all items have reasons
    const missingReason = returnItems.value.some(item => !item.reason || !item.reason.trim());
    if (missingReason) {
        return { valid: false, message: "Please provide a reason for all return items" };
    }

    // Validate return quantities
    const invalidQuantity = returnItems.value.some(item => !item.return_quantity || item.return_quantity < 1);
    if (invalidQuantity) {
        return { valid: false, message: "Please enter valid return quantities" };
    }

    // For P2P returns, verify that products have been added via User Manual
    const hasP2PReturns = returnItems.value.some(item => item.return_type === 'p2p');
    if (hasP2PReturns && products.value.length === 0) {
        return { valid: false, message: "For P2P returns, please add the new product(s) using User Manual Product Add" };
    }

    // Validate that return quantities don't exceed remaining quantities
    const exceedsRemaining = returnItems.value.some(item =>
        item.return_quantity > item.remaining_quantity
    );
    if (exceedsRemaining) {
        return { valid: false, message: "Return quantity cannot exceed remaining quantity" };
    }

    return { valid: true };
};


const openReturnBills = () => {
    isReturnBillsModalOpen.value = true;
};
const ReturnbillForm = useForm({
    order_id:"",
    reason:"",
    return_date:"",

});

const employee_id = ref("");
const modalEmployee = ref({ name: "" }); // Separate ref for modal display
const modalCustomer = ref({ name: "", contactNumber: "", email: "" }); // Separate ref for modal
const modalProducts = ref([]); // Separate ref for modal products - DON'T use main products!
const returnAmount = ref(0);
const newProductAmount = ref(0);

const employee = computed(() => {
  if (!employee_id.value) return null;
  // Convert both to strings for comparison to handle type mismatches
  return props.allemployee.find(emp => String(emp.id) === String(employee_id.value));
});

const selectedPaymentMethod = ref("cash");
const isCreditBill = ref(false);

const refreshData = () => {
    router.visit(route("pos.index"), {
        preserveScroll: false, // Reset scroll
        preserveState: false, // Reset component state
    });
};

const removeProduct = (id) => {
    products.value = products.value.filter((item) => item.id !== id);
};

const removeCoupon = () => {
    appliedCoupon.value = null; // Clear the applied coupon
    couponForm.code = ""; // Clear the coupon field
};

const handleCreditBillChange = () => {
    if (isCreditBill.value) {
        selectedPaymentMethod.value = "credit bill";
    } else {
        selectedPaymentMethod.value = "cash"; // Reset to default when unchecked
    }
};

const selectPaymentMethod = (method) => {
    selectedPaymentMethod.value = method;
    if (method !== "credit bill") {
        isCreditBill.value = false; // Uncheck credit bill when other methods are selected
    }
};

const incrementQuantity = (id) => {
    const product = products.value.find((item) => item.id === id);
    if (product) {
        product.quantity += 1;
    }
};

// const save= () => {
//     isReturnBillsModalOpen.value = false;
// }
const decrementQuantity = (id) => {
    const product = products.value.find((item) => item.id === id);
    if (product && product.quantity > 1) {
        product.quantity -= 1;
    }
};

// const orderId = computed(() => {
//   const timestamp = Date.now().toString(36).toUpperCase(); // Convert timestamp to a base-36 string
//   const randomString = Math.random().toString(36).substr(2, 5).toUpperCase(); // Generate a shorter random string
//   return `ORD-${timestamp}-${randomString}`; // Combine to create unique order ID
// });
const orderId = computed(() => {
    const characters =
        "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    return Array.from({ length: 6 }, () =>
        characters.charAt(Math.floor(Math.random() * characters.length))
    ).join("");
});

const submitOrder = async () => {
    // Check if this is a return transaction
    if (returnItems.value.length > 0) {
        const validation = validateReturnItems();
        if (!validation.valid) {
            isAlertModalOpen.value = true;
            message.value = validation.message;
            return;
        }
    }

    // Only check balance for regular sales, not for returns
    if (balance.value < 0 && products.value.length > 0 && returnItems.value.length === 0) {
        isAlertModalOpen.value = true;
        message.value = "Cash is not enough";
        return;
    }

    try {
        // Prepare return items data
        const returnItemsData = returnItems.value.map(item => ({
            sale_id: item.sale_id,
            sale_item_id: item.sale_item_id,
            product_id: item.product_id,
            quantity: item.return_quantity,
            reason: item.reason,
            unit_price: item.unit_price,
            return_date: item.return_date,
            return_type: item.return_type,
        }));

        // Check if we have P2P returns with manually added products
        const hasP2PReturns = returnItems.value.some(item => item.return_type === 'p2p');
        const hasNewProducts = products.value.length > 0;

        // For P2P returns with new products, send to return endpoint with new products
        if (returnItems.value.length > 0 && hasP2PReturns && hasNewProducts) {
            // P2P Return with new products - Creates separate bill
            const newProductsData = products.value.map(product => ({
                product_id: product.id,
                quantity: product.quantity,
                selling_price: product.selling_price,
            }));

            const response = await axios.post('/return-bill', {
                return_items: returnItemsData,
                new_products: newProductsData
            });

            if (response.data.success) {
                const returnBillData = response.data.return_bill_data;
                const returnOrderId = response.data.return_order_id;
                const returnSaleData = response.data.return_sale_data;

                // If P2P return, show success modal with print option
                if (returnSaleData) {
                    // Update all the reactive variables used by the modal - use MODAL refs only
                    actualOrderId.value = returnSaleData.order_id || "";
                    modalCustomer.value = returnSaleData.customer || { name: "", contactNumber: "", email: "" };
                    modalEmployee.value = returnSaleData.employee || { name: "" };
                    modalProducts.value = returnSaleData.items || []; // Use modalProducts instead of products
                    cash.value = returnSaleData.total_amount || 0;
                    custom_discount.value = 0;
                    selectedPaymentMethod.value = returnSaleData.payment_method || "";

                    // Set return amount and new product amount for P2P bill display
                    returnAmount.value = returnSaleData.return_amount || 0;
                    newProductAmount.value = returnSaleData.new_product_amount || 0;

                    // Mark this as a return bill print modal (no auto-refresh on close)
                    isReturnBillPrinted.value = true;

                    // Show the success modal with print option
                    isSuccessModalOpen.value = true;

                    console.log('P2P Return Bill Data for Print:', returnSaleData);
                } else {
                    // Fallback to alert modal
                    isAlertModalOpen.value = true;
                    message.value = `P2P Return Bill Created Successfully!\n\n` +
                        `Return Bill ID: ${returnOrderId}\n` +
                        `Returned Amount: ${returnBillData.totals.return_amount.toFixed(2)} LKR\n` +
                        `New Product Amount: ${returnBillData.totals.new_product_amount.toFixed(2)} LKR\n` +
                        `Net Amount: ${returnBillData.totals.net_amount.toFixed(2)} LKR\n\n` +
                        `Original Sale Updated. New Return Bill Created.`;
                }

                console.log('P2P Return Bill Data:', {
                    return_order_id: returnOrderId,
                    original_sale_id: response.data.original_sale_id,
                    return_sale_id: response.data.return_sale_id,
                    ...returnBillData
                });

                // Clear data and close return form modal
                // DON'T clear products.value immediately - it may still have POS items
                await nextTick();
                returnItems.value = [];
                products.value = []; // Only clear if this was the only operation
                ReturnbillForm.order_id = "";
                selectedSale.value = null;
                selectedSaleEmployee.value = null;
                isReturnBillsModalOpen.value = false; // Close the return form modal

                // Don't refresh immediately if modal is showing - let user print first
                if (!returnSaleData) {
                    refreshData();
                }
            }
        }
        // P2P Return only (item-to-item, no new products)
        else if (returnItems.value.length > 0 && hasP2PReturns && !hasNewProducts) {
            const response = await axios.post('/return-bill', {
                return_items: returnItemsData
            });

            if (response.data.success) {
                const returnBillData = response.data.return_bill_data;
                const returnSaleData = response.data.return_sale_data;

                // Show return receipt with print option if data is available
                if (returnSaleData) {
                    // Prepare data for the success modal - use MODAL refs only
                    actualOrderId.value = returnSaleData.order_id || "";
                    modalCustomer.value = returnSaleData.customer || { name: "", contactNumber: "", email: "" };
                    modalEmployee.value = returnSaleData.employee || { name: "" };
                    modalProducts.value = returnSaleData.items || []; // Use modalProducts instead of products
                    cash.value = returnSaleData.total_amount || 0;
                    custom_discount.value = 0;
                    selectedPaymentMethod.value = returnSaleData.payment_method || "P2P Return";

                    // Mark this as a return bill print modal (no auto-refresh on close)
                    isReturnBillPrinted.value = true;

                    // Show success modal with print option
                    isSuccessModalOpen.value = true;

                    console.log('P2P Return Receipt Data:', returnSaleData);
                } else {
                    // Fallback to alert modal
                    isAlertModalOpen.value = true;
                    message.value = `P2P Return Processed Successfully!\n\n` +
                        `Returned Items: ${returnBillData.totals.return_items_count || returnItems.value.length}\n` +
                        `Returned Amount: ${returnBillData.totals.return_amount.toFixed(2)} LKR\n` +
                        `Original Sale Total Updated.`;
                }

                // Clear return items and close the return form modal
                // Use nextTick to ensure modal is fully rendered before clearing
                await nextTick();
                returnItems.value = [];
                // DON'T clear products.value - it belongs to main POS!
                ReturnbillForm.order_id = "";
                selectedSale.value = null;
                selectedSaleEmployee.value = null;
                isReturnBillsModalOpen.value = false; // Close the return form modal

                // Don't refresh immediately - let user print the receipt first
                // refreshData will be called when modal closes
            }
        }
        // Cash return only (no new products, no P2P)
        else if (returnItems.value.length > 0 && !hasP2PReturns && !hasNewProducts) {
            const response = await axios.post('/return-bill', {
                return_items: returnItemsData
            });

            if (response.data.success) {
                const returnBillData = response.data.return_bill_data;
                const cashReturnData = response.data.cash_return_data;

                // Show return receipt with print option if data is available
                if (cashReturnData) {
                    // Prepare data for the success modal - use MODAL refs only
                    actualOrderId.value = cashReturnData.order_id || "";
                    modalCustomer.value = cashReturnData.customer || { name: "", contactNumber: "", email: "" };
                    modalEmployee.value = cashReturnData.employee || { name: "" };
                    modalProducts.value = cashReturnData.return_items || []; // Use modalProducts instead of products
                    cash.value = cashReturnData.total_amount || 0;
                    custom_discount.value = 0;
                    selectedPaymentMethod.value = cashReturnData.payment_method || "Cash Return";

                    // Mark this as a return bill print modal (no auto-refresh on close)
                    isReturnBillPrinted.value = true;

                    // Show success modal with print option
                    isSuccessModalOpen.value = true;

                    console.log('Cash Return Receipt Data:', cashReturnData);
                } else {
                    // Fallback to alert modal
                    isAlertModalOpen.value = true;
                    message.value = `Cash Return Processed Successfully!\n\n` +
                        `Returned Amount: ${returnBillData.totals.return_amount.toFixed(2)} LKR\n` +
                        `Original Sale Total Updated.`;
                }

                // Clear return items and close return form modal
                // Use nextTick to ensure modal is fully rendered before clearing
                await nextTick();
                returnItems.value = [];
                // DON'T clear products.value - it belongs to main POS!
                ReturnbillForm.order_id = "";
                selectedSale.value = null;
                selectedSaleEmployee.value = null;
                isReturnBillsModalOpen.value = false; // Close the return form modal

                // Don't refresh immediately - let user print the receipt first
                // refreshData will be called when modal closes
            }
        }
        // Regular sale (no returns)
        else {
            const response = await axios.post("/pos/submit", {
                customer: customer.value,
                products: products.value,
                employee_id: employee_id.value,
                paymentMethod: selectedPaymentMethod.value,
                userId: props.loggedInUser.id,
                orderid: orderid.value,
                cash: cash.value,
                custom_discount: custom_discount.value,
                custom_discount_type: custom_discount_type.value,
                appliedCoupon: appliedCoupon.value,
                return_items: returnItemsData
            });

            // Set modal customer data for receipt display
            modalCustomer.value = customer.value;
            // Set modal employee data for receipt display
            modalEmployee.value = employee.value || { name: "" };

            // Debug logging
            console.log('Customer data:', customer.value);
            console.log('Employee data:', employee.value);
            console.log('Modal Customer:', modalCustomer.value);
            console.log('Modal Employee:', modalEmployee.value);

            isSuccessModalOpen.value = true;
            console.log(response.data);

            // Clear return items if any
            returnItems.value = [];
        }
    } catch (error) {
        if (error.response && error.response.status === 423) {
            isAlertModalOpen.value = true;
            message.value = error.response.data.message;
        }
        console.error(
            "Error submitting order:",
            error.response?.data || error.message
        );
        isAlertModalOpen.value = true;
        message.value = error.response?.data?.error || "Failed to process order. Please try again.";
    }
};

const filteredSaleItems = computed(() => {
    return saleItemsState.value;
});

const returnBillTotal = computed(() => {
    if (!returnItems.value.length) return 0;

    // Calculate total return amount for ALL returns (cash and P2P)
    // P2P returns will show as deduction, and manually added products will add to the total
    return returnItems.value.reduce((sum, item) => {
        const returnQty = item.return_quantity || 1;
        const unitPrice = parseFloat(item.unit_price) || 0;
        return sum + (returnQty * unitPrice);
    }, 0);
});

// Custom discount value calculation for display
const customDiscountValue = computed(() => {
    const newProductTotal = parseFloat(subtotal.value) || 0;
    const returnTotal = parseFloat(returnBillTotal.value) || 0;
    const customDiscount = parseFloat(custom_discount.value) || 0;

    if (custom_discount_type.value === 'percent') {
        // In return mode with no new products, apply discount to return amount
        if (isReturnMode.value && newProductTotal === 0 && returnTotal > 0) {
            return (returnTotal * customDiscount) / 100;
        }
        // Apply percentage to the absolute value of (new products - return amount)
        const baseAmount = Math.abs(newProductTotal - returnTotal);
        return (baseAmount * customDiscount) / 100;
    } else if (custom_discount_type.value === 'fixed') {
        return customDiscount;
    }
    return 0;
});

// Net return amount calculation (New Products - Return Amount + Custom Discount)
// In return mode, custom discount reduces the refund amount
const netReturnAmount = computed(() => {
    const newProductTotal = parseFloat(subtotal.value) || 0;
    const returnTotal = parseFloat(returnBillTotal.value) || 0;
    const discounts = parseFloat(totalDiscount.value) || 0;

    // In return mode, the discount reduces the refund (added back)
    // Positive = customer owes money, Negative = refund to customer
    return (newProductTotal - discounts + customDiscountValue.value) - returnTotal;
});

// };
// In your script setup
// Initialize as empty array
// If not already defined
const selectedSale = ref(null); // If not already defined

// Add this method to handle return bill submission
// const submitReturnBill = () => {
//     // Your return bill submission logic here
//     console.log('Submitting return bill:', ReturnbillForm);
//     isReturnBillsModalOpen.value = false;
// };


// Add method to remove item
const removeItem = (index) => {
    saleItemsState.value.splice(index, 1);
};

// Watch for order_id changes to load sale details
watch(() => ReturnbillForm.order_id, async (newOrderId) => {
    if (newOrderId) {
        try {
            // Fetch sale items with remaining quantities
            const response = await axios.post('/api/sale/items', {
                sale_id: newOrderId
            });

            // Set employee info
            selectedSaleEmployee.value = response.data.employee;

            // Initialize sale items with return fields
            saleItemsState.value = response.data.saleItems.map((item) => ({
                ...item,
                reason: "",
                return_date: new Date().toISOString().split('T')[0],
                return_type: "cash",
                return_quantity: 1,
                new_product_id: null,
                new_product_amount: null,
            }));
        } catch (error) {
            console.error('Error fetching sale items:', error);
            errorMessage.value = "Failed to load sale items. Please try again.";
            saleItemsState.value = [];
            selectedSaleEmployee.value = null;
        }
    } else {
        saleItemsState.value = [];
        selectedSaleEmployee.value = null;
    }
});

// Keep the original watch for backward compatibility
// watch(() => ReturnbillForm.order_id, async (newOrderId) => {
//     if (newOrderId) {
//         try {
//             const response = await axios.get(`api/sale/items/${newOrderId}`);
//             selectedSale.value = response.data.sale;
//             filteredSaleItems.value = response.data.items || []; // Ensure items is always an array
//         } catch (error) {
//             console.error('Error fetching sale details:', error);
//             filteredSaleItems.value = []; // Reset to empty array on error
//         }
//     } else {
//         selectedSale.value = null;
//         filteredSaleItems.value = []; // Reset to empty array
//     }
// });

watch(
    () => ReturnbillForm.order_id,
    (newValue) => {
        const sale = props.sales.find((sale) => sale.id === newValue) || null;
        selectedSale.value = sale;
        if (sale) {
            ReturnbillForm.discount = sale.discount || 0;
        } else {
            ReturnbillForm.discount = 0; // Default if no sale is found
        }
    }
);


const subtotal = computed(() => {
    return parseFloat(products.value
        .reduce(
            (total, item) => total + parseFloat(item.selling_price) * item.quantity,
            0
        )
        .toFixed(2)); // Ensures two decimal places
});

const totalDiscount = computed(() => {
    const productDiscount = products.value.reduce((total, item) => {
        // Check if item has a discount
        if (item.discount && item.discount > 0 && item.apply_discount == true) {
            const discountAmount =
                (parseFloat(item.selling_price) - parseFloat(item.discounted_price)) *
                item.quantity;
            return total + discountAmount;
        }
        return total; // If no discount, return total as-is
    }, 0); // Ensures two decimal places

    const couponDiscount = appliedCoupon.value
        ? Number(appliedCoupon.value.discount)
        : 0;

    return parseFloat((productDiscount + couponDiscount).toFixed(2));
});

const validateCustomDiscount = () => {
    if (!custom_discount.value || isNaN(custom_discount.value)) {
        custom_discount.value = 0; // Set default to 0 if the field is empty or invalid
    }
};

// const openReturnBills =() =>{
//     router.visit(route('return-bill.index'));
// }

const total = computed(() => {
    const subtotalValue = parseFloat(subtotal.value) || 0;
    const discountValue = parseFloat(totalDiscount.value) || 0;
    const customDiscount = parseFloat(custom_discount.value) || 0;
    const returnAmount = parseFloat(returnBillTotal.value) || 0;

    let customValue = 0;

    if (custom_discount_type.value === 'percent') {
        // In return mode with no new products, apply discount to return amount
        if (isReturnMode.value && subtotalValue === 0 && returnAmount > 0) {
            customValue = (returnAmount * customDiscount) / 100;
        } else {
            // Apply to the base (new products - return amount)
            const baseForDiscount = subtotalValue - returnAmount;
            customValue = (Math.abs(baseForDiscount) * customDiscount) / 100;
        }
    } else if (custom_discount_type.value === 'fixed') {
        customValue = customDiscount;
    }

    let baseTotal = subtotalValue - discountValue - returnAmount;

    // Apply custom discount - in return mode it reduces the refund amount
    if (isReturnMode.value) {
        baseTotal = baseTotal + customValue; // Add back discount to reduce refund
    } else {
        baseTotal = baseTotal - customValue;
    }

    // Add Koko surcharge if Koko payment method is selected
    if (selectedPaymentMethod.value === 'Koko') {
        const kokoSurcharge = baseTotal * 0.115; // 11.5% surcharge
        baseTotal += kokoSurcharge;
    }

    return parseFloat(baseTotal.toFixed(2));
});

const kokoSurcharge = computed(() => {
    if (selectedPaymentMethod.value === 'Koko') {
        const subtotalValue = parseFloat(subtotal.value) || 0;
        const discountValue = parseFloat(totalDiscount.value) || 0;
        const customDiscount = parseFloat(custom_discount.value) || 0;
        const returnAmount = parseFloat(returnBillTotal.value) || 0;

        let customValue = 0;

        if (custom_discount_type.value === 'percent') {
            customValue = (subtotalValue * customDiscount) / 100;
        } else if (custom_discount_type.value === 'fixed') {
            customValue = customDiscount;
        }

        const baseTotal = subtotalValue - discountValue - customValue - returnAmount;
        return parseFloat((baseTotal * 0.115).toFixed(2)); // 11.5% surcharge
    }
    return 0;
});

const balance = computed(() => {
    if (cash.value == null || cash.value === 0) {
        return 0; // If cash.value is null or 0, return 0
    }
    return parseFloat((parseFloat(cash.value) - parseFloat(total.value)).toFixed(2));
});
// Check for product or handle errors
const form = useForm({
    employee_id: "",
    barcode: "", // Form field for barcode
});

const couponForm = useForm({
    code: "",
});

// Temporary barcode storage during scanning
let barcode = "";
let timeout; // Timeout to detect the end of the scan

const submitCoupon = async () => {
    try {
        const response = await axios.post(route("pos.getCoupon"), {
            code: couponForm.code, // Send the coupon field
        });

        const { coupon: fetchedCoupon, error: fetchedError } = response.data;

        if (fetchedCoupon) {
            appliedCoupon.value = fetchedCoupon;
            products.value.forEach((product) => {
                product.apply_discount = false;
            });
        } else {
            isAlertModalOpen.value = true;
            message.value = fetchedError;
            error.value = fetchedError;
        }
    } catch (err) {
        // console.error(error);
        if (err.response.status === 422) {
            isAlertModalOpen.value = true;
            message.value = err.response.data.message;
        }
    }
};

// Automatically submit the barcode to the backend
const submitBarcode = async () => {
    // Store the barcode value before clearing
    const barcodeValue = form.barcode;

    // Clear immediately to prevent overlay from showing
    form.barcode = "";
    searchTerm.value = "";

    try {
        // Send POST request to the backend
        const response = await axios.post(route("pos.getProduct"), {
            barcode: barcodeValue, // Send the stored barcode value
        });

        // Extract the response data
        const { product: fetchedProduct, error: fetchedError } = response.data;

        if (fetchedProduct) {
            if (fetchedProduct.stock_quantity < 1) {
                isAlertModalOpen.value = true;
                message.value = "Product is out of stock";
                return;
            }
            // Check if the product already exists in the products array
            const existingProduct = products.value.find(
                (item) => item.id === fetchedProduct.id
            );

            if (existingProduct) {
                // If it exists, increment the quantity
                existingProduct.quantity += 1;
            } else {
                // If it doesn't exist, add it to the products array with quantity 1
                products.value.push({
                    ...fetchedProduct,
                    quantity: 1,
                     discount_type: 'percent',
                    apply_discount: false, // Add the new attribute
                });
            }

            product.value = fetchedProduct; // Update product state for individual display
            error.value = null; // Clear any previous errors

            console.log(
                "Product fetched successfully and added to cart:",
                fetchedProduct
            );
        } else {
            isAlertModalOpen.value = true;
            message.value = fetchedError;
            error.value = fetchedError; // Set the error message
            console.error("Error:", fetchedError);
        }
    } catch (err) {
        if (err.response.status === 422) {
            isAlertModalOpen.value = true;
            message.value = err.response.data.message;
        }

        console.error("An error occurred:", err.response?.data || err.message);
        error.value = "An unexpected error occurred. Please try again.";
    }
};

// Handle input from the barcode scanner
const handleScannerInput = (event) => {
    // Ignore if the barcode input field is focused (manual entry)
    if (document.activeElement && document.activeElement.id === 'search') {
        return;
    }

    clearTimeout(timeout); // Clear the timeout for each keypress
    if (event.key === "Enter") {
        // Barcode scanning completed
        form.barcode = barcode; // Set the scanned barcode into the form
        submitBarcode(); // Automatically submit the barcode
        barcode = ""; // Reset the barcode for the next scan
    } else {
        // Append the pressed key to the barcode
        barcode += event.key;
    }

    // Timeout to reset the barcode if scanning is interrupted
    timeout = setTimeout(() => {
        barcode = "";
    }, 100); // Adjust timeout based on scanner speed
};

// Attach the keypress event listener when the component is mounted
onMounted(async() => {
    document.addEventListener("keypress", handleScannerInput);

    try{
        const response = await axios.get('/pos');
        sales.value = response.data.sales || [];
    }
    catch(error){
        console.error("Error fetching sales:", error);
        sales.value = [];
    }

});

// const loadSales = async () => {
//     isLoading.value = true;
//     try {
//         const response = await axios.get("/api/sales");
//         sales.value = response.data.sales || [];
//     } catch(error) {
//         console.error("Error fetching sales:", error);
//         sales.value = [];
//     } finally {
//         isLoading.value = false;
//     }
// };

const updateItemTotal = (item) => {
  // Ensure quantity is a valid number
  if (item.quantity < 0 || isNaN(item.quantity)) {
    item.quantity = 1;
  }
};

const applyDiscount = (id) => {
    products.value.forEach((product) => {
        if (product.id === id) {
            product.apply_discount = true;
        }
    });
};

const removeDiscount = (id) => {
    products.value.forEach((product) => {
        if (product.id === id) {
            product.apply_discount = false;
        }
    });
};


const handleSelectedProducts = (selectedProducts) => {
    selectedProducts.forEach((fetchedProduct) => {
        // In return mode with P2P, check stock
        if (isReturnMode.value) {
            if (fetchedProduct.stock_quantity < 1) {
                isAlertModalOpen.value = true;
                message.value = `Product "${fetchedProduct.name}" is out of stock`;
                return;
            }
        }

        const existingProduct = products.value.find(
            (item) => item.id === fetchedProduct.id
        );

        if (existingProduct) {
            // If the product exists, increment its quantity
            existingProduct.quantity += 1;
        } else {
            // If the product doesn't exist, add it with a default quantity
            products.value.push({
                ...fetchedProduct,
                quantity: 1,
                 discount_type: 'percent',
                apply_discount: false, // Default additional attribute
                is_p2p_product: isReturnMode.value, // Mark as P2P product if in return mode
            });
        }
    });
};

// Watch for changes to isReturnBill
// watch(isReturnBill, (newValue) => {
//   if (newValue && selectedOrder.value) {
//     // If turning on return bill mode, make all products need a reason
//     products.value.forEach(product => {
//       if (!product.returnReason) {
//         product.returnReason = "";
//       }
//     });
//   }
// });


const searchTerm = ref('');

// Computed property for filtered product results in ascending order
const searchResults = computed(() => {
  if (searchTerm.value === "" || searchTerm.value.length < 2) {
    return [];
  }

  const searchLower = searchTerm.value.toLowerCase();

  return props.products
    .filter((product) => {
      const name = product.name.toLowerCase();
      const barcode = product.barcode?.toLowerCase() || '';
      const code = product.code?.toLowerCase() || '';

      // Match if name starts with search term, or exact barcode/code match
      return name.startsWith(searchLower) ||
             barcode === searchLower ||
             code === searchLower ||
             barcode.startsWith(searchLower) ||
             code.startsWith(searchLower);
    })
    .sort((a, b) => {
      // Sort by priority: exact start match first, then alphabetical
      const aStarts = a.name.toLowerCase().startsWith(searchLower);
      const bStarts = b.name.toLowerCase().startsWith(searchLower);

      if (aStarts && !bStarts) return -1;
      if (!aStarts && bStarts) return 1;

      return a.name.localeCompare(b.name);
    })
    .slice(0, 10); // Limit to 10 results
});

// Watch for changes in the form barcode field and update the search term
watch(
  () => form.barcode,
  (newValue) => {
    searchTerm.value = newValue;
  }
);

// Method to select a product (or barcode)
const selectProduct = (productName) => {
  form.barcode = productName; // Set the selected product name to the barcode field
  searchTerm.value = ""; // Clear the search term after selection
};
</script>
