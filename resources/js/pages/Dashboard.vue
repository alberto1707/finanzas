<template>
  <v-container>
    <v-row dense class="mb-2">
        <v-col cols="6" md="2">
            <v-select
                v-model="selectedMonth"
                :items="months"
                item-title="title"
                item-value="value"
                label="Mes"
                density="compact"
                hide-details
                @update:modelValue="updateFilters"
            ></v-select>
        </v-col>
        <v-col cols="6" md="2">
            <v-select
                v-model="selectedYear"
                :items="years"
                item-title="title"
                item-value="value"
                label="Año"
                density="compact"
                hide-details
                @update:modelValue="updateFilters"
            ></v-select>
        </v-col>
    </v-row>
    <v-row dense>
      <v-col cols="4" md="4">
        <v-card color="green-lighten-4" height="100%" elevation="2">
          <v-card-text :class="mobile ? 'pa-2 d-flex flex-column align-center text-center' : 'd-flex align-center'">
            <v-avatar color="green-darken-1" :size="mobile ? 32 : 48" :class="mobile ? 'mb-1' : 'mr-3'">
              <v-icon color="white" :size="mobile ? 'small' : 'default'">mdi-trending-up</v-icon>
            </v-avatar>
            <div>
              <div class="text-caption text-green-darken-4" :style="mobile ? 'font-size: 0.7rem !important' : ''">Ingresos</div>
              <div :class="mobile ? 'text-caption font-weight-bold' : 'text-h5 font-weight-bold'" class="text-green-darken-4">
                {{ formatMoney(balanceData.total_income) }}
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="4" md="4">
        <v-card color="red-lighten-4" height="100%" elevation="2">
          <v-card-text :class="mobile ? 'pa-2 d-flex flex-column align-center text-center' : 'd-flex align-center'">
            <v-avatar color="red-darken-1" :size="mobile ? 32 : 48" :class="mobile ? 'mb-1' : 'mr-3'">
              <v-icon color="white" :size="mobile ? 'small' : 'default'">mdi-trending-down</v-icon>
            </v-avatar>
            <div>
              <div class="text-caption text-red-darken-4" :style="mobile ? 'font-size: 0.7rem !important' : ''">Egresos</div>
              <div :class="mobile ? 'text-caption font-weight-bold' : 'text-h5 font-weight-bold'" class="text-red-darken-4">
                {{ formatMoney(balanceData.total_expense) }}
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="4" md="4">
        <v-card color="blue-lighten-4" height="100%" elevation="2">
          <v-card-text :class="mobile ? 'pa-2 d-flex flex-column align-center text-center' : 'd-flex align-center'">
            <v-avatar color="blue-darken-1" :size="mobile ? 32 : 48" :class="mobile ? 'mb-1' : 'mr-3'">
              <v-icon color="white" :size="mobile ? 'small' : 'default'">mdi-wallet</v-icon>
            </v-avatar>
            <div>
              <div class="text-caption text-blue-darken-4" :style="mobile ? 'font-size: 0.7rem !important' : ''">Balance</div>
              <div :class="mobile ? 'text-caption font-weight-bold' : 'text-h5 font-weight-bold'" class="text-blue-darken-4">
                {{ formatMoney(balanceData.balance) }}
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Chart section removed -->

    <v-row class="mt-4">
      <v-col cols="12">
         <div class="d-flex align-center mb-2">
            <v-btn color="primary" @click="openNewTransaction" class="mr-2" elevation="2">
                <v-icon start>mdi-plus</v-icon>
                Nuevo
            </v-btn>
            <v-spacer></v-spacer>
        </div>
        <TransactionForm ref="formRef" @saved="refreshData" />
      </v-col>
    </v-row>

    <v-row class="mt-4">
      <v-col cols="12">
        <TransactionList ref="listRef" :year="selectedYear" :month="selectedMonth" @edit="openEditTransaction" @deleted="refreshData" />
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useDisplay } from 'vuetify';
import axios from 'axios';
import TransactionForm from '../components/TransactionForm.vue';
import TransactionList from '../components/TransactionList.vue';

// Chart moved to Statistics.vue
// import { Line } from 'vue-chartjs';
// import { Chart as ChartJS... }

const { mobile } = useDisplay();
const router = useRouter();
const balanceData = ref({ total_income: 0, total_expense: 0, balance: 0, chart_data: [] });
const loaded = ref(false);
const user = ref(null);

onMounted(() => {
    const token = localStorage.getItem('token');
    const userData = localStorage.getItem('user');
    if (token) {
        if(userData) {
            user.value = JSON.parse(userData);
        }
        loadBalance();
    } else {
        router.push('/login');
    }
});

const userInitials = computed(() => {
    if (!user.value || !user.value.name) return 'U';
    const names = user.value.name.split(' ');
    if (names.length >= 2) {
        return (names[0][0] + names[1][0]).toUpperCase();
    }
    return user.value.name.substring(0, 2).toUpperCase();
});
const listRef = ref(null);
// const formRef defined below near usage, but let's consolidate if cleaner,
// actually I added it in the previous block. Wait, I added it inside the function block?
// No, I added 'const formRef = ref(null);' in the previous block I wrote.
// But to be safe, let's make sure it's valid scope.
// Ah, previous block replaced 'toggleViewMode'.
// It is better to have refs at top. I will remove it from there and put it here.
const formRef = ref(null);

const viewMode = ref('monthly'); // 'monthly' or 'daily'

const currentYear = new Date().getFullYear();
const currentMonth = new Date().getMonth() + 1;

const selectedYear = ref(currentYear);
const selectedMonth = ref(currentMonth);

const years = Array.from({ length: 5 }, (_, i) => currentYear - i);
const months = [
    { title: 'Enero', value: 1 },
    { title: 'Febrero', value: 2 },
    { title: 'Marzo', value: 3 },
    { title: 'Abril', value: 4 },
    { title: 'Mayo', value: 5 },
    { title: 'Junio', value: 6 },
    { title: 'Julio', value: 7 },
    { title: 'Agosto', value: 8 },
    { title: 'Septiembre', value: 9 },
    { title: 'Octubre', value: 10 },
    { title: 'Noviembre', value: 11 },
    { title: 'Diciembre', value: 12 },
];



/* Chart logic moved to Statistics.vue */

const loadBalance = async () => {
    try {
        // Only send month/year if we are NOT in 'chart' mode?
        // Wait, the user wants the CHART to just show the selected month if we are in daily mode?
        // Or should the filters apply to the chart too?
        // "puedes poner un filtro que me muestre solo del mes que se elija? igual la tabla"
        // Implicitly, this applies to everything visible.
        // However, the current balance endpoint returns 6-month history for 'monthly' mode.
        // If we filter by month, showing 6-month history doesn't make sense?
        // Maybe if specific month selected, chart should force to 'daily' view for that month?
        // For simplicity, let's keep chart logic separte or loosely coupled?
        // Actually, let's pass params to everything.

        const params = {
            mode: viewMode.value,
            year: selectedYear.value,
            month: selectedMonth.value
        };

        const response = await axios.get('/api/balance', { params });
        balanceData.value = response.data;
        loaded.value = true;
    } catch (e) {
        console.error(e);
    }
};

const refreshData = () => {
    loadBalance();
    if(listRef.value) {
        // List will auto-reload via props watch if filters changed,
        // or we call it manually for new items.
        // For new items (saved from form), filters obviously didn't change, so we trigger reload.
        listRef.value.loadItems({ page: 1 });
    }
};

const updateFilters = () => {
    refreshData();
};

const openNewTransaction = () => {
    formRef.value.open();
};

const openEditTransaction = (item) => {
    formRef.value.open(item);
};

// toggleViewMode moved to logic inside Statistics.vue if needed,
// or Dashboard keeps it if using viewMode for other things?
// Dashboard still uses viewMode string in template title? Yes: "Comportamiento {{ viewMode ... }}"
// Wait, I removed the Chart card title in template, but I might have kept the title "Comportamiento...".
// Let's check imports. I removed chart section.
// Ah, I removed the whole card containing the chart.
// So viewMode is likely not used anymore in Dashboard unless it affects the balance calculation?
// loadBalance uses viewMode.value.
// Balance API breakdown logic relies on it.
// But the Dashboard cards (Income/Expense/Balance) are Totals, which are usually not affected by "Daily/Monthly" breakdown mode in the backend
// (Totals are controlled by date filters Year/Month).
// The 'mode' param in backend only affects 'chart_data'.
// So if Dashboard doesn't show chart, it doesn't need viewMode.
// I should clean up viewMode too.




const logout = async () => {
    try {
        await axios.post('/api/logout');
    } catch (e) {
        console.error(e);
    } finally {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        router.push('/login');
    }
};

const formatMoney = (value) => {
    return '$ ' + parseFloat(value || 0).toFixed(2);
};
</script>
