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
          <!-- Loading state -->
          <div v-if="loading" class="bg-white rounded-lg shadow-sm p-6">
            <div class="animate-pulse space-y-4">
              <div class="h-4 bg-gray-200 rounded w-1/4"></div>
              <div class="space-y-3">
                <div class="h-4 bg-gray-200 rounded"></div>
                <div class="h-4 bg-gray-200 rounded"></div>
                <div class="h-4 bg-gray-200 rounded"></div>
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
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">Informations</h3>
              </div>
              <div class="p-6 grid grid-cols-2 gap-6">
                <div>
                  <p class="text-sm text-gray-600">Creation date</p>
                  <p class="mt-1 text-sm font-medium text-gray-900">
                    {{ formatDate(viewDetails.create_date) }}
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Last modification</p>
                  <p class="mt-1 text-sm font-medium text-gray-900">
                    {{ formatDate(viewDetails.modify_date) }}
                  </p>
                </div>
              </div>
            </div>
  
            <!-- Structure de la vue -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
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
                          {{ column.is_nullable ? 'Oui' : 'Non' }}
                        </span>
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
                      <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                        No column found
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
                <pre class="whitespace-pre-wrap text-sm text-gray-600 font-mono bg-gray-50 p-4 rounded-lg">{{ viewDetails.definition }}</pre>
              </div>
            </div>
  
            <!-- Bouton pour sauvegarder toutes les informations -->
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
import { ref, onMounted, watch } from 'vue' // ✅ N'oubliez pas d'importer 'watch'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
// import { Link } from '@inertiajs/vue3' // Link est importé mais non utilisé directement dans le script, on peut le retirer si non nécessaire.

const form = ref({
  description: ''
})

// ✅ Définition des props au début du script setup
const props = defineProps({
  viewName: {
    type: String,
    required: true
  }
})

const loading = ref(true)
const error = ref(null)
const saving = ref(false)
const viewDetails = ref({
  columns: [],
  definition: '',
  create_date: null,
  modify_date: null,
  description: '' // Assurez-vous que le modèle de données inclut la description de la vue
})

// Pour l'édition des descriptions de colonnes
const editingColumnName = ref(null)
const editingColumnDescription = ref('')

const startEdit = (column) => {
  editingColumnName.value = column.column_name
  editingColumnDescription.value = column.description || ''
}

const cancelEdit = () => {
  editingColumnName.value = null
  editingColumnDescription.value = ''
}

// ✅ EXTRAIRE LA LOGIQUE DE CHARGEMENT DANS UNE FONCTION SÉPARÉE
const loadViewDetailsFromAPI = async (nameOfView) => {
  try {
    console.log('🔍 [VIEW] Début du chargement des détails pour:', nameOfView);
    // Réinitialiser les états de chargement et d'erreur
    loading.value = true;
    error.value = null;
    // Réinitialiser les données pour un feedback visuel immédiat
    viewDetails.value = {
      columns: [],
      definition: '',
      create_date: null,
      modify_date: null,
      description: ''
    };
    form.value.description = ''; // Réinitialiser la description du formulaire

    // ✅ Vérifiez que cette URL correspond à votre API pour les vues
    const response = await axios.get(`/api/view/${encodeURIComponent(nameOfView)}/details`);
    console.log('🔍 [VIEW] Réponse reçue:', response.data);
    
    viewDetails.value = response.data;
    // Assurez-vous que la description de la vue est bien dans `response.data`
    form.value.description = response.data.description || '';
    
    console.log('🔍 [VIEW] Données chargées avec succès pour:', nameOfView);
  } catch (err) {
    console.error('❌ [VIEW] Erreur lors du chargement des détails de la vue:', err);
    error.value = err.response?.data?.error || `Erreur lors du chargement des détails de la vue "${nameOfView}"`;
  } finally {
    loading.value = false;
    console.log('🔍 [VIEW] Finalisation du chargement pour:', nameOfView);
  }
}

// ✅ NOUVEAU WATCHER POUR LA PROP viewName
watch(
  () => props.viewName,
  async (newViewName, oldViewName) => {
    // Évite le rechargement si la prop n'a pas réellement changé
    if (newViewName === oldViewName) {
      console.log('🔍 [VIEW] Watcher: viewName inchangé, pas de rechargement.');
      return;
    }
    console.log(`🔍 [VIEW] Watcher: viewName a changé de "${oldViewName}" à "${newViewName}". Rechargement des détails...`);
    await loadViewDetailsFromAPI(newViewName);
  },
  { immediate: true } // `immediate: true` pour exécuter le watcher une fois au montage initial
);

// onMounted est maintenant géré par le watcher avec `immediate: true`
onMounted(() => {
  console.log('🔍 [VIEW] Composant ViewDetails monté. Le chargement initial est géré par le watcher.');
  // Vous pouvez ajouter ici d'autres logiques qui ne dépendent PAS de viewName changeant
});

// Fonction pour sauvegarder la description d'une colonne
const saveColumnDescription = async (columnName) => {
  try {
    saving.value = true; // Potentiellement, utiliser un état de sauvegarde spécifique pour les colonnes
    
    // ✅ Vérifiez que cette URL correspond à votre API pour la sauvegarde de description de colonne
    const response = await axios.post(`/api/view/${props.viewName}/column/${columnName}/description`, {
      description: editingColumnDescription.value
    });
    
    if (response.data.success) {
      // Mise à jour locale
      const column = viewDetails.value.columns.find(c => c.column_name === columnName);
      if (column) {
        column.description = editingColumnDescription.value;
      }
      alert('Description de la colonne enregistrée avec succès.');
      cancelEdit();
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde de la description de colonne');
    }
  } catch (error) {
    console.error('❌ [VIEW] Erreur lors de la sauvegarde de la description de la colonne:', error);
    console.error('Détails:', error.response?.data);
    alert('Erreur lors de la sauvegarde de la description de la colonne: ' + (error.response?.data?.error || error.message));
  } finally {
    saving.value = false; // Réinitialiser l'état de sauvegarde global, ou un état spécifique pour les colonnes
  }
}

// Fonction pour sauvegarder la description de la vue (globale)
const saveDescription = async () => {
  try {
    saving.value = true
    
    // ✅ Vérifiez que cette URL correspond à votre API pour la sauvegarde de description de la vue
    const response = await axios.post(`/api/view/${props.viewName}/description`, {
      description: form.value.description
    })
    
    if (response.data.success) {
      alert('Description de la vue enregistrée avec succès')
      viewDetails.value.description = form.value.description
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde')
    }
  } catch (error) {
    console.error('❌ [VIEW] Erreur lors de la sauvegarde de la description de la vue:', error)
    console.error('Détails:', error.response?.data)
    alert('Erreur lors de la sauvegarde de la description de la vue: ' + (error.response?.data?.error || error.message))
  } finally {
    saving.value = false
  }
}

// Fonction pour sauvegarder toutes les informations
const saveAll = async () => {
  try {
    saving.value = true
    
    // Préparer les données à envoyer (description de la vue et descriptions des colonnes)
    const viewData = {
      description: form.value.description,
      columns: viewDetails.value.columns.map(column => ({
        column_name: column.column_name,
        description: column.description
      }))
    }
    
    // ✅ Vérifiez que cette URL correspond à votre API pour la sauvegarde globale
    const response = await axios.post(`/api/view/${props.viewName}/save-all`, viewData)
    
    if (response.data.success) {
      alert('Les descriptions de la vue et de ses colonnes ont été enregistrées avec succès')
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde globale')
    }
  } catch (error) {
    console.error('❌ [VIEW] Erreur lors de la sauvegarde globale des descriptions:', error)
    console.error('Détails:', error.response?.data)
    alert('Erreur lors de la sauvegarde globale des descriptions: ' + (error.response?.data?.error || error.message))
  } finally {
    saving.value = false
  }
}

const formatDataType = (column) => {
  let type = column.data_type
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
  if (isNaN(date.getTime())) { // Vérifie si la date est valide
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