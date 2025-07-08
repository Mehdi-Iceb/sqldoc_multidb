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

          <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <h3 class="text-lg font-medium text-gray-900">Informations</h3>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <p class="text-sm text-gray-600">Schema</p>
                <p class="mt-1 text-sm font-medium text-gray-900">
                  {{ procedureData.schema || 'Not specified' }}
                </p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Creation Date</p>
                <p class="mt-1 text-sm font-medium text-gray-900">
                  {{ formatDate(procedureData.create_date) }}
                </p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Last Modification</p>
                <p class="mt-1 text-sm font-medium text-gray-900">
                  {{ formatDate(procedureData.modify_date) }}
                </p>
              </div>
            </div>
          </div>

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
                        Output
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

          <div class="bg-white rounded-lg shadow-sm overflow-hidden">
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
import { ref, onMounted, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
// No need for 'Link' from @inertiajs/vue3 if not directly used for navigation within the script.

const saving = ref(false);

// ✅ Define all props received from Inertia
const props = defineProps({
  procedureName: {
    type: String,
    required: true
  },
  // This prop will carry the initial procedure data from the Inertia controller
  procedureDetails: {
    type: Object,
    default: () => ({ // Provide a default empty object to prevent errors if not passed
      name: '',
      schema: '',
      create_date: null,
      modify_date: null,
      description: '',
      parameters: [],
      definition: ''
    })
  },
  // Add a prop for initial errors passed by Inertia
  error: {
    type: String,
    default: null
  }
});

// Reactive state for procedure data (initialized from props)
const procedureData = ref(props.procedureDetails);

// Reactive state for the editable description (initialized from props)
const procedureForm = ref({
  description: props.procedureDetails.description || ''
});

const loading = ref(true); // Initial state is loading until props are processed or API call finishes
const currentError = ref(props.error); // Initialize with any error passed by Inertia

// For editing parameter descriptions
const editingParamId = ref(null);
const editingValue = ref('');

// --- Functions ---

// Function to fetch procedure details from the API (for re-fetching on name change)
const loadProcedureDetailsFromAPI = async (nameOfProcedure) => {
  try {
    console.log('🔍 [PROCEDURE] Starting details fetch for:', nameOfProcedure);
    loading.value = true;
    currentError.value = null;

    // Reset data to show loading state more clearly
    procedureData.value = {
      parameters: [], definition: '', schema: '',
      create_date: null, modify_date: null, description: ''
    };
    procedureForm.value.description = '';

    // ✅ Ensure this API URL matches your Laravel route (e.g., in api.php)
    const response = await axios.get(`/api/procedure/${encodeURIComponent(nameOfProcedure)}/details`);
    console.log('🔍 [PROCEDURE] API response received:', response.data);

    procedureData.value = response.data;
    procedureForm.value.description = response.data.description || '';

    console.log('🔍 [PROCEDURE] Data loaded successfully for:', nameOfProcedure);
  } catch (err) {
    console.error('❌ [PROCEDURE] Error loading procedure details:', err);
    currentError.value = err.response?.data?.error || `Error loading procedure details for "${nameOfProcedure}"`;
  } finally {
    loading.value = false;
    console.log('🔍 [PROCEDURE] Details fetch completed for:', nameOfProcedure);
  }
};

// --- Lifecycle Hooks and Watchers ---

// Watch the procedureName prop for changes
watch(
  () => props.procedureName,
  async (newProcedureName, oldProcedureName) => {
    // If the new procedure name is the same as the old one AND we already have data, no need to re-fetch
    if (newProcedureName === oldProcedureName && procedureData.value.name) {
      console.log('🔍 [PROCEDURE] Watcher: procedureName unchanged and data already present, no re-fetch.');
      loading.value = false; // Ensure loading is false if already loaded
      return;
    }
    console.log(`🔍 [PROCEDURE] Watcher: procedureName changed from "${oldProcedureName}" to "${newProcedureName}". Re-fetching details...`);
    await loadProcedureDetailsFromAPI(newProcedureName);
  },
  { immediate: true } // Run the watcher immediately when the component is mounted for the initial load
);

// onMounted is now primarily for initial setup not covered by the watcher
onMounted(() => {
  console.log('🔍 [PROCEDURE] ProcedureDetails component mounted.');
  // If initial data is provided by Inertia, set loading to false immediately.
  // Otherwise, the watcher will handle the initial fetch.
  if (props.procedureDetails.name) {
    loading.value = false;
  }
});

// --- Description Saving Function ---
const saveDescription = async () => {
  try {
    saving.value = true;

    // ✅ Ensure this API URL matches your Laravel route for updating procedure description
    const response = await axios.post(`/api/procedure/${props.procedureName}/description`, {
      description: procedureForm.value.description
    });

    if (response.data.success) {
      alert('Procedure description saved successfully!');
      procedureData.value.description = procedureForm.value.description; // Update local data
    } else {
      throw new Error(response.data.error || 'Failed to save description.');
    }
  } catch (error) {
    console.error('❌ [PROCEDURE] Error saving description:', error);
    currentError.value = error.response?.data?.error || `Error saving description: ${error.message}`;
    alert('Error saving description: ' + (error.response?.data?.error || error.message));
  } finally {
    saving.value = false;
  }
};

// --- Parameter Editing Functions ---
// (These are new as they were not in your original procedureDetails but are good to have for consistency with FunctionDetails)
const startEdit = (param) => {
  // Assuming parameters have a unique 'parameter_id' or 'parameter_name'
  editingParamId.value = param.parameter_id || param.parameter_name;
  editingValue.value = param.description || '';
};

const cancelEdit = () => {
  editingParamId.value = null;
  editingValue.value = '';
};

const saveParameterDescription = async (param) => {
  try {
    const parameterIdentifier = param.parameter_id || param.parameter_name; // Use an ID if available, else name
    if (!parameterIdentifier) {
      alert("Cannot save: Parameter identifier is missing.");
      return;
    }

    saving.value = true; // Use global saving for simplicity

    // ✅ You'll need to define this API route and controller method
    // It's good practice to use an ID if available for uniqueness.
    // Assuming you'll add a 'description' field to your procedure parameters in the DB.
    const response = await axios.post(`/api/procedure/${props.procedureName}/parameters/${encodeURIComponent(parameterIdentifier)}/description`, {
      description: editingValue.value
    });

    if (response.data.success) {
      // Find and update the parameter in the local reactive data
      const index = procedureData.value.parameters.findIndex(p => (p.parameter_id === parameterIdentifier || p.parameter_name === parameterIdentifier));
      if (index !== -1) {
        procedureData.value.parameters[index].description = editingValue.value;
      }
      alert('Parameter description saved successfully!');
      cancelEdit();
    } else {
      throw new Error(response.data.error || 'Failed to save parameter description.');
    }
  } catch (error) {
    console.error('❌ [PROCEDURE] Error saving parameter description:', error);
    currentError.value = error.response?.data?.error || `Error saving parameter description: ${error.message}`;
    alert('Error saving parameter description: ' + (error.response?.data?.error || error.message));
  } finally {
    saving.value = false;
  }
};


// The `saveAll` function seems redundant if `saveDescription` handles the only editable field.
// I've kept it commented out. If you plan to add more editable fields, you can use it.
/*
const saveAll = async () => {
  try {
    saving.value = true;

    const procedureDataToSave = {
      description: procedureForm.value.description
      // Add other editable fields here if they exist
    };

    // Make sure this API route exists and handles the full data object
    const response = await axios.post(`/api/procedure/${props.procedureName}/save-all`, procedureDataToSave);

    if (response.data.success) {
      alert('Procedure details saved successfully!');
      // Update other local data fields if 'save-all' returns them
    } else {
      throw new Error(response.data.error || 'Failed to save all procedure details.');
    }

  } catch (error) {
    console.error('❌ [PROCEDURE] Error saving all details:', error);
    currentError.value = error.response?.data?.error || `Error saving all details: ${error.message}`;
    alert('Error saving all details: ' + (error.response?.data?.error || error.message));
  } finally {
    saving.value = false;
  }
};
*/

// --- Utility Functions ---
const formatDate = (dateString) => {
  if (!dateString) return 'Not specified';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) {
    console.warn("Invalid date provided for formatDate:", dateString);
    return 'Invalid Date';
  }
  return date.toLocaleDateString('en-US', { // Using 'en-US' for consistency with "Not specified"
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>