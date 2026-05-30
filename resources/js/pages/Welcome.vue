<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    appName: string;
}>();
</script>

<template>
    <Head title="Início" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-zinc-50 px-6 text-zinc-900">
        <main class="w-full max-w-xl rounded-2xl border border-zinc-200 bg-white p-10 shadow-sm">
            <p class="text-sm font-medium uppercase tracking-wide text-zinc-500">
                CiViTas
            </p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight">
                Bem-vindo
            </h1>
            <p class="mt-4 text-base leading-7 text-zinc-600">
                Aplicação Laravel + Vue + Inertia pronta para evoluir.
            </p>
            <p class="mt-6 text-sm text-zinc-500">
                Ambiente: {{ appName }}
            </p>
        </main>
    </div>
</template>
