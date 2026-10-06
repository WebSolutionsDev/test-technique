<script>
import { ElMessage } from 'element-plus'
import api from '@/services/api'

export default {
  props: {
    product: {
      type: Object,
      default: null,
    },
  },

  emits: ['created', 'updated', 'cancel'],

  data() {
    return {
      form: {
        name: this.product?.name ?? '',
        sku: this.product?.sku ?? '',
        price: this.product?.price ?? '',
        stock: this.product?.stock ?? 0,
        category_id: this.product?.category_id ?? null,
      },
      categories: [],
      loadingCategories: false,
      saving: false,
      categoryError: '',
      submitError: '',
      errors: {},
    }
  },

  computed: {
    editing() {
      return this.product !== null
    },
  },

  mounted() {
    this.loadCategories()
  },

  methods: {
    async loadCategories() {
      this.loadingCategories = true
      this.categoryError = ''

      try {
        const { data } = await api.get('/categories')
        this.categories = data

        if (data.length === 0) {
          this.categoryError = 'Aucune catégorie disponible.'
        }
      } catch {
        this.categoryError = 'Impossible de charger les catégories.'
      } finally {
        this.loadingCategories = false
      }
    },

    validateForm() {
      const errors = {}
      const price = String(this.form.price).trim()
      const stock = this.form.stock

      if (!this.form.name.trim()) {
        errors.name = ['Le nom est obligatoire.']
      }

      if (!this.form.sku.trim()) {
        errors.sku = ['La référence est obligatoire.']
      }

      if (!/^\d+([.,]\d{1,2})?$/.test(price)) {
        errors.price = ['Saisissez un prix avec deux décimales maximum.']
      } else {
        const amount = Number(price.replace(',', '.'))

        if (amount < 0.01 || amount > 99999999.99) {
          errors.price = ['Le prix doit être compris entre 0,01 et 99 999 999,99 €.']
        }
      }

      if (!Number.isInteger(stock) || stock < 0 || stock > 4294967295) {
        errors.stock = ['Le stock doit être un entier entre 0 et 4 294 967 295.']
      }

      if (!this.form.category_id) {
        errors.category_id = ['Choisissez une catégorie.']
      }

      this.errors = errors
      return Object.keys(errors).length === 0
    },

    async submit() {
      if (this.saving) return

      this.submitError = ''

      if (!this.validateForm()) return

      this.saving = true

      try {
        const payload = {
          ...this.form,
          name: this.form.name.trim(),
          sku: this.form.sku.trim(),
          price: String(this.form.price).trim().replace(',', '.'),
        }

        const { data } = this.editing
          ? await api.patch(`/products/${this.product.id}`, payload)
          : await api.post('/products', payload)

        ElMessage.success(this.editing ? 'Produit modifié.' : 'Produit créé.')
        this.$emit(this.editing ? 'updated' : 'created', data)
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors ?? {}
          this.submitError = 'Veuillez corriger les champs indiqués.'
        } else {
          this.submitError = this.editing
            ? 'Impossible de modifier le produit. Veuillez réessayer.'
            : 'Impossible de créer le produit. Veuillez réessayer.'
        }
      } finally {
        this.saving = false
      }
    },
  },
}
</script>

<template>
  <el-form label-position="top" @submit.prevent="submit">
    <el-alert
      v-if="categoryError"
      :title="categoryError"
      type="error"
      :closable="false"
      show-icon
      class="form-alert"
    />

    <el-button
      v-if="categoryError"
      :loading="loadingCategories"
      class="retry-button"
      @click="loadCategories"
    >
      Recharger les catégories
    </el-button>

    <el-alert
      v-if="submitError"
      :title="submitError"
      type="error"
      :closable="false"
      show-icon
      class="form-alert"
    />

    <el-form-item label="Nom" :error="errors.name?.[0]" required>
      <el-input v-model="form.name" maxlength="255" :disabled="saving" />
    </el-form-item>

    <el-form-item label="Référence / SKU" :error="errors.sku?.[0]" required>
      <el-input v-model="form.sku" maxlength="255" :disabled="saving" />
    </el-form-item>

    <el-form-item label="Prix (€)" :error="errors.price?.[0]" required>
      <el-input
        v-model="form.price"
        placeholder="Exemple : 29,90"
        inputmode="decimal"
        :disabled="saving"
      />
    </el-form-item>

    <el-form-item label="Stock" :error="errors.stock?.[0]" required>
      <el-input-number
        v-model="form.stock"
        :min="0"
        :max="4294967295"
        :step="1"
        :disabled="saving"
      />
    </el-form-item>

    <el-form-item label="Catégorie" :error="errors.category_id?.[0]" required>
      <el-select
        v-model="form.category_id"
        placeholder="Choisir une catégorie"
        :loading="loadingCategories"
        :disabled="saving || loadingCategories || categories.length === 0"
        class="category-select"
      >
        <el-option
          v-for="category in categories"
          :key="category.id"
          :label="category.name"
          :value="category.id"
        />
      </el-select>
    </el-form-item>

    <div class="form-actions">
      <el-button :disabled="saving" @click="$emit('cancel')">
        Annuler
      </el-button>

      <el-button
        type="primary"
        native-type="submit"
        :loading="saving"
        :disabled="loadingCategories || categories.length === 0"
      >
        {{ editing ? 'Enregistrer les modifications' : 'Créer le produit' }}
      </el-button>
    </div>
  </el-form>
</template>

<style scoped>
.form-alert,
.retry-button {
  margin-bottom: 1rem;
}

.category-select {
  width: 100%;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
}
</style>