// resources/js/types/global.d.ts

// Importaciones (si se necesitan para otros tipos, pero no para extender PageProps aquí)
// import { ... } from '...';

// Extender la interfaz PageProps de Inertia para incluir nuestras propiedades globales
declare module '@inertiajs/core' {
    interface PageProps {
        // Añadir aquí propiedades que *siempre* estarán presentes en todas las páginas
        // Por ejemplo, si defines auth globalmente en HandleInertiaRequests:
        // auth?: {
        //     user: { id: number; name: string; email: string } | null;
        // };

        // Añadir aquí propiedades que *podrían* estar presentes en *algunas* páginas
        // Nota: Esto las hace *opcionales* en todas las páginas, lo cual puede no ser ideal
        // para props específicas de una página. Se prefiere usar `defineProps` en el componente.
        // flash?: { success?: string; error?: string }; // Ejemplo de flash global
    }
}

// Definir interfaces para las entidades que se pasan como props específicas
// Estas se usarán en defineProps de los componentes Vue

export interface CategoryResource { // Renombrado para claridad
    id: number;
    name: string;
    description: string;
    status: boolean;
    created_at_formatted: string; // O created_at si se formatea en el frontend
    updated_at_formatted: string; // O updated_at si se formatea en el frontend
    is_featured: boolean;
    // Añadir otros campos devueltos por CategoryResource
}

export interface CategoriesData { // Para la colección paginada
    data: CategoryResource[]; // Array de CategoryResource
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        path: string;
        per_page: number;
        to: number;
        total: number;
    };
    links: { url: string | null; label: string; active: boolean }[]; // Simplificado
}

// --- Interfaces para otras entidades ---
export interface CharacterResource { // Renombrado para claridad
    id: number;
    fullname: string;
    nickname: string;
    status: boolean;
    created_at_formatted: string; // O created_at si se formatea en el frontend
    // Añadir otros campos devueltos por CharacterResource
}

export interface CharactersData {
    data: CharacterResource[];
    meta: { /* ... */ }; // Similar a CategoriesData.meta
    links: { /* ... */ }; // Similar a CategoriesData.links
}

export interface SurveyResource { // Renombrado para claridad
    id: number;
    title: string;
    description: string;
    status: boolean;
    created_at_formatted: string; // O created_at si se formatea en el frontend
    // Añadir otros campos devueltos por SurveyResource
}

export interface SurveysData {
    data: SurveyResource[];
    meta: { /* ... */ };
    links: { /* ... */ };
}

// --- Tipos para Breadcrumbs (si no los tienes en otro lugar) ---
export interface BreadcrumbItem {
    title: string;
    href?: string;
}