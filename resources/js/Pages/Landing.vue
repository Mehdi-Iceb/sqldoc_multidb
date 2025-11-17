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
          <a href="#features" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">
            Features
          </a>
          <a href="/pricing" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">
            Pricing
          </a>
          <a href="#contact" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">
            Contact
          </a>
        </nav>

        <div class="flex items-center space-x-4">
          <!-- <a href="/login" class="text-gray-700 hover:text-blue-600 transition-colors font-medium">
            Sign in
          </a> -->
          <button 
            @click="goToPricing"
            class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-2.5 rounded-full font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
          >
            Get Started
          </button>
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
          <!-- <div class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-medium mb-8">
            🚀 New: SQL Server support now available
          </div> -->
          
          <h1 class="text-5xl md:text-6xl font-bold leading-tight mb-8 bg-gradient-to-r from-gray-900 via-blue-800 to-indigo-900 bg-clip-text text-transparent">
            Document your databases intelligently
            <!-- <span class="block text-transparent bg-gradient-to-r from-blue-800 to-indigo-700 bg-clip-text">
              intelligently
            </span> -->
          </h1>
          
          <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-12 leading-relaxed">
            Connect, explore, and document your PostgreSQL, MySQL, and SQL Server schemas 
            with a modern interface that transforms complexity into clarity.
          </p>
          
          <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <button 
              @click="goToPricing"
              class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-4 rounded-full font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 text-lg"
            >
              Create my workspace
            </button>
            <!-- <button class="text-gray-700 hover:text-blue-600 transition-colors font-medium flex items-center space-x-2">
              <span>Watch demo</span>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m-6-8h12a2 2 0 012 2v8a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2z" />
              </svg>
            </button> -->
          </div>
        </div>

        <!-- Dashboard Preview -->
        <div class="relative max-w-5xl mx-auto">
          <div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-2xl border border-gray-200/50 p-8 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50/50 to-indigo-50/50"></div>
            <div class="relative">
              <div class="flex items-center space-x-2 mb-6">
                <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                <div class="w-3 h-3 bg-green-400 rounded-full"></div>
              </div>
              <div class="bg-gray-100 rounded-lg overflow-hidden">
                <img 
                  src="/images/dashboard_sqlinfo2.jpg" 
                  alt="SQL Info Dashboard Preview"
                  class="w-full h-auto object-cover"
                />
              </div>
            </div>
          </div>
        </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
      <div class="container mx-auto px-6 max-w-6xl">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold text-gray-900 mb-6">
            Why choose SQL-INFO?
          </h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            A complete suite of tools designed to simplify documentation 
            and collaboration around your databases.
          </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
          <FeatureCard
            icon="🧠"
            title="Intelligent exploration"
            description="Automatic schema analysis, relationship detection, and documentation suggestions."
            :features="['Automatic scan', 'Pattern detection', 'AI suggestions']"
          />
          <FeatureCard
            icon="👥"
            title="Real-time collaboration"
            description="Work as a team with comments, annotations, and a granular permission system."
            :features="['Shared comments', 'Notifications', 'Access control']"
          />
          <FeatureCard
            icon="🔌"
            title="Native integrations"
            description="Connect your favorite tools: PostgreSQL, MySQL, SQL Server, and more."
            :features="['Multi-DBMS', 'REST API', 'Webhooks']"
          />
        </div>
      </div>
    </section>

    <!-- Stats Section -->
    <section class="py-20 bg-gradient-to-r from-blue-600 to-indigo-600 text-white">
      <div class="container mx-auto px-6 max-w-4xl">
        <div class="grid md:grid-cols-3 gap-8 text-center">
          <div>
            <div class="text-4xl font-bold mb-2">2,500+</div>
            <div class="text-blue-100">Documented databases</div>
          </div>
          <div>
            <div class="text-4xl font-bold mb-2">150+</div>
            <div class="text-blue-100">Active teams</div>
          </div>
          <div>
            <div class="text-4xl font-bold mb-2">99.9%</div>
            <div class="text-blue-100">Guaranteed uptime</div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-gray-50">
      <div class="container mx-auto px-6 max-w-4xl text-center">
        <h2 class="text-4xl font-bold text-gray-900 mb-6">
          Ready to transform your documentation?
        </h2>
        <p class="text-xl text-gray-600 mb-8">
          Join hundreds of teams who trust SQL-INFO
        </p>
        <button 
          @click="goToPricing"
          class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-8 py-4 rounded-full font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 text-lg"
        >
          Start now
        </button>
      </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="bg-white border-t border-gray-200 py-12">
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
              <li><a href="#" class="hover:text-blue-600 transition-colors">Features</a></li>
              <li><a href="#" class="hover:text-blue-600 transition-colors">Pricing</a></li>
              <li><a href="#" class="hover:text-blue-600 transition-colors">Security</a></li>
            </ul>
          </div>
          
          <div>
            <h4 class="font-semibold text-gray-900 mb-4">Support</h4>
            <ul class="space-y-2 text-sm text-gray-600">
              <li><a href="#" class="hover:text-blue-600 transition-colors">Documentation</a></li>
              <li><a href="#" class="hover:text-blue-600 transition-colors">Guides</a></li>
              <li><a href="#" class="hover:text-blue-600 transition-colors">Contact</a></li>
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
import { router } from '@inertiajs/vue3'
import { h } from 'vue'

function goToPricing() {
  router.visit('/pricing')
}


const FeatureCard = (props) => 
  h('div', { 
    class: 'bg-white rounded-2xl p-8 shadow-lg border border-gray-100 hover:shadow-xl transition-all duration-300 hover:-translate-y-1 group' 
  }, [
    h('div', { class: 'text-4xl mb-6 group-hover:scale-110 transition-transform duration-300' }, props.icon),
    h('h3', { class: 'text-2xl font-bold text-gray-900 mb-4' }, props.title),
    h('p', { class: 'text-gray-600 mb-6 leading-relaxed' }, props.description),
    h('ul', { class: 'space-y-2' }, 
      props.features.map(feature => 
        h('li', { class: 'flex items-center text-sm text-gray-500' }, [
          h('svg', { 
            class: 'w-4 h-4 text-green-500 mr-2',
            fill: 'currentColor',
            viewBox: '0 0 20 20'
          }, [
            h('path', { 
              'fill-rule': 'evenodd',
              d: 'M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z',
              'clip-rule': 'evenodd'
            })
          ]),
          feature
        ])
      )
    )
  ])
</script>

