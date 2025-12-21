<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-md p-4">
      <div class="container mx-auto flex justify-between items-center">
        <h1 class="text-xl font-bold">Task Manager</h1>
        <button
            @click="handleLogout"
            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
        >
          Logout
        </button>
      </div>
    </nav>

    <div class="container mx-auto p-4">
      <!-- Header with Create Button -->
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">My Tasks</h2>
        <button
            @click="showCreateModal = true"
            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
        >
          + New Task
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white p-4 rounded-lg shadow mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select
                v-model="taskStore.filters.status"
                @change="taskStore.fetchTasks()"
                class="w-full px-3 py-2 border border-gray-300 rounded"
            >
              <option value="">All</option>
              <option value="pending">Pending</option>
              <option value="in-progress">In Progress</option>
              <option value="completed">Completed</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Search</label>
            <input
                v-model="taskStore.filters.search"
                @input="debounceSearch"
                type="text"
                placeholder="Search tasks..."
                class="w-full px-3 py-2 border border-gray-300 rounded"
            />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Sort By</label>
            <select
                v-model="taskStore.filters.sort"
                @change="taskStore.fetchTasks()"
                class="w-full px-3 py-2 border border-gray-300 rounded"
            >
              <option value="created_at">Created Date</option>
              <option value="due_date">Due Date</option>
              <option value="title">Title</option>
              <option value="status">Status</option>
            </select>
          </div>

          <div class="flex items-end">
            <button
                @click="taskStore.clearFilters()"
                class="w-full bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600"
            >
              Clear Filters
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="taskStore.loading" class="text-center py-8">
        <p class="text-gray-600">Loading tasks...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="taskStore.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        {{ taskStore.error }}
      </div>

      <!-- Empty State -->
      <div v-else-if="taskStore.tasks.length === 0" class="bg-white p-8 rounded-lg shadow text-center">
        <p class="text-gray-600">No tasks found. Create your first task!</p>
      </div>

      <!-- Task List -->
      <div v-else class="space-y-4">
        <div
            v-for="task in taskStore.tasks"
            :key="task.id"
            class="bg-white p-4 rounded-lg shadow hover:shadow-md transition"
        >
          <div class="flex justify-between items-start">
            <div class="flex-1">
              <h3 class="text-lg font-semibold">{{ task.title }}</h3>
              <p class="text-gray-600 text-sm mt-1">{{ task.description }}</p>

              <div class="flex gap-4 mt-2 text-sm text-gray-500">
                <span :class="statusClass(task.status)" class="px-2 py-1 rounded">
                  {{ task.status }}
                </span>
                <span v-if="task.due_date">Due: {{ formatDate(task.due_date) }}</span>
              </div>
            </div>

            <div class="flex gap-2">
              <button
                  @click="editTask(task)"
                  class="text-blue-500 hover:text-blue-700"
              >
                Edit
              </button>
              <button
                  @click="deleteTask(task.id)"
                  class="text-red-500 hover:text-red-700"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination Info -->
      <div v-if="taskStore.tasks.length > 0" class="mt-4 text-center text-gray-600">
        Showing {{ taskStore.tasks.length }} of {{ taskStore.pagination.total }} tasks
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <task-modal
      :show="showCreateModal"
      :task="editingTask"
      :users="taskStore.users"
      @close="closeModal"
      @saved="closeModal"
    />
  </div>
</template>

<script setup>
import TaskModal from '../components/TaskModal.vue'
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useTaskStore } from '../stores/tasks'

const router = useRouter()
const authStore = useAuthStore()
const taskStore = useTaskStore()

let searchTimeout = null

const debounceSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    taskStore.fetchTasks()
  }, 300)
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

const showCreateModal = ref(false)
const editingTask = ref(null)

const editTask = (task) => {
  editingTask.value = task
  showCreateModal.value = true
}

const closeModal = () => {
  showCreateModal.value = false
  editingTask.value = null
}

const deleteTask = async (id) => {
  if (confirm('Are you sure you want to delete this task?')) {
    await taskStore.deleteTask(id)
  }
}

const statusClass = (status) => {
  const classes = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'in-progress': 'bg-blue-100 text-blue-800',
    'completed': 'bg-green-100 text-green-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

onMounted(() => {
  taskStore.fetchTasks()
  taskStore.fetchUsers()
})
</script>