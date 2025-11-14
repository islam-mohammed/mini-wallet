import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router/auto'
import App from './App.vue'
import '../css/app.css'

const router = createRouter({
  history: createWebHistory(),
})

createApp(App).use(router).mount('#app')
