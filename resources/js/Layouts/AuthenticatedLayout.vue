<template>
  <div>
    <div class="flex h-screen bg-gray-100">
      <!-- SUPPRESSION de la prop databaseStructure -->
      <Navigation />
      <NavigationMobile />

      <div class="flex flex-col flex-1 w-full">
        <TopMenu />

        <main class="h-full overflow-y-auto">
          <div class="container px-6 mx-auto grid">
            <h2 class="my-6 text-2xl font-semibold text-gray-700">
              <slot name="header" />
            </h2>

            <slot />
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import Navigation from './Navigation.vue';
import TopMenu from "./TopMenu.vue";
import NavigationMobile from './NavigationMobile.vue';

// SUPPRESSION COMPLÈTE de la logique de chargement AJAX
// Les données sont maintenant fournies par AppServiceProvider via les props partagées

// Si vous avez besoin de debug, ajoutez ceci temporairement :
// import { usePage } from '@inertiajs/vue3'
// const page = usePage()
// console.log('AuthenticatedLayout - Navigation data available:', !!page.props.navigationData)
</script>
