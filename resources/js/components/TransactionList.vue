<template>
  <v-card>
    <v-card-text>
        <v-row dense class="mb-2">
            <v-col cols="12" md="8">
                <v-text-field
                    v-model="search"
                    prepend-inner-icon="mdi-magnify"
                    label="Buscar descripción..."
                    variant="outlined"
                    density="compact"
                    hide-details
                    clearable
                    @click:clear="loadItems({ page: 1 })"
                    @keyup.enter="loadItems({ page: 1 })"
                ></v-text-field>
            </v-col>
            <v-col cols="12" md="4">
                <v-select
                    v-model="typeFilter"
                    :items="typeOptions"
                    item-title="title"
                    item-value="value"
                    label="Tipo"
                    variant="outlined"
                    density="compact"
                    hide-details
                    @update:modelValue="loadItems({ page: 1 })"
                ></v-select>
            </v-col>
        </v-row>

        <v-data-table-server
            v-model:items-per-page="itemsPerPage"
            :headers="headers"
            :items="serverItems"
            :items-length="totalItems"
            :loading="loading"
            class="elevation-0"
            :density="mobile ? 'compact' : 'default'"
            :items-per-page-options="[5, 10, 25]"
            @update:options="loadItems"
        >
            <!-- Group Header Style (Manual grouping visual) -->
            <template v-slot:item.date="{ item, index }">
                <span :class="isNewDay(item, index) ? 'font-weight-bold text-primary' : 'text-grey-lighten-1'">
                    {{ item.formatted_date }}
                </span>
            </template>

            <template v-slot:item.type="{ item }">
                <v-icon :color="getColor(item.type)" size="small" class="mr-1">
                    {{ item.type === 'income' ? 'mdi-arrow-up-circle' : 'mdi-arrow-down-circle' }}
                </v-icon>
                <span :class="`text-${getColor(item.type)}`" class="text-caption font-weight-bold">
                    {{ item.type === 'income' ? 'Ingreso' : 'Egreso' }}
                </span>
            </template>

            <template v-slot:item.amount="{ item }">
                <span :class="item.type === 'income' ? 'text-green-darken-2' : 'text-red-darken-2'" class="font-weight-bold">
                    {{ item.type === 'income' ? '+' : '-' }} {{ formatMoney(item.amount) }}
                </span>
            </template>

            <template v-slot:item.actions="{ item }">
                <v-btn icon="mdi-pencil" variant="text" size="small" color="blue" @click="editItem(item)"></v-btn>
                <v-btn icon="mdi-delete" variant="text" size="small" color="red" @click="deleteItem(item)"></v-btn>
            </template>
        </v-data-table-server>
    </v-card-text>
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
const typeFilter = ref('');
const typeOptions = [
    { title: 'Todos', value: '' },
    { title: 'Ingresos', value: 'income' },
    { title: 'Egresos', value: 'expense' },
];

const props = defineProps({
  year: Number,
  month: Number
});

const emit = defineEmits(['edit', 'deleted']);

const loadItems = async (options = {}) => {
  const { page, itemsPerPage, sortBy } = options;

  const params = {
      page: page || 1,
      search: search.value,
      type: typeFilter.value,
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

const isNewDay = (item, index) => {
    if (index === 0) return true;
    return serverItems.value[index - 1].date !== item.date;
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
