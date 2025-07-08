<template>
  <AuthenticatedLayout>
    <!-- Header -->
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">
          <span class="text-gray-500 font-normal">Table :</span> 
          {{ tableName }}
        </h2>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
        <!-- 🎯 SPINNER DE CHARGEMENT PRINCIPAL DE LA PAGE -->
        <div v-if="loading" class="fixed inset-0 bg-gray-200 bg-opacity-20 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg shadow-xl p-8 flex flex-col items-center max-w-sm w-full mx-4">
            <!-- Spinner principal -->
            <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-green-600 mb-6"></div>
            
            <!-- Titre et sous-titre -->
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Loading table details</h3>
            <p class="text-gray-600 text-center mb-4">
              Retrieving structure for <span class="font-medium text-blue-600">{{ tableName }}</span>
            </p>
            
            <!-- Barre de progression simulée -->
            <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
              <div class="bg-green-600 h-2 rounded-full transition-all duration-300 ease-out" 
                   :style="{ width: loadingProgress + '%' }"></div>
            </div>
            
            <!-- Messages de progression -->
            <div class="text-sm text-gray-500 text-center">
              <div v-if="loadingProgress < 30" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Connecting to database...
              </div>
              <div v-else-if="loadingProgress < 60" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Loading table structure...
              </div>
              <div v-else-if="loadingProgress < 90" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Loading relations and indexes...
              </div>
              <div v-else class="flex items-center">
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
        <div v-else class="space-y-8">

          <!-- Description de la table -->
          <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Table description</h3>
                <!--  Bouton conditionnel selon les permissions -->
                <button 
                  v-if="tableDetails.can_edit"
                  @click="saveTableStructure" 
                  class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 relative"
                  :class="{ 'opacity-50 cursor-not-allowed': saving }"
                  :disabled="saving"
                >
                  <!--  SPINNER 1: Bouton Save descriptions -->
                  <span v-if="!saving">Save descriptions</span>
                  <span v-else class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving...
                  </span>
                </button>
                <!-- ✅ Message si pas de permissions -->
                <span v-else class="text-sm text-gray-500 italic">
                  Read-only access
                </span>
              </div>
            </div>
            <div class="p-6">
              <textarea
                v-model="form.description"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                :class="{ 'opacity-50 cursor-not-allowed bg-gray-100': !tableDetails.can_edit }"
                placeholder="Optional description (use, environment, content...)"
                :disabled="!tableDetails.can_edit || saving"
                :readonly="!tableDetails.can_edit"
              ></textarea>
            </div>
          </div>
          
          <!-- Structure de la table -->
          <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">
                  <svg class="h-5 w-5 text-gray-500 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7z"/>
                  </svg>
                  Table structure
                </h3>
                <PrimaryButton v-if="tableDetails.can_add_columns" @click="showAddColumnModal = true">
                  Add a column
                </PrimaryButton>
              </div>
            </div>

            <!--  MODAL 1: Add Column avec spinner -->
            <div v-if="showAddColumnModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
              <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
                <!-- Overlay de chargement pour le modal -->
                <div v-if="addingColumn" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10 rounded-md">
                  <div class="flex flex-col items-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-2"></div>
                    <p class="text-gray-600 text-sm">Adding column...</p>
                  </div>
                </div>

                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-lg font-medium text-gray-900">Add new column</h3>
                  <button @click="showAddColumnModal = false" class="text-gray-400 hover:text-gray-500" :disabled="addingColumn">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                
                <form @submit.prevent="addNewColumn" :class="{ 'opacity-50 pointer-events-none': addingColumn }">
                  <div class="space-y-4">
                    <div>
                      <label for="column_name" class="block text-sm font-medium text-gray-700">Column name</label>
                      <input 
                        id="column_name" 
                        v-model="newColumn.column_name" 
                        type="text" 
                        required
                        :disabled="addingColumn"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      >
                    </div>
                    
                    <div>
                      <label for="data_type" class="block text-sm font-medium text-gray-700">Data type</label>
                      <input 
                        id="data_type" 
                        v-model="newColumn.data_type" 
                        type="text" 
                        required
                        :disabled="addingColumn"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="ex: varchar(255), int, date..."
                      >
                    </div>
                    
                    <div class="flex items-center">
                      <input 
                        id="is_nullable" 
                        v-model="newColumn.is_nullable" 
                        type="checkbox" 
                        :disabled="addingColumn"
                        class="h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                      >
                      <label for="is_nullable" class="ml-2 block text-sm text-gray-700">Nullable</label>
                    </div>
                    
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Key type</label>
                      <div class="mt-1 flex items-center space-x-4">
                        <div class="flex items-center">
                          <input 
                            id="no_key" 
                            v-model="newColumn.key_type" 
                            type="radio" 
                            value="none"
                            :disabled="addingColumn"
                            class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                          >
                          <label for="no_key" class="ml-2 block text-sm text-gray-700">None</label>
                        </div>
                        <div class="flex items-center">
                          <input 
                            id="primary_key" 
                            v-model="newColumn.key_type" 
                            type="radio" 
                            value="PK"
                            :disabled="addingColumn"
                            class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                          >
                          <label for="primary_key" class="ml-2 block text-sm text-gray-700">Primary key</label>
                        </div>
                        <div class="flex items-center">
                          <input 
                            id="foreign_key" 
                            v-model="newColumn.key_type" 
                            type="radio" 
                            value="FK"
                            :disabled="addingColumn"
                            class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                          >
                          <label for="foreign_key" class="ml-2 block text-sm text-gray-700">Foreign key</label>
                        </div>
                      </div>
                    </div>
                    
                    <div>
                      <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                      <textarea 
                        id="description" 
                        v-model="newColumn.description" 
                        rows="2"
                        :disabled="addingColumn"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      ></textarea>
                    </div>
                    
                    <div>
                      <label for="possible_values" class="block text-sm font-medium text-gray-700">Range possible</label>
                      <textarea 
                        id="possible_values" 
                        v-model="newColumn.possible_values" 
                        rows="2"
                        :disabled="addingColumn"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      ></textarea>
                    </div>
                    
                    <div>
                      <label for="release" class="block text-sm font-medium text-gray-700">Version</label>
                      <select 
                        id="release" 
                        v-model="newColumn.release"
                        :disabled="addingColumn"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                      >
                        <option v-for="release in availableReleases" :key="release.id" :value="release.id">
                          {{ release.display_name }}
                        </option>
                      </select>
                    </div>
                  </div>
                  
                  <div class="mt-6 flex justify-end space-x-3">
                    <button 
                      type="button"
                      @click="showAddColumnModal = false"
                      :disabled="addingColumn"
                      class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
                    >
                      Cancel
                    </button>
                    <button 
                      type="submit"
                      class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 relative"
                      :disabled="addingColumn"
                    >
                      <span v-if="!addingColumn">Add</span>
                      <span v-else class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Adding...
                      </span>
                    </button>
                  </div>
                </form>
              </div>
            </div>

            <!-- Table structure -->
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead>
                  <tr class="bg-gray-50">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Column</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nullable</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Key</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Range value</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Release</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Show</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="column in tableDetails.columns" 
                      :key="column.column_name"
                      class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ column.column_name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                      <!--  SPINNER 2: Édition du type de données -->
                      <div class="flex items-center space-x-2">
                        <span v-if="!editingDataType[column.column_name]" class="font-mono">
                          {{ formatDataType(column) }}
                        </span>
                        <input
                          v-else
                          v-model="editingDataTypeValue"
                          type="text"
                          class="flex-1 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500"
                          :disabled="!tableDetails.can_edit"
                          @keyup.enter="saveDataType(column.column_name)"
                          @keyup.esc="cancelEdit('dataType', column.column_name)"
                          placeholder="Data type"
                        >
                        <button
                          v-if="!editingDataType[column.column_name] && tableDetails.can_edit"
                          @click="startEdit('dataType', column.column_name, column.data_type)"
                          class="p-1 text-gray-400 hover:text-gray-600"
                          title="Modifier le type de données"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </button>
                        <div v-else class="flex space-x-1">
                          <button
                            @click="saveDataType(column.column_name)"
                            class="p-1 text-green-600 hover:text-green-700 relative"
                            title="Sauvegarder"
                            :disabled="savingDataType[column.column_name]"
                          >
                            <svg v-if="!savingDataType[column.column_name]" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="animate-spin h-4 w-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                          </button>
                          <button
                            @click="cancelEdit('dataType', column.column_name)"
                            class="p-1 text-red-600 hover:text-red-700"
                            title="Annuler"
                            :disabled="savingDataType[column.column_name]"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                          </button>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <!--  SPINNER 3: Mise à jour Nullable avec overlay -->
                      <div class="flex items-center space-x-2 relative">
                        <select 
                        :value="column.is_nullable ? 'true' : 'false'"
                        @change="updateNullable(column, $event.target.value === 'true')"
                        :disabled="!tableDetails.can_edit || updatingNullable[column.column_name]"
                          :class="[
                            'block w-full pl-2 pr-7 py-1 text-xs border-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 rounded-md transition-opacity',
                            column.is_nullable ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800',
                            updatingNullable[column.column_name] ? 'opacity-50' : ''
                          ]"
                        >
                          <option value="true">Yes</option>
                          <option value="false">No</option>
                        </select>
                        <!-- Mini spinner pour nullable -->
                        <div v-if="updatingNullable[column.column_name]" class="absolute right-2 top-1/2 transform -translate-y-1/2">
                          <svg class="animate-spin h-3 w-3 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <div class="flex gap-1">
                        <span v-if="column.is_primary_key" 
                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                          <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                          </svg>
                          PK
                        </span>
                        <span v-if="column.is_foreign_key"
                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                          <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                          </svg>
                          FK
                        </span>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                      <div class="flex items-center space-x-2">
                        <span v-if="!editingDescription[column.column_name]"
                          style="max-height: 128px; max-width: 320px; overflow: auto; white-space: pre-wrap; display: block;"
                        >
                          {{ column.description || '-' }}
                        </span>
                        <textarea
                          v-else
                          v-model="editingDescriptionValue"
                          style="max-height: 128px; overflow-y: auto; resize: vertical;"
                          class="flex-1 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500"
                          :disabled="!tableDetails.can_edit"
                          @keydown.ctrl.enter="saveDescription(column.column_name)"
                          @keydown.esc="cancelEdit('description', column.column_name)"
                        ></textarea>
                        <button
                          v-if="!editingDescription[column.column_name] && tableDetails.can_edit"
                          @click="startEdit('description', column.column_name, column.description)"
                          class="p-1 text-gray-400 hover:text-gray-600"
                          title="Modifier la description"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </button>
                        <div v-else class="flex space-x-1">
                          <button
                            @click="saveDescription(column.column_name)"
                            class="p-1 text-green-600 hover:text-green-700 relative"
                            title="Sauvegarder"
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
                            title="Annuler"
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
                        <span v-if="!editingPossibleValues[column.column_name]"
                          style="max-height: 128px; max-width: 320px; overflow: auto; white-space: pre-wrap; display: block;"
                        >
                          {{ column.possible_values || '-' }}
                        </span>
                        <textarea
                          v-else
                          v-model="editingPossibleValuesValue"
                          style="max-height: 128px; overflow-y: auto; resize: vertical;"
                          class="flex-1 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500"
                          :disabled="!tableDetails.can_edit"
                          @keyup.enter="savePossibleValues(column.column_name)"
                          @keyup.esc="cancelEdit('possibleValues', column.column_name)"
                          placeholder="exemple of possible value"
                        ></textarea>
                        <button
                          v-if="!editingPossibleValues[column.column_name] && tableDetails.can_edit"
                          @click="startEdit('possibleValues', column.column_name, column.possible_values)"
                          class="p-1 text-gray-400 hover:text-gray-600"
                          title="Modifier les valeurs possibles"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                          </svg>
                        </button>
                        <div v-else class="flex space-x-1">
                          <button
                            @click="savePossibleValues(column.column_name)"
                            class="p-1 text-green-600 hover:text-green-700 relative"
                            title="Sauvegarder"
                            :disabled="savingPossibleValues[column.column_name]"
                          >
                            <svg v-if="!savingPossibleValues[column.column_name]" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="animate-spin h-4 w-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                          </button>
                          <button
                            @click="cancelEdit('possibleValues', column.column_name)"
                            class="p-1 text-red-600 hover:text-red-700"
                            title="Annuler"
                            :disabled="savingPossibleValues[column.column_name]"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                          </button>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                      <!-- SPINNER 4: Release dropdown avec spinner -->
                      <div class="flex items-center space-x-2 relative">
                        <select 
                          :value="column.release_id || ''"
                          @change="updateColumnRelease(column, $event.target.value)"
                          :disabled="!tableDetails.can_edit || updatingRelease[column.column_name]"
                          :class="[
                            'block w-full pl-2 pr-7 py-1 text-xs border-gray-300 rounded-md',
                            column.release_id ? 'bg-blue-50 text-blue-800' : '',
                            !tableDetails.can_edit ? 'opacity-50 cursor-not-allowed' : '',
                            updatingRelease[column.column_name] ? 'opacity-50' : ''
                          ]"
                        >
                          <option value=""> no  -</option>
                          <option v-for="release in availableReleases" :key="release.id" :value="release.id">
                            {{ release.display_name }}
                          </option>
                        </select>
                        <!-- Mini spinner pour release -->
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
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="blue" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                          </svg>
                        </span>
                        <span v-else class="flex items-center">
                          <svg class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                        </span>
                      </SecondaryButton>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Index -->
          <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <div class="flex items-center">
                <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900">Index</h3>
              </div>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead>
                  <tr class="bg-gray-50">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Column</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Properties</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="index in tableDetails.indexes" 
                      :key="index.index_name"
                      class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ index.index_name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                      {{ index.index_type }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                      <span class="font-mono">{{ index.columns }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <div class="flex gap-2">
                        <span v-if="index.is_primary_key" 
                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                          Primary Keys
                        </span>
                        <span v-if="index.is_unique" 
                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                          Unique
                        </span>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="!tableDetails.indexes?.length">
                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                      No Index found
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Relations -->
          <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">
                  <svg class="h-5 w-5 text-gray-500 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                  </svg>
                  Relations
                </h3>
                <PrimaryButton v-if="tableDetails.can_add_relations" @click="showAddRelationModal = true">
                  Add relation
                </PrimaryButton>
              </div>
            </div>

            <!--  MODAL 2: Add Relation avec spinner -->
            <div v-if="showAddRelationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
              <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
                <!-- Overlay de chargement pour le modal relation -->
                <div v-if="addingRelation" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10 rounded-md">
                  <div class="flex flex-col items-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-2"></div>
                    <p class="text-gray-600 text-sm">Adding relation...</p>
                  </div>
                </div>

                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-lg font-medium text-gray-900">Add relation</h3>
                  <button @click="showAddRelationModal = false" class="text-gray-400 hover:text-gray-500" :disabled="addingRelation">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                
                <form @submit.prevent="addNewRelation" :class="{ 'opacity-50 pointer-events-none': addingRelation }">
                  <div class="space-y-4">
                    <div>
                      <label for="constraint_name" class="block text-sm font-medium text-gray-700">Name of constraint</label>
                      <input 
                        id="constraint_name" 
                        v-model="newRelation.constraint_name" 
                        type="text" 
                        required
                        :disabled="addingRelation"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="ex: FK_TableA_TableB"
                      >
                    </div>
                    
                    <div>
                      <label for="column_name" class="block text-sm font-medium text-gray-700">Column origin</label>
                      <select 
                        id="column_name" 
                        v-model="newRelation.column_name"
                        required
                        :disabled="addingRelation"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                      >
                        <option value="">Select a column</option>
                        <option v-for="column in tableDetails.columns" :key="column.column_name" :value="column.column_name">
                          {{ column.column_name }}
                        </option>
                      </select>
                    </div>
                    
                    <div>
                      <label for="referenced_table" class="block text-sm font-medium text-gray-700">Referenced Table</label>
                      <input 
                        id="referenced_table" 
                        v-model="newRelation.referenced_table" 
                        type="text" 
                        required
                        :disabled="addingRelation"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      >
                    </div>
                    
                    <div>
                      <label for="referenced_column" class="block text-sm font-medium text-gray-700">Referenced Column</label>
                      <input 
                        id="referenced_column" 
                        v-model="newRelation.referenced_column" 
                        type="text" 
                        required
                        :disabled="addingRelation"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      >
                    </div>
                    
                    <div>
                      <label for="delete_rule" class="block text-sm font-medium text-gray-700">Action ON DELETE</label>
                      <select 
                        id="delete_rule" 
                        v-model="newRelation.delete_rule"
                        required
                        :disabled="addingRelation"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                      >
                        <option value="NO ACTION">NO ACTION</option>
                        <option value="CASCADE">CASCADE</option>
                        <option value="SET NULL">SET NULL</option>
                        <option value="SET DEFAULT">SET DEFAULT</option>
                        <option value="RESTRICT">RESTRICT</option>
                      </select>
                    </div>
                    
                    <div>
                      <label for="update_rule" class="block text-sm font-medium text-gray-700">Action ON UPDATE</label>
                      <select 
                        id="update_rule" 
                        v-model="newRelation.update_rule"
                        required
                        :disabled="addingRelation"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                      >
                        <option value="NO ACTION">NO ACTION</option>
                        <option value="CASCADE">CASCADE</option>
                        <option value="SET NULL">SET NULL</option>
                        <option value="SET DEFAULT">SET DEFAULT</option>
                        <option value="RESTRICT">RESTRICT</option>
                      </select>
                    </div>
                  </div>
                  
                  <div class="mt-6 flex justify-end space-x-3">
                    <button 
                      type="button"
                      @click="showAddRelationModal = false"
                      :disabled="addingRelation"
                      class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
                    >
                      cancelEdit
                    </button>
                    <button 
                      type="submit"
                      class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 relative"
                      :disabled="addingRelation"
                    >
                      <span v-if="!addingRelation">Ajouter</span>
                      <span v-else class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Adding...
                      </span>
                    </button>
                  </div>
                </form>
              </div>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead>
                  <tr class="bg-gray-50">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Constraint</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Column</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referenced table</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referenced column</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="relation in tableDetails.relations" 
                      :key="relation.constraint_name"
                      class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ relation.constraint_name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                      {{ relation.column_name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <Link 
                        :href="route('table.details', { tableName: relation.referenced_table })"
                        class="text-blue-600 hover:text-blue-900 font-medium hover:underline"
                      >
                        {{ relation.referenced_table }}
                      </Link>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                      {{ relation.referenced_column }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <div class="space-y-1 text-xs">
                        <div class="px-2 py-1 bg-gray-100 rounded">
                          ON DELETE {{ relation.delete_rule }}
                        </div>
                        <div class="px-2 py-1 bg-gray-100 rounded">
                          ON UPDATE {{ relation.update_rule }}
                        </div>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="!tableDetails.relations?.length">
                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                      No relations found
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal pour afficher les audit logs -->
    <div v-if="showAuditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">
            Change history - column name: {{ currentColumn }}
          </h3>
          <button @click="closeAuditModal" class="text-gray-400 hover:text-gray-500">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <!-- 🎯 SPINNER 5: Loading audit logs -->
        <div v-if="loadingAuditLogs" class="text-center py-8">
          <div class="flex flex-col items-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-3"></div>
            <p class="text-gray-600">Loading change history...</p>
          </div>
        </div>
        
        <div v-else-if="auditLogs.length === 0" class="text-center py-4 text-gray-500">
          No change found on this column !
        </div>
        
        <div v-else class="overflow-y-auto max-h-96">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Property</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Old value</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">New value</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="log in auditLogs" :key="log.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(log.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ log.user?.name || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <span class="font-medium" :class="getPropertyClass(log.column_name)">
                    {{ getPropertyName(log.column_name) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span :class="[
                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                    log.change_type === 'update' ? 'bg-yellow-100 text-yellow-800' :
                    log.change_type === 'add' ? 'bg-green-100 text-green-800' :
                    log.change_type === 'delete' ? 'bg-red-100 text-red-800' :
                    'bg-gray-100 text-gray-800'
                  ]">
                    {{ log.change_type }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                  <pre class="whitespace-pre-wrap font-mono text-xs">{{ formatLogValue(log.old_data) }}</pre>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                  <pre class="whitespace-pre-wrap font-mono text-xs">{{ formatLogValue(log.new_data) }}</pre>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
// ✅ Import corrigé avec computed
import { ref, onMounted, watch, computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link } from '@inertiajs/vue3'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import Dropdown from '@/Components/Dropdown.vue'

const props = defineProps({
  tableName: {
    type: String,
    required: true
  }
})

// Variables d'état pour les spinners
const loadingProgress = ref(0)
const savingDataType = ref({})
const savingDescription = ref({})
const savingPossibleValues = ref({})
const updatingNullable = ref({})
const updatingRelease = ref({})


// Simulation de progression de chargement
let progressInterval = null

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
}

// États principaux
const loading = ref(true)
const error = ref(null)
const saving = ref(false)
const tableDetails = ref({
  description: '',
  columns: [],
  indexes: [],
  relations: [],
  can_edit: false,
  can_add_columns: false,
  can_add_relations: false
})
const form = ref({
  description: ''
})

const canEdit = computed(() => {
  console.log('🔍 [COMPUTED] canEdit:', tableDetails.value.can_edit)
  return tableDetails.value.can_edit || false
})

const canAddColumns = computed(() => {
  console.log('🔍 [COMPUTED] canAddColumns:', tableDetails.value.can_add_columns)
  return tableDetails.value.can_add_columns || false
})

const canAddRelations = computed(() => {
  console.log('🔍 [COMPUTED] canAddRelations:', tableDetails.value.can_add_relations)
  return tableDetails.value.can_add_relations || false
})

// computed pour debugging
const permissionsDebug = computed(() => ({
  can_edit: tableDetails.value.can_edit,
  can_add_columns: tableDetails.value.can_add_columns,
  can_add_relations: tableDetails.value.can_add_relations,
  is_owner: tableDetails.value.is_owner
}))

// États pour l'édition
const editingDescription = ref({})
const editingDescriptionValue = ref('')
const editingPossibleValues = ref({})
const editingPossibleValuesValue = ref('')
const editingDataType = ref({})
const editingDataTypeValue = ref('')

// États pour le modal d'audit
const showAuditModal = ref(false)
const loadingAuditLogs = ref(false)
const auditLogs = ref([])
const currentColumn = ref('')

// États pour les modaux
const showAddColumnModal = ref(false)
const addingColumn = ref(false)
const newColumn = ref({
  column_name: '',
  data_type: '',
  is_nullable: false,
  key_type: 'none',
  description: '',
  possible_values: '',
  release: ''
})

const showAddRelationModal = ref(false)
const addingRelation = ref(false)
const newRelation = ref({
  constraint_name: '',
  column_name: '',
  referenced_table: '',
  referenced_column: '',
  delete_rule: 'NO ACTION',
  update_rule: 'NO ACTION'
})

const availableReleases = ref([])

// ✅ CHARGEMENT INITIAL AVEC DEBUGGING AMÉLIORÉ
onMounted(async () => {
  try {
    console.log('🔍 [TABLE] Début du chargement pour:', props.tableName)
    console.log('🔍 [TABLE] Session info:', {
      user: window.Laravel?.user,
      dbId: window.Laravel?.session?.current_db_id
    })
    
    // Démarrer la simulation de progression
    simulateLoadingProgress()
    
    console.log(' [TABLE] URL de requête:', `/table/${encodeURIComponent(props.tableName)}/details`)
    
    const response = await axios.get(`/table/${encodeURIComponent(props.tableName)}/details`)
    console.log('🔍 [TABLE] Réponse complète du serveur:', response.data)
    
    // ✅ DEBUGGING DÉTAILLÉ
    console.log('🔍 [TABLE] Permissions reçues:', {
      can_edit: response.data.can_edit,
      can_add_columns: response.data.can_add_columns,
      can_add_relations: response.data.can_add_relations,
      is_owner: response.data.is_owner,
      permissions_debug: response.data.permissions_debug
    })
    
    tableDetails.value = response.data
    form.value.description = response.data.description || ''

    // Debug des permissions après assignation
    console.log(' [TABLE] Permissions après assignation:', {
      can_edit: tableDetails.value.can_edit,
      can_add_columns: tableDetails.value.can_add_columns,
      can_add_relations: tableDetails.value.can_add_relations,
      is_owner: tableDetails.value.is_owner
    })
    
    // check si les computed se mettent à jour
    setTimeout(() => {
      console.log('🔍 [TABLE] Computed values (après timeout):', {
        canEdit: canEdit.value,
        canAddColumns: canAddColumns.value,
        canAddRelations: canAddRelations.value
      })
    }, 100)

    // WATCHERS POUR SURVEILLER LES CHANGEMENTS
    watch(() => tableDetails.value.can_edit, (newVal) => {
      console.log('🔍 [WATCH] tableDetails.can_edit changed to:', newVal)
    })

    watch(canEdit, (newVal) => {
      console.log('🔍 [WATCH] canEdit computed changed to:', newVal)
    })

    // COMPUTED POUR DÉBUGGER
    const debugPermissions = computed(() => {
      return {
        tableDetails_can_edit: tableDetails.value.can_edit,
        tableDetails_can_add_columns: tableDetails.value.can_add_columns,
        tableDetails_can_add_relations: tableDetails.value.can_add_relations,
        computed_canEdit: canEdit.value,
        computed_canAddColumns: canAddColumns.value,
        computed_canAddRelations: canAddRelations.value
      }
    })

    await loadAvailableReleases()
    
    console.log(' [TABLE] Données chargées avec succès')
    console.log(' [TABLE] État final tableDetails:', tableDetails.value)
    
  } catch (err) {
    console.error('❌ [TABLE] Erreur complète:', err)
    console.error('❌ [TABLE] Statut:', err.response?.status)
    console.error('❌ [TABLE] Données erreur:', err.response?.data)
    
    if (err.response?.status === 403) {
      error.value = `Accès refusé: ${err.response?.data?.error || 'Permissions insuffisantes'}`
    } else if (err.response?.status === 404) {
      error.value = `Table "${props.tableName}" non trouvée`
    } else {
      error.value = `Erreur: ${err.response?.data?.error || err.message}`
    }
  } finally {
    console.log('🔍 [TABLE] Finalisation du chargement')
    
    stopLoadingProgress()
    
    setTimeout(() => {
      console.log('🔍 [TABLE] Masquage du spinner')
      loading.value = false
    }, 500)
  }
})

// Fonction générique pour démarrer l'édition
const startEdit = (type, columnName, currentValue) => {
  console.log('🔍 [EDIT] Tentative d\'édition:', { type, columnName, canEdit: canEdit.value })
  
  if (!canEdit.value) {
    alert('Vous n\'avez pas les permissions pour modifier cette table')
    return
  }
  
  if (type === 'description') {
    editingDescription.value = { [columnName]: true }
    editingDescriptionValue.value = currentValue || ''
  } else if (type === 'possibleValues') {
    editingPossibleValues.value = { [columnName]: true }
    editingPossibleValuesValue.value = currentValue || ''
  } else if (type === 'dataType') {
    editingDataType.value = { [columnName]: true }
    editingDataTypeValue.value = currentValue || ''
  }
}

// Fonction générique pour annuler l'édition
const cancelEdit = (type, columnName) => {
  if (type === 'description') {
    editingDescription.value = { [columnName]: false }
    editingDescriptionValue.value = ''
  } else if (type === 'possibleValues') {
    editingPossibleValues.value = { [columnName]: false }
    editingPossibleValuesValue.value = ''
  } else if (type === 'dataType') {
    editingDataType.value = { [columnName]: false }
    editingDataTypeValue.value = ''
  }
}

// Formatage du type de données
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

// Fonction pour sauvegarder toute la structure
const saveTableStructure = async () => {
  try {
    console.log('🔍 [SAVE] Tentative de sauvegarde, canEdit:', canEdit.value)
    
    if (!canEdit.value) {
      alert('Vous n\'avez pas les permissions pour sauvegarder')
      return
    }
    
    saving.value = true
    
    const tableData = {
      description: form.value.description,
      language: 'fr',
      columns: tableDetails.value.columns.map(column => ({
        column: column.column_name,
        description: column.description || null,
        rangevalues: column.possible_values || null
      }))
    }
    
    console.log('🔍 [SAVE] Données à envoyer:', tableData)
    
    const response = await axios.post(`/table/${props.tableName}/save-structure`, tableData)
    
    if (response.data.success) {
      alert('Descriptions et valeurs possibles enregistrées avec succès')
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde')
    }
    
  } catch (error) {
    console.error('❌ [SAVE] Erreur lors de la sauvegarde:', error)
    alert('Erreur lors de la sauvegarde: ' + (error.response?.data?.error || error.message))
  } finally {
    saving.value = false
  }
}

// Fonction pour sauvegarder la description avec spinner
const saveDescription = async (columnName) => {
  try {
    console.log('🔍 [DESC] Sauvegarde description:', { columnName, canEdit: canEdit.value })
    
    if (!canEdit.value) {
      alert('Permissions insuffisantes')
      return
    }
    
    savingDescription.value[columnName] = true
    
    const response = await axios.post(`/table/${props.tableName}/column/${columnName}/description`, {
      description: editingDescriptionValue.value
    })
    
    if (response.data.success) {
      const column = tableDetails.value.columns.find(c => c.column_name === columnName)
      if (column) {
        column.description = editingDescriptionValue.value
      }
      cancelEdit('description', columnName)
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde de la description')
    }
  } catch (error) {
    console.error('❌ [DESC] Erreur:', error)
    alert('Erreur lors de la sauvegarde de la description: ' + (error.response?.data?.error || error.message))
  } finally {
    savingDescription.value[columnName] = false
  }
}

// Fonction pour sauvegarder les valeurs possibles avec spinner
const savePossibleValues = async (columnName) => {
  try {
    savingPossibleValues.value[columnName] = true
    
    const response = await axios.post(`/table/${props.tableName}/column/${columnName}/possible-values`, {
      possible_values: editingPossibleValuesValue.value
    })
    
    if (response.data.success) {
      const column = tableDetails.value.columns.find(c => c.column_name === columnName)
      if (column) {
        column.possible_values = editingPossibleValuesValue.value
      }
      cancelEdit('possibleValues', columnName)
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde des valeurs possibles')
    }
  } catch (error) {
    console.error('❌ [VALUES] Erreur:', error)
    alert('Erreur lors de la sauvegarde des valeurs possibles: ' + (error.response?.data?.error || error.message))
  } finally {
    savingPossibleValues.value[columnName] = false
  }
}

// Fonction pour sauvegarder le type de données avec spinner
const saveDataType = async (columnName) => {
  try {
    savingDataType.value[columnName] = true
    
    const column = tableDetails.value.columns.find(c => c.column_name === columnName)
    const response = await axios.post(`/table/${props.tableName}/column/${columnName}/properties`, {
      column_name: columnName,
      data_type: editingDataTypeValue.value,
      is_nullable: column.is_nullable,
      is_primary_key: column.is_primary_key,
      is_foreign_key: column.is_foreign_key
    })
    
    if (response.data.success) {
      if (column) {
        column.data_type = editingDataTypeValue.value
      }
      cancelEdit('dataType', columnName)
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde du type de données')
    }
  } catch (error) {
    console.error('❌ [TYPE] Erreur:', error)
    alert('Erreur lors de la sauvegarde du type de données: ' + (error.response?.data?.error || error.message))
  } finally {
    savingDataType.value[columnName] = false
  }
}

// Fonction pour basculer la nullabilité avec spinner
const updateNullable = async (column, isNullable) => {
  try {
    console.log('🔍 [NULLABLE] Mise à jour:', { column: column.column_name, isNullable, canEdit: canEdit.value })
    
    if (!canEdit.value) {
      alert('Permissions insuffisantes')
      return
    }
    
    updatingNullable.value[column.column_name] = true
    
    if (typeof isNullable === 'string') {
      isNullable = isNullable === 'true'
    }
    
    if (column.is_nullable === isNullable) {
      return
    }
    
    const columnProperties = {
      column_name: column.column_name,
      data_type: column.data_type,
      is_nullable: isNullable,
      is_primary_key: column.is_primary_key,
      is_foreign_key: column.is_foreign_key
    }
    
    const response = await axios.post(
      `/table/${props.tableName}/column/${column.column_name}/properties`,
      columnProperties
    )
    
    if (response.data.success) {
      column.is_nullable = isNullable
    } else {
      throw new Error(response.data.error || 'Erreur lors de la modification de la nullabilité')
    }
  } catch (error) {
    console.error('❌ [NULLABLE] Erreur:', error)
    alert('Erreur lors de la modification de la nullabilité: ' + (error.response?.data?.error || error.message))
    await reloadTableData()
  } finally {
    updatingNullable.value[column.column_name] = false
  }
}

// Fonction pour mettre à jour la version avec spinner
const updateColumnRelease = async (column, releaseId) => {
  try {
    updatingRelease.value[column.column_name] = true
    
    const finalReleaseId = releaseId === '' ? null : parseInt(releaseId)
    
    const response = await axios.post(`/table/${props.tableName}/column/${column.column_name}/release`, {
      release_id: finalReleaseId
    })
    
    if (response.data.success) {
      column.release_id = finalReleaseId
      const selectedRelease = availableReleases.value.find(r => r.id === finalReleaseId)
      column.release_version = selectedRelease ? selectedRelease.version_number : ''
    } else {
      throw new Error(response.data.error || 'Erreur lors de la mise à jour')
    }
  } catch (error) {
    console.error('❌ [RELEASE] Erreur:', error)
    alert('Erreur: ' + (error.response?.data?.error || error.message))
    await reloadTableData()
  } finally {
    updatingRelease.value[column.column_name] = false
  }
}

// Fonction pour afficher les audit logs
const showAuditLogs = async (columnName) => {
  showAuditModal.value = true
  loadingAuditLogs.value = true
  currentColumn.value = columnName
  
  try {
    const response = await axios.get(`/table/${props.tableName}/column/${columnName}/audit-logs`)
    auditLogs.value = response.data
  } catch (error) {
    console.error('❌ [AUDIT] Erreur:', error)
    alert('Erreur lors du chargement de l\'historique des modifications')
  } finally {
    loadingAuditLogs.value = false
  }
}

// Fonction pour fermer le modal
const closeAuditModal = () => {
  showAuditModal.value = false
  auditLogs.value = []
  currentColumn.value = ''
}

// Fonction pour formater la date
const formatDate = (date) => {
  return new Date(date).toLocaleString('fr-FR', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Fonction pour obtenir le nom descriptif de la propriété modifiée
const getPropertyName = (columnNameWithSuffix) => {
  if (!columnNameWithSuffix) return 'Unknown'
  
  if (columnNameWithSuffix === 'table_description') return 'Table Description'
  if (columnNameWithSuffix === 'table_language') return 'Table Language'
  if (columnNameWithSuffix.endsWith('_description')) return 'Description'
  if (columnNameWithSuffix.endsWith('_rangevalues') || 
      columnNameWithSuffix.endsWith('_possible_values')) return 'Range Values'
  if (columnNameWithSuffix.endsWith('_type')) return 'Data Type'
  if (columnNameWithSuffix.endsWith('_nullable')) return 'Nullable'
  if (columnNameWithSuffix.endsWith('_key')) return 'Key Type'
  if (columnNameWithSuffix.endsWith('_release')) return 'Release'
  
  if (columnNameWithSuffix.includes('_')) {
    const parts = columnNameWithSuffix.split('_')
    const lastPart = parts[parts.length - 1]
    if (['name', 'update_name'].includes(lastPart)) return 'Column Name'
  }
  
  return columnNameWithSuffix
}

// Fonction pour obtenir une classe CSS selon le type de propriété
const getPropertyClass = (columnNameWithSuffix) => {
  if (!columnNameWithSuffix) return ''
  
  if (columnNameWithSuffix.endsWith('_description')) return 'text-purple-700'
  if (columnNameWithSuffix.endsWith('_rangevalues')) return 'text-green-700'
  if (columnNameWithSuffix.endsWith('_type')) return 'text-blue-700'
  if (columnNameWithSuffix.endsWith('_nullable')) return 'text-red-700'
  if (columnNameWithSuffix.endsWith('_key')) return 'text-yellow-700'
  if (columnNameWithSuffix.endsWith('_release')) return 'text-indigo-700'
  
  return 'text-gray-900'
}

// Fonction pour formater les valeurs des logs
const formatLogValue = (value) => {
  if (!value) return '-'
  try {
    const parsed = JSON.parse(value)
    return JSON.stringify(parsed, null, 2)
  } catch {
    return value
  }
}

// Fonction pour ajouter une nouvelle colonne
const addNewColumn = async () => {
  try {
    if (!canAddColumns.value) {
      alert('Permissions insuffisantes pour ajouter une colonne')
      return
    }
    
    addingColumn.value = true
    
    const columnData = {
      column_name: newColumn.value.column_name,
      data_type: newColumn.value.data_type,
      is_nullable: newColumn.value.is_nullable,
      is_primary_key: newColumn.value.key_type === 'PK',
      is_foreign_key: newColumn.value.key_type === 'FK',
      description: newColumn.value.description,
      possible_values: newColumn.value.possible_values,
      release: newColumn.value.release
    }
    
    const response = await axios.post(`/table/${props.tableName}/column/add`, columnData)
    
    if (response.data.success) {
      showAddColumnModal.value = false
      newColumn.value = {
        column_name: '',
        data_type: '',
        is_nullable: false,
        key_type: 'none',
        description: '',
        possible_values: '',
        release: ''
      }
      reloadTableData()
      alert('Colonne ajoutée avec succès')
    } else {
      throw new Error(response.data.error || 'Erreur lors de l\'ajout de la colonne')
    }
  } catch (error) {
    console.error('❌ [ADD_COL] Erreur:', error)
    alert('Erreur lors de l\'ajout de la colonne: ' + (error.response?.data?.error || error.message))
  } finally {
    addingColumn.value = false
  }
}

// Fonction pour ajouter une nouvelle relation
const addNewRelation = async () => {
  try {
    if (!canAddRelations.value) {
      alert('Permissions insuffisantes pour ajouter une relation')
      return
    }
    
    addingRelation.value = true
    
    const relationData = {
      constraint_name: newRelation.value.constraint_name,
      column_name: newRelation.value.column_name,
      referenced_table: newRelation.value.referenced_table,
      referenced_column: newRelation.value.referenced_column,
      delete_rule: newRelation.value.delete_rule,
      update_rule: newRelation.value.update_rule
    }
    
    const response = await axios.post(`/table/${props.tableName}/relation/add`, relationData)
    
    if (response.data.success) {
      showAddRelationModal.value = false
      newRelation.value = {
        constraint_name: '',
        column_name: '',
        referenced_table: '',
        referenced_column: '',
        delete_rule: 'NO ACTION',
        update_rule: 'NO ACTION'
      }
      reloadTableData()
      alert('Relation ajoutée avec succès')
    } else {
      throw new Error(response.data.error || 'Erreur lors de l\'ajout de la relation')
    }
  } catch (error) {
    console.error('❌ [ADD_REL] Erreur:', error)
    alert('Erreur lors de l\'ajout de la relation: ' + (error.response?.data?.error || error.message))
  } finally {
    addingRelation.value = false
  }
}

// Fonction pour recharger les données de la table
const reloadTableData = async () => {
  try {
    const response = await axios.get(`/table/${encodeURIComponent(props.tableName)}/details`)
    tableDetails.value = response.data
    form.value.description = response.data.description || ''
  } catch (err) {
    console.error('❌ [RELOAD] Erreur lors du rechargement des données:', err)
  }
}

// Fonction pour charger les versions disponibles
const loadAvailableReleases = async () => {
  try {
    const response = await axios.get('/api/releases/all')
    availableReleases.value = response.data
  } catch (error) {
    console.error('❌ [RELEASES] Erreur lors du chargement des versions:', error)
  }
}

// ✅ NOUVEAU WATCHER POUR LA PROP tableName
watch(
  () => props.tableName,
  async (newTableName, oldTableName) => {
    // Ne pas recharger si le nom de la table est le même (première initialisation ou pas de changement réel)
    if (newTableName === oldTableName) {
      console.log('🔍 [TABLE] Watcher: tableName inchangé, pas de rechargement.');
      return;
    }
    
    console.log(`🔍 [TABLE] Watcher: tableName a changé de "${oldTableName}" à "${newTableName}". Rechargement des détails...`);
    
    // Réinitialiser l'état de chargement
    loading.value = true;
    error.value = null; // Réinitialiser l'erreur précédente
    tableDetails.value = { // Réinitialiser les détails pour montrer que quelque chose charge
      description: '',
      columns: [],
      indexes: [],
      relations: [],
      can_edit: false,
      can_add_columns: false,
      can_add_relations: false
    };
    form.value.description = '';

    // Déclencher la logique de chargement que vous avez déjà dans onMounted
    // Vous pouvez extraire cette logique dans une fonction séparée pour la réutiliser.
    await loadTableDetailsFromAPI(newTableName);
  },
  { immediate: true } // `immediate: true` pour exécuter le watcher une fois au montage initial
)

// ✅ EXTRAIRE LA LOGIQUE DE CHARGEMENT DANS UNE FONCTION SÉPARÉE
const loadTableDetailsFromAPI = async (nameOfTable) => {
  try {
    console.log('🔍 [TABLE] Début du chargement pour:', nameOfTable);
    simulateLoadingProgress();

    const response = await axios.get(`/table/${encodeURIComponent(nameOfTable)}/details`);
    console.log('🔍 [TABLE] Réponse complète du serveur:', response.data);

    tableDetails.value = response.data;
    form.value.description = response.data.description || '';
    
    await loadAvailableReleases(); // Assurez-vous que ceci est appelé après que tableDetails est mis à jour

    console.log('🔍 [TABLE] Données chargées avec succès pour:', nameOfTable);
    console.log('🔍 [TABLE] État final tableDetails:', tableDetails.value);

  } catch (err) {
    console.error('❌ [TABLE] Erreur complète:', err);
    if (err.response?.status === 403) {
      error.value = `Accès refusé: ${err.response?.data?.error || 'Permissions insuffisantes'}`;
    } else if (err.response?.status === 404) {
      error.value = `Table "${nameOfTable}" non trouvée`;
    } else {
      error.value = `Erreur: ${err.response?.data?.error || err.message}`;
    }
  } finally {
    stopLoadingProgress();
    setTimeout(() => {
      loading.value = false;
    }, 500);
    console.log('🔍 [TABLE] Finalisation du chargement pour:', nameOfTable);
  }
}

// ✅ MODIFIER onMounted pour appeler la nouvelle fonction
onMounted(() => {
  // Le watcher avec `immediate: true` gérera le chargement initial,
  // donc le onMounted peut être plus simple ou même vide pour le chargement.
  // Vous pouvez laisser des logs ici si vous voulez.
  console.log('🔍 [TABLE] Composant TableDetails monté.');
});
</script>