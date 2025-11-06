import { Character } from './resources.d';
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
interface Pagination {
    current_page: number;
    from: number;
    last_page: number;
    path: string;
    per_page: number;
    to: number;
    total: number;
}

interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

export interface UserResource { // Interfaz para el recurso individual resuelto
    id: number;
    name: string;
    email: string;
    avatar: string;
    timezone: string;
    locale: string;
    type: string;
    status: boolean;
    last_login_at: string;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    created_at_formatted: string;
    updated_at_formatted: string;
    // Añadir otros campos devueltos por UserResource si es necesario
}

export interface UsersData { // Para la colección paginada
    data: UserResource[]; // Array de UserResource
    meta: Pagination;
    links: PaginationLinks[]; // Simplificado
}

export interface CategoryResource { // Renombrado para claridad
    id: number;
    name: string;
    description: string;
    image: string | null;
    status: boolean;
    is_featured: boolean;
    slug: string;
    created_at_formatted: string; // O created_at si se formatea en el frontend
    updated_at_formatted: string; // O updated_at si se formatea en el frontend
    // Añadir otros campos devueltos por CategoryResource
}

export interface CategoriesData { // Para la colección paginada
    data: CategoryResource[]; // Array de CategoryResource
    meta: Pagination;
    links: PaginationLinks[]; // Simplificado
}

// --- Interfaces para otras entidades ---
// --- Interfaces para Characters ---
export interface CharacterResource { // Interfaz para el recurso individual resuelto
    id: number;
    fullname: string;
    nickname: string;
    slug: string;
    bio: string;
    dob: string | null; // Puede ser null si no tiene fecha de nacimiento
    dob_for_humans: string | null; // Puede ser null si no tiene fecha de nacimiento
    gender: number | null; // 0=otro, 1=masculino, 2=femenino, 3=no-binario
    nationality: string;
    occupation: string;
    picture: File | null; // URL o path
    picture_url: string | undefined;
    thumbnail_url: string;
    status: boolean;
    meta_title: string | null;
    meta_description: string | null;
    created_at: string; // Formato ISO
    updated_at: string; // Formato ISO
    // Añadir otros campos devueltos por CharacterResource si es necesario
    created_at_formatted: string;
    updated_at_formatted: string;
    category_ids: number[];
}

export interface CharacterResourceForm { // Interfaz para el recurso individual resuelto
    id: number;
    fullname: string;
    nickname: string;
    slug: string;
    bio: string;
    dob: string | null; // Puede ser null si no tiene fecha de nacimiento
    gender: number | null; // 0=otro, 1=masculino, 2=femenino, 3=no-binario
    nationality: string;
    occupation: string;
    picture: File | null; // URL o path
    picture_url: string | undefined;
    status: boolean;
    meta_title: string | null;
    meta_description: string | null;
    // Añadir otros campos devueltos por CharacterResource si es necesario
    category_ids: number[];
}



export interface CharactersData { // Para la colección paginada resuelta
    data: CharacterResource[]; // Array de objetos CharacterResource directos
    meta: Pagination;
    links: PaginationLinks[];
}


// --- Interfaces para Surveys ---
export interface SurveyResource { // Interfaz para el recurso individual resuelto
    id: number;
    category_id: number; // ID de la categoría
    category: CategoryData; // Objeto de la categoría relacionada (resuelto)
    category: CategoryResource;
    title: string;
    slug: string;
    description: string;
    image: string | null; // URL o path
    type: number; // 0=pública, 1=privada
    status: boolean;
    reverse: boolean; // 0 : orden 'cual es mejor' (default) | 1 : orden 'cual es peor'
    date_start: string; // Formato ISO
    date_end: string; // Formato ISO
    selection_strategy: string; // Nombre de la estrategia
    max_votes_per_user: number | null; // 0=ilimitado
    allow_ties: boolean;
    tie_weight: number; // Decimal
    is_featured: boolean;
    sort_order: number;
    counter: number;
    meta_title: string | null;
    meta_description: string | null;
    created_at: string; // Formato ISO
    updated_at: string; // Formato ISO
    // deleted_at: string | null; // Incluido si se maneja soft delete y se envía

    date_start_formatted: string;
    date_end_formatted: string;
    duration: number;
    /* selection_strategy_info: {
        name: string;
        description: string;
        metadata: {
            [key: string]: any;
        };
    }; */
    character_count: number;
    combinations_count: number;
    user_votes_count: number;
    progress_percentage: number;
    is_completed: boolean;
    created_at_formatted: string;
    updated_at_formatted: string;
    characters: CharacterResource[];
    strategy: string;
    // votes: VoteResource[];

    total_combinations_expected: number;
    total_combinations: number;
    total_votes: number;
}

export interface SurveyResourceForm { // Interfaz para el recurso individual resuelto
    // id: number;
    category_id: number | null; // ID de la categoría
    // category: CategoryResource; // Objeto de la categoría relacionada (resuelto)
    title: string;
    // slug: string;
    description: string;
    // image: string | null; // URL o path
    type: number; // 0=pública, 1=privada
    status: boolean;
    reverse: boolean; // 0 : orden 'cual es mejor' (default) | 1 : orden 'cual es peor'
    date_start: string; // Formato ISO
    date_end: string; // Formato ISO
    selection_strategy: string; // Nombre de la estrategia
    // max_votes_per_user: number | null; // 0=ilimitado
    // allow_ties: boolean;
    // tie_weight: number; // Decimal
    is_featured: boolean;
    // sort_order: number;
    // counter: number;
    // meta_title: string | null;
    // meta_description: string | null;
    // created_at: string; // Formato ISO
    // updated_at: string; // Formato ISO
    // deleted_at: string | null; // Incluido si se maneja soft delete y se envía
    characters: number[];
}

export interface SurveysData {
    data: SurveyResource[];
    meta: Pagination;
    links: PaginationLinks[];
}

/*--------------------------------------------------------------------------*/
// --- Interfaces para el Progreso del Usuario en una Encuesta ---
export interface UserSurveyProgress {
    exists: boolean;
    is_completed: boolean;
    progress: number; // Porcentaje de progreso
    total_votes: number;
    total_expected: number | null; // Total de combinaciones esperadas
    pivot: SurveyUser | null; // El objeto pivote completo, si es necesario
}

// Asumiendo que SurveyUser es la estructura del modelo pivote
export interface SurveyUser {
    user_id: number;
    survey_id: number;
    progress_percentage: number;
    total_votes: number;
    total_combinations_expected: number | null; // Campo añadido
    completed_at: string | null;
    started_at: string;
    last_activity_at: string;
    is_completed: boolean;
    completion_time: number | null;
}

export interface CombinatoricResource_ {
    id: number;
    survey_id: number;
    character1_id: number;
    character2_id: number;
    character1: CharacterResource;
    character2: CharacterResource;
    created_at: string;
    updated_at: string;
}

export interface CombinatoricResource {
    id: number;
    character1: CharacterResource;
    character2: CharacterResource;
    // Añadir otros campos si son necesarios
}

// Asegurarse de que SurveyResource y CharacterResource estén definidos o importados si se usan aquí
// export interface SurveyResource { ... }
// export interface CharacterResource { ... }

/*--------------------------------------------------------------------------*/
// --- Tipos para Breadcrumbs (si no los tienes en otro lugar) ---
/* export interface BreadcrumbItem {
    title: string;
    href?: string;
} */