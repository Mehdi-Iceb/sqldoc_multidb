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

          <!-- Description de la table -->
          <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
              <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Table description</h3>
                <button 
                  @click="saveTableStructure" 
                  class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  :disabled="saving"
                >
                  {{ saving ? 'Enregistrement...' : 'Save descriptions' }}
                </button>
              </div>
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
                <PrimaryButton @click="showAddColumnModal = true">
                  Add a column
                </PrimaryButton>
              </div>
            </div>
            <!-- modal pour ajouter une nouvelle colonne -->
            <div v-if="showAddColumnModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
              <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-lg font-medium text-gray-900">
                    Add new column
                  </h3>
                  <button @click="showAddColumnModal = false" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                
                <form @submit.prevent="addNewColumn">
                  <div class="space-y-4">
                    <!-- Nom de la colonne -->
                    <div>
                      <label for="column_name" class="block text-sm font-medium text-gray-700">Column name</label>
                      <input 
                        id="column_name" 
                        v-model="newColumn.column_name" 
                        type="text" 
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      >
                    </div>
                    
                    <!-- Type de données -->
                    <div>
                      <label for="data_type" class="block text-sm font-medium text-gray-700">Data type</label>
                      <input 
                        id="data_type" 
                        v-model="newColumn.data_type" 
                        type="text" 
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="ex: varchar(255), int, date..."
                      >
                    </div>
                    
                    <!-- Nullable -->
                    <div class="flex items-center">
                      <input 
                        id="is_nullable" 
                        v-model="newColumn.is_nullable" 
                        type="checkbox" 
                        class="h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                      >
                      <label for="is_nullable" class="ml-2 block text-sm text-gray-700">Nullable</label>
                    </div>
                    
                    <!-- Type de clé -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Key type</label>
                      <div class="mt-1 flex items-center space-x-4">
                        <div class="flex items-center">
                          <input 
                            id="no_key" 
                            v-model="newColumn.key_type" 
                            type="radio" 
                            value="none"
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
                            class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                          >
                          <label for="foreign_key" class="ml-2 block text-sm text-gray-700">Foreign key</label>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Description -->
                    <div>
                      <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                      <textarea 
                        id="description" 
                        v-model="newColumn.description" 
                        rows="2"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      ></textarea>
                    </div>
                    
                    <!-- Valeurs possibles -->
                    <div>
                      <label for="possible_values" class="block text-sm font-medium text-gray-700">Range possible</label>
                      <textarea 
                        id="possible_values" 
                        v-model="newColumn.possible_values" 
                        rows="2"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      ></textarea>
                    </div>
                    
                    <!-- Version release -->
                    <div>
                      <label for="release" class="block text-sm font-medium text-gray-700">Version</label>
                      <select 
                        id="release" 
                        v-model="newColumn.release"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                      >
                        <option v-for="version in versions" :key="version" :value="version">
                          {{ version }}
                        </option>
                      </select>
                    </div>
                  </div>
                  
                  <div class="mt-6 flex justify-end space-x-3">
                    <button 
                      type="button"
                      @click="showAddColumnModal = false"
                      class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
                    >
                      Cancel
                    </button>
                    <button 
                      type="submit"
                      class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      :disabled="addingColumn"
                    >
                      {{ addingColumn ? 'Ajout en cours...' : 'Add' }}
                    </button>
                  </div>
                </form>
              </div>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead>
                  <tr class="bg-gray-50">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Column
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Type
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Nullable
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Key
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Description
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Range value
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      release
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Show
                    </th>
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
                      <div class="flex items-center space-x-2">
                        <span v-if="!editingDataType[column.column_name]" class="font-mono">
                          {{ formatDataType(column) }}
                        </span>
                        <input
                          v-else
                          v-model="editingDataTypeValue"
                          type="text"
                          class="flex-1 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500 font-mono"
                          @keyup.enter="saveDataType(column.column_name)"
                          @keyup.esc="cancelEdit('dataType', column.column_name)"
                          placeholder="Data type"
                        >
                        <button
                          v-if="!editingDataType[column.column_name]"
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
                            class="p-1 text-green-600 hover:text-green-700"
                            title="Sauvegarder"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                          </button>
                          <button
                            @click="cancelEdit('dataType', column.column_name)"
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <div class="flex items-center space-x-2">
                        <select 
                          :class="[
                            'block w-full pl-3 pr-10 py-1 text-xs border-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 rounded-md',
                            column.is_nullable ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'
                          ]"
                          :value="column.is_nullable ? 'true' : 'false'"
                          @change="updateNullable(column, $event.target.value === 'true')"
                        >
                          <option value="true">Oui</option>
                          <option value="false">Non</option>
                        </select>
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
                        <span v-if="!editingDescription[column.column_name]">
                          {{ column.description || '-' }}
                        </span>
                        <textarea
                          v-else
                          v-model="editingDescriptionValue"
                          type="text"
                          class="flex-1 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500"
                          @keydown.ctrl.enter="saveDescription(column.column_name)"
                          @keydown.esc="cancelEdit('description', column.column_name)"
                        ></textarea>
                        <button
                          v-if="!editingDescription[column.column_name]"
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
                            class="p-1 text-green-600 hover:text-green-700"
                            title="Sauvegarder"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                          </button>
                          <button
                            @click="cancelEdit('description', column.column_name)"
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
                    <td class="px-6 py-4 text-sm text-gray-500">
                      <div class="flex items-center space-x-2">
                        <span v-if="!editingPossibleValues[column.column_name]">
                          {{ column.possible_values || '-' }}
                        </span>
                        <textarea
                          v-else
                          v-model="editingPossibleValuesValue"
                          type="text"
                          class="flex-1 px-2 py-1 text-sm border rounded focus:ring-blue-500 focus:border-blue-500"
                          @keyup.enter="savePossibleValues(column.column_name)"
                          @keyup.esc="cancelEdit('possibleValues', column.column_name)"
                          placeholder="exemple of possible value"
                        ></textarea>
                        <button
                          v-if="!editingPossibleValues[column.column_name]"
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
                            class="p-1 text-green-600 hover:text-green-700"
                            title="Sauvegarder"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                          </button>
                          <button
                            @click="cancelEdit('possibleValues', column.column_name)"
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
                    <td class="px-6 py-4 text-sm text-gray-500">
                      <div class="flex items-center space-x-2">
                        <!-- Placeholder for release column content -->
                        <select 
                          :value="column.release_id || ''"
                          @change="updateColumnRelease(column, $event.target.value)"
                          class="block w-full pl-3 pr-8 py-1 text-xs border-gray-300 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 rounded-md"
                          :class="column.release_id ? 'bg-blue-50 text-blue-800' : ''"
                        >
                          <option value="">-- Aucune version --</option>
                          <option v-for="release in availableReleases" :key="release.id" :value="release.id">
                            {{ release.display_name }}
                          </option>
                        </select>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-sm">
                         <SecondaryButton @click="showAuditLogs(column.column_name)">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="blue" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                          </svg>
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
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Name
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Type
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Column
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Properties
                    </th>
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
                <PrimaryButton @click="showAddRelationModal = true">
                  Add relation
                </PrimaryButton>
              </div>
            </div>

            <!-- modal pour ajouter une nouvelle relation -->
            <div v-if="showAddRelationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
              <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-lg font-medium text-gray-900">
                    Add relation
                  </h3>
                  <button @click="showAddRelationModal = false" class="text-gray-400 hover:text-gray-500">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                
                <form @submit.prevent="addNewRelation">
                  <div class="space-y-4">
                    <!-- Nom de la contrainte -->
                    <div>
                      <label for="constraint_name" class="block text-sm font-medium text-gray-700">Name of constraint</label>
                      <input 
                        id="constraint_name" 
                        v-model="newRelation.constraint_name" 
                        type="text" 
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="ex: FK_TableA_TableB"
                      >
                    </div>
                    
                    <!-- Colonne source -->
                    <div>
                      <label for="column_name" class="block text-sm font-medium text-gray-700">Column origin</label>
                      <select 
                        id="column_name" 
                        v-model="newRelation.column_name"
                        required
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                      >
                        <option value="">Select a column</option>
                        <option v-for="column in tableDetails.columns" :key="column.column_name" :value="column.column_name">
                          {{ column.column_name }}
                        </option>
                      </select>
                    </div>
                    
                    <!-- Table référencée -->
                    <div>
                      <label for="referenced_table" class="block text-sm font-medium text-gray-700">Referenced Table</label>
                      <input 
                        id="referenced_table" 
                        v-model="newRelation.referenced_table" 
                        type="text" 
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      >
                    </div>
                    
                    <!-- Colonne référencée -->
                    <div>
                      <label for="referenced_column" class="block text-sm font-medium text-gray-700">Referenced Column</label>
                      <input 
                        id="referenced_column" 
                        v-model="newRelation.referenced_column" 
                        type="text" 
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                      >
                    </div>
                    
                    <!-- Action ON DELETE -->
                    <div>
                      <label for="delete_rule" class="block text-sm font-medium text-gray-700">Action ON DELETE</label>
                      <select 
                        id="delete_rule" 
                        v-model="newRelation.delete_rule"
                        required
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                      >
                        <option value="NO ACTION">NO ACTION</option>
                        <option value="CASCADE">CASCADE</option>
                        <option value="SET NULL">SET NULL</option>
                        <option value="SET DEFAULT">SET DEFAULT</option>
                        <option value="RESTRICT">RESTRICT</option>
                      </select>
                    </div>
                    
                    <!-- Action ON UPDATE -->
                    <div>
                      <label for="update_rule" class="block text-sm font-medium text-gray-700">Action ON UPDATE</label>
                      <select 
                        id="update_rule" 
                        v-model="newRelation.update_rule"
                        required
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
                      class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
                    >
                      Annuler
                    </button>
                    <button 
                      type="submit"
                      class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      :disabled="addingRelation"
                    >
                      {{ addingRelation ? 'Ajout en cours...' : 'Ajouter' }}
                    </button>
                  </div>
                </form>
              </div>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead>
                  <tr class="bg-gray-50">
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Constraint
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Column
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Referenced table
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      referenced column
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
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
        
        <div v-if="loadingAuditLogs" class="text-center py-4">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
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
import { ref, onMounted } from 'vue'
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
});

// États
const loading = ref(true);
const error = ref(null);
const saving = ref(false);
const tableDetails = ref({
  description: '',
  columns: [],
  indexes: [],
  relations: []
});
const form = ref({
  description: ''
});

// États pour l'édition
const editingDescription = ref({});
const editingDescriptionValue = ref('');
const editingPossibleValues = ref({});
const editingPossibleValuesValue = ref('');
const editingDataType = ref({});
const editingDataTypeValue = ref('');

// États pour le modal d'audit
const showAuditModal = ref(false);
const loadingAuditLogs = ref(false);
const auditLogs = ref([]);
const currentColumn = ref('');

// Chargement des détails de la table
onMounted(async () => {
  try {
    console.log('Chargement des détails pour:', props.tableName);
    const response = await axios.get(`/table/${encodeURIComponent(props.tableName)}/details`);
    console.log('Réponse reçue:', response.data);
    
    tableDetails.value = response.data;
    form.value.description = response.data.description || '';

    await loadAvailableReleases();
    
  } catch (err) {
    console.error('Erreur complète:', err);
    error.value = `Erreur: ${err.response?.data?.error || err.message}`;
  } finally {
    loading.value = false;
  }
});

// Formatage du type de données
const formatDataType = (column) => {
  let type = column.data_type;
  
  if (['varchar', 'nvarchar', 'char', 'nchar'].includes(type.toLowerCase())) {
    if (column.max_length) {
      type += `(${column.max_length === -1 ? 'MAX' : column.max_length})`;
    }
  } else if (['decimal', 'numeric'].includes(type.toLowerCase())) {
    if (column.precision && column.scale !== undefined) {
      type += `(${column.precision},${column.scale})`;
    }
  }
  
  return type;
};

// Fonction pour sauvegarder toute la structure
const saveTableStructure = async () => {
  try {
    saving.value = true;
    
    // Préparer les données à envoyer - uniquement les descriptions et valeurs possibles
    const tableData = {
      description: form.value.description,
      language: 'fr',
      columns: tableDetails.value.columns.map(column => ({
        column: column.column_name,
        description: column.description || null,
        rangevalues: column.possible_values || null
      }))
    };
    
    // Appel à l'API
    const response = await axios.post(`/table/${props.tableName}/save-structure`, tableData);
    
    if (response.data.success) {
      alert('Descriptions et valeurs possibles enregistrées avec succès');
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde');
    }
    
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error);
    alert('Erreur lors de la sauvegarde des descriptions et valeurs possibles');
  } finally {
    saving.value = false;
  }
};

// Fonction générique pour démarrer l'édition
const startEdit = (type, columnName, currentValue) => {
  if (type === 'description') {
    editingDescription.value = { [columnName]: true };
    editingDescriptionValue.value = currentValue || '';
  } else if (type === 'possibleValues') {
    editingPossibleValues.value = { [columnName]: true };
    editingPossibleValuesValue.value = currentValue || '';
  } else if (type === 'dataType') {
    editingDataType.value = { [columnName]: true };
    editingDataTypeValue.value = currentValue || '';
  }
};

// Fonction générique pour annuler l'édition
const cancelEdit = (type, columnName) => {
  if (type === 'description') {
    editingDescription.value = { [columnName]: false };
    editingDescriptionValue.value = '';
  } else if (type === 'possibleValues') {
    editingPossibleValues.value = { [columnName]: false };
    editingPossibleValuesValue.value = '';
  } else if (type === 'dataType') {
    editingDataType.value = { [columnName]: false };
    editingDataTypeValue.value = '';
  }
};

// Fonction pour sauvegarder la description
const saveDescription = async (columnName) => {
  try {
    const response = await axios.post(`/table/${props.tableName}/column/${columnName}/description`, {
      description: editingDescriptionValue.value
    });
    
    if (response.data.success) {
      // Mise à jour de la description dans les données locales
      const column = tableDetails.value.columns.find(c => c.column_name === columnName);
      if (column) {
        column.description = editingDescriptionValue.value;
      }
      
      // Réinitialise l'état d'édition
      cancelEdit('description', columnName);
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde de la description');
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour de la description:', error);
    alert('Erreur lors de la sauvegarde de la description');
  }
};

// Fonction pour sauvegarder les valeurs possibles
const savePossibleValues = async (columnName) => {
  try {
    console.log('Sauvegarde des valeurs possibles pour', columnName, ':', editingPossibleValuesValue.value);
    
    const response = await axios.post(`/table/${props.tableName}/column/${columnName}/possible-values`, {
      possible_values: editingPossibleValuesValue.value
    });
    
    console.log('Réponse du serveur:', response.data);
    
    if (response.data.success) {
      // Mise à jour des valeurs possibles dans les données locales
      const column = tableDetails.value.columns.find(c => c.column_name === columnName);
      if (column) {
        const oldValue = column.possible_values;
        column.possible_values = editingPossibleValuesValue.value;
        console.log('Valeur mise à jour localement:', {
          column: columnName,
          old: oldValue,
          new: column.possible_values
        });
      }
      
      // Réinitialise l'état d'édition
      cancelEdit('possibleValues', columnName);
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde des valeurs possibles');
    }
  } catch (error) {
    console.error('Erreur détaillée:', error);
    alert('Erreur lors de la sauvegarde des valeurs possibles');
  }
};

// Fonction pour sauvegarder le type de données
const saveDataType = async (columnName) => {
  try {
    const column = tableDetails.value.columns.find(c => c.column_name === columnName);
    const response = await axios.post(`/table/${props.tableName}/column/${columnName}/properties`, {
      column_name: columnName,
      data_type: editingDataTypeValue.value,
      is_nullable: column.is_nullable,
      is_primary_key: column.is_primary_key,
      is_foreign_key: column.is_foreign_key
    });
    
    if (response.data.success) {
      // Mise à jour du type de données dans les données locales
      if (column) {
        column.data_type = editingDataTypeValue.value;
      }
      
      // Réinitialise l'état d'édition
      cancelEdit('dataType', columnName);
    } else {
      throw new Error(response.data.error || 'Erreur lors de la sauvegarde du type de données');
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour du type de données:', error);
    alert('Erreur lors de la sauvegarde du type de données');
  }
};

// Fonction pour basculer la nullabilité d'une colonne
const updateNullable = async (column, isNullable) => {
  try {
    // Convertir la valeur string en boolean si nécessaire
    if (typeof isNullable === 'string') {
      isNullable = isNullable === 'true';
    }
    
    // Ne rien faire si la valeur n'a pas changé
    if (column.is_nullable === isNullable) {
      return;
    }
    
    // Copier les propriétés actuelles de la colonne
    const columnProperties = {
      column_name: column.column_name,
      data_type: column.data_type,
      is_nullable: isNullable, // Nouvelle valeur
      is_primary_key: column.is_primary_key,
      is_foreign_key: column.is_foreign_key
    };
    
    // Appel à l'API pour mettre à jour les propriétés
    const response = await axios.post(
      `/table/${props.tableName}/column/${column.column_name}/properties`,
      columnProperties
    );
    
    if (response.data.success) {
      // Mettre à jour localement la propriété is_nullable
      column.is_nullable = isNullable;
      
      // alert('Nullabilité modifiée avec succès');
    } else {
      throw new Error(response.data.error || 'Erreur lors de la modification de la nullabilité');
    }
  } catch (error) {
    console.error('Erreur lors de la modification de la nullabilité:', error);
    alert('Erreur lors de la modification de la nullabilité');
    
    // Recharger les données pour revenir à l'état initial en cas d'erreur
    await reloadTableData();
  }
};

// Fonction pour afficher les audit logs
const showAuditLogs = async (columnName) => {
  showAuditModal.value = true;
  loadingAuditLogs.value = true;
  currentColumn.value = columnName;
  
  try {
    const response = await axios.get(`/table/${props.tableName}/column/${columnName}/audit-logs`);
    auditLogs.value = response.data;
    
    // Analyser les noms de colonnes pour le débogage
    const uniqueColumnNames = [...new Set(response.data.map(log => log.column_name))];
    console.log('Unique column_name values in logs:', uniqueColumnNames);
    
  } catch (error) {
    console.error('Erreur lors du chargement des logs d\'audit:', error);
    alert('Erreur lors du chargement de l\'historique des modifications');
  } finally {
    loadingAuditLogs.value = false;
  }
};

// Fonction pour fermer le modal
const closeAuditModal = () => {
  showAuditModal.value = false;
  auditLogs.value = [];
  currentColumn.value = '';
};

// Fonction pour formater la date
const formatDate = (date) => {
  return new Date(date).toLocaleString('fr-FR', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Fonction pour obtenir le nom descriptif de la propriété modifiée
const getPropertyName = (columnNameWithSuffix) => {
  if (!columnNameWithSuffix) return 'Unknown';
  
  // Gestion des propriétés de table
  if (columnNameWithSuffix === 'table_description') return 'Table Description';
  if (columnNameWithSuffix === 'table_language') return 'Table Language';
  
  // Vérifier les suffixes spécifiques pour les propriétés de colonne
  if (columnNameWithSuffix.endsWith('_description')) return 'Description';
  
  // Mettre à jour cette condition pour inclure votre nouveau format
  if (columnNameWithSuffix.endsWith('_rangevalues') || 
      columnNameWithSuffix.endsWith('_rangevalues') || 
      columnNameWithSuffix.endsWith('_possible_values')) {
    return 'Range Values';
  }
  
  if (columnNameWithSuffix.endsWith('_type')) return 'Data Type';
  if (columnNameWithSuffix.endsWith('_nullable')) return 'Nullable';
  if (columnNameWithSuffix.endsWith('_key')) return 'Key Type';
  if (columnNameWithSuffix.endsWith('_release')) return 'Release';
  
  // Si c'est une modification du nom de colonne ou autre
  if (columnNameWithSuffix.includes('_')) {
    const parts = columnNameWithSuffix.split('_');
    const lastPart = parts[parts.length - 1];
    if (['name', 'update_name'].includes(lastPart)) return 'Column Name';
  }
  
  // Par défaut, retourner le nom tel quel
  return columnNameWithSuffix;
};

// Fonction pour obtenir une classe CSS selon le type de propriété
const getPropertyClass = (columnNameWithSuffix) => {
  if (!columnNameWithSuffix) return '';
  
  // Couleurs différentes selon le type de propriété
  if (columnNameWithSuffix.endsWith('_description')) return 'text-purple-700';
  if (columnNameWithSuffix.endsWith('_rangevalues')) return 'text-green-700';
  if (columnNameWithSuffix.endsWith('_type')) return 'text-blue-700';
  if (columnNameWithSuffix.endsWith('_nullable')) return 'text-red-700';
  if (columnNameWithSuffix.endsWith('_key')) return 'text-yellow-700';
  if (columnNameWithSuffix.endsWith('_release')) return 'text-indigo-700';
  
  // Par défaut
  return 'text-gray-900';
};

// Fonction pour formater les valeurs des logs
const formatLogValue = (value) => {
  if (!value) return '-';
  try {
    // Si c'est une chaîne JSON, on essaie de la parser et de la formater
    const parsed = JSON.parse(value);
    return JSON.stringify(parsed, null, 2);
  } catch {
    // Si ce n'est pas du JSON, on retourne la valeur telle quelle
    return value;
  }
};

// États pour le modal d'ajout de colonne
const showAddColumnModal = ref(false);
const addingColumn = ref(false);
const newColumn = ref({
  column_name: '',
  data_type: '',
  is_nullable: false,
  key_type: 'none', // 'none', 'PK', ou 'FK'
  description: '',
  possible_values: '',
  release: '1.0.0'
});

// Fonction pour ajouter une nouvelle colonne
const addNewColumn = async () => {
  try {
    addingColumn.value = true;
    
    // Préparer les données à envoyer
    const columnData = {
      column_name: newColumn.value.column_name,
      data_type: newColumn.value.data_type,
      is_nullable: newColumn.value.is_nullable,
      is_primary_key: newColumn.value.key_type === 'PK',
      is_foreign_key: newColumn.value.key_type === 'FK',
      description: newColumn.value.description,
      possible_values: newColumn.value.possible_values,
      release: newColumn.value.release
    };
    
    console.log('Ajout d\'une nouvelle colonne:', columnData);
    
    // Appel à l'API
    const response = await axios.post(`/table/${props.tableName}/column/add`, columnData);
    
    if (response.data.success) {
      // Fermer le modal
      showAddColumnModal.value = false;
      
      // Réinitialiser le formulaire
      newColumn.value = {
        column_name: '',
        data_type: '',
        is_nullable: false,
        key_type: 'none',
        description: '',
        possible_values: '',
        release: '1.0.0'
      };
      
      // Recharger les données de la table pour afficher la nouvelle colonne
      reloadTableData();
      
      // Notification de succès
      alert('Colonne ajoutée avec succès');
    } else {
      throw new Error(response.data.error || 'Erreur lors de l\'ajout de la colonne');
    }
  } catch (error) {
    console.error('Erreur lors de l\'ajout de la colonne:', error);
    alert('Erreur lors de l\'ajout de la colonne: ' + (error.response?.data?.error || error.message));
  } finally {
    addingColumn.value = false;
  }
};

// Fonction pour recharger les données de la table
const reloadTableData = async () => {
  try {
    const response = await axios.get(`/table/${encodeURIComponent(props.tableName)}/details`);
    tableDetails.value = response.data;
  } catch (err) {
    console.error('Erreur lors du rechargement des données:', err);
  }
};

// États pour le modal d'ajout de relation
const showAddRelationModal = ref(false);
const addingRelation = ref(false);
const newRelation = ref({
  constraint_name: '',
  column_name: '',
  referenced_table: '',
  referenced_column: '',
  delete_rule: 'NO ACTION',
  update_rule: 'NO ACTION'
});

// Fonction pour ajouter une nouvelle relation
const addNewRelation = async () => {
  try {
    addingRelation.value = true;
    
    // Préparer les données à envoyer
    const relationData = {
      constraint_name: newRelation.value.constraint_name,
      column_name: newRelation.value.column_name,
      referenced_table: newRelation.value.referenced_table,
      referenced_column: newRelation.value.referenced_column,
      delete_rule: newRelation.value.delete_rule,
      update_rule: newRelation.value.update_rule
    };
    
    console.log('Ajout d\'une nouvelle relation:', relationData);
    
    // Appel à l'API
    const response = await axios.post(`/table/${props.tableName}/relation/add`, relationData);
    
    if (response.data.success) {
      // Fermer le modal
      showAddRelationModal.value = false;
      
      // Réinitialiser le formulaire
      newRelation.value = {
        constraint_name: '',
        column_name: '',
        referenced_table: '',
        referenced_column: '',
        delete_rule: 'NO ACTION',
        update_rule: 'NO ACTION'
      };
      
      // Recharger les données de la table pour afficher la nouvelle relation
      reloadTableData();
      
      // Notification de succès
      alert('Relation ajoutée avec succès');
    } else {
      throw new Error(response.data.error || 'Erreur lors de l\'ajout de la relation');
    }
  } catch (error) {
    console.error('Erreur lors de l\'ajout de la relation:', error);
    alert('Erreur lors de l\'ajout de la relation: ' + (error.response?.data?.error || error.message));
  } finally {
    addingRelation.value = false;
  }
};

// Variables d'état pour les versions
const availableReleases = ref([]);

// Fonction pour charger les versions disponibles
const loadAvailableReleases = async () => {
  try {
    const response = await axios.get('/api/releases/all');
    availableReleases.value = response.data;
  } catch (error) {
    console.error('Erreur lors du chargement des versions:', error);
  }
};

// Fonction améliorée pour mettre à jour la version d'une colonne
const updateColumnRelease = async (column, releaseId) => {
  try {
    console.log('Début de updateColumnRelease', {
      column: column,
      releaseId: releaseId
    });
    
    // Si releaseId est une chaîne vide, la convertir en null
    const finalReleaseId = releaseId === '' ? null : parseInt(releaseId);
    
    console.log('Finding table_id', {
      tableDetails: tableDetails.value
    });
    
    // Trouver l'ID de la table - explorons toutes les possibilités
    let tableId;
    
    // Option 1: Directement dans tableDetails
    if (tableDetails.value && typeof tableDetails.value.id !== 'undefined') {
      tableId = tableDetails.value.id;
      console.log('Using tableDetails.value.id', tableId);
    } 
    // Option 2: Dans les données de la colonne
    else if (column.id_table) {
      tableId = column.id_table;
      console.log('Using column.id_table', tableId);
    } 
    // Option 3: ID de tableau spécifique aux colonnes
    else if (tableDetails.value && tableDetails.value.columns && tableDetails.value.columns.length > 0 && tableDetails.value.columns[0].table_id) {
      tableId = tableDetails.value.columns[0].table_id;
      console.log('Using tableDetails.value.columns[0].table_id', tableId);
    }
    // Option 4: Rechercher dans l'URL
    else {
      const urlSegments = window.location.pathname.split('/');
      const tableNameIndex = urlSegments.indexOf('table');
      if (tableNameIndex !== -1 && tableNameIndex + 1 < urlSegments.length) {
        const tableName = urlSegments[tableNameIndex + 1];
        console.log('Found table name from URL:', tableName);
        
        // Faire une requête pour obtenir l'ID de la table
        const tableInfoResponse = await axios.get(`/api/table-id/${tableName}`);
        tableId = tableInfoResponse.data.id;
        console.log('Retrieved table_id from API:', tableId);
      }
    }
    
    if (!tableId) {
      throw new Error('Impossible de déterminer l\'ID de la table');
    }
    
    console.log('Envoi de la requête pour mettre à jour la version', {
      release_id: finalReleaseId,
      table_id: tableId,
      column_name: column.column_name
    });
    
    // Requête API pour mettre à jour la version
    const response = await axios.post('/api/releases/assign-to-column', {
      release_id: finalReleaseId,
      table_id: tableId,
      column_name: column.column_name
    });
    
    console.log('Réponse de l\'API', response.data);
    
    if (response.data.success) {
      // Mettre à jour localement
      column.release_id = finalReleaseId;
      
      // Stocker en localStorage pour la persistance entre les rechargements
      try {
        const storageKey = `column_release_${tableId}_${column.column_name}`;
        if (finalReleaseId) {
          localStorage.setItem(storageKey, finalReleaseId);
          
          // Mettre à jour le nom de la version pour l'affichage
          const selectedRelease = availableReleases.value.find(r => r.id === finalReleaseId);
          column.release_version = selectedRelease ? selectedRelease.version_number : '';
          
          console.log('Version mise à jour localement et dans localStorage', {
            column: column,
            release_id: finalReleaseId,
            release_version: column.release_version
          });
        } else {
          localStorage.removeItem(storageKey);
          column.release_version = null;
          console.log('Version retirée localement et du localStorage');
        }
      } catch (storageError) {
        console.warn('Erreur lors de l\'écriture dans localStorage', storageError);
      }
    } else {
      throw new Error(response.data.error || 'Erreur lors de la mise à jour');
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour de la version:', error);
    alert('Erreur: ' + (error.response?.data?.error || error.message));
    
    // Recharger les données en cas d'erreur
    try {
      await reloadTableData();
    } catch (reloadError) {
      console.error('Erreur lors du rechargement des données:', reloadError);
    }
  }
};

</script>