<template>
    <AuthenticatedLayout>
      <template #header>
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-gray-800">
            <span class="text-gray-500 font-normal">Stocked procedures :</span> 
            {{ procedureName }}
          </h2>
        </div>
      </template>
  
      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
          <!-- État de chargement -->
          <div v-if="loading" class="bg-white rounded-lg shadow-sm p-6">
            <div class="animate-pulse space-y-4">
              <div class="h-4 bg-gray-200 rounded w-1/4"></div>
              <div class="space-y-3">
                <div class="h-4 bg-gray-200 rounded"></div>
                <div class="h-4 bg-gray-200 rounded"></div>
              </div>
            </div>
          </div>
  
          <!-- État d'erreur -->
          <div v-else-if="error" 
               class="bg-red-50 border-l-4 border-red-400 p-6 rounded-lg shadow-sm">
            <div class="flex items-center">
              <svg class="h-5 w-5 text-red-400 mr-3" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
              </svg>
              <div class="text-red-700">{{ error }}</div>
            </div>
          </div>
  
          <!-- État succès -->
          <div v-else>
            
            <!-- Description de la procédure stockée -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <div class="flex justify-between items-center">
                  <h3 class="text-lg font-medium text-gray-900">Description</h3>
                  <button 
                    @click="saveDescription" 
                    class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    :disabled="saving"
                  >
                    {{ saving ? 'Recording...' : 'Save' }}
                  </button>
                </div>
              </div>
              <div class="p-6">
                <textarea
                  v-model="form.description"
                  rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  placeholder="Description of the stored procedure (usage, parameters, examples, ...)"
                ></textarea>
              </div>
            </div>
  
            <!-- Informations de la procédure stockée -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Informations</h3>
              </div>
              <div class="p-6 grid grid-cols-2 gap-6">
                <div>
                  <p class="text-sm text-gray-600">Schema</p>
                  <p class="mt-1 text-sm font-medium text-gray-900">
                    {{ procedureDetails.schema || 'Not specified' }}
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Creation date</p>
                  <p class="mt-1 text-sm font-medium text-gray-900">
                    {{ formatDate(procedureDetails.create_date) }}
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Last modification</p>
                  <p class="mt-1 text-sm font-medium text-gray-900">
                    {{ formatDate(procedureDetails.modify_date) }}
                  </p>
                </div>
              </div>
            </div>
  
            <!-- Paramètres -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
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
                        Range value
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="param in procedureDetails.parameters" 
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
                          Output
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ param.default_value || '-' }}
                      </td>
                    </tr>
                    <tr v-if="!procedureDetails.parameters || procedureDetails.parameters.length === 0">
                      <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                        No parameters found
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
  
            <!-- Définition SQL -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900"> SQL Definition</h3>
              </div>
              <div class="p-6">
                <pre class="whitespace-pre-wrap text-sm text-gray-600 font-mono bg-gray-50 p-4 rounded-lg">{{ procedureDetails.definition }}</pre>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  </template>
  
  <script setup>
import { ref, onMounted, watch } from 'vue' // ✅ N'oubliez pas d'importer 'watch'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link } from '@inertiajs/vue3' // Link est importé mais non utilisé directement dans le script, juste pour info

const saving = ref(false)

// ✅ Définition des props au début du script setup
const props = defineProps({
  procedureName: {
    type: String,
    required: true
  }
})

const loading = ref(true)
const error = ref(null)
const procedureDetails = ref({
  parameters: [],
  definition: '',
  schema: '',
  create_date: null,
  modify_date: null,
  description: ''
})

const form = ref({
  description: ''
})

// ✅ EXTRAIRE LA LOGIQUE DE CHARGEMENT DANS UNE FONCTION SÉPARÉE
const loadProcedureDetailsFromAPI = async (nameOfProcedure) => {
  try {
    console.log('🔍 [PROCEDURE] Début du chargement des détails pour:', nameOfProcedure);
    // Réinitialiser les états de chargement et d'erreur
    loading.value = true;
    error.value = null;
    // Réinitialiser les données pour un feedback visuel immédiat
    procedureDetails.value = {
      parameters: [],
      definition: '',
      schema: '',
      create_date: null,
      modify_date: null,
      description: ''
    };
    form.value.description = ''; // Réinitialiser la description du formulaire

    const response = await axios.get(`/api/procedure/${encodeURIComponent(nameOfProcedure)}/details`);
    console.log('🔍 [PROCEDURE] Réponse reçue:', response.data);
    
    procedureDetails.value = response.data;
    form.value.description = response.data.description || '';
    
    console.log('🔍 [PROCEDURE] Données chargées avec succès pour:', nameOfProcedure);
  } catch (err) {
    console.error('❌ [PROCEDURE] Erreur lors du chargement des détails de la procédure:', err);
    error.value = err.response?.data?.error || `Erreur lors du chargement des détails de la procédure "${nameOfProcedure}"`;
  } finally {
    loading.value = false;
    console.log('🔍 [PROCEDURE] Finalisation du chargement pour:', nameOfProcedure);
  }
}

// ✅ NOUVEAU WATCHER POUR LA PROP procedureName
watch(
  () => props.procedureName,
  async (newProcedureName, oldProcedureName) => {
    // Évite le rechargement si la prop n'a pas réellement changé
    if (newProcedureName === oldProcedureName) {
      console.log('🔍 [PROCEDURE] Watcher: procedureName inchangé, pas de rechargement.');
      return;
    }
    console.log(`🔍 [PROCEDURE] Watcher: procedureName a changé de "${oldProcedureName}" à "${newProcedureName}". Rechargement des détails...`);
    await loadProcedureDetailsFromAPI(newProcedureName);
  },
  { immediate: true } // `immediate: true` pour exécuter le watcher une fois au montage initial
);

// onMounted est maintenant géré par le watcher avec `immediate: true`
onMounted(() => {
  console.log('🔍 [PROCEDURE] Composant ProcedureDetails monté. Le chargement initial est géré par le watcher.');
  // Vous pouvez ajouter ici d'autres logiques qui ne dépendent PAS de procedureName changeant
});


// Fonction pour sauvegarder la description
const saveDescription = async () => {
  try {
    saving.value = true
    
    // Appel à l'API pour sauvegarder uniquement la description
    const response = await axios.post(`/api/procedure/${props.procedureName}/description`, { // ✅ Vérifiez l'URL de l'API
      description: form.value.description
    })
    
    if (response.data.success) {
      alert('Description de la procédure stockée enregistrée avec succès')
      procedureDetails.value.description = form.value.description
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde')
    }
    
  } catch (error) {
    console.error('❌ [PROCEDURE] Erreur lors de la sauvegarde:', error)
    console.error('Détails:', error.response?.data)
    alert('Erreur lors de la sauvegarde de la description: ' + (error.response?.data?.error || error.message))
  } finally {
    saving.value = false
  }
}

// Fonction pour sauvegarder toutes les informations (actuellement, ne sauvegarde que la description)
// Cette fonction semble dupliquer `saveDescription` si elle ne fait qu'envoyer la description.
// Vous pourriez la renommer ou l'étendre si elle est censée gérer plus de champs à l'avenir.
const saveAll = async () => {
  try {
    saving.value = true
    
    const procedureData = {
      description: form.value.description
    }
    
    // Appel à l'API
    const response = await axios.post(`/api/procedure/${props.procedureName}/save-all`, procedureData) // ✅ Vérifiez l'URL de l'API
    
    if (response.data.success) {
      alert('Description de la procédure stockée enregistrée avec succès')
      // Mettre à jour d'autres champs si save-all les renvoie ou les modifie
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde')
    }
    
  } catch (error) {
    console.error('❌ [PROCEDURE] Erreur lors de la sauvegarde:', error)
    console.error('Détails:', error.response?.data)
    alert('Erreur lors de la sauvegarde de la description: ' + (error.response?.data?.error || error.message))
  } finally {
    saving.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  // Vérifier si la date est valide
  if (isNaN(date.getTime())) {
    console.warn("Date invalide fournie pour formatDate:", dateString);
    return 'Date invalide';
  }
  return date.toLocaleString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>