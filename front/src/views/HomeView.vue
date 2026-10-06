<script>
import { ElMessage, ElMessageBox } from 'element-plus'
import api from '@/services/api'
import ProductForm from '@/components/ProductForm.vue'

export default {
  components: {
    ProductForm,
  },

  data() {
    return {
      products: [],
      search: '',
      appliedSearch: '',
      currentPage: 1,
      pageSize: 10,
      total: 0,
      loading: false,
      deletingId: null,
      error: '',
      showCreateDialog: false,
      showEditDialog: false,
      editingProduct: null,
    }
  },

  computed: {
    busy() {
      return this.loading || this.deletingId !== null
    },
  },

  mounted() {
    this.loadProducts()
  },

  methods: {
    async loadProducts() {
      this.loading = true
      this.error = ''

      try {
        const { data } = await api.get('/products', {
          params: {
            search: this.appliedSearch,
            page: this.currentPage,
          },
        })

        this.products = data.data
        this.total = data.total
        this.pageSize = data.per_page
        this.currentPage = data.current_page
      } catch {
        this.error = 'Impossible de charger les produits. Veuillez réessayer.'
      } finally {
        this.loading = false
      }
    },

    applySearch() {
      this.appliedSearch = this.search.trim()
      this.currentPage = 1
      this.loadProducts()
    },

    changePage(page) {
      this.currentPage = page
      this.loadProducts()
    },

    productCreated() {
      this.showCreateDialog = false
      this.search = ''
      this.appliedSearch = ''
      this.currentPage = 1
      this.loadProducts()
    },

    editProduct(product) {
      if (this.busy) return

      this.editingProduct = { ...product }
      this.showEditDialog = true
    },

    async productUpdated() {
      this.showEditDialog = false
      this.editingProduct = null
      await this.loadProducts()

      if (!this.error && this.products.length === 0 && this.currentPage > 1) {
        this.currentPage = Math.max(1, Math.ceil(this.total / this.pageSize))
        await this.loadProducts()
      }
    },

    closeEditDialog() {
      this.showEditDialog = false
      this.editingProduct = null
    },

    async deleteProduct(product) {
      if (this.busy) return

      this.deletingId = product.id

      try {
        await ElMessageBox.confirm(
          `Supprimer le produit « ${product.name} » ? Cette action est définitive.`,
          'Confirmer la suppression',
          {
            confirmButtonText: 'Supprimer',
            cancelButtonText: 'Annuler',
            type: 'warning',
          },
        )
      } catch {
        this.deletingId = null
        return
      }

      try {
        await api.delete(`/products/${product.id}`)
        ElMessage.success('Produit supprimé.')

        if (this.products.length === 1 && this.currentPage > 1) {
          this.currentPage -= 1
        }

        await this.loadProducts()
      } catch {
        ElMessage.error('Impossible de supprimer le produit. Veuillez réessayer.')
      } finally {
        this.deletingId = null
      }
    },

    formatPrice(price) {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
      }).format(Number(price))
    },
  },
}
</script>

<template>
  <main class="home">
    <div class="page-header">
      <h1>Catalogue de produits</h1>

      <el-button
        type="primary"
        :disabled="busy"
        @click="showCreateDialog = true"
      >
        Ajouter un produit
      </el-button>
    </div>

    <form class="search-form" @submit.prevent="applySearch">
      <el-input
        v-model="search"
        placeholder="Rechercher par nom"
        aria-label="Rechercher un produit par nom"
        maxlength="255"
        clearable
        :disabled="busy"
        @clear="applySearch"
      />

      <el-button native-type="submit" type="primary" :disabled="busy">
        Rechercher
      </el-button>
    </form>

    <el-alert
      v-if="error"
      :title="error"
      type="error"
      show-icon
      :closable="false"
    />

    <el-button
      v-if="error"
      class="retry-button"
      :disabled="busy"
      @click="loadProducts"
    >
      Réessayer
    </el-button>

    <el-table
      v-loading="loading"
      :data="products"
      row-key="id"
      empty-text="Aucun produit trouvé"
      class="products-table"
    >
      <el-table-column prop="name" label="Nom" min-width="180" />
      <el-table-column prop="sku" label="Référence / SKU" min-width="150" />
      <el-table-column prop="category.name" label="Catégorie" min-width="140" />

      <el-table-column label="Prix" min-width="120" align="right">
        <template #default="{ row }">
          {{ formatPrice(row.price) }}
        </template>
      </el-table-column>

      <el-table-column prop="stock" label="Stock" min-width="100" align="right" />

      <el-table-column label="Actions" min-width="220" align="center">
        <template #default="{ row }">
          <el-button
            size="small"
            :disabled="busy"
            @click="editProduct(row)"
          >
            Modifier
          </el-button>

          <el-button
            type="danger"
            size="small"
            :disabled="busy"
            @click="deleteProduct(row)"
          >
            Supprimer
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-pagination
      v-if="total > 0"
      class="pagination"
      :current-page="currentPage"
      :page-size="pageSize"
      :total="total"
      :disabled="busy"
      layout="total, prev, pager, next"
      @current-change="changePage"
    />

    <el-dialog
      v-model="showCreateDialog"
      title="Ajouter un produit"
      width="min(560px, 92vw)"
      :show-close="false"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      destroy-on-close
    >
      <ProductForm
        v-if="showCreateDialog"
        @created="productCreated"
        @cancel="showCreateDialog = false"
      />
    </el-dialog>

    <el-dialog
      v-model="showEditDialog"
      title="Modifier un produit"
      width="min(560px, 92vw)"
      :show-close="false"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      destroy-on-close
    >
      <ProductForm
        v-if="showEditDialog && editingProduct"
        :key="editingProduct.id"
        :product="editingProduct"
        @updated="productUpdated"
        @cancel="closeEditDialog"
      />
    </el-dialog>
  </main>
</template>

<style scoped>
.home {
  max-width: 1180px;
  margin: 3rem auto;
  padding: 2.5rem;
  color: #343232;
  background: #fff;
  border: 1px solid #e4d8ce;
  border-radius: 18px;
  box-shadow: 0 8px 28px rgba(180, 151, 134, 0.12);
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  padding-bottom: 1.75rem;
  margin-bottom: 1.75rem;
  border-bottom: 1px solid #e7ddd5;
}

h1 {
  position: relative;
  width: fit-content;
  margin: 0;
  padding-bottom: 0.85rem;
  color: #343232;
  font-size: clamp(1.8rem, 4vw, 2.6rem);
  font-family: 'Catchy Mager', Georgia, 'Times New Roman', serif;
  font-weight: 400;
  line-height: 1.15;
  letter-spacing: -1px;
}

h1::after {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 90px;
  height: 5px;
  border-radius: 3px;
  background: #ff914d;
  content: '';
}

.page-header > .el-button {
  min-height: 46px;
  padding: 0 20px;
  color: #fff;
  background: #343232;
  border-color: #343232;
  border-radius: 8px;
}

.page-header > .el-button:hover:not(:disabled) {
  background: #514b47;
  border-color: #514b47;
}

.search-form {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.search-form .el-input {
  max-width: 420px;
}

.search-form :deep(.el-input__wrapper) {
  min-height: 44px;
  border-radius: 8px;
  box-shadow: 0 0 0 1px #d8c6b8 inset;
}

.search-form :deep(.el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 2px #ff914d inset;
}

.search-form > .el-button {
  min-height: 44px;
  padding: 0 20px;
  color: #343232;
  background: #ff914d;
  border: 0;
  border-radius: 8px;
}

.search-form > .el-button:hover:not(:disabled) {
  background: #ed803d;
}

.page-header > .el-button:disabled,
.search-form > .el-button:disabled {
  opacity: 0.5;
}

.products-table {
  margin-top: 1rem;
  overflow: hidden;
  border: 1px solid #e4d8ce;
  border-radius: 10px;

  --el-table-header-bg-color: #f1e9e2;
  --el-table-header-text-color: #343232;
  --el-table-text-color: #343232;
  --el-table-row-hover-bg-color: #fbf5ef;
  --el-table-border-color: #eee5dd;
}

.products-table :deep(th.el-table__cell) {
  height: 52px;
  font-weight: 700;
}

.products-table :deep(td.el-table__cell) {
  height: 58px;
}

.products-table :deep(.el-button:not(.el-button--danger)) {
  color: #343232;
  background: #f6ede6;
  border-color: #d8c6b8;
}

.products-table :deep(.el-button:not(.el-button--danger):hover:not(:disabled)) {
  background: #f1e9e2;
  border-color: #ff914d;
}

.products-table :deep(.el-button--danger) {
  color: #763737;
  background: #fbefef;
  border-color: #dd8b8b;
}

.products-table :deep(.el-button--danger:hover:not(:disabled)) {
  background: #f4dcdc;
  border-color: #c77575;
}

.products-table :deep(.el-button:disabled) {
  opacity: 0.5;
}

.retry-button {
  margin-top: 1rem;
}

.pagination {
  display: flex;
  justify-content: flex-end;
  margin-top: 1.5rem;
  overflow-x: auto;
}

.pagination :deep(.el-pager li.is-active) {
  color: #fff;
  background: #343232;
  border-radius: 6px;
}

@media (max-width: 700px) {
  .home {
    margin: 1rem;
    padding: 1.25rem;
    border-radius: 12px;
  }

  .page-header,
  .search-form {
    flex-direction: column;
    align-items: stretch;
  }

  .search-form .el-input {
    max-width: none;
  }

  .pagination {
    justify-content: flex-start;
  }
}
</style>