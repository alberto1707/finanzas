<template>
  <v-app>
    <v-navigation-drawer v-model="drawer" :permanent="!mobile">
      <v-list>
        <v-list-item
          prepend-avatar="https://randomuser.me/api/portraits/lego/1.jpg"
          :title="user?.name || 'Usuario'"
          :subtitle="user?.email || 'email@example.com'"
        ></v-list-item>
      </v-list>

      <v-divider></v-divider>

      <v-list density="compact" nav>
        <v-list-item prepend-icon="mdi-home" title="Inicio" value="home" to="/"></v-list-item>
        <v-list-item prepend-icon="mdi-chart-bar" title="Estadísticas" value="stats" to="/stats"></v-list-item>
      </v-list>

      <template v-slot:append>
        <div class="pa-2">
          <v-btn block color="error" variant="text" @click="logout">
            <v-icon start>mdi-logout</v-icon>
            Cerrar Sesión
          </v-btn>
        </div>
      </template>
    </v-navigation-drawer>

    <v-app-bar color="primary" density="compact">
      <v-app-bar-nav-icon variant="text" @click.stop="drawer = !drawer" v-if="mobile"></v-app-bar-nav-icon>
      <v-app-bar-title>Finanzas Personales</v-app-bar-title>
      <v-spacer></v-spacer>

      <!-- Dark Mode Toggle in Header -->
      <v-btn icon @click="isDark = !isDark; toggleTheme()">
        <v-icon>{{ isDark ? 'mdi-weather-sunny' : 'mdi-weather-night' }}</v-icon>
      </v-btn>

      <!-- User Menu in Header -->
      <v-menu min-width="200px" rounded v-if="user">
        <template v-slot:activator="{ props }">
          <v-btn icon v-bind="props" class="ml-2">
            <v-avatar color="white" size="small">
              <span class="text-primary font-weight-bold">{{ userInitials }}</span>
            </v-avatar>
          </v-btn>
        </template>
        <v-card>
          <v-card-text>
            <div class="mx-auto text-center">
              <h3 class="mb-1">{{ user.name }}</h3>
              <p class="text-caption text-grey">{{ user.email }}</p>
              <v-divider class="my-3"></v-divider>
              <v-list density="compact" nav>
                <v-list-item prepend-icon="mdi-account" title="Mi Perfil" to="/profile"></v-list-item>
                <v-list-item prepend-icon="mdi-logout" title="Cerrar Sesión" @click="logout" color="error"></v-list-item>
              </v-list>
            </div>
          </v-card-text>
        </v-card>
      </v-menu>
    </v-app-bar>

    <v-main>
      <router-view></router-view>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useDisplay, useTheme } from 'vuetify';
import { useRouter } from 'vue-router';
import axios from 'axios';

const { mobile } = useDisplay();
const theme = useTheme();
const drawer = ref(!mobile.value);
const user = ref(null);
const router = useRouter();
const isDark = ref(false);

const userInitials = computed(() => {
    if (!user.value || !user.value.name) return 'U';
    const names = user.value.name.split(' ');
    if (names.length >= 2) {
        return (names[0][0] + names[1][0]).toUpperCase();
    }
    return user.value.name.substring(0, 2).toUpperCase();
});

onMounted(() => {
    // Sync drawer with mobile state initially
    drawer.value = !mobile.value;

    const userData = localStorage.getItem('user');
    if (userData) {
        user.value = JSON.parse(userData);
    }

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        theme.global.name.value = savedTheme;
        isDark.value = savedTheme === 'dark';
    }
});

const toggleTheme = () => {
    theme.global.name.value = isDark.value ? 'dark' : 'light';
    localStorage.setItem('theme', theme.global.name.value);
};

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
</script>
