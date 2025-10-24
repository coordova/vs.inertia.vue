import { toast } from 'vue-sonner'

export function useToast() {
    return {
        // Métodos helper existentes
        success: (message: string) => toast.success(message),
        error: (message: string) => toast.error(message),
        info: (message: string) => toast(message),
        warning: (message: string) => toast.warning(message),
        loading: (message: string) => toast.loading(message),
        dismiss: (id?: string | number) => toast.dismiss(id),

        // Exportar la instancia toast directamente por si se necesita
        toast, // <- Ahora se puede usar como toast.toast.success(...)
    }
}

// Exportar toast también si se necesita acceso directo en algún caso
// export { toast };