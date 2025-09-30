import { toast } from 'vue-sonner'

export function useToast() {
    return {
        success: (message: string) => toast.success(message),
        error: (message: string) => toast.error(message),
        info: (message: string) => toast(message),
        loading: (message: string) => toast.loading(message),
        dismiss: (id?: string | number) => toast.dismiss(id),
    }
}
