<template>
  <v-dialog v-model="dialog" max-width="500px" :fullscreen="mobile">
    <template v-slot:activator="{ props }">
      <v-btn color="primary" dark v-bind="props" class="mb-2">
        Nuevo Movimiento
      </v-btn>
    </template>
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

watch(dialog, (val) => {
  if (!val) {
    close();
  }
});

const close = () => {
    dialog.value = false;
    editedItem.value = Object.assign({}, defaultItem);
    editedIndex.value = -1;
};

const save = async () => {
    try {
        const payload = {
            ...editedItem.value,
            amount: parseFloat(editedItem.value.amount)
        };

        if (editedIndex.value > -1) {
            // Update logic if we pass ID
             // Not implemented fully in this snippet, assumes create for now or handled by parent logic?
             // Actually, letting this component handle API call:
        } else {
             await axios.post('/api/transactions', payload);
        }

        emit('saved');
        close();
    } catch (e) {
        console.error(e);
        alert('Error al guardar');
    }
};
</script>
