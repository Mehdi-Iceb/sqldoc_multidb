<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">
          <span class="text-gray-500 font-normal">Function :</span>
          {{ functionName }}
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
                <h3 class="text-lg font-medium text-gray-900">Function Description</h3>
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
                v-model="descriptionText"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Optional description (usage, inputs/outputs, business rules, ...)"
              ></textarea>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <div class="flex items-center">
                <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900">Informations</h3>
              </div>
            </div>
            <div class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <h4 class="text-sm font-semibold text-gray-500 mb-1">Name</h4>
                  <div class="bg-gray-50 p-3 rounded">
                    <p class="text-gray-800">{{ functionData.name || 'Not specified' }}</p>
                  </div>
                </div>
                <div>
                  <h4 class="text-sm font-semibold text-gray-500 mb-1">Type</h4>
                  <div class="bg-gray-50 p-3 rounded">
                    <p class="text-gray-800">{{ functionData.function_type || 'Not specified' }}</p>
                  </div>
                </div>
                <div>
                  <h4 class="text-sm font-semibold text-gray-500 mb-1">Return type</h4>
                  <div class="bg-gray-50 p-3 rounded">
                    <p class="text-gray-800">{{ functionData.return_type || 'Not specified' }}</p>
                  </div>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                  <h4 class="text-sm font-semibold text-gray-500 mb-1">Creation Date</h4>
                  <div class="bg-gray-50 p-3 rounded">
                    <p class="text-gray-800">{{ formatDate(functionData.create_date) }}</p>
                  </div>
                </div>
                <div>
                  <h4 class="text-sm font-semibold text-gray-500 mb-1">Last Modification</h4>
                  <div class="bg-gray-50 p-3 rounded">
                    <p class="text-gray-800">{{ formatDate(functionData.modify_date) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
              <a
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                  activeTab === tab.id
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                  'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm cursor-pointer'
                ]"
              >
                {{ tab.name }}
              </a>
            </nav>
          </div>

          <div v-show="activeTab === 'parameters'">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                  </svg>
                  <h3 class="text-lg font-medium text-gray-900">Parameters</h3>
                </div>
              </div>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead>
                    <tr class="bg-gray-50">
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Name
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Type
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Output
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Description
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="param in functionData.parameters"
                        :key="param.parameter_id"
                        class="hover:bg-gray-50 transition-colors">
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ param.parameter_name }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        <span class="font-mono">{{ param.data_type }}</span>
                      </td>
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
                            @keyup.enter="saveParamDescription(param)"
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
                              @click="saveParamDescription(param)"
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
                    <tr v-if="!functionData.parameters || functionData.parameters.length === 0">
                      <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                        No parameters found
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div v-show="activeTab === 'definition'">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <div class="flex items-center">
                  <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                  </svg>
                  <h3 class="text-lg font-medium text-gray-900">Definition</h3>
                </div>
              </div>
              <div class="p-6">
                <div class="bg-gray-900 p-4 rounded overflow-auto max-h-96">
                  <pre class="text-gray-200 text-sm whitespace-pre-wrap">{{ functionData.definition || 'Definition not available' }}</pre>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'; // Import 'watch' here
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
// No need for 'router' from @inertiajs/vue3 if you're not programmatically navigating from this component.

// Define props: functionName, and importantly, functionDetails (which comes from Inertia)
const props = defineProps({
  functionName: {
    type: String,
    required: true
  },
  // ✅ This prop will receive data directly from your Inertia controller
  functionDetails: {
    type: Object,
    default: () => ({ // Provide a default empty object to prevent errors if not passed
      name: '',
      description: '',
      function_type: '',
      return_type: '',
      create_date: null,
      modify_date: null,
      parameters: [],
      definition: ''
    })
  },
  // ✅ Add a prop for initial errors passed by Inertia
  error: {
    type: String,
    default: null
  }
});

// Reactive state for function data (will be populated by prop or API call)
const functionData = ref(props.functionDetails); // Initialize with data from Inertia prop

// Reactive state for the editable description
const descriptionText = ref(props.functionDetails.description || ''); // Initialize with data from Inertia prop

const saving = ref(false); // To manage the saving state for buttons
const loading = ref(true); // To manage the loading state for initial data fetch
const currentError = ref(props.error); // To manage error state (initial or from API calls)

// Tabs state
const activeTab = ref('parameters');
const tabs = [
  { id: 'parameters', name: 'Parameters' },
  { id: 'definition', name: 'Definition' }
];

// For editing parameter descriptions
const editingParamId = ref(null);
const editingValue = ref('');

// --- Functions ---

// Function to fetch function details from the API
const loadFunctionDetailsFromAPI = async (nameOfFunction) => {
  try {
    console.log('🔍 [FUNCTION] Starting details fetch for:', nameOfFunction);
    loading.value = true; // Set loading to true before fetch
    currentError.value = null; // Clear any previous errors

    // Reset data to show loading state more clearly
    functionData.value = {
      name: '', description: '', function_type: '', return_type: '',
      create_date: null, modify_date: null, parameters: [], definition: ''
    };
    descriptionText.value = '';

    // ✅ Ensure this API URL matches your Laravel route (e.g., in api.php)
    const response = await axios.get(`/api/function/${encodeURIComponent(nameOfFunction)}/details`);
    console.log('🔍 [FUNCTION] API response received:', response.data);

    functionData.value = response.data; // Update the main data ref
    descriptionText.value = response.data.description || ''; // Update the editable description

    console.log('🔍 [FUNCTION] Details loaded successfully for:', nameOfFunction);
  } catch (error) {
    console.error('❌ [FUNCTION] Error loading function details:', error);
    currentError.value = error.response?.data?.error || `Error loading function details for "${nameOfFunction}"`;
  } finally {
    loading.value = false; // Set loading to false after fetch (success or error)
    console.log('🔍 [FUNCTION] Details fetch completed for:', nameOfFunction);
  }
};

// --- Lifecycle Hooks and Watchers ---

// Watch the functionName prop for changes
watch(
  () => props.functionName,
  async (newFunctionName, oldFunctionName) => {
    // If the new function name is the same as the old one, no need to re-fetch
    if (newFunctionName === oldFunctionName && functionData.value.name) {
      console.log('🔍 [FUNCTION] Watcher: functionName unchanged and data already present, no re-fetch.');
      loading.value = false; // Ensure loading is false if already loaded
      return;
    }
    console.log(`🔍 [FUNCTION] Watcher: functionName changed from "${oldFunctionName}" to "${newFunctionName}". Re-fetching details...`);
    await loadFunctionDetailsFromAPI(newFunctionName);
  },
  { immediate: true } // Run the watcher immediately when the component is mounted for the initial load
);

// onMounted is now primarily for initial setup not covered by the watcher
onMounted(() => {
  console.log('🔍 [FUNCTION] FunctionDetails component mounted.');
  // If the initial data comes from Inertia, 'loading' can be set to false immediately.
  // If no initial data (e.g., direct access with empty prop), the watcher handles it.
  if (props.functionDetails.name) { // Check if Inertia provided initial data
    loading.value = false;
  }
});

// --- Parameter Editing Functions ---
const startEdit = (param) => {
  editingParamId.value = param.parameter_id;
  editingValue.value = param.description || '';
};

const cancelEdit = () => {
  editingParamId.value = null;
  editingValue.value = '';
};

const saveParamDescription = async (param) => {
  try {
    const parameterId = param.parameter_id;
    if (!parameterId) {
      alert("Cannot save: Parameter ID is missing.");
      return;
    }

    // You might want to show a saving indicator specifically for this parameter
    // For simplicity, we'll just use the global 'saving' ref for now.
    saving.value = true;

    // ✅ Ensure this API URL matches your Laravel route for updating parameter descriptions
    // It should ideally use the functionName and parameter's unique identifier.
    // Given your controller doesn't have a 'saveParameterDescription' method yet,
    // let's assume an endpoint like this. You'll need to add it to your FunctionController.
    const response = await axios.post(`/api/function/${props.functionName}/parameters/${parameterId}/description`, {
      description: editingValue.value
    });

    if (response.data.success) {
      // Update local data with the new description
      const index = functionData.value.parameters.findIndex(p => p.parameter_id === parameterId);
      if (index !== -1) {
        functionData.value.parameters[index].description = editingValue.value;
      }
      alert('Parameter description saved successfully!');
      cancelEdit();
    } else {
      throw new Error(response.data.error || 'Failed to save parameter description.');
    }
  } catch (error) {
    console.error('❌ [FUNCTION] Error saving parameter description:', error);
    alert('Error saving parameter description: ' + (error.response?.data?.error || error.message));
  } finally {
    saving.value = false;
  }
};

// --- Function Description Saving Function ---
const saveDescription = async () => {
  try {
    saving.value = true;

    // ✅ Ensure this API URL matches your Laravel route for updating function description
    const response = await axios.post(`/api/function/${props.functionName}/description`, {
      description: descriptionText.value
    });

    if (response.data.success) {
      functionData.value.description = descriptionText.value; // Update local data
      alert('Function description saved successfully!');
    } else {
      throw new Error(response.data.error || 'Failed to save function description.');
    }
  } catch (error) {
    console.error('❌ [FUNCTION] Error saving function description:', error);
    alert('Error saving function description: ' + (error.response?.data?.error || error.message));
  } finally {
    saving.value = false;
  }
};

// --- Utility Functions ---
const formatDate = (dateString) => {
  if (!dateString) return 'Not specified';

  const date = new Date(dateString);
  if (isNaN(date.getTime())) {
    console.warn("Invalid date provided for formatDate:", dateString);
    return 'Invalid Date';
  }
  return date.toLocaleDateString('en-US', { // Changed to 'en-US' for consistency with "Not specified"
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>
