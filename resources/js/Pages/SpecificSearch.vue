<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">
          <span class="text-gray-500 font-normal">Specific search</span>
        </h2>
      </div>
    </template>

    <div class="p-6">
      <!-- Filtres -->
      <form @submit.prevent="performSearch" class="mb-4 space-y-4">
        <div class="flex items-center space-x-4">
          <label>
            <input type="checkbox" v-model="searchInTables" />
            <span class="ml-1">Search for column in Table</span>
          </label>
          <label>
            <input type="checkbox" v-model="searchInViews" />
            <span class="ml-1">Search for column in View</span>
          </label>
        </div>

        <div class="relative w-full">
		<span class="absolute inset-y-0 left-0 flex items-center pl-3">
			<svg class="w-4 h-4 text-gray-500" aria-hidden="true" fill="none" viewBox="0 0 20 20">
			<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
			</svg>
		</span>

		<input
			v-model="searchQuery"
			type="text"
			placeholder="Searching for..."
			class="w-full pl-10 pr-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
		/>
		</div>

        <button
          type="submit"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          Search
        </button>
      </form>

      <!-- Résultats des tables -->
      <div v-if="searchInTables && tableResults.length">
        <h3 class="text-lg font-semibold mb-2">Result - Tables</h3>
        <table class="min-w-full divide-y divide-gray-200 border mb-6">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Column</th>
              <th class="px-6 py-3">Type</th>
              <th class="px-6 py-3">Nullable</th>
              <th class="px-6 py-3">Key</th>
              <th class="px-6 py-3">Description</th>
              <th class="px-6 py-3">Range Value</th>
              <th class="px-6 py-3">Release</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="item in tableResults" :key="item.id">
              <td class="px-6 py-2">{{ item.column }}</td>
              <td class="px-6 py-2">{{ item.type }}</td>
              <td class="px-6 py-2">{{ item.nullable }}</td>
              <td class="px-6 py-2">{{ item.key }}</td>
              <td class="px-6 py-2">{{ item.description }}</td>
              <td class="px-6 py-2">{{ item.rangevalues }}</td>
              <td class="px-6 py-2">{{ item.release_id }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Résultats des vues -->
      <div v-if="searchInViews && viewResults.length">
        <h3 class="text-lg font-semibold mb-2">Result - View</h3>
        <table class="min-w-full divide-y divide-gray-200 border">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3">Column</th>
              <th class="px-6 py-3">Type</th>
              <th class="px-6 py-3">Nullable</th>
              <th class="px-6 py-3">Max Length</th>
              <th class="px-6 py-3">Precision</th>
              <th class="px-6 py-3">Scale</th>
              <th class="px-6 py-3">Description</th>
              <th class="px-6 py-3">Range Value</th>
              <th class="px-6 py-3">Release</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="item in viewResults" :key="item.id">
              <td class="px-6 py-2">{{ item.name }}</td>
              <td class="px-6 py-2">{{ item.type }}</td>
              <td class="px-6 py-2">{{ item.nullable }}</td>
              <td class="px-6 py-2">{{ item.max_length }}</td>
              <td class="px-6 py-2">{{ item.precision }}</td>
              <td class="px-6 py-2">{{ item.scale }}</td>
              <td class="px-6 py-2">{{ item.description }}</td>
              <td class="px-6 py-2">{{ item.rangevalues }}</td>
              <td class="px-6 py-2">{{ item.release_id }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Aucun résultat -->
      <div
        v-if="hasSearched && (tableResults.length === 0 && viewResults.length === 0)"
        class="mt-6 text-gray-500"
      >
        No result matched your research.
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const searchQuery = ref('');
const searchInTables = ref(true);
const searchInViews = ref(false);

const tableResults = ref([]);
const viewResults = ref([]);
const hasSearched = ref(false);

const performSearch = () => {
  hasSearched.value = true;

  router.get(
    '/specific-search',
    {
      column: searchQuery.value,
      in_tables: searchInTables.value,
      in_views: searchInViews.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: (page) => {
        tableResults.value = page.props.tableResults || [];
        viewResults.value = page.props.viewResults || [];
      },
    }
  );
};
</script>