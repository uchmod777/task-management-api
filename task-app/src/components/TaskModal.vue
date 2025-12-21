<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
      <h3 class="text-xl font-bold mb-4">{{ isEdit ? 'Edit Task' : 'Create Task' }}</h3>

      <form @submit.prevent="handleSubmit">
        <div class="mb-4">
          <label class="block text-gray-700 mb-2">Title *</label>
          <input
              v-model="form.title"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500"
          />
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 mb-2">Description</label>
          <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500"
          ></textarea>
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 mb-2">Status</label>
          <select
              v-model="form.status"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500"
          >
            <option value="pending">Pending</option>
            <option value="in-progress">In Progress</option>
            <option value="completed">Completed</option>
          </select>
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 mb-2">Due Date</label>
          <input
              v-model="form.due_date"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500"
          />
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 mb-2">Assign To</label>
          <select
              v-model="form.assigned_to"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500"
          >
            <option :value="null">Unassigned</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }} ({{ user.email }})
            </option>
          </select>
        </div>

        <div v-if="error" class="mb-4 text-red-600 text-sm">
          {{ error }}
        </div>

        <div class="flex gap-2 justify-end">
          <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-100"
          >
            Cancel
          </button>
          <button
              type="submit"
              :disabled="loading"
              class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:bg-gray-400"
          >
            {{ loading ? 'Saving...' : (isEdit ? 'Update' : 'Create') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useTaskStore } from '../stores/tasks'

const props = defineProps({
  show: Boolean,
  task: Object,
  users: Array
})

const emit = defineEmits(['close', 'saved'])

const taskStore = useTaskStore()

const isEdit = ref(false)
const loading = ref(false)
const error = ref('')

const form = ref({
  title: '',
  description: '',
  status: 'pending',
  due_date: '',
  assigned_to: null
})

watch(() => props.task, (newTask) => {
  if (newTask) {
    isEdit.value = true
    form.value = {
      title: newTask.title,
      description: newTask.description || '',
      status: newTask.status,
      due_date: newTask.due_date || '',
      assigned_to: newTask.assigned_to || null
    }
  } else {
    isEdit.value = false
    form.value = {
      title: '',
      description: '',
      status: 'pending',
      due_date: '',
      assigned_to: null
    }
  }
}, { immediate: true })

const handleSubmit = async () => {
  loading.value = true
  error.value = ''

  try {
    if (isEdit.value) {
      await taskStore.updateTask(props.task.id, form.value)
    } else {
      await taskStore.createTask(form.value)
    }
    emit('saved')
    emit('close')
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to save task'
  } finally {
    loading.value = false
  }
}
</script>