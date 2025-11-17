<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 flex items-center justify-center p-6">
    <!-- Background decorations -->
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-blue-400/20 to-indigo-600/20 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-br from-green-400/20 to-emerald-600/20 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-3xl relative">
      <!-- Success Card -->
      <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-2xl border border-gray-200/50 p-8 md:p-12">
        <!-- Success Icon -->
        <div class="flex justify-center mb-6">
          <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-emerald-600 rounded-full flex items-center justify-center animate-bounce">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </div>

        <!-- Title -->
        <h1 class="text-4xl font-bold text-center text-gray-900 mb-4">
          🎉 Congratulations !
        </h1>
        <p class="text-xl text-center text-gray-600 mb-8">
          Your registration has been done successfully !
        </p>

        <!-- Tenant Info -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 mb-6 border border-blue-200">
          <!-- Logo de l'entreprise -->
          <div v-if="tenant.logo" class="flex justify-center mb-4">
            <img :src="`/storage/${tenant.logo}`" alt="Company logo" class="h-20 w-20 object-contain">
          </div>
          <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
            </svg>
            Your organisation
          </h2>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-gray-600 font-medium">Name :</span>
              <span class="text-gray-900 font-bold text-lg">{{ tenant.name }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-600 font-medium">Administrator :</span>
              <span class="text-gray-900 font-semibold">{{ tenant.contact_name }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-600 font-medium">Email :</span>
              <span class="text-gray-900 font-semibold">{{ tenant.contact_email }}</span>
            </div>
          </div>
        </div>

        <!-- Subdomain Info -->
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-6 mb-6 border border-purple-200">
          <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd" />
            </svg>
            Your URL space
          </h2>
          <div class="bg-white rounded-lg p-4 border-2 border-purple-300">
            <a :href="tenantUrl" class="text-lg font-mono font-bold text-purple-600 hover:text-purple-800 break-all">
              {{ tenant.subdomain }}
            </a>
          </div>
        </div>

        <!-- Subscription Info -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 mb-8 border border-green-200">
          <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
              <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" />
              <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" />
            </svg>
            Your subscription
          </h2>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-gray-600 font-medium">Plan :</span>
              <span class="text-gray-900 font-bold text-lg">{{ subscription.plan_name }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-600 font-medium">Invoicing :</span>
              <span class="text-gray-900 font-semibold">
                {{ subscription.billing_cycle === 'yearly' ? 'Annual' : 'Monthly' }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-600 font-medium">Price :</span>
              <span class="text-gray-900 font-semibold">
                {{ subscription.billing_cycle === 'yearly' 
                  ? formatPrice(subscription.yearly_price) + '/year' 
                  : formatPrice(subscription.monthly_price) + '/month' 
                }}
              </span>
            </div>
          </div>
          
          <!-- Trial Badge -->
          <div class="mt-4 bg-white rounded-lg p-4 border-2 border-green-300">
            <div class="flex items-center">
              <span class="text-3xl mr-3">🎁</span>
              <div>
                <p class="font-bold text-green-900">3 months free trials</p>
                <p class="text-sm text-green-700">
                  Enjoy all features for free until {{ subscription.trial_ends_at }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Next Steps -->
        <div class="bg-blue-50 rounded-xl p-6 mb-8 border border-blue-200">
          <h2 class="text-xl font-bold text-gray-900 mb-4">🚀 Next Steps</h2>
          <ol class="space-y-3">
            <li class="flex items-start">
              <span class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-3">1</span>
              <div>
                <p class="font-semibold text-gray-900">Log in to your space</p>
                <p class="text-sm text-gray-600">Use your credentials to access your dashboard</p>
              </div>
            </li>
            <li class="flex items-start">
              <span class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-3">2</span>
              <div>
                <p class="font-semibold text-gray-900">Check your emails</p>
                <p class="text-sm text-gray-600">A confirmation email has been sent to {{ tenant.contact_email }}</p>
              </div>
            </li>
            <li class="flex items-start">
              <span class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold mr-3">3</span>
              <div>
                <p class="font-semibold text-gray-900">Configure your workspace</p>
                <p class="text-sm text-gray-600">Customize your settings and invite your collaborators</p>
              </div>
            </li>
          </ol>
        </div>

        <!-- CTA Button -->
        <div class="text-center">
          <a 
            :href="tenantUrl"
            class="inline-flex items-center bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-4 rounded-xl font-bold text-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1"
          >
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
            </svg>
            Access my workspace
          </a>
        </div>

        <!-- Help Link -->
        <div class="text-center mt-6">
          <p class="text-sm text-gray-600">
            Need Help ? 
            <a href="/landing#contact" class="text-blue-600 hover:text-blue-800 font-semibold">
              Contact us
            </a>
          </p>
        </div>
      </div>

      <!-- Back to home -->
      <!-- <div class="text-center mt-6">
        <a href="/landing" class="text-gray-600 hover:text-blue-600 transition-colors text-sm">
          ← Retour à l'accueil
        </a>
      </div> -->
    </div>
  </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({
  tenant: Object,
  subscription: Object,
  tenantUrl: String,
})

function formatPrice(price) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}
</script>