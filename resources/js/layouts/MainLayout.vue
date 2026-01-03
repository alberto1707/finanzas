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
    </v-app-bar>

    <v-main>
      <router-view></router-view>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useDisplay } from 'vuetify';
import { useRouter } from 'vue-router';
import axios from 'axios';

const { mobile } = useDisplay();
const drawer = ref(!mobile.value);
const user = ref(null);
const router = useRouter();

onMounted(() => {
    // Sync drawer with mobile state initially
    drawer.value = !mobile.value;

    const userData = localStorage.getItem('user');
    if (userData) {
        user.value = JSON.parse(userData);
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
</script>
