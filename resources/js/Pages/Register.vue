<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 flex items-center justify-center p-6 relative">
    <!-- Background decorations -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-blue-400/20 to-indigo-600/20 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-br from-purple-400/20 to-pink-600/20 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-2xl relative">
      <!-- Back to home -->
      <div class="mb-2 text-center">
        <a href="/landing" class="inline-flex items-center text-gray-600 hover:text-blue-600 transition-colors">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Back to home
        </a>
      </div>

      <!-- Selected plan badge -->
      <div v-if="props.selectedPlan" class="mb-4">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clip-rule="evenodd" />
              </svg>
              <div>
                <p class="text-sm font-semibold text-blue-900">Selected plan: {{ props.selectedPlan.name }}</p>
                <p class="text-xs text-blue-700">
                  {{ props.selectedCycle === 'yearly' ? 'Annual billing' : 'Monthly billing' }} - 3 months free 🎁
                </p>
              </div>
            </div>
            <a href="/pricing" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Change</a>
          </div>
        </div>
      </div>

      <!-- Main form -->
      <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-200/50 p-8">
        <!-- Steps indicator -->
        <div class="flex items-center justify-center space-x-4 mb-8">
          <div class="flex items-center">
            <div
              :class="['w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold transition-all duration-300',
                       currentStep === 1 ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-600']">1</div>
            <span :class="['ml-3 text-sm font-medium transition-colors duration-300',
                           currentStep === 1 ? 'text-blue-600' : 'text-gray-500']">Personal Information</span>
          </div>

          <div :class="['w-16 h-px transition-colors duration-300', currentStep === 2 ? 'bg-blue-300' : 'bg-gray-300']"></div>

          <div class="flex items-center">
            <div
              :class="['w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold transition-all duration-300',
                       currentStep === 2 ? 'bg-blue-600 text-white' : currentStep > 1 ? 'bg-blue-100 text-blue-600' : 'bg-gray-200 text-gray-500']">2</div>
            <span :class="['ml-3 text-sm font-medium transition-colors duration-300',
                           currentStep === 2 ? 'text-blue-600' : 'text-gray-500']">Organization Setup</span>
          </div>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Step 1 -->
          <div v-show="currentStep === 1" class="space-y-6">
            <div class="text-center mb-6">
              <p class="text-gray-600">We'll need some basic information to get started</p>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Your name</label>
              <input v-model="form.contact_name" @blur="validateField('contact_name')" type="text" placeholder="John Doe"
                     class="input-modern w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              <div v-if="form.errors.contact_name" class="text-red-500 text-sm mt-1">{{ form.errors.contact_name }}</div>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Contact email</label>
              <input v-model="form.contact_email" @blur="validateField('contact_email')" type="email"
                     placeholder="john@whatever.com"
                     class="input-modern w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              <div v-if="form.errors.contact_email" class="text-red-500 text-sm mt-1">{{ form.errors.contact_email }}</div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                <input v-model="form.admin_password" @blur="validateField('admin_password')" type="password"
                       placeholder="••••••••"
                       class="input-modern w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                <div v-if="form.errors.admin_password" class="text-red-500 text-sm mt-1">{{ form.errors.admin_password }}</div>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Confirm Password</label>
                <input v-model="form.admin_password_confirmation" type="password" placeholder="••••••••"
                       class="input-modern w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              </div>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
              <input v-model="form.address" @blur="validateField('address')" type="text" placeholder="123 Avenue Road"
                     class="input-modern w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              <div v-if="form.errors.address" class="text-red-500 text-sm mt-1">{{ form.errors.address }}</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Postal Code</label>
                <input v-model="form.postalcode" @blur="validateField('postalcode')" type="text" placeholder="75001"
                       class="input-modern w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                <div v-if="form.errors.postalcode" class="text-red-500 text-sm mt-1">{{ form.errors.postalcode }}</div>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">City</label>
                <input v-model="form.city" @blur="validateField('city')" type="text" placeholder="Paris"
                       class="input-modern w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                <div v-if="form.errors.city" class="text-red-500 text-sm mt-1">{{ form.errors.city }}</div>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Country</label>
                <select v-model="form.country" @blur="validateField('country')"
                        class="input-modern w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="">Select a country</option>
                  <option v-for="(label, code) in countries" :key="code" :value="code">{{ label }}</option>
                </select>
                <div v-if="form.errors.country" class="text-red-500 text-sm mt-1">{{ form.errors.country }}</div>
              </div>
            </div>

            <button type="button" @click="nextStep" :disabled="!isStep1Valid"
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-4 rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed">
              <span class="flex items-center justify-center">
                Continue to Organization Setup
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
              </span>
            </button>
          </div>

          <!-- Step 2 -->
          <div v-show="currentStep === 2" class="space-y-6">
            <div class="text-center mb-6">
              <h2 class="text-xl font-semibold text-gray-900 mb-2">Setup your organization</h2>
              <p class="text-gray-600">Configure your workspace settings</p>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Name of your organization</label>
              <input v-model="form.name" @blur="validateField('name')" type="text" placeholder="Ex: Capsule Corp"
                     class="input-modern w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">Unique identifier</label>
              <div class="relative">
                <input v-model="form.slug" @blur="validateField('slug')" type="text" placeholder="your-company-name"
                       class="input-modern w-full px-4 py-3 pr-32 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-sm text-gray-500">.{{ domain }}</span>
              </div>
              <p class="text-xs text-gray-500 mt-1">Your URL will be: {{ form.slug || 'your-id' }}.{{ domain }}</p>
              <div v-if="form.errors.slug" class="text-red-500 text-sm mt-1">{{ form.errors.slug }}</div>
            </div>

            <!-- Logo Upload -->
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Company Logo (optional)
              </label>
              
              <div class="flex items-center space-x-4">
                <!-- Preview -->
                <div v-if="logoPreview" class="relative">
                  <img :src="logoPreview" alt="Logo preview" class="w-24 h-24 object-cover rounded-lg border-2 border-gray-300">
                  <button 
                    type="button"
                    @click="removeLogo"
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                
                <!-- Upload button -->
                <div v-else class="flex-1">
                  <label class="cursor-pointer">
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition-colors">
                      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <p class="mt-2 text-sm text-gray-600">
                        Click to upload or drag and drop
                      </p>
                      <p class="text-xs text-gray-500">PNG, JPG, SVG up to 2MB</p>
                    </div>
                    <input 
                      type="file" 
                      @change="handleLogoUpload" 
                      accept="image/png,image/jpeg,image/jpg,image/svg+xml"
                      class="hidden"
                    >
                  </label>
                </div>
              </div>
              
              <div v-if="form.errors.logo" class="text-red-500 text-sm mt-1">{{ form.errors.logo }}</div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Organization type</label>
                <select v-model="form.type" @blur="validateField('type')"
                        class="input-modern w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="pro">Enterprise</option>
                  <option value="private">Private</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Industry</label>
                <select v-model="form.industry" @blur="validateField('industry')"
                        class="input-modern w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option value="">Select an industry</option>
                  <option value="technology">Technology</option>
                  <option value="healthcare">Healthcare</option>
                  <option value="finance">Finance</option>
                  <option value="education">Education</option>
                  <option value="retail">Retail</option>
                  <option value="manufacturing">Manufacturing</option>
                  <option value="consulting">Consulting</option>
                  <option value="real_estate">Real Estate</option>
                  <option value="hospitality">Hospitality</option>
                  <option value="entertainment">Entertainment</option>
                  <option value="other">Other</option>
                </select>
                <div v-if="form.errors.industry" class="text-red-500 text-sm mt-1">{{ form.errors.industry }}</div>
              </div>
            </div>

            <div class="flex space-x-4">
              <button type="button" @click="previousStep"
                      class="flex-1 bg-gray-100 text-gray-700 py-4 rounded-xl font-semibold hover:bg-gray-200 transition-all duration-300">
                <span class="flex items-center justify-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                  </svg>
                  Back
                </span>
              </button>

              <button type="submit" :disabled="form.processing || !isStep2Valid"
                      class="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-4 rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed">
                <span v-if="!form.processing" class="flex items-center justify-center">
                  Create my space
                  <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                  </svg>
                </span>
                <span v-else class="flex items-center justify-center">
                  <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                       viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Creation in progress...
                </span>
              </button>
            </div>
          </div>

          <!-- Error block -->
          <div v-if="form.hasErrors" class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
              <svg class="w-5 h-5 text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Please check the following errors:</h3>
                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                  <li v-for="(error, key) in form.errors" :key="key">
                    {{ Array.isArray(error) ? error[0] : error }}
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- <div class="text-center mt-6">
        <p class="text-gray-600">
          Already have an account?
          <a href="/login" class="text-blue-600 hover:text-blue-800 font-medium">Log in here</a>
        </p>
      </div> -->
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

// === Props ===
const props = defineProps({
  domain: { type: String, default: 'domain.com' },
  selectedPlan: { type: Object, default: null },
  selectedCycle: { type: String, default: 'monthly' }
})

// État pour le logo
const logoPreview = ref(null)

// === State ===
const currentStep = ref(1)
const form = useForm({
  contact_name: '',
  contact_email: '',
  admin_password: '',
  admin_password_confirmation: '',
  address: '',
  postalcode: '',
  country: '',
  city: '',
  name: '',
  slug: '',
  type: 'pro',
  industry: '',
  selected_plan_slug: '',
  billing_cycle: '',
  logo : null
})

// Gérer l'upload du logo
function handleLogoUpload(event) {
  const file = event.target.files[0]
  
  if (!file) return
  
  // Vérifier la taille (max 2MB)
  if (file.size > 2 * 1024 * 1024) {
    form.errors.logo = 'The logo must not exceed 2MB.'
    return
  }
  
  // Vérifier le type
  const validTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml']
  if (!validTypes.includes(file.type)) {
    form.errors.logo = 'The logo must be a PNG, JPG, or SVG file.'
    return
  }
  
  // Créer preview
  const reader = new FileReader()
  reader.onload = (e) => {
    logoPreview.value = e.target.result
  }
  reader.readAsDataURL(file)
  
  // Stocker le fichier dans le form
  form.logo = file
  form.errors.logo = null
}

// Supprimer le logo
function removeLogo() {
  logoPreview.value = null
  form.logo = null
  form.errors.logo = null
}

// === Lifecycle ===
onMounted(() => {
  form.selected_plan_slug = props.selectedPlan?.slug || 'basic'
  form.billing_cycle = props.selectedCycle || 'monthly'
})

const countries = {
  AF: "Afghanistan",
  AL: "Albania",
  DZ: "Algeria",
  AD: "Andorra",
  AO: "Angola",
  AG: "Antigua and Barbuda",
  AR: "Argentina",
  AM: "Armenia",
  AU: "Australia",
  AT: "Austria",
  AZ: "Azerbaijan",
  BS: "Bahamas",
  BH: "Bahrain",
  BD: "Bangladesh",
  BB: "Barbados",
  BY: "Belarus",
  BE: "Belgium",
  BZ: "Belize",
  BJ: "Benin",
  BT: "Bhutan",
  BO: "Bolivia",
  BA: "Bosnia and Herzegovina",
  BW: "Botswana",
  BR: "Brazil",
  BN: "Brunei",
  BG: "Bulgaria",
  BF: "Burkina Faso",
  BI: "Burundi",
  KH: "Cambodia",
  CM: "Cameroon",
  CA: "Canada",
  CV: "Cape Verde",
  CF: "Central African Republic",
  TD: "Chad",
  CL: "Chile",
  CN: "China",
  CO: "Colombia",
  KM: "Comoros",
  CG: "Congo",
  CD: "Congo (Democratic Republic)",
  CR: "Costa Rica",
  HR: "Croatia",
  CU: "Cuba",
  CY: "Cyprus",
  CZ: "Czech Republic",
  DK: "Denmark",
  DJ: "Djibouti",
  DM: "Dominica",
  DO: "Dominican Republic",
  EC: "Ecuador",
  EG: "Egypt",
  SV: "El Salvador",
  GQ: "Equatorial Guinea",
  ER: "Eritrea",
  EE: "Estonia",
  SZ: "Eswatini",
  ET: "Ethiopia",
  FJ: "Fiji",
  FI: "Finland",
  FR: "France",
  GA: "Gabon",
  GM: "Gambia",
  GE: "Georgia",
  DE: "Germany",
  GH: "Ghana",
  GR: "Greece",
  GD: "Grenada",
  GT: "Guatemala",
  GN: "Guinea",
  GW: "Guinea-Bissau",
  GY: "Guyana",
  HT: "Haiti",
  HN: "Honduras",
  HU: "Hungary",
  IS: "Iceland",
  IN: "India",
  ID: "Indonesia",
  IR: "Iran",
  IQ: "Iraq",
  IE: "Ireland",
  IL: "Israel",
  IT: "Italy",
  JM: "Jamaica",
  JP: "Japan",
  JO: "Jordan",
  KZ: "Kazakhstan",
  KE: "Kenya",
  KI: "Kiribati",
  KW: "Kuwait",
  KG: "Kyrgyzstan",
  LA: "Laos",
  LV: "Latvia",
  LB: "Lebanon",
  LS: "Lesotho",
  LR: "Liberia",
  LY: "Libya",
  LI: "Liechtenstein",
  LT: "Lithuania",
  LU: "Luxembourg",
  MG: "Madagascar",
  MW: "Malawi",
  MY: "Malaysia",
  MV: "Maldives",
  ML: "Mali",
  MT: "Malta",
  MH: "Marshall Islands",
  MR: "Mauritania",
  MU: "Mauritius",
  MX: "Mexico",
  FM: "Micronesia",
  MD: "Moldova",
  MC: "Monaco",
  MN: "Mongolia",
  ME: "Montenegro",
  MA: "Morocco",
  MZ: "Mozambique",
  MM: "Myanmar",
  NA: "Namibia",
  NR: "Nauru",
  NP: "Nepal",
  NL: "Netherlands",
  NZ: "New Zealand",
  NI: "Nicaragua",
  NE: "Niger",
  NG: "Nigeria",
  KP: "North Korea",
  MK: "North Macedonia",
  NO: "Norway",
  OM: "Oman",
  PK: "Pakistan",
  PW: "Palau",
  PA: "Panama",
  PG: "Papua New Guinea",
  PY: "Paraguay",
  PE: "Peru",
  PH: "Philippines",
  PL: "Poland",
  PT: "Portugal",
  QA: "Qatar",
  RO: "Romania",
  RU: "Russia",
  RW: "Rwanda",
  KN: "Saint Kitts and Nevis",
  LC: "Saint Lucia",
  VC: "Saint Vincent and the Grenadines",
  WS: "Samoa",
  SM: "San Marino",
  ST: "Sao Tome and Principe",
  SA: "Saudi Arabia",
  SN: "Senegal",
  RS: "Serbia",
  SC: "Seychelles",
  SL: "Sierra Leone",
  SG: "Singapore",
  SK: "Slovakia",
  SI: "Slovenia",
  SB: "Solomon Islands",
  SO: "Somalia",
  ZA: "South Africa",
  KR: "South Korea",
  SS: "South Sudan",
  ES: "Spain",
  LK: "Sri Lanka",
  SD: "Sudan",
  SR: "Suriname",
  SE: "Sweden",
  CH: "Switzerland",
  SY: "Syria",
  TW: "Taiwan",
  TJ: "Tajikistan",
  TZ: "Tanzania",
  TH: "Thailand",
  TL: "Timor-Leste",
  TG: "Togo",
  TO: "Tonga",
  TT: "Trinidad and Tobago",
  TN: "Tunisia",
  TR: "Turkey",
  TM: "Turkmenistan",
  TV: "Tuvalu",
  UG: "Uganda",
  UA: "Ukraine",
  AE: "United Arab Emirates",
  GB: "United Kingdom",
  US: "United States",
  UY: "Uruguay",
  UZ: "Uzbekistan",
  VU: "Vanuatu",
  VA: "Vatican City",
  VE: "Venezuela",
  VN: "Vietnam",
  YE: "Yemen",
  ZM: "Zambia",
  ZW: "Zimbabwe"
};

// === Validation Helpers ===
const validateEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
const isStep1Valid = computed(() => {
  return (
    form.contact_name &&
    form.contact_email &&
    form.admin_password &&
    form.admin_password_confirmation &&
    form.address &&
    form.postalcode &&
    form.country &&
    form.city
  )
})
const isStep2Valid = computed(() => {
  return form.name && form.slug && form.type && form.industry
})

// === Validation dynamique sur blur ===
async function validateField(field) {
  form.errors[field] = null

  switch (field) {
    case 'contact_name':
      if (!form.contact_name.trim()) {
        form.errors.contact_name = 'Le nom est obligatoire.'
      }
      break

    case 'contact_email':
      if (!form.contact_email) {
        form.errors.contact_email = 'L’email est obligatoire.'
      } else if (!validateEmail(form.contact_email)) {
        form.errors.contact_email = 'Format d’email invalide.'
      } else {
        try {
          const { data } = await axios.post('/api/check-email', {
            email: form.contact_email
          })
          if (data.exists) {
            form.errors.contact_email = 'Cet email est déjà utilisé.'
          }
        } catch (e) {
          console.error('Erreur check-email:', e)
        }
      }
      break

    case 'admin_password':
      if (form.admin_password.length < 8) {
        form.errors.admin_password = 'Le mot de passe doit contenir au moins 8 caractères.'
      }
      break

    case 'admin_password_confirmation':
      if (form.admin_password_confirmation !== form.admin_password) {
        form.errors.admin_password_confirmation = 'Les mots de passe ne correspondent pas.'
      }
      break

    case 'address':
      if (!form.address.trim()) {
        form.errors.address = 'L’adresse est obligatoire.'
      }
      break

    case 'postalcode':
      if (!form.postalcode.trim()) {
        form.errors.postalcode = 'Le code postal est obligatoire.'
      }
      break

    case 'city':
      if (!form.city.trim()) {
        form.errors.city = 'La ville est obligatoire.'
      }
      break

    case 'slug':
      if (!form.slug.trim()) {
        form.errors.slug = 'L’identifiant est obligatoire.'
      } else {
        try {
          const { data } = await axios.post('/api/check-slug', {
            slug: form.slug
          })
          if (data.exists) {
            form.errors.slug = 'Cet identifiant est déjà pris.'
          }
        } catch (e) {
          console.error('Erreur check-slug:', e)
        }
      }
      break
  }
}

// === Navigation étapes ===
function nextStep() {
  if (currentStep.value < 2 && isStep1Valid.value) currentStep.value++
}
function previousStep() {
  if (currentStep.value > 1) currentStep.value--
}

// === Soumission ===
function handleSubmit() {
  if (currentStep.value === 1) {
    nextStep()
  } else {
    submit()
  }
}

// === Envoi Inertia ===
function submit() {
  form
    .transform((data) => data)
    .post('/start', {
      preserveScroll: false,
      preserveState: false,
      replace: false,
      onError: (errors) => {
        if (
          errors.contact_name ||
          errors.contact_email ||
          errors.admin_password ||
          errors.address ||
          errors.postalcode ||
          errors.country ||
          errors.city
        ) {
          currentStep.value = 1
        }
      }
    })
}
</script>
