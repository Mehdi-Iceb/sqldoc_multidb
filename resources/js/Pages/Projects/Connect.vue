
<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextareaInput from '@/Components/TextareaInput.vue';

const props = defineProps({
    project: Object
});

const page = usePage();
const authMode = ref(props.project.db_type === 'sqlserver' ? 'windows' : 'sql');
const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref('error'); // 'success', 'error', 'warning'

const form = useForm({
    server: '',
    database: '',
    port: props.project.db_type === 'mysql' ? '3306' : props.project.db_type === 'pgsql' ? '5432' : '',
    authMode: authMode.value,
    username: '',
    password: '',
    description: ''
});

// Surveiller les messages flash
watch(() => page.props.flash, (flash) => {
    console.log('Flash message reçu:', flash);
    
    if (flash?.error) {
        showErrorToast(flash.error);
    }
    if (flash?.success) {
        showSuccessToast(flash.success);
    }
    if (flash?.warning) {
        showWarningToast(flash.warning);
    }
    if (flash?.info) {
        showWarningToast(flash.info); // Afficher les infos comme des warnings
    }
}, { immediate: true, deep: true });


const showAuthFields = computed(() => {
    return props.project.db_type !== 'sqlserver' || (props.project.db_type === 'sqlserver' && authMode.value === 'sql');
});

const submit = () => {
    form.post(route('projects.handle-connect', props.project.id), {
        onSuccess: (page) => {
            // Vérifier s'il y a un message de succès
            if (page.props.flash?.success) {
                showSuccessToast(page.props.flash.success);
            } else {
                showSuccessToast('Connection successful!');
            }
        },
        onError: (errors) => {
            console.log('Erreurs reçues:', errors);
            
            // Gérer les erreurs de validation spécifiques
            if (errors.server) {
                showErrorToast(`Server error: ${errors.server}`);
            } else if (errors.database) {
                showErrorToast(`Database error: ${errors.database}`);
            } else if (errors.username) {
                showErrorToast(`Username error: ${errors.username}`);
            } else if (errors.password) {
                showErrorToast(`Password error: ${errors.password}`);
            } else if (errors.port) {
                showErrorToast(`Port error: ${errors.port}`);
            } else {
                // Message d'erreur générique si aucune erreur spécifique
                showErrorToast('Connection failed. Please check your parameters and try again.');
            }
        },
        onFinish: () => {
            // Cette méthode est appelée après onSuccess ou onError
            console.log('Requête terminée');
        }
    });
};

const showErrorToast = (message) => {
    toastMessage.value = message;
    toastType.value = 'error';
    showToast.value = true;
    autoHideToast();
};

const showSuccessToast = (message) => {
    toastMessage.value = message;
    toastType.value = 'success';
    showToast.value = true;
    autoHideToast();
};

const showWarningToast = (message) => {
    toastMessage.value = message;
    toastType.value = 'warning';
    showToast.value = true;
    autoHideToast();
};

const hideToast = () => {
    showToast.value = false;
    setTimeout(() => {
        toastMessage.value = '';
    }, 300);
};

const autoHideToast = () => {
    setTimeout(() => {
        hideToast();
    }, 5000);
};

const getDbTypeName = (type) => {
    const types = {
        'mysql': 'MySQL',
        'sqlserver': 'SQL Server',
        'pgsql': 'PostgreSQL'
    };
    return types[type] || type;
};

const updateAuthMode = (value) => {
    form.authMode = value;
    authMode.value = value;
};

const testConnection = async () => {
    // Validation basique avant le test
    if (!form.server || !form.database) {
        showWarningToast('Please fill in server and database fields before testing.');
        return;
    }
    
    if (showAuthFields.value && (!form.username || !form.password)) {
        showWarningToast('Please fill in username and password before testing.');
        return;
    }
    
    showWarningToast('Testing connection...');
    
    try {
        // Faire un test réel de connexion (vous pouvez créer une route spéciale pour ça)
        const response = await fetch(route('projects.test-connection', props.project.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                server: form.server,
                database: form.database,
                port: form.port,
                authMode: form.authMode,
                username: form.username,
                password: form.password
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccessToast('Connection test successful! You can now connect.');
        } else {
            showErrorToast(result.error || 'Connection test failed.');
        }
    } catch (error) {
        console.error('Test connection error:', error);
        showErrorToast('Unable to test connection. Please try connecting directly.');
    }
};

const getToastClasses = computed(() => {
    const baseClasses = 'fixed top-4 right-4 z-50 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden transform transition-all duration-300 ease-in-out';
    
    if (!showToast.value) {
        return baseClasses + ' translate-x-full opacity-0';
    }
    
    return baseClasses + ' translate-x-0 opacity-100';
});

const getToastIconAndColor = computed(() => {
    switch (toastType.value) {
        case 'success':
            return {
                icon: 'M5 13l4 4L19 7',
                bgColor: 'bg-green-50',
                iconColor: 'text-green-400',
                titleColor: 'text-green-800',
                messageColor: 'text-green-700'
            };
        case 'error':
            return {
                icon: 'M6 18L18 6M6 6l12 12',
                bgColor: 'bg-red-50',
                iconColor: 'text-red-400',
                titleColor: 'text-red-800',
                messageColor: 'text-red-700'
            };
        case 'warning':
            return {
                icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                bgColor: 'bg-yellow-50',
                iconColor: 'text-yellow-400',
                titleColor: 'text-yellow-800',
                messageColor: 'text-yellow-700'
            };
        default:
            return {
                icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                bgColor: 'bg-blue-50',
                iconColor: 'text-blue-400',
                titleColor: 'text-blue-800',
                messageColor: 'text-blue-700'
            };
    }
});
</script>

<template>
    <Head title="Connexion au projet" />
    
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Connexion au projet: {{ project.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="mb-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-gray-900">Informations de connexion</h3>
                                <p class="text-sm text-gray-600">Type de base de données: {{ getDbTypeName(project.db_type) }}</p>
                            </div>
                            <Link
                                :href="route('projects.index')"
                                class="inline-flex items-center px-3 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                :class="{ 'pointer-events-none opacity-50': form.processing }"
                            >
                                Retour aux projets
                            </Link>
                        </div>
                        
                        <!-- Overlay de chargement -->
                        <div v-if="form.processing" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                            <div class="bg-white rounded-lg p-6 flex flex-col items-center shadow-xl">
                                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mb-4"></div>
                                <p class="text-gray-700 font-medium">Testing connection...</p>
                                <p class="text-gray-500 text-sm mt-1">Please wait while we connect to your database</p>
                            </div>
                        </div>
                        
                        <form @submit.prevent="submit" class="max-w-lg" :class="{ 'opacity-50 pointer-events-none': form.processing }">
                            <div class="mb-4">
                                <InputLabel for="server" value="Serveur" />
                                <TextInput
                                    id="server"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.server"
                                    required
                                    placeholder="localhost ou adresse IP"
                                    :disabled="form.processing"
                                />
                                <InputError class="mt-2" :message="form.errors.server" />
                            </div>

                            <div class="mb-4">
                                <InputLabel for="database" value="Base de données" />
                                <TextInput
                                    id="database"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.database"
                                    required
                                    :disabled="form.processing"
                                />
                                <InputError class="mt-2" :message="form.errors.database" />
                            </div>

                            <div v-if="project.db_type !== 'sqlserver'" class="mb-4">
                                <InputLabel for="port" value="Port" />
                                <TextInput
                                    id="port"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.port"
                                    required
                                    :disabled="form.processing"
                                />
                                <InputError class="mt-2" :message="form.errors.port" />
                            </div>

                            <div v-if="project.db_type === 'sqlserver'" class="mb-4">
                                <InputLabel value="Mode d'authentification" />
                                <div class="mt-2 space-y-2">
                                    <div class="flex items-center">
                                        <input
                                            id="windows-auth"
                                            type="radio"
                                            value="windows"
                                            name="authMode"
                                            :checked="authMode === 'windows'"
                                            @change="updateAuthMode('windows')"
                                            :disabled="form.processing"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                        />
                                        <label for="windows-auth" class="ml-2 block text-sm text-gray-900">
                                            Authentification Windows
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input
                                            id="sql-auth"
                                            type="radio"
                                            value="sql"
                                            name="authMode"
                                            :checked="authMode === 'sql'"
                                            @change="updateAuthMode('sql')"
                                            :disabled="form.processing"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                                        />
                                        <label for="sql-auth" class="ml-2 block text-sm text-gray-900">
                                            Authentification SQL Server
                                        </label>
                                    </div>
                                </div>
                                <InputError class="mt-2" :message="form.errors.authMode" />
                            </div>

                            <div v-if="showAuthFields" class="mb-4">
                                <InputLabel for="username" value="Nom d'utilisateur" />
                                <TextInput
                                    id="username"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.username"
                                    required
                                    :disabled="form.processing"
                                />
                                <InputError class="mt-2" :message="form.errors.username" />
                            </div>

                            <div v-if="showAuthFields" class="mb-4">
                                <InputLabel for="password" value="Mot de passe" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    class="mt-1 block w-full"
                                    v-model="form.password"
                                    required
                                    :disabled="form.processing"
                                />
                                <InputError class="mt-2" :message="form.errors.password" />
                            </div>

                            <div class="mb-4">
                                <InputLabel for="description" value="Description (optionnelle)" />
                                <TextareaInput
                                    id="description"
                                    class="mt-1 block w-full"
                                    v-model="form.description"
                                    rows="4"
                                    :disabled="form.processing"
                                />
                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>

                            <div class="flex items-center justify-between mt-6">
                                <button
                                    type="button"
                                    @click="testConnection"
                                    :disabled="form.processing"
                                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150"
                                >
                                    Test Connection
                                </button>
                                
                                <PrimaryButton 
                                    :class="{ 'opacity-25': form.processing }" 
                                    :disabled="form.processing"
                                    class="relative"
                                >
                                    <span v-if="!form.processing">Se connecter</span>
                                    <span v-else class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Connexion...
                                    </span>
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Notification -->
        <div :class="getToastClasses">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div :class="['rounded-full p-2', getToastIconAndColor.bgColor]">
                            <svg class="h-5 w-5" :class="getToastIconAndColor.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getToastIconAndColor.icon"/>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p :class="['text-sm font-medium', getToastIconAndColor.titleColor]">
                            {{ toastType === 'error' ? 'Connection Error' : toastType === 'success' ? 'Success' : 'Information' }}
                        </p>
                        <p :class="['mt-1 text-sm', getToastIconAndColor.messageColor]">
                            {{ toastMessage }}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button @click="hideToast" class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>