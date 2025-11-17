import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import { routes  } from 'vue-router/auto-routes'
import App from './App.vue'
import '../css/app.css'
import './lib/echo'


const router = createRouter({
  history: createWebHistory(),
  routes
})
const app = createApp(App);

app.use(router);


app.mount('#app')
