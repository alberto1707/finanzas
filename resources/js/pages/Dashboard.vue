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
                label="Año"
                density="compact"
                hide-details
                @update:modelValue="updateFilters"
            ></v-select>
        </v-col>
    </v-row>
    <v-row dense>
      <v-col cols="4" md="4">
        <v-card color="green-lighten-4" height="100%">
          <v-card-title :class="mobile ? 'text-caption px-2' : ''">Ingresos</v-card-title>
          <v-card-text :class="mobile ? 'text-body-2 font-weight-bold px-2' : 'text-h4'" class="text-green-darken-4">
            {{ formatMoney(balanceData.total_income) }}
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="4" md="4">
        <v-card color="red-lighten-4" height="100%">
          <v-card-title :class="mobile ? 'text-caption px-2' : ''">Egresos</v-card-title>
          <v-card-text :class="mobile ? 'text-body-2 font-weight-bold px-2' : 'text-h4'" class="text-red-darken-4">
            {{ formatMoney(balanceData.total_expense) }}
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="4" md="4">
        <v-card color="blue-lighten-4" height="100%">
          <v-card-title :class="mobile ? 'text-caption px-2' : ''">Balance</v-card-title>
          <v-card-text :class="mobile ? 'text-body-2 font-weight-bold px-2' : 'text-h4'" class="text-blue-darken-4">
            {{ formatMoney(balanceData.balance) }}
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row class="mt-4">
      <v-col cols="12">
        <v-card>
          <v-card-title>
            Comportamiento {{ viewMode === 'monthly' ? 'Mensual' : 'Diario' }}
            <v-spacer></v-spacer>
            <v-btn
                v-if="mobile"
                icon
                variant="text"
                density="compact"
                class="mr-2"
                @click="showChart = !showChart"
            >
                <v-icon>{{ showChart ? 'mdi-chevron-up' : 'mdi-chevron-down' }}</v-icon>
            </v-btn>
            <v-btn-toggle v-if="showChart || !mobile" v-model="viewMode" mandatory class="mr-2" density="compact" @update:modelValue="loadBalance">
               <v-btn value="monthly">Mes</v-btn>
               <v-btn value="daily">Día</v-btn>
            </v-btn-toggle>
            <v-btn color="primary" @click="openNewTransaction" density="compact" class="ml-2">
                Nuevo
            </v-btn>
            <TransactionForm ref="formRef" @saved="refreshData" />
          </v-card-title>
          <v-expand-transition>
            <v-card-text v-if="loaded && showChart">
                 <Line :data="chartData" :options="chartOptions" style="height: 300px"/>
            </v-card-text>
          </v-expand-transition>
        </v-card>
      </v-col>
    </v-row>

    <v-row class="mt-4">
      <v-col cols="12">
        <TransactionList ref="listRef" :year="selectedYear" :month="selectedMonth" @edit="openEditTransaction" @deleted="refreshData" />
        <v-btn color="error" class="mt-4" @click="logout" size="small">Cerrar Sesión</v-btn>
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
import { Line } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

const { mobile } = useDisplay();
const router = useRouter();
const balanceData = ref({ total_income: 0, total_expense: 0, balance: 0, chart_data: [] });
const loaded = ref(false);
const listRef = ref(null);
// const formRef defined below near usage, but let's consolidate if cleaner,
// actually I added it in the previous block. Wait, I added it inside the function block?
// No, I added 'const formRef = ref(null);' in the previous block I wrote.
// But to be safe, let's make sure it's valid scope.
// Ah, previous block replaced 'toggleViewMode'.
// It is better to have refs at top. I will remove it from there and put it here.
const formRef = ref(null);
const viewMode = ref('monthly'); // 'monthly' or 'daily'
const showChart = ref(!mobile.value);

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

watch(mobile, (val) => {
    showChart.value = !val;
});

const chartData = computed(() => {
    // chart_data contains 'label', 'income', 'expense'
    const labels = balanceData.value.chart_data.map(m => m.label);
    const income = balanceData.value.chart_data.map(m => m.income);
    const expense = balanceData.value.chart_data.map(m => m.expense);

    return {
        labels,
        datasets: [
            {
                label: 'Ingresos',
                backgroundColor: '#4CAF50',
                borderColor: '#4CAF50',
                data: income,
                fill: false
            },
            {
                label: 'Egresos',
                backgroundColor: '#F44336',
                borderColor: '#F44336',
                data: expense,
                fill: false
            }
        ]
    };
});

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: {
            ticks: {
                font: {
                    size: mobile.value ? 10 : 12
                }
            }
        },
        y: {
            ticks: {
                font: {
                    size: mobile.value ? 10 : 12
                }
            }
        }
    },
    plugins: {
        legend: {
            labels: {
                font: {
                    size: mobile.value ? 11 : 12
                }
            }
        }
    }
}));

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

const toggleViewMode = () => {
    viewMode.value = viewMode.value === 'monthly' ? 'daily' : 'monthly';
    loadBalance();
};

onMounted(() => {
    const token = localStorage.getItem('token');
    if (token) {
        loadBalance();
    } else {
        router.push('/login');
    }
});

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
