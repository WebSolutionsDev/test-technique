<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const apiStatus = ref('checking...')

onMounted(async () => {
  try {
    await api.get('/ping')
    apiStatus.value = 'API OK'
  } catch {
    apiStatus.value = "API injoignable (avez-vous lancé l'API et vérifié VITE_API_URL ?)"
  }
})
</script>

<template>
  <main class="home">
    <h1>Gestion de catalogue produit</h1>
    <p>Front Vue 3 + Element Plus prêt. Statut API : {{ apiStatus }}</p>
    <el-button type="primary">Element Plus fonctionne</el-button>
  </main>
</template>

<style scoped>
.home {
  padding: 2rem;
}
</style>
