<template>
  <v-container>
    <v-row dense class="mb-4">
        <v-col cols="12">
            <h2 class="text-h5">Estadísticas</h2>
        </v-col>
    </v-row>

    <v-row dense class="mb-4">
        <v-col cols="6" md="2">
            <v-select
                v-model="selectedMonth"
                :items="months"
                item-title="title"
                item-value="value"
                label="Mes"
                density="compact"
                hide-details
                @update:modelValue="loadData"
            ></v-select>
        </v-col>
        <v-col cols="6" md="2">
            <v-select
                v-model="selectedYear"
                :items="years"
                label="Año"
                density="compact"
                hide-details
                @update:modelValue="loadData"
            ></v-select>
        </v-col>
         <v-col cols="12" md="4" class="d-flex align-center">
             <v-btn-toggle v-model="viewMode" mandatory density="compact" @update:modelValue="loadData">
               <v-btn value="monthly">Mensual</v-btn>
               <v-btn value="daily">Diario</v-btn>
            </v-btn-toggle>
         </v-col>
    </v-row>

    <!-- Top Stats Cards -->
    <v-row dense class="mb-4">
        <v-col cols="12" md="6">
            <v-card color="orange-lighten-4">
                <v-card-title class="text-subtitle-1">Mayor Gasto</v-card-title>
                <v-card-text>
                    <div v-if="stats.highest_expense">
                        <div class="text-h6 text-orange-darken-4 font-weight-bold">
                            {{ formatMoney(stats.highest_expense.amount) }}
                        </div>
                        <div class="text-caption">{{ stats.highest_expense.description }}</div>
                        <div class="text-caption">{{ formatDate(stats.highest_expense.date) }}</div>
                    </div>
                    <div v-else class="text-caption text-grey">Sin datos</div>
                </v-card-text>
            </v-card>
        </v-col>
        <v-col cols="12" md="6">
            <v-card color="teal-lighten-4">
                <v-card-title class="text-subtitle-1">Promedio Diario</v-card-title>
                 <v-card-text>
                    <div class="text-h6 text-teal-darken-4 font-weight-bold">
                        {{ formatMoney(stats.average_daily_expense) }}
                    </div>
                    <div class="text-caption">Gasto promedio por día</div>
                </v-card-text>
            </v-card>
        </v-col>
    </v-row>

    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-text>
             <Line v-if="loaded" :data="chartData" :options="chartOptions" style="height: 400px"/>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useDisplay } from 'vuetify';
import axios from 'axios';
import { Line } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

const { mobile } = useDisplay();
const loaded = ref(false);
const viewMode = ref('monthly');
const stats = ref({ highest_expense: null, average_daily_expense: 0, chart_data: [] });

const currentYear = new Date().getFullYear();
const currentMonth = new Date().getMonth() + 1;
const selectedYear = ref(currentYear);
const selectedMonth = ref(currentMonth);

const years = Array.from({ length: 5 }, (_, i) => currentYear - i);
const months = [
    { title: 'Enero', value: 1 }, { title: 'Febrero', value: 2 }, { title: 'Marzo', value: 3 },
    { title: 'Abril', value: 4 }, { title: 'Mayo', value: 5 }, { title: 'Junio', value: 6 },
    { title: 'Julio', value: 7 }, { title: 'Agosto', value: 8 }, { title: 'Septiembre', value: 9 },
    { title: 'Octubre', value: 10 }, { title: 'Noviembre', value: 11 }, { title: 'Diciembre', value: 12 },
];

const loadData = async () => {
    try {
        const params = {
            mode: viewMode.value,
            year: selectedYear.value,
            month: selectedMonth.value,
            stats: true // Flag to ask for extra stats
        };
        const response = await axios.get('/api/balance', { params });
        stats.value = response.data;
        loaded.value = true;
    } catch (e) {
        console.error(e);
    }
};

onMounted(() => {
    loadData();
});

const chartData = computed(() => {
    if (!stats.value.chart_data) return { labels: [], datasets: [] };

    const labels = stats.value.chart_data.map(m => m.label);
    const income = stats.value.chart_data.map(m => m.income);
    const expense = stats.value.chart_data.map(m => m.expense);

    return {
        labels,
        datasets: [
            {
                label: 'Ingresos',
                backgroundColor: '#4CAF50',
                borderColor: '#4CAF50',
                data: income,
                fill: false,
                tension: 0.1
            },
            {
                label: 'Egresos',
                backgroundColor: '#F44336',
                borderColor: '#F44336',
                data: expense,
                fill: false,
                 tension: 0.1
            }
        ]
    };
});

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
     scales: {
        x: { ticks: { font: { size: mobile.value ? 10 : 12 } } },
        y: { ticks: { font: { size: mobile.value ? 10 : 12 } } }
    },
    plugins: {
        legend: { labels: { font: { size: mobile.value ? 11 : 12 } } }
    }
}));

const formatMoney = (value) => {
    return '$ ' + parseFloat(value || 0).toFixed(2);
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
};
</script>
