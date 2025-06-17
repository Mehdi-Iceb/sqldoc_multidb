<template>
    <Head title="Projects" />
    
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Projects
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Messages Flash -->
                <div v-if="flashMessage" class="mb-6">
                    <!-- Message de succès -->
                    <div v-if="flashMessage.type === 'success'" class="bg-green-50 border-l-4 border-green-400 p-4 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">{{ flashMessage.message }}</p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button @click="hideFlashMessage" class="text-green-400 hover:text-green-600">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Message d'avertissement -->
                    <div v-else-if="flashMessage.type === 'warning'" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">{{ flashMessage.message }}</p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button @click="hideFlashMessage" class="text-yellow-400 hover:text-yellow-600">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Message d'information -->
                    <div v-else-if="flashMessage.type === 'info'" class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">{{ flashMessage.message }}</p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button @click="hideFlashMessage" class="text-blue-400 hover:text-blue-600">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Message d'erreur -->
                    <div v-else-if="flashMessage.type === 'error'" class="bg-red-50 border-l-4 border-red-400 p-4 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700">{{ flashMessage.message }}</p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button @click="hideFlashMessage" class="text-red-400 hover:text-red-600">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center mb-6">
                    <!-- afficher les projets supprimés -->
                    <div class="flex items-center space-x-4">
                        <button
                            @click="showDeleted = false"
                            :class="[
                                'px-4 py-2 rounded-md text-sm font-medium',
                                !showDeleted 
                                    ? 'bg-indigo-100 text-indigo-700' 
                                    : 'text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            Active Projects ({{ activeProjects.length }})
                        </button>
                        <button
                            @click="loadDeletedProjects"
                            :class="[
                                'px-4 py-2 rounded-md text-sm font-medium',
                                showDeleted 
                                    ? 'bg-red-100 text-red-700' 
                                    : 'text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            Deleted Projects ({{ deletedProjects.length }})
                        </button>
                    </div>

                    <Link
                        :href="route('projects.create')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Create a new project
                    </Link>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <!-- Projets actifs -->
                    <div v-if="!showDeleted">
                        <div v-if="activeProjects.length === 0" class="text-center py-8">
                            <h3 class="text-lg font-medium text-gray-900">Vous n'avez pas encore de projets</h3>
                            <p class="mt-2 text-gray-600">Commencez par créer un nouveau projet en cliquant sur le bouton ci-dessus.</p>
                        </div>
                        
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div 
                                v-for="project in activeProjects" 
                                :key="project.id"
                                class="border rounded-lg overflow-hidden hover:shadow-md transition-shadow"
                            >
                                <div class="p-4 border-b bg-gray-50">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-800">{{ project.name }}</h3>
                                            <p class="text-sm text-gray-500 mt-1">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ getBdTypeName(project.db_type) }}
                                                </span>
                                            </p>
                                        </div>
                                        <!-- Bouton delete -->
                                        <button
                                            @click="confirmDelete(project)"
                                            class="p-1 text-gray-400 hover:text-red-600 transition-colors"
                                            title="Supprimer le projet"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="red">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="flex justify-end space-x-2">
                                        <button
                                            @click="openProject(project)"
                                            :disabled="openingProject === project.id"
                                            class="inline-flex items-center px-3 py-2 bg-indigo-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                        >
                                            <span v-if="openingProject === project.id" class="flex items-center">
                                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Opening...
                                            </span>
                                            <span v-else>Open</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Projets supprimés -->
                    <div v-else>
                        <div v-if="deletedProjects.length === 0" class="text-center py-8">
                            <h3 class="text-lg font-medium text-gray-900">No deleted project</h3>
                            <p class="mt-2 text-gray-600">Deleted project will appear here.</p>
                        </div>
                        
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div 
                                v-for="project in deletedProjects" 
                                :key="project.id"
                                class="border border-red-200 rounded-lg overflow-hidden bg-red-50"
                            >
                                <div class="p-4 border-b bg-red-100">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-800">{{ project.name }}</h3>
                                            <p class="text-sm text-red-600 mt-1">
                                                Deleted on {{ formatDate(project.deleted_at) }}
                                            </p>
                                        </div>
                                        <!-- Bouton restore -->
                                        <button
                                            @click="restoreProject(project)"
                                            class="p-1 text-gray-400 hover:text-green-600 transition-colors"
                                            title="Restore the project"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="flex justify-end space-x-2">
                                        <button
                                            @click="restoreProject(project)"
                                            class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        >
                                            Restore
                                        </button>
                                        <button
                                            @click="confirmForceDelete(project)"
                                            class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        >
                                            Delete Forever
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal confirmation suppression -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/3 shadow-lg rounded-md bg-white">
                <div class="flex flex-col items-center">
                    <svg class="h-16 w-16 text-red-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2 text-center">
                        Delete the project
                    </h3>
                    <p class="text-sm text-gray-500 text-center mb-6">
                        Do you really want to delete the project <span class="font-semibold">{{ selectedProject?.name }}</span> ?<br>
                        The project won't be available anymore.
                    </p>
                    
                    <div class="flex justify-center space-x-4 w-full">
                        <button 
                            @click="showDeleteModal = false"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="deleteProject"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                            :disabled="deleting"
                        >
                            {{ deleting ? 'Deletion...' : 'Delete' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal confirmation suppression définitive -->
        <div v-if="showForceDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/3 shadow-lg rounded-md bg-white">
                <div class="flex flex-col items-center">
                    <svg class="h-16 w-16 text-red-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2 text-center">
                        Definitive deletion
                    </h3>
                    <p class="text-sm text-gray-500 text-center mb-6">
                        Delete definitively the project <span class="font-semibold">{{ selectedProject?.name }}</span> ?<br>
                        <span class="text-red-600 font-medium">This action is irreversible !</span>
                    </p>
                    
                    <div class="flex justify-center space-x-4 w-full">
                        <button 
                            @click="showForceDeleteModal = false"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="forceDeleteProject"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                            :disabled="deleting"
                        >
                            {{ deleting ? 'Deletion...' : 'Permanently delete' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';

const props = defineProps({
    projects: Array
});

const page = usePage();

// États
const showDeleted = ref(false);
const deletedProjects = ref([]);
const showDeleteModal = ref(false);
const showForceDeleteModal = ref(false);
const selectedProject = ref(null);
const deleting = ref(false);
const openingProject = ref(null);
const flashMessage = ref(null);

// Projets actifs (non supprimés)
const activeProjects = computed(() => {
    return props.projects || [];
});

// Surveiller les messages flash
watch(() => page.props.flash, (flash) => {
    if (flash) {
        if (flash.success) {
            flashMessage.value = { type: 'success', message: flash.success };
        } else if (flash.error) {
            flashMessage.value = { type: 'error', message: flash.error };
        } else if (flash.warning) {
            flashMessage.value = { type: 'warning', message: flash.warning };
        } else if (flash.info) {
            flashMessage.value = { type: 'info', message: flash.info };
        }
        
        // Auto-hide après 8 secondes sauf pour les erreurs
        if (flashMessage.value && flashMessage.value.type !== 'error') {
            setTimeout(() => {
                hideFlashMessage();
            }, 8000);
        }
    }
}, { immediate: true, deep: true });

// Fonction pour cacher le message flash
const hideFlashMessage = () => {
    flashMessage.value = null;
};

// Fonction pour obtenir le nom formaté du type de base de données
const getBdTypeName = (type) => {
    const types = {
        'mysql': 'MySQL',
        'sqlserver': 'SQL Server',
        'pgsql': 'PostgreSQL'
    };
    return types[type] || type;
};

// Fonction pour formater les dates
const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Fonction pour ouvrir un projet avec indicateur de chargement
const openProject = async (project) => {
    openingProject.value = project.id;
    
    try {
        // Utiliser Inertia pour naviguer vers la route d'ouverture
        window.location.href = route('projects.open', project.id);
    } catch (error) {
        console.error('Error opening project:', error);
        openingProject.value = null;
        flashMessage.value = { 
            type: 'error', 
            message: 'Error opening project: ' + error.message 
        };
    }
};

// Charger les projets supprimés
const loadDeletedProjects = async () => {
    showDeleted.value = true;
    
    try {
        const response = await axios.get('/projects/deleted', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        if (response.data.success) {
            deletedProjects.value = response.data.projects;
        } else {
            throw new Error(response.data.error || 'Error loading');
        }
    } catch (error) {
        console.error('Error loading deleted projects:', error);
        
        if (error.response?.status === 403) {
            flashMessage.value = { 
                type: 'error', 
                message: 'Unauthorized access. Administrator privileges required.' 
            };
            showDeleted.value = false;
        } else if (error.response?.status === 404) {
            flashMessage.value = { 
                type: 'error', 
                message: 'Route not found. Make sure you are an administrator.' 
            };
        } else {
            flashMessage.value = { 
                type: 'error', 
                message: 'Error loading deleted projects' 
            };
        }
    }
};

// Confirmer la suppression
const confirmDelete = (project) => {
    selectedProject.value = project;
    showDeleteModal.value = true;
};

// Confirmer la suppression définitive
const confirmForceDelete = (project) => {
    selectedProject.value = project;
    showForceDeleteModal.value = true;
};

// Supprimer un projet (soft delete)
const deleteProject = async () => {
    deleting.value = true;
    
    try {
        const response = await axios.delete(`/projects/${selectedProject.value.id}/soft`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        if (response.data.success) {
            showDeleteModal.value = false;
            selectedProject.value = null;
            flashMessage.value = { 
                type: 'success', 
                message: 'Project successfully deleted' 
            };
            window.location.reload();
        } else {
            throw new Error(response.data.error || 'Error while deleting');
        }
    } catch (error) {
        console.error('Error while deleting:', error);
        flashMessage.value = { 
            type: 'error', 
            message: 'Error: ' + (error.response?.data?.error || error.message) 
        };
    } finally {
        deleting.value = false;
    }
};

// Supprimer définitivement un projet
const forceDeleteProject = async () => {
    deleting.value = true;
    
    try {
        const response = await axios.delete(`/projects/${selectedProject.value.id}/force`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        if (response.data.success) {
            showForceDeleteModal.value = false;
            selectedProject.value = null;
            await loadDeletedProjects();
            flashMessage.value = { 
                type: 'success', 
                message: 'Project definitively deleted' 
            };
        } else {
            throw new Error(response.data.error || 'Error while definitive deletion');
        }
    } catch (error) {
        console.error('Error while definitive deletion:', error);
        flashMessage.value = { 
            type: 'error', 
            message: 'Error: ' + (error.response?.data?.error || error.message) 
        };
    } finally {
        deleting.value = false;
    }
};

// Restaurer un projet
const restoreProject = async (project) => {
    try {
        const response = await axios.post(`/projects/${project.id}/restore`, {}, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        if (response.data.success) {
            await loadDeletedProjects();
            flashMessage.value = { 
                type: 'success', 
                message: 'Project successfully restored' 
            };
        } else {
            throw new Error(response.data.error || 'Error while restoring');
        }
    } catch (error) {
        console.error('Error while restoring:', error);
        flashMessage.value = { 
            type: 'error', 
            message: 'Error: ' + (error.response?.data?.error || error.message) 
        };
    }
};

// Computed pour vérifier si l'utilisateur est admin
const isAdmin = computed(() => {
    return window.Laravel?.user?.role === 'admin' || 
           page.props.auth?.user?.role === 'admin';
});

// Montage du composant
onMounted(() => {
    // Initialisation si nécessaire
    console.log('Projects component mounted');
    console.log('Active projects:', activeProjects.value.length);
});
</script>