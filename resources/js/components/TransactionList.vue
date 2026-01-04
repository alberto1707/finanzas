<template>
  <v-card>
    <v-card-title :class="mobile ? 'text-subtitle-1' : ''">
      Historial
      <v-spacer></v-spacer>
      <!-- We can insert Form component here or just a button that opens it -->
    </v-card-title>
    <v-data-table-server
      v-model:items-per-page="itemsPerPage"
      :headers="headers"
      :items="serverItems"
      :items-length="totalItems"
      :loading="loading"
      :search="search"
      class="elevation-1"
      :density="mobile ? 'compact' : 'default'"
      @update:options="loadItems"
    >
      <template v-slot:item.date="{ item }">
        {{ item.formatted_date }}
      </template>
      <template v-slot:item.type="{ item }">
        <v-chip :color="getColor(item.type)">
          {{ item.type === 'income' ? 'Ingreso' : 'Egreso' }}
        </v-chip>
      </template>
      <template v-slot:item.amount="{ item }">
         {{ formatMoney(item.amount) }}
      </template>
      <template v-slot:item.actions="{ item }">
        <v-icon size="small" class="me-2" @click="editItem(item)">
          mdi-pencil
        </v-icon>
        <v-icon size="small" @click="deleteItem(item)">
          mdi-delete
        </v-icon>
      </template>
    </v-data-table-server>
  </v-card>
</template>

<script setup>
import { ref } from 'vue';
import { useDisplay } from 'vuetify';
import axios from 'axios';

const { mobile } = useDisplay();

const itemsPerPage = ref(10);
const headers = [
  { title: 'Fecha', key: 'date', align: 'start', width: '100px', cellProps: { class: 'text-no-wrap' } },
  { title: 'Descripción', key: 'description', align: 'start' },
  { title: 'Tipo', key: 'type', align: 'start' },
  { title: 'Monto', key: 'amount', align: 'end', cellProps: { class: 'text-no-wrap font-weight-bold' } },
  { title: 'Acciones', key: 'actions', sortable: false, align: 'end' },
];
const serverItems = ref([]);
const loading = ref(true);
const totalItems = ref(0);
const search = ref('');

const props = defineProps({
  year: Number,
  month: Number
});

const emit = defineEmits(['edit', 'deleted']);

const loadItems = async (options = {}) => {
  const { page, itemsPerPage, sortBy } = options;

  // Use props directly
  const params = {
      page: page || 1,
      type: search.value,
      year: props.year,
      month: props.month
  };

  loading.value = true;
  try {
    const response = await axios.get('/api/transactions', { params });
    serverItems.value = response.data.data;
    totalItems.value = response.data.total;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

// ... watch ...
import { watch } from 'vue';
watch(() => [props.year, props.month], () => {
    loadItems({ page: 1 });
});

const getColor = (type) => {
  return type === 'income' ? 'green' : 'red';
};

const formatMoney = (value) => {
    return '$ ' + parseFloat(value).toFixed(2);
};



const editItem = (item) => {
    emit('edit', item);
};

const deleteItem = async (item) => {
    if (!confirm('¿Estás seguro de eliminar este movimiento?')) return;

    try {
        await axios.delete(`/api/transactions/${item.id}`);
        emit('deleted');
        loadItems({ page: 1 }); // Refresh list
    } catch (e) {
        console.error(e);
        alert('Error al eliminar');
    }
};

// Expose reload function
defineExpose({ loadItems });
</script>
