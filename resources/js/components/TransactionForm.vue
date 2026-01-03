<template>
  <v-dialog v-model="dialog" max-width="500px" :fullscreen="mobile">
    <!-- Activator removed, controlled via expose -->
    <v-card>
      <v-card-title>
        <span class="text-h5">{{ formTitle }}</span>
      </v-card-title>

      <v-card-text>
        <v-container>
          <v-row>
            <v-col cols="12" sm="6" md="4">
              <v-select
                v-model="editedItem.type"
                :items="[{ title: 'Ingreso', value: 'income' }, { title: 'Egreso', value: 'expense' }]"
                item-title="title"
                item-value="value"
                label="Tipo"
              ></v-select>
            </v-col>
            <v-col cols="12" sm="6" md="8">
              <v-text-field
                v-model="editedItem.amount"
                label="Monto"
                type="number"
                prefix="$"
              ></v-text-field>
            </v-col>
            <v-col cols="12">
              <v-text-field
                v-model="editedItem.description"
                label="Descripción"
              ></v-text-field>
            </v-col>
            <v-col cols="12">
              <v-text-field
                v-model="editedItem.date"
                label="Fecha"
                type="date"
              ></v-text-field>
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>

      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn color="blue-darken-1" variant="text" @click="close">
          Cancelar
        </v-btn>
        <v-btn color="blue-darken-1" variant="text" @click="save">
          Guardar
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useDisplay } from 'vuetify';
import axios from 'axios';

const { mobile } = useDisplay();

const props = defineProps({
  modelValue: Boolean, // To control dialog from outside if needed
  item: Object
});

const emit = defineEmits(['saved']);

const dialog = ref(false);
const editedIndex = ref(-1);
const editedItem = ref({
  type: 'expense',
  amount: '',
  description: '',
  date: new Date().toISOString().substr(0, 10),
});
const defaultItem = {
  type: 'expense',
  amount: '',
  description: '',
  date: new Date().toISOString().substr(0, 10),
};

const formTitle = computed(() => {
  return editedIndex.value === -1 ? 'Nuevo Movimiento' : 'Editar Movimiento';
});

// Expose open method for parent
const open = (item = null) => {
    if (item) {
        editedItem.value = { ...item }; // Clone item
        editedIndex.value = item.id; // Use ID to track edit mode
    } else {
        editedItem.value = Object.assign({}, defaultItem);
        editedIndex.value = -1;
    }
    dialog.value = true;
};

watch(dialog, (val) => {
  if (!val) {
    close();
  }
});

const close = () => {
    dialog.value = false;
    setTimeout(() => {
        editedItem.value = Object.assign({}, defaultItem);
        editedIndex.value = -1;
    }, 300); // Delay clear to allow closing animation
};

const save = async () => {
    try {
        const payload = {
            ...editedItem.value,
            amount: parseFloat(editedItem.value.amount)
        };

        if (editedIndex.value > -1) {
             // Edit mode
             await axios.put(`/api/transactions/${editedIndex.value}`, payload);
        } else {
             // Create mode
             await axios.post('/api/transactions', payload);
        }

        emit('saved');
        close();
    } catch (e) {
        console.error(e);
        alert('Error al guardar');
    }
};

defineExpose({ open });
</script>
