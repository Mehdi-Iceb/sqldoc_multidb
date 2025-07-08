<template>
    <AuthenticatedLayout>
      <template #header>
        <div class="flex items-center justify-between">
          <h2 class="text-xl font-semibold text-gray-800">
            <span class="text-gray-500 font-normal">Trigger :</span> 
            {{ triggerName }}
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
  
          <!-- État de succès -->
          <div v-else class="space-y-8">
  
            <!-- Description du trigger -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <div class="flex justify-between items-center">
                  <h3 class="text-lg font-medium text-gray-900">Description</h3>
                  <button 
                    @click="saveDescription" 
                    class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    :disabled="saving"
                  >
                    {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
                  </button>
                </div>
              </div>
              <div class="p-6">
                <textarea
                  v-model="form.description"
                  rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  placeholder="Description du trigger (usage, comportement, impact...)"
                ></textarea>
              </div>
            </div>
  
            <!-- Informations générales -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">General Informations</h3>
              </div>
              <div class="p-6 grid grid-cols-2 gap-6">
                <div>
                  <p class="text-sm text-gray-600">Table</p>
                  <p class="mt-1 font-medium">{{ triggerDetails.table_name }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Schema</p>
                  <p class="mt-1 font-medium">{{ triggerDetails.schema }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Type</p>
                  <p class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      {{ triggerDetails.trigger_type }}
                    </span>
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Event</p>
                  <p class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      {{ triggerDetails.trigger_event }}
                    </span>
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">State</p>
                  <p class="mt-1">
                    <span :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      triggerDetails.is_disabled 
                        ? 'bg-red-100 text-red-800' 
                        : 'bg-green-100 text-green-800'
                    ]">
                      {{ triggerDetails.is_disabled ? 'Désactivé' : 'Activé' }}
                    </span>
                  </p>
                </div>
                <div v-if="triggerDetails.create_date">
                  <p class="text-sm text-gray-600">Creation date</p>
                  <p class="mt-1 font-medium">{{ formatDate(triggerDetails.create_date) }}</p>
                </div>
              </div>
            </div>
  
            <!-- Définition SQL -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900"> SQL Definition</h3>
              </div>
              <div class="p-6">
                <pre class="whitespace-pre-wrap text-sm font-mono bg-gray-50 p-4 rounded-lg text-gray-600">{{ triggerDetails.definition }}</pre>
              </div>
            </div>
  
            <!-- Bouton pour sauvegarder toutes les informations -->
            <div class="flex justify-end mt-6">
                <button 
                    @click="saveAll"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    :disabled="saving"
                >
                {{ saving ? 'Enregistrement...' : 'Enregistrer la description' }}
                </button>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  </template>
  
  <script setup>
import { ref, onMounted, watch } from 'vue' // ✅ N'oubliez pas d'importer 'watch'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const form = ref({
  description: ''
})

// ✅ Définition des props au début du script setup
const props = defineProps({
  triggerName: {
    type: String,
    required: true
  }
})

const loading = ref(true)
const error = ref(null)
const saving = ref(false)
const triggerDetails = ref({
  table_name: '',
  schema: '',
  trigger_type: '',
  trigger_event: '',
  is_disabled: false,
  definition: '',
  create_date: null,
  description: ''
})

const formatDate = (date) => {
  if (!date) return '-';
  const d = new Date(date)
  // Vérifier si la date est valide
  if (isNaN(d.getTime())) {
    console.warn("Date invalide fournie pour formatDate:", date);
    return 'Date invalide';
  }
  return d.toLocaleString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// ✅ EXTRAIRE LA LOGIQUE DE CHARGEMENT DANS UNE FONCTION SÉPARÉE
const loadTriggerDetailsFromAPI = async (nameOfTrigger) => {
  try {
    console.log('🔍 [TRIGGER] Début du chargement des détails pour:', nameOfTrigger);
    // Réinitialiser les états de chargement et d'erreur
    loading.value = true;
    error.value = null;
    // Réinitialiser les données pour un feedback visuel immédiat
    triggerDetails.value = {
      table_name: '',
      schema: '',
      trigger_type: '',
      trigger_event: '',
      is_disabled: false,
      definition: '',
      create_date: null,
      description: ''
    };
    form.value.description = ''; // Réinitialiser la description du formulaire
    
    // ✅ Vérifiez que cette URL correspond à votre API pour les triggers
    const response = await axios.get(`/api/trigger/${encodeURIComponent(nameOfTrigger)}/details`); 
    console.log('🔍 [TRIGGER] Réponse reçue:', response.data);
    
    triggerDetails.value = response.data;
    form.value.description = response.data.description || '';
    
    console.log('🔍 [TRIGGER] Données chargées avec succès pour:', nameOfTrigger);
  } catch (err) {
    console.error('❌ [TRIGGER] Erreur lors du chargement des détails du trigger:', err);
    error.value = err.response?.data?.error || `Erreur lors du chargement des détails du trigger "${nameOfTrigger}"`;
  } finally {
    loading.value = false;
    console.log('🔍 [TRIGGER] Finalisation du chargement pour:', nameOfTrigger);
  }
}

// ✅ NOUVEAU WATCHER POUR LA PROP triggerName
watch(
  () => props.triggerName,
  async (newTriggerName, oldTriggerName) => {
    // Évite le rechargement si la prop n'a pas réellement changé
    if (newTriggerName === oldTriggerName) {
      console.log('🔍 [TRIGGER] Watcher: triggerName inchangé, pas de rechargement.');
      return;
    }
    console.log(`🔍 [TRIGGER] Watcher: triggerName a changé de "${oldTriggerName}" à "${newTriggerName}". Rechargement des détails...`);
    await loadTriggerDetailsFromAPI(newTriggerName);
  },
  { immediate: true } // `immediate: true` pour exécuter le watcher une fois au montage initial
);

// onMounted est maintenant géré par le watcher avec `immediate: true`
onMounted(() => {
  console.log('🔍 [TRIGGER] Composant TriggerDetails monté. Le chargement initial est géré par le watcher.');
  // Vous pouvez ajouter ici d'autres logiques qui ne dépendent PAS de triggerName changeant
});

// Sauvegarder uniquement la description
const saveDescription = async () => {
  try {
    saving.value = true
    
    // Appel à l'API pour sauvegarder uniquement la description
    // ✅ Vérifiez que cette URL correspond à votre API pour la sauvegarde de description
    const response = await axios.post(`/api/trigger/${props.triggerName}/description`, { 
      description: form.value.description
    })
    
    if (response.data.success) {
      alert('Description du trigger enregistrée avec succès')
      triggerDetails.value.description = form.value.description
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde')
    }
  } catch (error) {
    console.error('❌ [TRIGGER] Erreur lors de la sauvegarde de la description:', error)
    console.error('Détails:', error.response?.data)
    alert('Erreur lors de la sauvegarde de la description: ' + (error.response?.data?.error || error.message))
  } finally {
    saving.value = false
  }
}

// Sauvegarder toutes les informations
const saveAll = async () => {
  try {
    saving.value = true
    
    const triggerData = {
      description: form.value.description,
      language: 'fr' // 'language' semble être un champ fixe ici, assurez-vous qu'il est pertinent pour votre API.
    }
    
    // Appel à l'API
    // ✅ Vérifiez que cette URL correspond à votre API pour la sauvegarde de toutes les infos
    const response = await axios.post(`/api/trigger/${props.triggerName}/save-all`, triggerData) 
    
    if (response.data.success) {
      alert('Description du trigger enregistrée avec succès')
      // Mettre à jour d'autres champs si save-all les renvoie ou les modifie
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde')
    }
  } catch (error) {
    console.error('❌ [TRIGGER] Erreur lors de la sauvegarde globale:', error)
    console.error('Détails:', error.response?.data)
    alert('Erreur lors de la sauvegarde des informations du trigger: ' + (error.response?.data?.error || error.message))
  } finally {
    saving.value = false
  }
}
</script>