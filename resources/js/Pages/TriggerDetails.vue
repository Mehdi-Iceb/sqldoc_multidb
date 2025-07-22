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
        <div class="max-w-10xl mx-auto sm:px-6 lg:px-8 space-y-8">
          
          <!-- État d'erreur -->
          <div v-if="error" 
               class="bg-red-50 border-l-4 border-red-400 p-6 rounded-lg shadow-sm">
            <div class="flex items-center">
              <svg class="h-5 w-5 text-red-400 mr-3" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
              </svg>
              <div class="text-red-700">{{ error }}</div>
            </div>
          </div>
  
          <!-- Contenu principal -->
          <div class="space-y-8">
  
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
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">General information</h3>
              </div>
              <div class="p-6 grid grid-cols-2 gap-6">
                <div>
                  <p class="text-sm text-gray-600">Table</p>
                  <p class="mt-1 font-medium">{{ triggerDetails.table_name || '-' }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Schema</p>
                  <p class="mt-1 font-medium">{{ triggerDetails.schema || '-' }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Type</p>
                  <p class="mt-1">
                    <span v-if="triggerDetails.trigger_type" 
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      {{ triggerDetails.trigger_type }}
                    </span>
                    <span v-else class="text-gray-400">-</span>
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Event</p>
                  <p class="mt-1">
                    <span v-if="triggerDetails.trigger_event" 
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      {{ triggerDetails.trigger_event }}
                    </span>
                    <span v-else class="text-gray-400">-</span>
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
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Definition SQL</h3>
              </div>
              <div class="p-6">
                <pre v-if="triggerDetails.definition" 
                     class="whitespace-pre-wrap text-sm font-mono bg-gray-50 p-4 rounded-lg text-gray-600">{{ triggerDetails.definition }}</pre>
                <p v-else class="text-gray-400 italic">No definition available</p>
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
import { ref, onMounted } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

// ✅ Props définies avec des valeurs par défaut
const props = defineProps({
  triggerName: {
    type: String,
    required: true
  },
  triggerDetails: {
    type: Object,
    default: () => ({
      name: '',
      description: '',
      table_name: '',
      schema: null,
      trigger_type: '',
      trigger_event: '',
      is_disabled: false,
      definition: '',
      create_date: null
    })
  },
  error: {
    type: String,
    default: null
  }
})

// ✅ Réactifs locaux simplifiés
const saving = ref(false)
const form = ref({
  description: props.triggerDetails.description || ''
})

// ✅ Fonction de formatage de date
const formatDate = (date) => {
  if (!date) return '-';
  const d = new Date(date)
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

// ✅ Initialisation au montage
onMounted(() => {
  // Synchroniser la description du formulaire avec les props
  form.value.description = props.triggerDetails.description || ''
  console.log('🔍 [TRIGGER] Composant monté avec les données:', props.triggerDetails)
})

// ✅ Fonction de sauvegarde de la description
const saveDescription = async () => {
  if (!props.triggerName) {
    alert('Erreur: nom du trigger manquant');
    return;
  }

  try {
    saving.value = true
    
    const response = await axios.post(`/api/trigger/${encodeURIComponent(props.triggerName)}/description`, { 
      description: form.value.description
    })
    
    if (response.data.success) {
      alert('Description du trigger enregistrée avec succès')
      // Optionnel : mettre à jour les props localement
      // props.triggerDetails.description = form.value.description
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde')
    }
  } catch (error) {
    console.error('❌ [TRIGGER] Erreur lors de la sauvegarde de la description:', error)
    alert('Erreur lors de la sauvegarde de la description: ' + (error.response?.data?.error || error.message))
  } finally {
    saving.value = false
  }
}

// ✅ Fonction de sauvegarde complète
const saveAll = async () => {
  if (!props.triggerName) {
    alert('Erreur: nom du trigger manquant');
    return;
  }

  try {
    saving.value = true
    
    const triggerData = {
      description: form.value.description,
      language: 'fr'
    }
    
    const response = await axios.post(`/api/trigger/${encodeURIComponent(props.triggerName)}/save-all`, triggerData) 
    
    if (response.data.success) {
      alert('Description du trigger enregistrée avec succès')
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde')
    }
  } catch (error) {
    console.error('❌ [TRIGGER] Erreur lors de la sauvegarde globale:', error)
    alert('Erreur lors de la sauvegarde des informations du trigger: ' + (error.response?.data?.error || error.message))
  } finally {
    saving.value = false
  }
}
</script>