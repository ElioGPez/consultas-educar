import {createWebHistory, createRouter} from "vue-router";

import Home from '../pages/Home.vue';
import About from '../pages/About.vue';
import Register from '../pages/Register.vue';
import Login from '../pages/Login.vue';
import Dashboard from '../pages/Dashboard.vue';
import CargarPreferencias from '../pages/vacantes/CargarPreferencias.vue';


export const routes = [
    {
        name: 'home',
        path: '/',
        component: Home
    },
    {
        name: 'about',
        path: '/about',
        component: About
    },
    {
        name: 'register',
        path: '/register',
        component: Register
    },
    {
        name: 'login',
        path: '/login',
        component: Login
    },
    {
        name: 'dashboard',
        path: '/dashboard',
        component: Dashboard
    },
    {
        name: 'cargar-preferencias',
        path: '/cargar-preferencias',
        component: CargarPreferencias
    },
    

];

const router = createRouter({
    history: createWebHistory(),
    routes: routes,
});

export default router;
