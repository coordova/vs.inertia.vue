// resources/js/types/global.d.ts
import { PageProps } from '@inertiajs/vue3'; // Asegúrate de que esta importación sea correcta para tu versión

declare module '@inertiajs/vue3' {
    interface PageProps<T> extends InertiaPageProps<T> { // Usa tu interfaz base si es necesario
        // Tus propiedades personalizadas aquí
        // categories: Category[];
        category: Category;
        character: Character;
        survey: Survey;
    }
}

export interface Category {
    data: {
        id: number;
        name: string;
        description: string;
        status: boolean;
        created_at_formatted: string;
    };
}
// todo: completar cuando se este usando
export interface Character {
    data: {
        id: number;
        fullname: string;
        nickname: string;
        status: boolean;
        created_at_formatted: string;
    };
}
// todo: completar cuando se este usando
export interface Survey {
    data: {
        id: number;
        title: string;
        description: string;
        status: boolean;
        created_at_formatted: string;
    };
}
