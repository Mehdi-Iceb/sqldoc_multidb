<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">
          <span class="text-gray-500 font-normal">View :</span> 
          {{ viewName }}
        </h2>
        <button 
          @click="saveAll"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
          :disabled="saving"
          >
          {{ saving ? 'Enregistrement...' : 'Enregistrer toutes les descriptions' }}
        </button>
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
              <h3 class="text-lg font-medium text-gray-900">Description</h3>
            </div>
            <div class="p-6">
              <textarea
                v-model="form.description"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Description optionnelle (usage, environnement, contenu...)"
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
                        <span v-if="!editingColumnName || editingColumnName !== column.column_name">
                          {{ column.description || '-' }}
                        </span>
                        <input
                          v-else
                          v-model="editingColumnDescription"
                          type="text"
                          class="flex-1 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500"
                          @keyup.enter="saveColumnDescription(column.column_name)"
                          @keyup.esc="cancelEdit()"
                        >
                        <button
                          v-if="!editingColumnName || editingColumnName !== column.column_name"
                          @click="startEdit(column)"
                          class="p-1 text-gray-400 hover:text-gray-600"
                          title="Modifier la description"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </button>
                        <div v-else class="flex space-x-1">
                          <button
                            @click="saveColumnDescription(column.column_name)"
                            class="p-1 text-green-600 hover:text-green-700"
                            title="Sauvegarder"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                          </button>
                          <button
                            @click="cancelEdit()"
                            class="p-1 text-red-600 hover:text-red-700"
                            title="Annuler"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                          </button>
                        </div>
                      </div>
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

          <!-- Bouton pour sauvegarder -->
          <div class="flex justify-end mt-6">
            <button 
              @click="saveAll"
              class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
              :disabled="saving"
            >
              {{ saving ? 'Enregistrement...' : 'Enregistrer toutes les informations' }}
            </button>
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

const props = defineProps({
  viewName: {
    type: String,
    required: true
  },
  viewDetails: {
    type: Object,
    required: true
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

// ✅ WATCHERS APRÈS LA DÉCLARATION DES FONCTIONS
watch(
  () => props.viewDetails,
  (newViewDetails) => {
    console.log('🔍 [VIEW] Props viewDetails ont changé:', newViewDetails)
    
    // Mettre à jour les données locales
    viewDetails.value = { ...newViewDetails }
    form.value.description = newViewDetails.description || ''
    
    // Réinitialiser les états d'édition
    editingColumnName.value = null
    editingColumnDescription.value = ''
    
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
      editingColumnName.value = null
      editingColumnDescription.value = ''
    }
  }
)

// Fonctions d'édition
const startEdit = (column) => {
  editingColumnName.value = column.column_name
  editingColumnDescription.value = column.description || ''
}

const cancelEdit = () => {
  editingColumnName.value = null
  editingColumnDescription.value = ''
}

// Fonction pour sauvegarder la description d'une colonne
const saveColumnDescription = async (columnName) => {
  try {
    saving.value = true
    
    router.post(`/view/${props.viewName}/column/${columnName}/description`, {
      description: editingColumnDescription.value
    }, {
      onSuccess: () => {
        // Mise à jour locale
        const column = viewDetails.value.columns.find(c => c.column_name === columnName)
        if (column) {
          column.description = editingColumnDescription.value
        }
        alert('Description de la colonne enregistrée avec succès.')
        cancelEdit()
      },
      onError: (errors) => {
        console.error('Erreur lors de la sauvegarde:', errors)
        alert('Erreur lors de la sauvegarde de la description de la colonne')
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

// Fonction pour sauvegarder toutes les informations
const saveAll = async () => {
  try {
    saving.value = true
    
    const viewData = {
      description: form.value.description,
      columns: viewDetails.value.columns.map(column => ({
        column_name: column.column_name,
        description: column.description
      }))
    }
    
    router.post(`/view/${props.viewName}/save-all`, viewData, {
      onSuccess: () => {
        alert('Les descriptions ont été enregistrées avec succès')
      },
      onError: (errors) => {
        console.error('Erreur lors de la sauvegarde:', errors)
        alert('Erreur lors de la sauvegarde')
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
  console.log('Props reçues:', props)
  console.log('ViewDetails:', props.viewDetails)
  console.log('Colonnes:', props.viewDetails.columns)
})
</script>