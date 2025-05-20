<template>
  <AuthenticatedLayout>
    <!-- Header -->
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">
          <span class="text-gray-500 font-normal">Versions :</span> 
          Gestion des versions
        </h2>
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
        <div v-else class="space-y-8">
          <!-- Liste des versions -->
          <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">
                  <svg class="h-5 w-5 text-gray-500 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                  </svg>
                  Versions disponibles
                </h3>
                <div class="flex space-x-2">
                  <div class="relative">
                    <input 
                      v-model="searchQuery" 
                      type="text" 
                      placeholder="Rechercher..." 
                      class="px-3 py-1.5 text-sm border rounded focus:ring-blue-500 focus:border-blue-500"
                    />
                    <svg v-if="searchQuery" @click="searchQuery = ''" class="absolute top-2 right-2 h-4 w-4 text-gray-400 cursor-pointer hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </div>
                  <select 
                    v-model="filterVersion" 
                    class="px-3 py-1.5 text-sm border rounded focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option value="">Toutes les versions</option>
                    <option v-for="version in uniqueVersions" :key="version" :value="version">
                      {{ version }}
                    </option>
                  </select>
                  <select 
                    v-model="filterProject" 
                    class="px-3 py-1.5 text-sm border rounded focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option value="">Tous les projets</option>
                    <option v-for="project in projects" :key="project.id" :value="project.id">
                      {{ project.name }}
                    </option>
                  </select>
                  <PrimaryButton @click="showAddReleaseModal = true">
                    Ajouter une version
                  </PrimaryButton>
                </div>
              </div>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead>
                  <tr class="bg-gray-50">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Version
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Projet
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Description
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Colonnes associées
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Créée le
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="release in filteredReleases" 
                      :key="release.id"
                      class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                      {{ release.version_number }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ release.project_name || 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">
                      {{ release.description || '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ release.column_count || 0 }} colonnes
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ release.created_at }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <div class="flex space-x-2">
                        <button @click="editRelease(release)" class="text-indigo-600 hover:text-indigo-900" title="Modifier">
                          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </button>
                        <button @click="confirmDeleteRelease(release)" class="text-red-600 hover:text-red-900" title="Supprimer">
                          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                          </svg>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="filteredReleases.length === 0">
                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                      Aucune version trouvée
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal pour ajouter une version -->
    <div v-if="showAddReleaseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">
            {{ editingReleaseId ? 'Modifier la version' : 'Ajouter une nouvelle version' }}
          </h3>
          <button @click="closeReleaseModal" class="text-gray-400 hover:text-gray-500">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <form @submit.prevent="saveRelease">
          <div class="space-y-4">
            <!-- Numéro de version -->
            <div>
              <label for="version_number" class="block text-sm font-medium text-gray-700">Numéro de version</label>
              <input 
                id="version_number" 
                v-model="newRelease.version_number" 
                type="text" 
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="ex: 1.0.0"
              >
            </div>
            
            <!-- Projet associé -->
            <div>
              <label for="project_id" class="block text-sm font-medium text-gray-700">Projet</label>
              <select 
                id="project_id" 
                v-model="newRelease.project_id"
                required
                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
              >
                <option value="">Sélectionnez un projet</option>
                <option v-for="project in projects" :key="project.id" :value="project.id">
                  {{ project.name }}
                </option>
              </select>
            </div>
            
            <!-- Description -->
            <div>
              <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
              <textarea 
                id="description" 
                v-model="newRelease.description" 
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Description optionnelle de cette version"
              ></textarea>
            </div>
            
            <!-- Date de création (optionnelle) -->
            <div v-if="editingReleaseId">
              <label class="block text-sm font-medium text-gray-700">Date de création</label>
              <div class="mt-1 text-sm text-gray-500">{{ newRelease.created_at || 'Non disponible' }}</div>
            </div>
          </div>
          
          <div class="mt-6 flex justify-end space-x-3">
            <button 
              type="button"
              @click="closeReleaseModal"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
            >
              Annuler
            </button>
            <button 
              type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
              :disabled="savingRelease"
            >
              {{ savingRelease ? 'Enregistrement...' : (editingReleaseId ? 'Mettre à jour' : 'Ajouter') }}
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- Modal de confirmation de suppression -->
    <div v-if="showDeleteConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/3 shadow-lg rounded-md bg-white">
        <div class="flex flex-col items-center">
          <svg class="h-16 w-16 text-red-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <h3 class="text-lg font-medium text-gray-900 mb-2 text-center">
            Supprimer la version
          </h3>
          <p class="text-sm text-gray-500 text-center mb-6">
            Êtes-vous sûr de vouloir supprimer la version <span class="font-semibold">{{ releaseToDelete?.version_number }}</span> du projet <span class="font-semibold">{{ releaseToDelete?.project_name }}</span> ?<br>
            Cette action est irréversible.
          </p>
          
          <div class="flex justify-center space-x-4 w-full">
            <button 
              @click="showDeleteConfirmModal = false"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
            >
              Annuler
            </button>
            <button 
              @click="deleteRelease"
              class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
              :disabled="deletingRelease"
            >
              {{ deletingRelease ? 'Suppression...' : 'Supprimer' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
  
// États
const loading = ref(true);
const error = ref(null);
const releases = ref([]);
const uniqueVersions = ref([]);
const projects = ref([]);

// États de filtrage
const searchQuery = ref('');
const filterVersion = ref('');
const filterProject = ref('');

// États pour le modal d'ajout/édition
const showAddReleaseModal = ref(false);
const savingRelease = ref(false);
const editingReleaseId = ref(null);
const newRelease = ref({
  version_number: '',
  project_id: '',
  description: '',
  created_at: null
});

// États pour le modal de suppression
const showDeleteConfirmModal = ref(false);
const deletingRelease = ref(false);
const releaseToDelete = ref(null);

// Chargement des données
onMounted(async () => {
  try {
    // Charger les versions
    await loadReleases();
  } catch (err) {
    console.error('Erreur lors du chargement des données:', err);
    error.value = `Erreur: ${err.response?.data?.error || err.message}`;
  } finally {
    loading.value = false;
  }
});

// Fonction pour charger les versions
const loadReleases = async () => {
  try {
    const response = await axios.get('/api/releases');
    releases.value = response.data.releases;
    uniqueVersions.value = response.data.uniqueVersions;
    projects.value = response.data.projects;
  } catch (err) {
    throw err;
  }
};

// Filtrage des versions
const filteredReleases = computed(() => {
  return releases.value.filter(release => {
    const matchesSearch = searchQuery.value === '' || 
      release.version_number.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (release.project_name && release.project_name.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      (release.description && release.description.toLowerCase().includes(searchQuery.value.toLowerCase()));
    
    const matchesVersion = filterVersion.value === '' || release.version_number === filterVersion.value;
    const matchesProject = filterProject.value === '' || release.project_id === parseInt(filterProject.value);
    
    return matchesSearch && matchesVersion && matchesProject;
  });
});

// Fonction pour ouvrir le modal en mode édition
const editRelease = (release) => {
  editingReleaseId.value = release.id;
  newRelease.value = {
    version_number: release.version_number,
    project_id: release.project_id,
    description: release.description || '',
    created_at: release.created_at
  };
  showAddReleaseModal.value = true;
};

// Fonction pour fermer le modal
const closeReleaseModal = () => {
  showAddReleaseModal.value = false;
  editingReleaseId.value = null;
  newRelease.value = {
    version_number: '',
    project_id: '',
    description: '',
    created_at: null
  };
};

// Fonction pour sauvegarder/mettre à jour une version
const saveRelease = async () => {
  try {
    savingRelease.value = true;
    
    let response;
    if (editingReleaseId.value) {
      // Mise à jour d'une version existante
      response = await axios.put(`/api/releases/${editingReleaseId.value}`, newRelease.value);
    } else {
      // Création d'une nouvelle version
      response = await axios.post('/api/releases', newRelease.value);
    }
    
    if (response.data.success) {
      // Recharger les données
      await loadReleases();
      
      // Fermer le modal
      closeReleaseModal();
      
      // Notification de succès
      alert(editingReleaseId.value ? 'Version mise à jour avec succès' : 'Version ajoutée avec succès');
    } else {
      throw new Error(response.data.error || 'Erreur lors de l\'opération');
    }
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error);
    alert('Erreur: ' + (error.response?.data?.error || error.message));
  } finally {
    savingRelease.value = false;
  }
};

// Fonction pour confirmer la suppression
const confirmDeleteRelease = (release) => {
  releaseToDelete.value = release;
  showDeleteConfirmModal.value = true;
};

// Fonction pour supprimer une version
const deleteRelease = async () => {
  try {
    deletingRelease.value = true;
    
    const response = await axios.delete(`/api/releases/${releaseToDelete.value.id}`);
    
    if (response.data.success) {
      // Recharger les données
      await loadReleases();
      
      // Fermer le modal
      showDeleteConfirmModal.value = false;
      releaseToDelete.value = null;
      
      // Notification de succès
      alert('Version supprimée avec succès');
    } else {
      throw new Error(response.data.error || 'Erreur lors de la suppression');
    }
  } catch (error) {
    console.error('Erreur lors de la suppression:', error);
    alert('Erreur: ' + (error.response?.data?.error || error.message));
  } finally {
    deletingRelease.value = false;
  }
};
</script>