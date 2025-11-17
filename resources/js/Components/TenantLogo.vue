<!-- resources/js/Components/TenantLogo.vue -->
<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ApplicationLogo from './ApplicationLogo.vue';

defineProps({
    size: {
        type: String,
        default: 'md' // sm, md, lg, xl
    }
});

const page = usePage();
const tenant = computed(() => page.props.tenant);

const sizeClasses = {
    sm: 'h-8 w-8',
    md: 'h-12 w-12',
    lg: 'h-20 w-20',
    xl: 'h-32 w-32'
};
</script>

<template>
    <div class="flex items-center">
        <img 
            v-if="tenant?.logo" 
            :src="tenant.logo" 
            :alt="`${tenant.name} logo`"
            :class="sizeClasses[size]"
            class="object-contain"
        />
        <ApplicationLogo 
            v-else 
            :class="sizeClasses[size]"
            class="fill-current text-gray-500"
        />
    </div>
</template>