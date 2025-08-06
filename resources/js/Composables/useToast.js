import { ref, nextTick } from 'vue'

// État global des toasts (en dehors de la fonction)
const toasts = ref([])
let toastId = 0

// Fonctions globales
const addToast = (message, type = 'info', duration = 5000) => {
  const id = ++toastId
  const toast = {
    id,
    message,
    type,
    duration,
    visible: false
  }
  
  toasts.value.push(toast)
  
  // Animation d'entrée
  nextTick(() => {
    const toastEl = toasts.value.find(t => t.id === id)
    if (toastEl) toastEl.visible = true
  })
  
  // Auto-suppression
  if (duration > 0) {
    setTimeout(() => removeToast(id), duration)
  }
  
  return id
}

const removeToast = (id) => {
  const index = toasts.value.findIndex(t => t.id === id)
  if (index > -1) {
    toasts.value[index].visible = false
    setTimeout(() => {
      toasts.value.splice(index, 1)
    }, 300)
  }
}

// Fonctions de convenance globales
const success = (message, duration = 5000) => addToast(message, 'success', duration)
const error = (message, duration = 7000) => addToast(message, 'error', duration)
const info = (message, duration = 5000) => addToast(message, 'info', duration)
const warning = (message, duration = 6000) => addToast(message, 'warning', duration)

export const useToast = () => {
  return {
    toasts,
    addToast,
    removeToast,
    success,
    error,
    info,
    warning
  }
}