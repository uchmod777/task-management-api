import { defineStore } from 'pinia'
import api from '../services/api'

export const useTaskStore = defineStore('tasks', {
    state: () => ({
        tasks: [],
        users: [],
        loading: false,
        error: null,
        pagination: {
            current_page: 1,
            last_page: 1,
            total: 0
        },
        filters: {
            status: '',
            search: '',
            due_after: '',
            due_before: '',
            sort: 'created_at',
            order: 'desc'
        }
    }),

    actions: {
        async fetchTasks() {
            this.loading = true
            this.error = null

            try {
                const params = new URLSearchParams()

                if (this.filters.status) params.append('status', this.filters.status)
                if (this.filters.search) params.append('search', this.filters.search)
                if (this.filters.due_after) params.append('due_after', this.filters.due_after)
                if (this.filters.due_before) params.append('due_before', this.filters.due_before)
                if (this.filters.sort) params.append('sort', this.filters.sort)
                if (this.filters.order) params.append('order', this.filters.order)

                const response = await api.get(`/tasks?${params}`)
                this.tasks = response.data.data
                this.pagination = {
                    current_page: response.data.current_page,
                    last_page: response.data.last_page,
                    total: response.data.total
                }
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to fetch tasks'
            } finally {
                this.loading = false
            }
        },

        async createTask(taskData) {
            const response = await api.post('/tasks', taskData)
            await this.fetchTasks()
            return response.data
        },

        async updateTask(id, taskData) {
            const response = await api.put(`/tasks/${id}`, taskData)
            await this.fetchTasks()
            return response.data
        },

        async deleteTask(id) {
            await api.delete(`/tasks/${id}`)
            await this.fetchTasks()
        },

        async fetchUsers() {
            const response = await api.get('/users')
            this.users = response.data
        },

        setFilter(key, value) {
            this.filters[key] = value
            this.fetchTasks()
        },

        clearFilters() {
            this.filters = {
                status: '',
                search: '',
                due_after: '',
                due_before: '',
                sort: 'created_at',
                order: 'desc'
            }
            this.fetchTasks()
        }
    }
})