<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
    <!-- Header -->
    <header class="relative bg-white/80 backdrop-blur-md border-b border-gray-200/50 sticky top-0 z-50">
      <div class="container mx-auto flex items-center justify-between px-6 py-4">
        <div class="flex items-center space-x-3">
          <img
            src="/images/openart-image_GwOKeCKx_1750239441227_raw.jpg"
            class="h-10 w-auto"
          />
          <span class="text-xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
            SQL-INFO
          </span>
        </div>
        
        <nav class="hidden md:flex items-center space-x-8">
          <a href="/landing" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">
            Home
          </a>
          <a href="#" class="text-blue-600 font-semibold">
            Pricing
          </a>
          <a href="/landing#contact" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">
            Contact
          </a>
        </nav>

        <div class="flex items-center space-x-4">
          <!-- <Link href="/login" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">
            Sign in
          </Link> -->
          <Link 
            href="/registerTenant"
            class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-2.5 rounded-full font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
          >
            Get Started
          </Link>
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="relative px-6 py-20 overflow-hidden">
      <!-- Background decorations -->
      <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-blue-400/20 to-indigo-600/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-br from-purple-400/20 to-pink-400/20 rounded-full blur-3xl"></div>
      </div>

      <div class="container mx-auto max-w-6xl relative">
        <div class="text-center mb-16">
          <h1 class="text-5xl md:text-6xl font-bold leading-tight mb-8">
            <span class="bg-gradient-to-r from-gray-900 via-blue-800 to-indigo-900 bg-clip-text text-transparent">
              Choose your plan
            </span>
          </h1>
          
          <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-8 leading-relaxed">
            Start free for 3 months, then scale according to your needs. 
            No credit card required for trial.
          </p>
          
          <!-- Toggle Monthly/Yearly -->
          <div class="flex items-center justify-center space-x-4 mb-16">
            <span :class="billingCycle === 'monthly' ? 'text-gray-900 font-semibold' : 'text-gray-500'" class="text-lg">
              Monthly
            </span>
            <button 
              @click="toggleBilling"
              class="relative w-16 h-8 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full transition-all shadow-md hover:shadow-lg"
            >
              <div 
                :class="billingCycle === 'yearly' ? 'translate-x-8' : 'translate-x-1'"
                class="absolute top-1 left-0 w-6 h-6 bg-white rounded-full shadow-md transition-transform duration-300"
              ></div>
            </button>
            <span :class="billingCycle === 'yearly' ? 'text-gray-900 font-semibold' : 'text-gray-500'" class="text-lg">
              Yearly
            </span>
            <span v-if="billingCycle === 'yearly'" class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-semibold animate-pulse">
              Save up to {{ maxSavings }}% 🎉
            </span>
          </div>
        </div>

        <!-- Alert if user already has a subscription -->
        <div v-if="currentSubscription" class="max-w-4xl mx-auto mb-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
          <div class="flex items-start">
            <svg class="w-6 h-6 text-blue-600 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <div>
              <h3 class="text-lg font-semibold text-blue-900 mb-1">You already have a subscription</h3>
              <p class="text-blue-700">
                Current plan: <strong>{{ currentSubscription.plan.name }}</strong>
                <span v-if="currentSubscription.is_on_trial" class="ml-2 text-green-700 font-semibold">
                  ({{ currentSubscription.remaining_trial_days }} trial days remaining)
                </span>
              </p>
              <Link 
                :href="route('subscriptions.show')" 
                class="inline-block mt-3 text-blue-600 hover:text-blue-800 font-semibold"
              >
                Manage my subscription →
              </Link>
            </div>
          </div>
        </div>

        <!-- Pricing Cards -->
        <div class="grid md:grid-cols-3 gap-8 max-w-7xl mx-auto">
          
          <!-- Dynamically render plans -->
          <div 
            v-for="plan in plans" 
            :key="plan.id"
            :class="[
              'bg-white/70 backdrop-blur-sm rounded-2xl shadow-xl border-2 p-8 hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 relative overflow-hidden',
              isPopularPlan(plan) ? 'border-blue-500 transform scale-105' : 'border-gray-200'
            ]"
          >
            <!-- Popular Badge -->
            <div v-if="isPopularPlan(plan)" class="absolute top-0 right-0 bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-4 py-1 rounded-bl-2xl text-sm font-semibold">
              ⭐ Popular
            </div>
            
            <div :class="[
              'absolute top-0 right-0 w-32 h-32 rounded-bl-full',
              getPlanGradient(plan)
            ]"></div>
            
            <div :class="isPopularPlan(plan) ? 'relative mt-8' : 'relative'">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-2xl font-bold text-gray-900">{{ plan.name }}</h3>
                <span class="text-3xl">{{ getPlanEmoji(plan) }}</span>
              </div>
              
              <div class="mb-6">
                <div v-if="plan.is_free" class="flex items-baseline">
                  <span class="text-5xl font-bold text-gray-900">$0</span>
                  <span class="text-gray-500 ml-2">/3 months</span>
                </div>
                <div v-else-if="plan.slug === 'private'" class="flex items-baseline">
                  <span class="text-5xl font-bold text-gray-900">Custom</span>
                </div>
                <div v-else class="flex items-baseline">
                  <span :class="[
                    'text-5xl font-bold',
                    isPopularPlan(plan) ? 'bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent' : 'text-gray-900'
                  ]">
                    {{ billingCycle === 'monthly' ? formatPrice(plan.monthly_price) : formatPrice(plan.yearly_price / 12) }}
                  </span>
                  <span class="text-gray-500 ml-2">/month</span>
                </div>
                
                <p v-if="plan.is_free" class="text-sm text-gray-500 mt-2">
                  Then {{ formatPrice(plan.monthly_price) }}/month
                </p>
                <p v-else-if="plan.slug === 'private'" class="text-sm text-gray-500 mt-2">
                  Pricing tailored to your needs
                </p>
                <p v-else-if="billingCycle === 'yearly'" class="text-sm text-green-600 font-semibold mt-2">
                  Only {{ formatPrice(plan.yearly_price) }}/year instead of {{ formatPrice(plan.monthly_price * 12) }}
                </p>
                <p v-else class="text-sm text-gray-500 mt-2">No commitment</p>
              </div>

              <!-- Plan Features -->
              <ul class="space-y-4 mb-8">
                <li v-for="feature in getPlanFeatures(plan)" :key="feature" class="flex items-start">
                  <svg :class="['w-5 h-5 mr-3 mt-0.5 flex-shrink-0', getPlanCheckColor(plan)]" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                  <span :class="feature.highlight ? 'text-gray-700 font-semibold' : 'text-gray-700'">
                    {{ feature.text }}
                  </span>
                </li>
              </ul>

              <!-- CTA Button -->
              <button
                v-if="plan.slug !== 'private'"
                @click="subscribeToPlan(plan)"
                :disabled="processing || isCurrentPlan(plan)"
                :class="[
                  'block w-full text-center px-6 py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5',
                  isCurrentPlan(plan) 
                    ? 'bg-gray-300 text-gray-600 cursor-not-allowed' 
                    : getPlanButtonClass(plan)
                ]"
              >
                {{ isCurrentPlan(plan) ? 'Current plan' : plan.is_free ? 'Start for free' : 'Choose this plan' }}
              </button>
              
              <Link
                v-else
                href="/landing#contact"
                class="block w-full text-center bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-purple-700 hover:to-pink-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
              >
                Contact us
              </Link>
              
              <p class="text-xs text-gray-500 text-center mt-3">
                {{ plan.is_free ? 'No credit card required' : plan.slug === 'private' ? 'Quote within 24h' : 'Cancel anytime' }}
              </p>
            </div>
          </div>

        </div>

        <!-- FAQ Section -->
        <div class="mt-20 max-w-4xl mx-auto">
          <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Frequently Asked Questions</h2>
          
          <div class="space-y-6">
            <div class="bg-white/70 backdrop-blur-sm rounded-xl p-6 shadow-md">
              <h3 class="font-semibold text-gray-900 mb-2">Can I change my plan at any time?</h3>
              <p class="text-gray-600">Yes, you can upgrade or downgrade your plan at any time. Changes take effect immediately with prorated billing.</p>
            </div>
            
            <div class="bg-white/70 backdrop-blur-sm rounded-xl p-6 shadow-md">
              <h3 class="font-semibold text-gray-900 mb-2">What happens after the 3 free months?</h3>
              <p class="text-gray-600">You will receive a reminder 7 days before the end of your trial period. You can then choose to continue with a paid subscription or cancel at no cost.</p>
            </div>
            
            <div class="bg-white/70 backdrop-blur-sm rounded-xl p-6 shadow-md">
              <h3 class="font-semibold text-gray-900 mb-2">What payment methods do you accept?</h3>
              <p class="text-gray-600">We accept credit cards (Visa, Mastercard, American Express) and bank transfers for businesses.</p>
            </div>

            <div class="bg-white/70 backdrop-blur-sm rounded-xl p-6 shadow-md">
              <h3 class="font-semibold text-gray-900 mb-2">Can I cancel my subscription?</h3>
              <p class="text-gray-600">Yes, you can cancel at any time. Your subscription will remain active until the end of the paid period.</p>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gradient-to-r from-blue-600 to-indigo-600 text-white">
      <div class="container mx-auto px-6 max-w-4xl text-center">
        <h2 class="text-4xl font-bold mb-6">
          Ready to get started?
        </h2>
        <p class="text-xl text-blue-100 mb-8">
          Try free for 3 months, no commitment required
        </p>
        <Link 
          href="/registerTenant"
          class="inline-block bg-white text-blue-600 px-8 py-4 rounded-full font-semibold hover:bg-gray-100 transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 text-lg"
        >
          Create my free workspace
        </Link>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-12">
      <div class="container mx-auto px-6 max-w-6xl">
        <div class="grid md:grid-cols-4 gap-8">
          <div>
            <div class="flex items-center space-x-3 mb-4">
              <img
                src="/images/openart-image_zEKwmagc_1750239814753_raw.jpg"
                class="h-10 w-auto"
              />
              <span class="font-bold text-gray-900">SQL-INFO</span>
            </div>
            <p class="text-gray-600 text-sm">
              The modern solution for documenting your databases.
            </p>
          </div>
          
          <div>
            <h4 class="font-semibold text-gray-900 mb-4">Product</h4>
            <ul class="space-y-2 text-sm text-gray-600">
              <li><a href="/landing#features" class="hover:text-blue-600 transition-colors">Features</a></li>
              <li><a href="#" class="hover:text-blue-600 transition-colors">Pricing</a></li>
              <li><a href="#" class="hover:text-blue-600 transition-colors">Security</a></li>
            </ul>
          </div>
          
          <div>
            <h4 class="font-semibold text-gray-900 mb-4">Support</h4>
            <ul class="space-y-2 text-sm text-gray-600">
              <li><a href="#" class="hover:text-blue-600 transition-colors">Documentation</a></li>
              <li><a href="#" class="hover:text-blue-600 transition-colors">Guides</a></li>
              <li><a href="/landing#contact" class="hover:text-blue-600 transition-colors">Contact</a></li>
            </ul>
          </div>
          
          <div>
            <h4 class="font-semibold text-gray-900 mb-4">Legal</h4>
            <ul class="space-y-2 text-sm text-gray-600">
              <li><a href="#" class="hover:text-blue-600 transition-colors">Privacy</a></li>
              <li><a href="#" class="hover:text-blue-600 transition-colors">Terms of Service</a></li>
              <li><a href="#" class="hover:text-blue-600 transition-colors">Cookies</a></li>
            </ul>
          </div>
        </div>
        
        <div class="border-t border-gray-200 mt-8 pt-8 text-center text-sm text-gray-500">
          © {{ new Date().getFullYear() }} SQL-INFO. All rights reserved.
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
  plans: {
    type: Array,
    required: true
  },
  currentSubscription: {
    type: Object,
    default: null
  }
})

const billingCycle = ref('monthly')
const processing = ref(false)

const maxSavings = computed(() => {
  const savings = props.plans.map(plan => plan.yearly_savings_percentage)
  return Math.max(...savings)
})

function toggleBilling() {
  billingCycle.value = billingCycle.value === 'monthly' ? 'yearly' : 'monthly'
}

function formatPrice(price) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

function isPopularPlan(plan) {
  return plan.slug === 'pro' || plan.slug === 'basic'
}

function isCurrentPlan(plan) {
  return props.currentSubscription && props.currentSubscription.plan.id === plan.id
}

function getPlanEmoji(plan) {
  const emojis = {
    'basic': '🎁',
    'pro': '🚀',
    'enterprise': '💼',
    'private': '👑'
  }
  return emojis[plan.slug] || '📦'
}

function getPlanGradient(plan) {
  const gradients = {
    'basic': 'bg-gradient-to-br from-green-400/10 to-emerald-400/10',
    'pro': 'bg-gradient-to-br from-blue-400/10 to-indigo-400/10',
    'enterprise': 'bg-gradient-to-br from-purple-400/10 to-pink-400/10',
    'private': 'bg-gradient-to-br from-yellow-400/10 to-orange-400/10'
  }
  return gradients[plan.slug] || 'bg-gradient-to-br from-gray-400/10 to-gray-600/10'
}

function getPlanCheckColor(plan) {
  const colors = {
    'basic': 'text-green-500',
    'pro': 'text-blue-500',
    'enterprise': 'text-purple-500',
    'private': 'text-yellow-500'
  }
  return colors[plan.slug] || 'text-gray-500'
}

function getPlanButtonClass(plan) {
  const classes = {
    'basic': 'bg-gradient-to-r from-green-500 to-emerald-600 text-white hover:from-green-600 hover:to-emerald-700',
    'pro': 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white hover:from-blue-700 hover:to-indigo-700',
    'enterprise': 'bg-gradient-to-r from-purple-600 to-pink-600 text-white hover:from-purple-700 hover:to-pink-700',
    'private': 'bg-gradient-to-r from-yellow-600 to-orange-600 text-white hover:from-yellow-700 hover:to-orange-700'
  }
  return classes[plan.slug] || 'bg-gray-600 text-white hover:bg-gray-700'
}

function getPlanFeatures(plan) {
  // Default features by slug
  const featureMap = {
    'basic': [
      { text: 'Up to 5 databases', highlight: false },
      { text: '3 users', highlight: false },
      { text: 'Email support', highlight: false },
      { text: 'All basic features', highlight: false }
    ],
    'pro': [
      { text: 'Unlimited databases', highlight: true },
      { text: 'Up to 10 users', highlight: true },
      { text: 'Priority support', highlight: false },
      { text: 'API & Webhooks', highlight: false },
      { text: 'Advanced integrations', highlight: false }
    ],
    'enterprise': [
      { text: 'Everything unlimited', highlight: true },
      { text: 'Dedicated 24/7 support', highlight: false },
      { text: 'Custom SLA', highlight: false },
      { text: 'Dedicated hosting', highlight: false },
      { text: 'Custom training', highlight: false }
    ],
    'private': [
      { text: 'Custom configuration', highlight: true },
      { text: 'Dedicated infrastructure', highlight: false },
      { text: 'Premium support', highlight: false },
      { text: 'Custom contract', highlight: false }
    ]
  }
  
  return featureMap[plan.slug] || []
}

function subscribeToPlan(plan) {
  if (processing.value || isCurrentPlan(plan)) return
  
  // If user already has a subscription, update it
  if (props.currentSubscription) {
    processing.value = true
    
    router.post('/subscriptions', {
      subscription_plan_id: plan.id,
      billing_cycle: billingCycle.value
    }, {
      onFinish: () => {
        processing.value = false
      }
    })
  } else {
    // Otherwise, redirect to registration with selected plan
    const params = new URLSearchParams({
      plan: plan.slug,
      cycle: billingCycle.value
    })
    window.location.href = `/registerTenant?${params.toString()}`
  }
}
</script>