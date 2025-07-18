<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">
          <span class="text-gray-500 font-normal">Stored Procedures :</span> 
          {{ procedureName }}
        </h2>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-10xl mx-auto sm:px-6 lg:px-8 space-y-8">
        
        <!-- Loading spinner -->
        <div v-if="loading" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg shadow-xl p-8 flex flex-col items-center max-w-sm w-full mx-4">
            <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-green-600 mb-6"></div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Loading procedure details</h3>
            <p class="text-gray-600 text-center mb-4">
              Retrieving structure for <span class="font-medium text-blue-600">{{ procedureName }}</span>
            </p>
          </div>
        </div>

        <!-- Error state -->
        <div v-else-if="currentError" 
             class="bg-red-50 border-l-4 border-red-400 p-6 rounded-lg shadow-sm">
          <div class="flex items-center">
            <svg class="h-5 w-5 text-red-400 mr-3" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <div class="text-red-700">{{ currentError }}</div>
          </div>
        </div>

        <!-- Success state -->
        <div v-else>
          <!-- Description de la procédure -->
          <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Description</h3>
                <button 
                  v-if="canEdit"
                  @click="saveDescription" 
                  class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  :disabled="saving"
                >
                  <span v-if="!saving">Save description</span>
                  <span v-else class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving...
                  </span>
                </button>
                <span v-else class="text-sm text-gray-500 italic">
                  Read only access
                </span>
              </div>
            </div>
            <div class="p-6">
              <textarea
                v-model="procedureForm.description"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                :class="{ 'opacity-50 cursor-not-allowed bg-gray-100': !canEdit }"
                placeholder="Optional description (use, environment, content...)"
                :disabled="!canEdit || saving"
                :readonly="!canEdit"
              ></textarea>
            </div>
          </div>

          <!-- Informations de la procédure -->
          <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <div class="flex items-center">
                <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900">Information</h3>
              </div>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <h4 class="text-sm font-semibold text-gray-500 mb-1">Schema</h4>
                <div class="bg-gray-50 p-3 rounded">
                  <p class="text-gray-800">{{ procedureData.schema || 'Not specified' }}</p>
                </div>
              </div>
              <div>
                <h4 class="text-sm font-semibold text-gray-500 mb-1">Creation Date</h4>
                <div class="bg-gray-50 p-3 rounded">
                  <p class="text-gray-800">{{ formatDate(procedureData.create_date) }}</p>
                </div>
              </div>
              <div>
                <h4 class="text-sm font-semibold text-gray-500 mb-1">Last Modification</h4>
                <div class="bg-gray-50 p-3 rounded">
                  <p class="text-gray-800">{{ formatDate(procedureData.modify_date) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Paramètres de la procédure -->
          <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Parameters</h3>
              </div>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead>
                  <tr class="bg-gray-50">
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Type
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Output
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Description
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Range Values
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Release
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      History
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="param in (procedureData?.parameters || [])" 
                      :key="param.parameter_name"
                      class="hover:bg-gray-50 transition-colors">
                    <!-- Nom du paramètre -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ param.parameter_name }}
                    </td>
                    
                    <!-- Type de données -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                      <span class="font-mono">{{ param.data_type }}</span>
                    </td>
                    
                    <!-- Output -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span :class="[
                          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                          param.is_output
                            ? 'bg-blue-100 text-blue-800'
                            : 'bg-gray-100 text-gray-800'
                        ]">
                          {{ param.is_output ? 'Yes' : 'No' }}
                        </span>
                     </td>
                    
                    <!-- Description -->
                    <td class="px-6 py-4 text-sm text-gray-500">
                      <div class="flex items-center space-x-2">
                        <!-- Mode lecture -->
                        <div v-if="editingParamId !== param.parameter_name" class="max-w-xs truncate">
                          <span class="max-w-xs truncate flex-1">
                            {{ param.description || '-' }}
                          </span>
                          <button
                            v-if="canEdit"
                            @click="startEdit(param)"
                            class="p-1 text-gray-400 hover:text-gray-600 flex-shrink-0"
                            title="Edit description"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                          </button>
                        </div>
                        
                        <!-- Mode édition -->
                        <div v-else class="flex items-center space-x-2 w-full">
                          <textarea
                            v-model="editingValue"
                            class="flex-1 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500 overflow-auto resize-none max-h-24"
                            :disabled="!canEdit"
                            @keydown.ctrl.enter="saveParameterDescription(param)"
                            @keydown.esc="cancelEdit"
                            rows="2"
                          ></textarea>
                          
                          <!-- Boutons sauvegarder/annuler -->
                          <div class="flex space-x-1 flex-shrink-0">
                            <button
                              @click="saveParameterDescription(param)"
                              class="p-1 text-green-600 hover:text-green-700"
                              :disabled="saving"
                            >
                              <svg v-if="!saving" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                              </svg>
                              <svg v-else class="animate-spin h-4 w-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                              </svg>
                            </button>
                            <button
                              @click="cancelEdit"
                              class="p-1 text-red-600 hover:text-red-700"
                              :disabled="saving"
                            >
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                              </svg>
                            </button>
                          </div>
                        </div>
                      </div>
                    </td>
                    
                    <!-- Range Values -->
                    <td class="px-6 py-4 text-sm text-gray-500">
                      <div class="flex items-center space-x-2">
                        <span v-if="!editingRangeValues[param.parameter_name]" class="max-w-xs truncate">
                          {{ param.rangevalues || '-' }}
                        </span>
                        <textarea
                          v-else
                          v-model="editingRangeValuesValue"
                          class="flex-1 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500"
                          :disabled="!canEdit"
                          @keyup.enter="saveRangeValues(param.parameter_name)"
                          @keyup.esc="cancelEditAdvanced('rangeValues', param.parameter_name)"
                        ></textarea>
                        <button
                          v-if="!editingRangeValues[param.parameter_name] && canEdit"
                          @click="startEditAdvanced('rangeValues', param.parameter_name, param.rangevalues)"
                          class="p-1 text-gray-400 hover:text-gray-600"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </button>
                        <div v-else-if="canEdit" class="flex space-x-1">
                          <button
                            @click="saveRangeValues(param.parameter_name)"
                            class="p-1 text-green-600 hover:text-green-700"
                            :disabled="savingRangeValues[param.parameter_name]"
                          >
                            <svg v-if="!savingRangeValues[param.parameter_name]" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="animate-spin h-4 w-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                          </button>
                          <button
                            @click="cancelEditAdvanced('rangeValues', param.parameter_name)"
                            class="p-1 text-red-600 hover:text-red-700"
                            :disabled="savingRangeValues[param.parameter_name]"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                          </button>
                        </div>
                      </div>
                    </td>
                    
                    <!-- Release -->
                    <td class="px-6 py-4 text-sm text-gray-500">
                      <div class="flex items-center space-x-2 relative">
                        <select 
                          :value="param.release_id || ''"
                          @change="updateColumnRelease(param, $event.target.value)"
                          :disabled="!canEdit || updatingRelease[param.parameter_name]"
                          :class="[
                            'block w-full pl-2 pr-7 py-1 text-xs border-gray-300 rounded-md',
                            param.release_id ? 'bg-blue-50 text-blue-800' : '',
                            !canEdit ? 'opacity-50 cursor-not-allowed' : '',
                            updatingRelease[param.parameter_name] ? 'opacity-50' : ''
                          ]"
                        >
                          <option value="">None</option>
                          <option v-for="release in availableReleases" :key="release.id" :value="release.id">
                            {{ release.display_name }}
                          </option>
                        </select>
                        <div v-if="updatingRelease[param.parameter_name]" class="absolute right-2 top-1/2 transform -translate-y-1/2">
                          <svg class="animate-spin h-3 w-3 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                        </div>
                      </div>
                    </td>
                    
                    <!-- History -->
                    <td class="px-4 py-3 text-sm">
                      <SecondaryButton @click="showAuditLogs(param.parameter_name)" :disabled="loadingAuditLogs && currentColumn === param.parameter_name">
                        <span v-if="!(loadingAuditLogs && currentColumn === param.parameter_name)">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                          </svg>
                        </span>
                        <span v-else>
                          <svg class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                        </span>
                      </SecondaryButton>
                    </td>
                  </tr>
                  
                  <!-- Message si aucun paramètre -->
                  <tr v-if="!procedureData?.parameters || procedureData.parameters.length === 0">
                    <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                      No parameters found
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Définition SQL -->
          <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <h3 class="text-lg font-medium text-gray-900">SQL Definition</h3>
            </div>
            <div class="p-6">
              <div class="bg-gray-900 p-4 rounded overflow-auto max-h-96">
                 <pre class="text-gray-200 text-sm whitespace-pre-wrap">{{ procedureData.definition || 'Definition not available' }}</pre>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal pour les audit logs -->
      <div v-if="showAuditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 shadow-lg rounded-md bg-white">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">
              Modification History - {{ currentColumn }}
            </h3>
            <button @click="closeAuditModal" class="text-gray-400 hover:text-gray-500">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div v-if="loadingAuditLogs" class="text-center py-8">
            <div class="flex flex-col items-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-3"></div>
              <p class="text-gray-600">Loading history...</p>
            </div>
          </div>
          
          <div v-else-if="auditLogs.length === 0" class="text-center py-4 text-gray-500">
            No modifications found for this parameter
          </div>
          
          <div v-else class="overflow-y-auto max-h-96">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Old Value</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">New Value</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="log in auditLogs" :key="log.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(log.created_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ log.user?.name || 'N/A' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <span :class="[
                      'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                      log.change_type === 'update' ? 'bg-yellow-100 text-yellow-800' :
                      log.change_type === 'add' ? 'bg-green-100 text-green-800' :
                      'bg-gray-100 text-gray-800'
                    ]">
                      {{ log.change_type }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                    {{ log.old_data || '-' }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                    {{ log.new_data || '-' }}
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

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { router } from '@inertiajs/vue3'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'

// ✅ Définition des props (basé sur votre version existante)
const props = defineProps({
  procedureName: {
    type: String,
    required: true
  },
  procedureDetails: {
    type: Object,
    required: true
  },
  availableReleases: {
    type: Array,
    default: () => []
  },
  permissions: {
    type: Object,
    default: () => ({})
  },
  error: {
    type: String,
    default: null
  }
})

// ✅ Computed pour les permissions (ajout)
const canEdit = computed(() => {
  return props.procedureDetails?.can_edit || props.permissions.can_edit || false
})

const isOwner = computed(() => {
  return props.procedureDetails?.is_owner || props.permissions.is_owner || false
})

// ✅ États locaux (votre version + ajouts)
const procedureForm = ref({
  description: props.procedureDetails.description || ''
})

const procedureData = ref(props.procedureDetails)
const loading = ref(false)
const saving = ref(false)
const editingParamId = ref(null)
const editingValue = ref('')

// ✅ NOUVEAUX ÉTATS pour les fonctionnalités avancées
const editingDescription = ref({})
const editingDescriptionValue = ref('')
const editingRangeValues = ref({})
const editingRangeValuesValue = ref('')
const savingDescription = ref({})
const savingRangeValues = ref({})
const updatingRelease = ref({})

// ✅ ÉTATS pour la modal d'audit
const showAuditModal = ref(false)
const loadingAuditLogs = ref(false)
const auditLogs = ref([])
const currentColumn = ref('')

// Computed pour l'erreur
const currentError = computed(() => props.error)

// ✅ WATCHERS (votre version existante)
watch(
  () => props.procedureDetails,
  (newProcedureDetails) => {
    console.log('🔍 [PROCEDURE] Props procedureDetails ont changé:', newProcedureDetails)
    
    // Mettre à jour les données locales avec les nouvelles props
    procedureData.value = { ...newProcedureDetails }
    procedureForm.value.description = newProcedureDetails.description || ''
    
    // Réinitialiser les états d'édition
    editingParamId.value = null
    editingValue.value = ''
    editingDescription.value = {}
    editingRangeValues.value = {}
    editingDescriptionValue.value = ''
    editingRangeValuesValue.value = ''
  },
  { deep: true, immediate: true }
)

watch(
  () => props.procedureName,
  (newProcedureName, oldProcedureName) => {
    if (newProcedureName !== oldProcedureName) {
      console.log(`🔍 [PROCEDURE] Nom de procédure changé: ${oldProcedureName} → ${newProcedureName}`)

      router.reload({ preserveScroll: true, preserveState: true }) // preserveScroll/State for better UX
      
      // Réinitialiser les états d'édition quand on change de procédure
      editingParamId.value = null
      editingValue.value = ''
      editingDescription.value = {}
      editingRangeValues.value = {}
      editingDescriptionValue.value = ''
      editingRangeValuesValue.value = ''
    }
  }
)

// ✅ FONCTIONS D'ÉDITION (votre version + améliorations)
const startEdit = (param) => {
  if (!canEdit.value) {
    alert('Vous n\'avez pas les permissions pour modifier cette procédure')
    return
  }
  editingParamId.value = param.parameter_name
  editingValue.value = param.description || ''
}


// ✅ NOUVELLES FONCTIONS pour l'édition avancée
const startEditAdvanced = (type, parameterName, currentValue) => {
  if (!canEdit.value) {
    alert('Vous n\'avez pas les permissions pour modifier cette procédure')
    return
  }
  
  if (type === 'description') {
    editingDescription.value = { [parameterName]: true }
    editingDescriptionValue.value = currentValue || ''
  } else if (type === 'rangeValues') {
    editingRangeValues.value = { [parameterName]: true }
    editingRangeValuesValue.value = currentValue || ''
  }
}

const cancelEdit = () => {
  editingParamId.value = null
  editingValue.value = ''
}

const cancelEditAdvanced = (type, parameterName) => {
  if (type === 'description') {
    editingDescription.value = { [parameterName]: false }
    editingDescriptionValue.value = ''
  } else if (type === 'rangeValues') {
    editingRangeValues.value = { [parameterName]: false }
    editingRangeValuesValue.value = ''
  }
}

// ✅ FONCTION DE SAUVEGARDE (votre version existante)
const saveDescription = async () => {
  if (!canEdit.value) {
    alert('Vous n\'avez pas les permissions pour modifier cette procédure')
    return
  }
  
  try {
    saving.value = true
    
    router.post(`/procedure/${props.procedureName}/description`, {
      description: procedureForm.value.description
    }, {
      onSuccess: () => {
        alert('Description de la procédure enregistrée avec succès!')
        procedureData.value.description = procedureForm.value.description
      },
      onError: (errors) => {
        console.error('Erreur lors de la sauvegarde:', errors)
        alert('Erreur lors de la sauvegarde de la description')
      },
      onFinish: () => {
        saving.value = false
      }
    })
  } catch (error) {
    console.error('Erreur:', error)
    saving.value = false
  }
}

// ✅ FONCTION DE SAUVEGARDE PARAMÈTRE (votre version existante)
const saveParameterDescription = async (param) => {
  try {
    const parameterIdentifier = param.parameter_name
    if (!parameterIdentifier) {
      alert("Impossible de sauvegarder : identifiant du paramètre manquant.")
      return
    }

    saving.value = true

    router.post(`/procedure/${props.procedureName}/parameters/${encodeURIComponent(parameterIdentifier)}/description`, {
      description: editingValue.value
    }, {
      onSuccess: () => {
        const index = procedureData.value.parameters.findIndex(p => p.parameter_name === parameterIdentifier)
        if (index !== -1) {
          procedureData.value.parameters[index].description = editingValue.value
        }
        alert('Description du paramètre enregistrée avec succès!')
        cancelEdit()
      },
      onError: (errors) => {
        console.error('Erreur lors de la sauvegarde:', errors)
        alert('Erreur lors de la sauvegarde de la description du paramètre')
      },
      onFinish: () => {
        saving.value = false
      }
    })
  } catch (error) {
    console.error('Erreur:', error)
    saving.value = false
  }
}

// ✅ NOUVELLES FONCTIONS pour l'édition avancée avec axios
const saveDescriptionAdvanced = async (parameterName) => {
  try {
    savingDescription.value[parameterName] = true
    
    const response = await axios.post(`/procedure/${props.procedureName}/parameter/${parameterName}/description`, {
      description: editingDescriptionValue.value
    })
    
    if (response.data.success) {
      const parameter = procedureData.value.parameters.find(p => p.parameter_name === parameterName)
      if (parameter) {
        parameter.description = editingDescriptionValue.value
      }
      cancelEditAdvanced('description', parameterName)
    } else {
      throw new Error(response.data.error)
    }
  } catch (error) {
    console.error('❌ Erreur:', error)
    alert('Erreur: ' + (error.response?.data?.error || error.message))
  } finally {
    savingDescription.value[parameterName] = false
  }
}

const saveRangeValues = async (parameterName) => {
  try {
    savingRangeValues.value[parameterName] = true
    
    const response = await axios.post(`/procedure/${props.procedureName}/parameter/${parameterName}/range-values`, {
      rangevalues: editingRangeValuesValue.value
    })
    
    if (response.data.success) {
      const parameter = procedureData.value.parameters.find(p => p.parameter_name === parameterName)
      if (parameter) {
        parameter.rangevalues = editingRangeValuesValue.value
      }
      cancelEditAdvanced('rangeValues', parameterName)
    } else {
      throw new Error(response.data.error)
    }
  } catch (error) {
    console.error('❌ Erreur:', error)
    alert('Erreur: ' + (error.response?.data?.error || error.message))
  } finally {
    savingRangeValues.value[parameterName] = false
  }
}

const updateColumnRelease = async (parameter, releaseId) => {
  if (!canEdit.value) {
    alert('Vous n\'avez pas les permissions pour modifier cette procédure')
    return
  }
  
  try {
    updatingRelease.value[parameter.parameter_name] = true
    
    const finalReleaseId = releaseId === '' ? null : parseInt(releaseId)
    
    const response = await axios.post(`/procedure/${props.procedureName}/parameter/${parameter.parameter_name}/release`, {
      release_id: finalReleaseId
    })
    
    if (response.data.success) {
      parameter.release_id = finalReleaseId
      const selectedRelease = props.availableReleases.find(r => r.id === finalReleaseId)
      parameter.release_version = selectedRelease ? selectedRelease.version_number : ''
    } else {
      throw new Error(response.data.error)
    }
  } catch (error) {
    console.error('❌ Erreur:', error)
    alert('Erreur: ' + (error.response?.data?.error || error.message))
  } finally {
    updatingRelease.value[parameter.parameter_name] = false
  }
}

// ✅ FONCTIONS pour la modal d'audit
const showAuditLogs = async (parameterName) => {
  try {
    console.log('🔍 Ouverture audit logs pour paramètre:', parameterName)
    
    showAuditModal.value = true
    loadingAuditLogs.value = true
    currentColumn.value = parameterName
    auditLogs.value = []
    
    const response = await axios.get(`/procedure/${props.procedureName}/parameter/${parameterName}/audit-logs`)
    
    console.log('📊 Audit logs reçus:', response.data)
    
    if (response.data && Array.isArray(response.data)) {
      auditLogs.value = response.data
    } else {
      auditLogs.value = []
      console.warn('Format de réponse inattendu:', response.data)
    }
    
  } catch (error) {
    console.error('❌ Erreur lors du chargement des audit logs:', error)
    
    let errorMessage = 'Erreur lors du chargement de l\'historique'
    
    if (error.response) {
      if (error.response.status === 404) {
        errorMessage = 'Procédure ou paramètre non trouvé'
      } else if (error.response.status === 400) {
        errorMessage = error.response.data.error || 'Requête invalide'
      } else if (error.response.data && error.response.data.error) {
        errorMessage = error.response.data.error
      }
    } else if (error.request) {
      errorMessage = 'Erreur de réseau - impossible de contacter le serveur'
    }
    
    alert(errorMessage)
    showAuditModal.value = false
    
  } finally {
    loadingAuditLogs.value = false
  }
}

const closeAuditModal = () => {
  showAuditModal.value = false
  auditLogs.value = []
  currentColumn.value = ''
  loadingAuditLogs.value = false
}

// ✅ FONCTION utilitaire (votre version existante)
const formatDate = (dateString) => {
  if (!dateString) return 'Not specified'
  const date = new Date(dateString)
  if (isNaN(date.getTime())) {
    console.warn("Date invalide:", dateString)
    return 'Invalid Date'
  }
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// ✅ Debug au montage (votre version + ajouts)
onMounted(() => {
  console.log('🔍 [PROCEDURE] Composant monté avec les props:', props)
  console.log('🔍 [PROCEDURE] ProcedureDetails:', props.procedureDetails)
  console.log('🔍 [PROCEDURE] Paramètres:', props.procedureDetails?.parameters)
  console.log('🔍 [PROCEDURE] Nombre de paramètres:', props.procedureDetails?.parameters?.length)
  console.log('🔍 [PROCEDURE] Can Edit:', canEdit.value)
  console.log('🔍 [PROCEDURE] Is Owner:', isOwner.value)
})
</script>