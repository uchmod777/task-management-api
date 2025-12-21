import { defineStore } from 'pinia'
import api from '../services/api'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('token') || null
    }),

    getters: {
        isAuthenticated: (state) => !!state.token
    },

    actions: {
        async login(email, password) {
            const response = await api.post('/login', { email, password })
            this.token = response.data.token
            this.user = response.data.user
            localStorage.setItem('token', this.token)
        },

        async register(name, email, password) {
            const response = await api.post('/register', { name, email, password })
            this.token = response.data.token
            this.user = response.data.user
            localStorage.setItem('token', this.token)
        },

        async logout() {
            await api.post('/logout')
            this.token = null
            this.user = null
            localStorage.removeItem('token')
        }
    }
})