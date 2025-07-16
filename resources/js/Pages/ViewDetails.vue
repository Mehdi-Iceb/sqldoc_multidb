<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">
          <span class="text-gray-500 font-normal">View :</span> 
          {{ viewName }}
        </h2>
        
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
        <!-- 🎯 SPINNER DE CHARGEMENT PRINCIPAL -->
        <div v-if="loading" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg shadow-xl p-8 flex flex-col items-center max-w-sm w-full mx-4">
            <!-- Spinner principal -->
            <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-green-600 mb-6"></div>
            
            <!-- Titre et sous-titre -->
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Loading view details</h3>
            <p class="text-gray-600 text-center mb-4">
              Retrieving structure for <span class="font-medium text-blue-600">{{ viewName }}</span>
            </p>
            
            <!-- Barre de progression simulée -->
            <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
              <div class="bg-green-600 h-2 rounded-full transition-all duration-300 ease-out" 
                   :style="{ width: loadingProgress + '%' }"></div>
            </div>
            
            <!-- Messages de progression -->
            <div class="text-sm text-gray-500 text-center">
              <div v-if="loadingProgress < 30" class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Connecting to database...
              </div>
              <div v-else-if="loadingProgress < 60" class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Loading view informations...
              </div>
              <div v-else-if="loadingProgress < 90" class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Loading view Definition...
              </div>
              <div v-else class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Almost ready...
              </div>
            </div>
          </div>
        </div>

        <!-- Error state -->
        <div v-else-if="error" 
             class="bg-red-50 border-l-4 border-red-400 p-6 rounded-lg shadow-sm">
          <div class="flex items-center">
            <svg class="h-5 w-5 text-red-400 mr-3" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <div class="text-red-700">{{ error }}</div>
          </div>
        </div>

        <!-- Success state -->
        <div v-else>
          <!-- Description de la vue -->
          <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <div class="flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-900">Description</h3>
              <button 
                v-if="viewDetails.can_edit"
                  @click="saveViewStructure" 
                  class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  :disabled="saving"
                >
                <span v-if="!saving">Save modification</span>
                  <span v-else class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Recording...
                  </span>
              </button>
              <span v-else class="text-sm text-gray-500 italic">
                Read only access
              </span>
              </div>
            </div>
            <div class="p-6">
              <textarea
                v-model="form.description"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                :class="{ 'opacity-50 cursor-not-allowed bg-gray-100': !viewDetails.can_edit }"
                placeholder="Optionnal description (use, environnement, content...)"
                :disabled="!viewDetails.can_edit || saving"
                :readonly="!viewDetails.can_edit"
              ></textarea>
            </div>
          </div>

          <!-- Informations de la vue -->
          <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <div class="flex items-center">
                <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900">Informations</h3>
              </div>
            </div>
            <div class="p-6 grid grid-cols-3 gap-6">
              <div>
                <h4 class="text-sm font-semibold text-gray-500 mb-1">Schema</h4>
                    <div class="bg-gray-50 p-3 rounded">
                      <p class="text-gray-800">{{ viewDetails.schema || 'Not specified' }}</p>
                  </div>
              </div>
              <div>
                <h4 class="text-sm font-semibold text-gray-500 mb-1">Creation Date</h4>
                  <div class="bg-gray-50 p-3 rounded">
                    <p class="text-gray-800">{{ formatDate(viewDetails.create_date) }}</p>
                  </div>
              </div>
              <div>
                <h4 class="text-sm font-semibold text-gray-500 mb-1">Last Modification</h4>
                  <div class="bg-gray-50 p-3 rounded">
                    <p class="text-gray-800">{{ formatDate(viewDetails.modify_date) }}</p>
                  </div>
              </div>
            </div>
          </div>

          <!-- Structure de la vue -->
          <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <h3 class="text-lg font-medium text-gray-900">Structure</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead>
                  <tr class="bg-gray-50">
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Column
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Type
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Nullable
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      max_length
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      precision
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Scale
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Description
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Range Value
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Release
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Historic
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="column in viewDetails.columns" 
                      :key="column.column_name"
                      class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ column.column_name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                      <span class="font-mono">{{ formatDataType(column) }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <span :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        column.is_nullable 
                          ? 'bg-green-100 text-green-800' 
                          : 'bg-red-100 text-red-800'
                      ]">
                        {{ column.is_nullable ? 'yes' : 'no' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ column.max_length || '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ column.precision || '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ column.scale || '-' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                      <div class="flex items-center space-x-2">
                        <span v-if="!editingDescription[column.column_name]" class="max-w-xs truncate">
                          {{ column.description || '-' }}
                        </span>
                        <textarea
                          v-else
                          v-model="editingDescriptionValue"
                          class="flex-1 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500"
                          :disabled="!viewDetails.can_edit"
                          @keydown.ctrl.enter="saveDescription(column.column_name)"
                          @keydown.esc="cancelEdit('description', column.column_name)"
                        ></textarea>
                        <button
                          v-if="!editingDescription[column.column_name] && viewDetails.can_edit"
                          @click="startEdit('description', column.column_name, column.description)"
                          class="p-1 text-gray-400 hover:text-gray-600"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </button>
                        <div v-else class="flex space-x-1">
                          <button
                            @click="saveDescription(column.column_name)"
                            class="p-1 text-green-600 hover:text-green-700"
                            :disabled="savingDescription[column.column_name]"
                          >
                            <svg v-if="!savingDescription[column.column_name]" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="animate-spin h-4 w-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                          </button>
                          <button
                            @click="cancelEdit('description', column.column_name)"
                            class="p-1 text-red-600 hover:text-red-700"
                            :disabled="savingDescription[column.column_name]"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                          </button>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                      <div class="flex items-center space-x-2">
                        <span v-if="!editingRangeValues[column.column_name]" class="max-w-xs truncate">
                          {{ column.rangevalues || '-' }}
                        </span>
                        <textarea
                          v-else
                          v-model="editingRangeValuesValue"
                          class="flex-1 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500"
                          :disabled="!viewDetails.can_edit"
                          @keyup.enter="saveRangeValues(column.column_name)"
                          @keyup.esc="cancelEdit('rangeValues', column.column_name)"
                        ></textarea>
                        <button
                          v-if="!editingRangeValues[column.column_name] && viewDetails.can_edit"
                          @click="startEdit('rangeValues', column.column_name, column.possible_values)"
                          class="p-1 text-gray-400 hover:text-gray-600"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </button>
                        <div v-else class="flex space-x-1">
                          <button
                            @click="saveRangeValues(column.column_name)"
                            class="p-1 text-green-600 hover:text-green-700"
                            :disabled="savingRangeValues[column.column_name]"
                          >
                            <svg v-if="!savingRangeValues[column.column_name]" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="animate-spin h-4 w-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                          </button>
                          <button
                            @click="cancelEdit('rangeValues', column.column_name)"
                            class="p-1 text-red-600 hover:text-red-700"
                            :disabled="savingRangeValues[column.column_name]"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                          </button>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                      <div class="flex items-center space-x-2 relative">
                        <select 
                          :value="column.release_id || ''"
                          @change="updateColumnRelease(column, $event.target.value)"
                          :disabled="!viewDetails.can_edit || updatingRelease[column.column_name]"
                          :class="[
                            'block w-full pl-2 pr-7 py-1 text-xs border-gray-300 rounded-md',
                            column.release_id ? 'bg-blue-50 text-blue-800' : '',
                            !viewDetails.can_edit ? 'opacity-50 cursor-not-allowed' : '',
                            updatingRelease[column.column_name] ? 'opacity-50' : ''
                          ]"
                        >
                          <option value="">None</option>
                          <option v-for="release in availableReleases" :key="release.id" :value="release.id">
                            {{ release.display_name }}
                          </option>
                        </select>
                        <div v-if="updatingRelease[column.column_name]" class="absolute right-2 top-1/2 transform -translate-y-1/2">
                          <svg class="animate-spin h-3 w-3 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                        </div>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-sm">
                      <SecondaryButton @click="showAuditLogs(column.column_name)" :disabled="loadingAuditLogs && currentColumn === column.column_name">
                        <span v-if="!(loadingAuditLogs && currentColumn === column.column_name)">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                          </svg>
                        </span>
                        <span v-else>
                          <svg class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                        </span>
                      </SecondaryButton>
                    </td>
                  </tr>
                  <tr v-if="!viewDetails.columns || viewDetails.columns.length === 0">
                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                      No column found
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
              <pre class="whitespace-pre-wrap text-sm text-gray-600 font-mono bg-gray-50 p-4 rounded-lg">{{ viewDetails.definition }}</pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
  
<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { router } from '@inertiajs/vue3'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  viewName: {
    type: String,
    required: true
  },
  viewDetails: {
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

const canEdit = computed(() => {
  return props.viewDetails.can_edit || props.permissions.can_edit || false
})

const isOwner = computed(() => {
  return props.viewDetails.is_owner || props.permissions.is_owner || false
})

const accessLevel = computed(() => {
  return props.viewDetails.access_level || props.permissions.access_level || 'read'
})

// États locaux
const form = ref({
  description: props.viewDetails.description || ''
})

const viewDetails = ref(props.viewDetails)
const loadingProgress = ref(0)
const loading = ref(false)
const saving = ref(false)
const editingColumnName = ref(null)
const editingColumnDescription = ref('')
const editingColumnRangeValue = ref('')


const savingDescription = ref({})
const savingRangeValues = ref({})

// Variable pour l'intervalle de progression
let progressInterval = null

// Computed pour l'erreur
const error = computed(() => props.error)

// ✅ DÉCLARATION DES FONCTIONS AVANT LES WATCHERS
const simulateLoadingProgress = () => {
  loadingProgress.value = 0
  
  if (progressInterval) {
    clearInterval(progressInterval)
  }
  
  progressInterval = setInterval(() => {
    if (loadingProgress.value < 95) {
      const increment = loadingProgress.value < 50 ? 
        Math.random() * 15 + 5 : 
        Math.random() * 8 + 2   
      
      loadingProgress.value = Math.min(95, loadingProgress.value + increment)
    }
  }, 200)
}

const stopLoadingProgress = () => {
  if (progressInterval) {
    clearInterval(progressInterval)
    progressInterval = null
  }
  loadingProgress.value = 100
  setTimeout(() => {
    loading.value = false
  }, 300)
}

const startLoading = () => {
  loading.value = true
  simulateLoadingProgress()
}

const editingDescription = ref({})
const editingDescriptionValue = ref('')
const editingRangeValues = ref({})
const editingRangeValuesValue = ref('')
const updatingRelease = ref({})
const loadingAuditLogs = ref(false)
const currentColumn = ref('')

// ✅ WATCHERS APRÈS LA DÉCLARATION DES FONCTIONS
watch(
  () => props.viewDetails,
  (newViewDetails) => {
    console.log('🔍 [VIEW] Props viewDetails ont changé:', newViewDetails)
    
    // Mettre à jour les données locales
    viewDetails.value = { ...newViewDetails }
    form.value.description = newViewDetails.description || ''
    
    // Réinitialiser les états d'édition
    editingDescription.value = {}
    editingRangeValues.value = {}
    editingDescriptionValue.value = ''
    editingRangeValuesValue.value = ''
    
    // Arrêter le chargement
    stopLoadingProgress()
  },
  { deep: true, immediate: true }
)

watch(
  () => props.viewName,
  (newViewName, oldViewName) => {
    if (newViewName !== oldViewName && oldViewName) {
      console.log(`🔍 [VIEW] Nom de vue changé: ${oldViewName} → ${newViewName}`)
      
      // Démarrer le chargement
      startLoading()
      
      // Réinitialiser les états d'édition
      editingDescription.value = {}
      editingRangeValues.value = {}
      editingDescriptionValue.value = ''
      editingRangeValuesValue.value = ''
    }
  }
)


// Fonctions d'édition
const startEdit = (type, columnName, currentValue) => {
  if (!canEdit.value) {
    alert('Vous n\'avez pas les permissions pour modifier cette vue')
    return
  }
  
  if (type === 'description') {
    editingDescription.value = { [columnName]: true }
    editingDescriptionValue.value = currentValue || ''
  } else if (type === 'rangeValues') {
    editingRangeValues.value = { [columnName]: true }
    editingRangeValuesValue.value = currentValue || ''
  }
}

const cancelEdit = (type, columnName) => {
  if (type === 'description') {
    editingDescription.value = { [columnName]: false }
    editingDescriptionValue.value = ''
  } else if (type === 'rangeValues') {
    editingRangeValues.value = { [columnName]: false }
    editingRangeValuesValue.value = ''
  }
}


// Fonction pour sauvegarder la description d'une colonne
// const saveColumnDescription = async (columnName) => {
//   try {
//     saving.value = true
    
//     router.post(`/view/${props.viewName}/column/${columnName}/description`, {
//       description: editingColumnDescription.value
//     }, {
//       onSuccess: () => {
//         // Mise à jour locale
//         const column = viewDetails.value.columns.find(c => c.column_name === columnName)
//         if (column) {
//           column.description = editingColumnDescription.value
//         }
//         alert('Description de la colonne enregistrée avec succès.')
//         cancelEdit()
//       },
//       onError: (errors) => {
//         console.error('Erreur lors de la sauvegarde:', errors)
//         alert('Erreur lors de la sauvegarde de la description de la colonne')
//       },
//       onFinish: () => {
//         saving.value = false
//       }
//     })
//   } catch (error) {
//     console.error('Erreur:', error)
//     saving.value = false
//   }
// }

// Fonction pour sauvegarder toutes les informations
const saveViewStructure = async () => {
  if (!canEdit.value) {
    alert('Vous n\'avez pas les permissions pour modifier cette vue')
    return
  }
  
  try {
    saving.value = true
    
    const viewData = {
      description: form.value.description,
      language: 'fr',
      columns: viewDetails.value.columns.map(column => ({
        column: column.column_name,
        description: column.description || null,
        rangevalues: column.rangevalues || null
      }))
    }
    
    const response = await axios.post(`/view/${props.viewName}/save-structure`, viewData)
    
    if (response.data.success) {
      alert('Descriptions et range values saved with success')
    } else {
      throw new Error(response.data.error || 'Error while saving')
    }
  } catch (error) {
    console.error('❌ Error While saving:', error)
    alert('Error: ' + (error.response?.data?.error || error.message))
  } finally {
    saving.value = false
  }
}

const saveDescription = async (columnName) => {
  try {
    savingDescription.value[columnName] = true
    
    const response = await axios.post(`/view/${props.viewName}/column/${columnName}/description`, {
      description: editingDescriptionValue.value
    })
    
    if (response.data.success) {
      const column = viewDetails.value.columns.find(c => c.column_name === columnName)
      if (column) {
        column.description = editingDescriptionValue.value
      }
      cancelEdit('description', columnName)
    } else {
      throw new Error(response.data.error)
    }
  } catch (error) {
    console.error('❌ Error:', error)
    alert('Error: ' + (error.response?.data?.error || error.message))
  } finally {
    savingDescription.value[columnName] = false
  }
}


const saveRangeValues = async (columnName) => {
  try {
    savingRangeValues.value[columnName] = true
    
    const response = await axios.post(`/view/${props.viewName}/column/${columnName}/range-values`, {
      rangevalues: editingRangeValuesValue.value
    })
    
    if (response.data.success) {
      const column = viewDetails.value.columns.find(c => c.column_name === columnName)
      if (column) {
        column.rangevalues = editingRangeValuesValue.value
      }
      cancelEdit('rangeValues', columnName)
    } else {
      throw new Error(response.data.error)
    }
  } catch (error) {
    console.error('❌ Error:', error)
    alert('Error: ' + (error.response?.data?.error || error.message))
  } finally {
    savingRangeValues.value[columnName] = false
  }
}

const updateColumnRelease = async (column, releaseId) => {
  if (!canEdit.value) {
    alert('Vous n\'avez pas les permissions pour modifier cette vue')
    return
  }
  
  try {
    updatingRelease.value[column.column_name] = true
    
    const finalReleaseId = releaseId === '' ? null : parseInt(releaseId)
    
    const response = await axios.post(`/view/${props.viewName}/column/${column.column_name}/release`, {
      release_id: finalReleaseId
    })
    
    if (response.data.success) {
      column.release_id = finalReleaseId
      const selectedRelease = props.availableReleases.find(r => r.id === finalReleaseId)
      column.release_version = selectedRelease ? selectedRelease.version_number : ''
    } else {
      throw new Error(response.data.error)
    }
  } catch (error) {
    console.error('❌ Error:', error)
    alert('Error: ' + (error.response?.data?.error || error.message))
  } finally {
    updatingRelease.value[column.column_name] = false
  }
}

const showAuditLogs = async (columnName) => {
  loadingAuditLogs.value = true
  currentColumn.value = columnName
  
  try {
    const response = await axios.get(`/view/${props.viewName}/column/${columnName}/audit-logs`)
    console.log('Audit logs:', response.data)
  } catch (error) {
    console.error('❌ Error:', error)
    alert('Error loading audit logs')
  } finally {
    loadingAuditLogs.value = false
  }
}

// Fonctions utilitaires
const formatDataType = (column) => {
  let type = column.type || column.data_type
  if (['varchar', 'nvarchar', 'char', 'nchar'].includes(type.toLowerCase())) {
    if (column.max_length) {
      type += `(${column.max_length === -1 ? 'MAX' : column.max_length})`
    }
  } else if (['decimal', 'numeric'].includes(type.toLowerCase())) {
    if (column.precision && column.scale !== undefined) {
      type += `(${column.precision},${column.scale})`
    }
  }
  return type
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  if (isNaN(date.getTime())) {
    console.warn("Date invalide:", dateString)
    return 'Date invalide'
  }
  return date.toLocaleString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Nettoyage au démontage
onUnmounted(() => {
  if (progressInterval) {
    clearInterval(progressInterval)
  }
})

// Debug au montage
onMounted(() => {
  console.log('🔍 Props reçues:', props)
  console.log('🔍 ViewDetails:', props.viewDetails)
  console.log('🔍 Permissions:', props.permissions)
  console.log('🔍 Can Edit:', canEdit.value)
  console.log('🔍 Is Owner:', isOwner.value)
  console.log('🔍 Access Level:', accessLevel.value)
})
</script>