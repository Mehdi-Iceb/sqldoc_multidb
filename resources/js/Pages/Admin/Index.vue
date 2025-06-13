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
                <!-- Table des rôles et permissions -->
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
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
            <!-- Nom -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input 
                v-model="newUser.name"
                type="text" 
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input 
                v-model="newUser.email"
                type="email" 
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
            </div>

            <!-- Mot de passe -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Password</label>
                <input 
                v-model="newUser.password"
                type="password" 
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
            </div>

            <!-- Rôle -->
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

            <!-- Bouton de soumission -->
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
        
          <!-- Gestion des utilisateurs -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-6">User management</h3>
  
            <!-- Liste des utilisateurs avec leur rôle -->
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    NAme
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Email
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Role
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
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <button
                      @click="updateUserRole(user)"
                      class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest bg-green-600 hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150"
                    >
                      Save
                    </button>
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
  import { ref, computed } from 'vue'
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
  
  const newUser = ref({
  name: '',
  email: '',
  password: '',
  role_id: ''
})

const createUser = async () => {
  try {
    const response = await axios.post('/admin/users', newUser.value)
    
    // Réinitialiser le formulaire
    newUser.value = {
      name: '',
      email: '',
      password: '',
      role_id: ''
    }

    // Rafraîchir la page pour voir le nouvel utilisateur
    window.location.reload()
    
    alert('User created with success !')
  } catch (error) {
    console.error('Error while creating user:', error)
    alert('Error while creating user !')
  }
}

  const props = defineProps({
    users: Array,
    roles: Array,
    permissions: Array
  })
  
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
      
      // Notification de succès
      alert('Permissions updating with success !')
    } catch (error) {
      console.error('Error while updating permissions:', error)
      alert('Error while updating permissions !')
    }
  }
  
  const updateUserRole = async (user) => {
    try {
      await axios.post(`/admin/users/${user.id}/role`, {
        role_id: user.role_id
      })
      
      // Notification de succès
      alert('Role updating with success !')
    } catch (error) {
      console.error('Error while updating role:', error)
      alert('Error while updating role !')
    }
    
  }

  
  </script>