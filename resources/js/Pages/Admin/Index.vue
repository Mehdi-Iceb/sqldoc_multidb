<template>
    <AuthenticatedLayout>
      <template #header>
        <h2 class="text-xl font-semibold text-gray-800">
          Administration
        </h2>
      </template>
  
      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
          <!-- Gestion des rôles et permissions -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-6">Roles and permissions management</h3>
            <div class="space-y-6">
                <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permissions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="role in roles" :key="role.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ role.name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <div class="flex gap-2 flex-wrap">
                        <div v-for="permission in permissions" :key="permission.id" 
                            class="inline-flex items-center">
                            <label class="inline-flex items-center space-x-2">
                            <input 
                                type="checkbox"
                                :checked="hasPermission(role, permission)"
                                @change="togglePermission(role, permission)"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >
                            <span class="text-sm text-gray-700">{{ permission.name }}</span>
                            </label>
                        </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button 
                        @click="saveRolePermissions(role)"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        >
                        Save
                        </button>
                    </td>
                    </tr>
                </tbody>
                </table>
            </div>
          </div>

          <!-- Formulaire de création d'utilisateur -->
          <div class="mb-8 p-6 bg-white rounded-lg shadow">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Create a user</h4>
            <form @submit.prevent="createUser" class="space-y-4">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input 
                    v-model="newUser.name"
                    type="text" 
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input 
                    v-model="newUser.email"
                    type="email" 
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input 
                    v-model="newUser.password"
                    type="password" 
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Role</label>
                    <select 
                    v-model="newUser.role_id"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                    <option value="">Select role</option>
                    <option v-for="role in roles" :key="role.id" :value="role.id">
                        {{ role.name }}
                    </option>
                    </select>
                </div>
                </div>
                <div class="flex justify-end">
                <button 
                    type="submit"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition"
                >
                    Create user
                </button>
                </div>
            </form>
          </div>
        
          <!-- Gestion des utilisateurs et accès aux projets -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-6">User management and project access</h3>
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Name
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Email
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Role
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Project access
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="user in users" :key="user.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ user.name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ user.email }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <select 
                      v-model="user.role_id"
                      @change="updateUserRole(user)"
                      class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                      <option v-for="role in roles" :key="role.id" :value="role.id">
                        {{ role.name }}
                      </option>
                    </select>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-500">
                    <div class="space-y-1">
                      <div v-if="user.project_accesses && user.project_accesses.length > 0" 
                           v-for="access in user.project_accesses" 
                           :key="access.id"
                           class="inline-flex items-center bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mr-1 mb-1">
                        {{ access.project.name }} 
                        <span class="ml-1 font-semibold">({{ access.access_level }})</span>
                        <button @click="revokeProjectAccess(user.id, access.project_id)" 
                                class="ml-1 text-red-600 hover:text-red-800">
                          <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                          </svg>
                        </button>
                      </div>
                      <div v-else class="text-gray-400 text-xs">
                        No project access
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 space-x-2">
                    <button
                      @click="updateUserRole(user)"
                      class="inline-flex items-center px-3 py-1 border border-transparent rounded-md text-xs text-white bg-green-600 hover:bg-green-700"
                    >
                      Save Role
                    </button>
                    <button
                      @click="openProjectAccessModal(user)"
                      class="inline-flex items-center px-3 py-1 border border-transparent rounded-md text-xs text-white bg-blue-600 hover:bg-blue-700"
                    >
                      Manage Access
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Modal pour gérer l'accès aux projets -->
      <div v-if="showProjectAccessModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">
              Manage project access for {{ selectedUser?.name }}
            </h3>
            <button @click="closeProjectAccessModal" class="text-gray-400 hover:text-gray-500">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <!-- Debug: Afficher le nombre de projets disponibles -->
          <div class="mb-2 text-sm text-gray-600">
            Projects available: {{ availableProjects.length }}
            <span v-if="loadingProjects" class="text-blue-600">(Loading...)</span>
          </div>
          
          <!-- Formulaire pour accorder un nouvel accès -->
          <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h4 class="text-md font-medium text-gray-900 mb-3">Grant new project access</h4>
            <form @submit.prevent="grantProjectAccess" class="space-y-3">
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- Sélection du projet -->
                <div>
                  <label class="block text-sm font-medium text-gray-700">Project</label>
                  <select 
                    v-model="newProjectAccess.project_id"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option value="">Select a project</option>
                    <option v-for="project in availableProjects" :key="project.id" :value="project.id">
                      {{ project.display_name || project.name }}
                    </option>
                  </select>
                  <!-- Debug: Afficher les projets disponibles -->
                  <div v-if="availableProjects.length === 0" class="mt-1 text-sm text-red-600">
                    No projects available. Check console for errors.
                  </div>
                </div>

                <!-- Niveau d'accès -->
                <div>
                  <label class="block text-sm font-medium text-gray-700">Access level</label>
                  <select 
                    v-model="newProjectAccess.access_level"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option value="read">Read only</option>
                    <option value="write">Read/Write</option>
                    <option value="admin">Full admin</option>
                  </select>
                </div>
              </div>

              <div class="flex justify-end">
                <button 
                  type="submit"
                  :disabled="!newProjectAccess.project_id || loadingProjects"
                  class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 disabled:opacity-50"
                >
                  Grant access
                </button>
              </div>
            </form>
          </div>

          <!-- Liste des accès actuels -->
          <div>
            <h4 class="text-md font-medium text-gray-900 mb-3">Current project accesses</h4>
            <div v-if="currentUserAccesses.length === 0" class="text-gray-500 text-sm">
              No project access granted yet.
            </div>
            <div v-else class="space-y-2">
              <div v-for="access in currentUserAccesses" 
                   :key="access.id"
                   class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div>
                  <div class="font-medium text-gray-900">{{ access.project_name }}</div>
                  <div class="text-sm text-gray-500">
                    Owner: {{ access.project_owner }} | 
                    Access: <span class="font-medium">{{ access.access_level }}</span> | 
                    Granted: {{ access.granted_at }}
                  </div>
                </div>
                <button 
                  @click="revokeProjectAccess(selectedUser.id, access.project_id)"
                  class="text-red-600 hover:text-red-800"
                  title="Revoke access"
                >
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
</template>
  
<script setup>
import { ref, computed, onMounted } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

// Props existants
const props = defineProps({
  users: Array,
  roles: Array,
  permissions: Array,
  projects: Array // Ajouté pour recevoir les projets depuis le controller
})

// États existants
const newUser = ref({
  name: '',
  email: '',
  password: '',
  role_id: ''
})

// Nouveaux états pour la gestion des accès aux projets
const showProjectAccessModal = ref(false)
const selectedUser = ref(null)
const currentUserAccesses = ref([])
const availableProjects = ref([])
const loadingProjects = ref(false)
const newProjectAccess = ref({
  project_id: '',
  access_level: 'read'
})

// Charger les projets disponibles au montage
onMounted(async () => {
  console.log('Component mounted')
  console.log('Props projects:', props.projects)
  
  // Utiliser les projets des props d'abord si disponibles
  if (props.projects && props.projects.length > 0) {
    availableProjects.value = props.projects.map(project => ({
      id: project.id,
      name: project.name,
      display_name: `${project.name} (${project.user?.name || 'Unknown owner'})`
    }))
    console.log('Projects loaded from props:', availableProjects.value)
  }
  
  // Charger également via API pour avoir la liste complète
  await loadAvailableProjects()
})

// Fonctions existantes
const createUser = async () => {
  try {
    const response = await axios.post('/admin/users', newUser.value)
    
    if (response.data.success) {
      newUser.value = {
        name: '',
        email: '',
        password: '',
        role_id: ''
      }
      window.location.reload()
      alert('User created successfully!')
    }
  } catch (error) {
    console.error('Error while creating user:', error)
    alert('Error while creating user: ' + (error.response?.data?.error || error.message))
  }
}

const hasPermission = (role, permission) => {
  return role.permissions.some(p => p.id === permission.id)
}

const togglePermission = (role, permission) => {
  const permissions = role.permissions || []
  const index = permissions.findIndex(p => p.id === permission.id)
  
  if (index === -1) {
    permissions.push(permission)
  } else {
    permissions.splice(index, 1)
  }
  
  role.permissions = permissions
}

const saveRolePermissions = async (role) => {
  try {
    await axios.put(`/admin/roles/${role.id}/permissions`, {
      permissions: role.permissions.map(p => p.id)
    })
    alert('Permissions updated successfully!')
  } catch (error) {
    console.error('Error while updating permissions:', error)
    alert('Error while updating permissions!')
  }
}

const updateUserRole = async (user) => {
  try {
    await axios.post(`/admin/users/${user.id}/role`, {
      role_id: user.role_id
    })
    alert('Role updated successfully!')
  } catch (error) {
    console.error('Error while updating role:', error)
    alert('Error while updating role!')
  }
}

// Nouvelles fonctions pour la gestion des accès aux projets
const loadAvailableProjects = async () => {
  try {
    loadingProjects.value = true
    console.log('Loading projects from API...')
    
    const response = await axios.get('/admin/projects/available')
    console.log('API Response:', response.data)
    
    if (response.data.success) {
      availableProjects.value = response.data.projects || []
      console.log('Projects loaded successfully:', availableProjects.value)
    } else {
      console.error('API returned success=false:', response.data)
    }
  } catch (error) {
    console.error('Error loading projects:', error)
    console.error('Error details:', {
      status: error.response?.status,
      statusText: error.response?.statusText,
      data: error.response?.data,
      url: error.config?.url
    })
    
    // Fallback: utiliser les projets des props
    if (props.projects && props.projects.length > 0) {
      availableProjects.value = props.projects.map(project => ({
        id: project.id,
        name: project.name,
        display_name: `${project.name} (${project.user?.name || 'Unknown owner'})`
      }))
      console.log('Using fallback projects from props:', availableProjects.value)
    }
  } finally {
    loadingProjects.value = false
  }
}

const openProjectAccessModal = async (user) => {
  console.log('Opening modal for user:', user)
  selectedUser.value = user
  showProjectAccessModal.value = true
  
  // Recharger les projets disponibles
  if (availableProjects.value.length === 0) {
    await loadAvailableProjects()
  }
  
  await loadUserProjectAccesses(user.id)
}

const closeProjectAccessModal = () => {
  showProjectAccessModal.value = false
  selectedUser.value = null
  currentUserAccesses.value = []
  newProjectAccess.value = {
    project_id: '',
    access_level: 'read'
  }
}

const loadUserProjectAccesses = async (userId) => {
  try {
    console.log('Loading project accesses for user:', userId)
    const response = await axios.get(`/admin/users/${userId}/project-accesses`)
    
    if (response.data.success) {
      currentUserAccesses.value = response.data.accesses
      console.log('User accesses loaded:', currentUserAccesses.value)
    }
  } catch (error) {
    console.error('Error loading user project accesses:', error)
  }
}

const grantProjectAccess = async () => {
  try {
    console.log('Granting access:', {
      user_id: selectedUser.value.id,
      project_id: newProjectAccess.value.project_id,
      access_level: newProjectAccess.value.access_level
    })
    
    const response = await axios.post('/admin/project-access/grant', {
      user_id: selectedUser.value.id,
      project_id: newProjectAccess.value.project_id,
      access_level: newProjectAccess.value.access_level
    })
    
    if (response.data.success) {
      alert('Project access granted successfully!')
      await loadUserProjectAccesses(selectedUser.value.id)
      newProjectAccess.value = {
        project_id: '',
        access_level: 'read'
      }
      window.location.reload()
    }
  } catch (error) {
    console.error('Error granting project access:', error)
    alert('Error granting project access: ' + (error.response?.data?.error || error.message))
  }
}

const revokeProjectAccess = async (userId, projectId) => {
  if (!confirm('Are you sure you want to revoke this project access?')) {
    return
  }
  
  try {
    const response = await axios.post('/admin/project-access/revoke', {
      user_id: userId,
      project_id: projectId
    })
    
    if (response.data.success) {
      alert('Project access revoked successfully!')
      
      if (showProjectAccessModal.value && selectedUser.value?.id === userId) {
        await loadUserProjectAccesses(userId)
      }
      
      window.location.reload()
    }
  } catch (error) {
    console.error('Error revoking project access:', error)
    alert('Error revoking project access: ' + (error.response?.data?.error || error.message))
  }
}
</script>