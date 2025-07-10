<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">
          <span class="text-gray-500 font-normal">Stocked Procedures :</span>
          {{ procedureName }}
        </h2>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
        <div v-if="loading" class="bg-white rounded-lg shadow-sm p-6">
          <div class="animate-pulse space-y-4">
            <div class="h-4 bg-gray-200 rounded w-1/4"></div>
            <div class="space-y-3">
              <div class="h-4 bg-gray-200 rounded"></div>
              <div class="h-4 bg-gray-200 rounded"></div>
            </div>
          </div>
        </div>

        <div v-else-if="currentError"
             class="bg-red-50 border-l-4 border-red-400 p-6 rounded-lg shadow-sm">
          <div class="flex items-center">
            <svg class="h-5 w-5 text-red-400 mr-3" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <div class="text-red-700">{{ currentError }}</div>
          </div>
        </div>

        <div v-else>

          <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Description</h3>
                <button
                  @click="saveDescription"
                  class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  :disabled="saving"
                >
                  {{ saving ? 'Saving...' : 'Save Description' }}
                </button>
              </div>
            </div>
            <div class="p-6">
              <textarea
                v-model="procedureForm.description"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Description of the stored procedure (usage, parameters, examples, ...)"
              ></textarea>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <div class="flex items-center">
                <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900">Informations</h3>
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

          <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <h3 class="text-lg font-medium text-gray-900">Parameters</h3>
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
                      Range Value
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Description
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="param in procedureData.parameters"
                      :key="param.parameter_name"
                      class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ param.parameter_name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                      <span class="font-mono">{{ param.data_type }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <span v-if="param.is_output"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ param.is_output }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                      {{ param.default_value || '-' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                      <div class="flex items-center space-x-2">
                        <span v-if="!editingParamId || editingParamId !== param.parameter_id">
                          {{ param.description || '-' }}
                        </span>
                        <input
                          v-else
                          v-model="editingValue"
                          type="text"
                          class="flex-1 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500"
                          @keyup.enter="saveParameterDescription(param)"
                          @keyup.esc="cancelEdit()"
                        >
                        <button
                          v-if="!editingParamId || editingParamId !== param.parameter_id"
                          @click="startEdit(param)"
                          class="p-1 text-gray-400 hover:text-gray-600"
                          title="Edit description"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </button>
                        <div v-else class="flex space-x-1">
                          <button
                            @click="saveParameterDescription(param)"
                            class="p-1 text-green-600 hover:text-green-700"
                            title="Save"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                          </button>
                          <button
                            @click="cancelEdit()"
                            class="p-1 text-red-600 hover:text-red-700"
                            title="Cancel"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                          </button>
                        </div>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="!procedureData.parameters || procedureData.parameters.length === 0">
                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                      No parameters found
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

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
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue' // Ajoutez 'watch'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { router } from '@inertiajs/vue3'

// Définition des props reçues d'Inertia
const props = defineProps({
  procedureName: {
    type: String,
    required: true
  },
  procedureDetails: {
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
const procedureForm = ref({
  description: props.procedureDetails.description || ''
})

const procedureData = ref(props.procedureDetails)
const loading = ref(false)
const saving = ref(false)
const editingParamId = ref(null)
const editingValue = ref('')

// Computed pour l'erreur
const currentError = computed(() => props.error)

// ✅ AJOUTEZ CE WATCHER POUR DÉTECTER LES CHANGEMENTS DE PROPS
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
  },
  { deep: true, immediate: true }
)

// ✅ AJOUTEZ AUSSI UN WATCHER POUR LE NOM DE LA PROCEDURE
watch(
  () => props.procedureName,
  (newProcedureName, oldProcedureName) => {
    if (newProcedureName !== oldProcedureName) {
      console.log(`🔍 [PROCEDURE] Nom de procédure changé: ${oldProcedureName} → ${newProcedureName}`)
      
      // Réinitialiser les états d'édition quand on change de procédure
      editingParamId.value = null
      editingValue.value = ''
    }
  }
)

// Fonctions d'édition des paramètres
const startEdit = (param) => {
  editingParamId.value = param.parameter_name
  editingValue.value = param.description || ''
}

const cancelEdit = () => {
  editingParamId.value = null
  editingValue.value = ''
}

// Fonction pour sauvegarder la description de la procédure
const saveDescription = async () => {
  try {
    saving.value = true
    
    router.post(`/procedure/${props.procedureName}/description`, {
      description: procedureForm.value.description
    }, {
      onSuccess: () => {
        alert('Description de la procédure enregistrée avec succès!')
        // Mettre à jour les données locales
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

// Fonction pour sauvegarder la description d'un paramètre
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
        // Mise à jour locale
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

// Fonction utilitaire pour formater les dates
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

// Debug au montage
onMounted(() => {
  console.log('🔍 [PROCEDURE] Composant monté avec les props:', props)
  console.log('🔍 [PROCEDURE] ProcedureDetails:', props.procedureDetails)
  console.log('🔍 [PROCEDURE] Paramètres:', props.procedureDetails.parameters)
})
</script>