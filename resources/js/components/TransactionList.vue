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
        {{ formatDate(item.date) }}
      </template>
      <template v-slot:item.type="{ item }">
        <v-chip :color="getColor(item.type)">
          {{ item.type === 'income' ? 'Ingreso' : 'Egreso' }}
        </v-chip>
      </template>
      <template v-slot:item.amount="{ item }">
         {{ formatMoney(item.amount) }}
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
  { title: 'Fecha', key: 'date', align: 'start' },
  { title: 'Descripción', key: 'description', align: 'start' },
  { title: 'Tipo', key: 'type', align: 'start' },
  { title: 'Monto', key: 'amount', align: 'end' },
];
const serverItems = ref([]);
const loading = ref(true);
const totalItems = ref(0);
const search = ref('');

const props = defineProps({
  year: Number,
  month: Number
});

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

// Watch props to reload
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

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    // Format: d-MMM-Y (e.g., 02-Ene-2026)
    // We can use Intl.DateTimeFormat
    const day = date.getDate().toString().padStart(2, '0');
    const month = date.toLocaleDateString('es-ES', { month: 'short' });
    const year = date.getFullYear();
    // Capitalize first letter of month
    const formattedMonth = month.charAt(0).toUpperCase() + month.slice(1);

    return `${day}-${formattedMonth}-${year}`;
};

// Expose reload function
defineExpose({ loadItems });
</script>
